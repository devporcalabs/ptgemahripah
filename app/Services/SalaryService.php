<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\GajiTambahan;
use App\Models\GajiKaryawan;
use App\Models\Karyawan;
use App\Models\KomponenGajiKaryawan;
use App\Models\PayrollPeriod;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SalaryService
{
    public function __construct(
        private readonly MonthlyScheduleService $monthlyScheduleService,
        private readonly KasbonService $kasbonService,
        private readonly LeaveService $leaveService,
    ) {
    }

    public function forEmployee(Karyawan $karyawan, Carbon $period): array
    {
        $month = $period->month;
        $year = $period->year;
        $settings = Setting::query()->find(1);
        $components = $this->componentsForEmployee($karyawan, $settings);
        $payrollType = $this->resolvePayrollType($karyawan);
        $payrollDivisor = $this->resolvePayrollDivisor($settings);
        $scheduleCalendar = $this->monthlyScheduleService->calendarForEmployee($karyawan, $period);
        $scheduledDays = $scheduleCalendar->filter(fn (array $day) => $day['is_workday'])->count();
        $employmentWindow = $this->resolveEmploymentWindow($karyawan, $period, $scheduleCalendar, $scheduledDays, $settings);
        $periodStart = $period->copy()->startOfMonth();
        $periodEnd = $period->copy()->endOfMonth();
        $approvedLeaves = $karyawan->izin()
            ->with('jenisIzin:id,nama,legacy_code,is_paid,deduct_quota,quota_field')
            ->where('status', 'disetujui')
            ->whereRaw('COALESCE(tanggal_mulai, tanggal_izin, tanggal) <= ?', [$periodEnd->toDateString()])
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_mulai, tanggal_izin, tanggal) >= ?', [$periodStart->toDateString()])
            ->get([
                'id',
                'tanggal',
                'tanggal_izin',
                'tanggal_mulai',
                'tanggal_selesai',
                'jumlah_hari',
                'jenis_izin',
                'jenis_izin_id',
            ]);

        $attendanceRows = Absensi::query()
            ->where('karyawan_id', $karyawan->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->get(['tanggal', 'status', 'menit_terlambat', 'menit_pulang_cepat', 'menit_lembur', 'tarif_lembur']);

        $scheduleByDate = $scheduleCalendar->keyBy('date');
        $expandedLeaves = $this->leaveService->expandLeavesAgainstCalendar($approvedLeaves, $scheduleCalendar, $period);
        $workdayAttendanceRows = $attendanceRows->filter(function (Absensi $row) use ($scheduleByDate): bool {
            $attendanceDate = Carbon::parse($row->tanggal)->toDateString();
            $schedule = $scheduleByDate->get($attendanceDate);

            return (bool) ($schedule['is_workday'] ?? false);
        });

        $hasAttendance = $workdayAttendanceRows->isNotEmpty();
        $totalHadir = $workdayAttendanceRows->filter(fn ($row) => in_array($row->status, ['hadir', 'terlambat'], true))->count();
        $totalTerlambat = $workdayAttendanceRows->where('status', 'terlambat')->count();
        $totalMenitTerlambat = (int) $workdayAttendanceRows->where('status', 'terlambat')->sum('menit_terlambat');
        $totalPulangCepat = $workdayAttendanceRows->filter(fn ($row) => (int) ($row->menit_pulang_cepat ?? 0) > 0)->count();
        $totalMenitPulangCepat = (int) $workdayAttendanceRows->sum('menit_pulang_cepat');
        $totalIzin = $expandedLeaves->count();
        $totalCuti = $expandedLeaves->where('legacy_code', 'cuti')->count();
        $totalSakit = $expandedLeaves->where('legacy_code', 'sakit')->count();
        $totalIzinLainnya = max(0, $totalIzin - $totalCuti - $totalSakit);
        $totalIzinDibayar = $expandedLeaves->where('is_paid', true)->count();
        $totalIzinTidakDibayar = max(0, $totalIzin - $totalIzinDibayar);
        $scheduledDaysForPayroll = $employmentWindow['scheduled_days_for_payroll'];
        $totalAlpha = max(0, $scheduledDaysForPayroll - $totalHadir - $totalIzin);

        $totalMenitLembur = (int) $attendanceRows->sum('menit_lembur');
        $totalLemburTarif = (float) $attendanceRows->sum('tarif_lembur');

        $totalPenyesuaian = (float) GajiTambahan::query()
            ->where('karyawan_id', $karyawan->id)
            ->whereMonth('bulan', $month)
            ->whereYear('bulan', $year)
            ->sum('jumlah');

        $fullMonthlyBaseSalary = (float) ($karyawan->gaji_pokok ?? 0);
        $dailySalaryRate = $this->resolveDailyPayrollRate(
            karyawan: $karyawan,
            components: $components,
            payrollType: $payrollType,
            payrollDivisor: $payrollDivisor,
            settings: $settings,
        );
        $payableDays = max(0, $totalHadir + $totalIzinDibayar);
        $prorateRatio = (float) ($employmentWindow['prorate_ratio'] ?? 1);

        if ($payrollType === 'bulanan') {
            if ($fullMonthlyBaseSalary <= 0 && $dailySalaryRate > 0) {
                $fullMonthlyBaseSalary = round($dailySalaryRate * $payrollDivisor, 2);
            }

            $monthlyBaseSalary = round($fullMonthlyBaseSalary * $prorateRatio, 2);
        } else {
            $monthlyBaseSalary = round($dailySalaryRate * $payableDays, 2);
        }

        $gajiKehadiran = $monthlyBaseSalary;
        $tunjanganMakanTotal = $components['tunjangan_makan'] * $totalHadir;
        $tunjanganTransportTotal = $components['tunjangan_transport'] * $totalHadir;
        $tunjanganJabatanTampil = round((float) ($components['tunjangan_jabatan'] ?? 0) * $prorateRatio, 2);
        $bonusPribadiTotal = (float) ($karyawan->bonus_pribadi ?? 0);
        $bonusTeamTotal = (float) ($karyawan->bonus_team ?? 0);
        $manualBonusTotal = $bonusPribadiTotal + $bonusTeamTotal;
        $premiKehadiran = $this->resolveAttendancePremium($karyawan, [
            'scheduled_days' => $scheduledDaysForPayroll,
            'total_hadir' => $totalHadir,
            'total_izin' => $totalIzin,
            'total_izin_dibayar' => $totalIzinDibayar,
            'total_izin_tidak_dibayar' => $totalIzinTidakDibayar,
            'total_alpha' => $totalAlpha,
            'total_terlambat' => $totalTerlambat,
            'total_pulang_cepat' => $totalPulangCepat,
        ]);
        $premiKehadiranTotal = (float) ($premiKehadiran['amount'] ?? 0);
        $totalBonus = $manualBonusTotal + $premiKehadiranTotal;
        $saldoKasbon = $this->kasbonService->currentBalance($karyawan);
        $potonganMangkir = 0.0;
        $potonganIzin = 0.0;

        if ($payrollType === 'bulanan') {
            $potonganMangkirRate = (float) ($components['potongan_mangkir'] ?? 0);
            $potonganIzinRate = (float) ($components['potongan_izin'] ?? 0);

            if ($potonganMangkirRate <= 0) {
                $potonganMangkirRate = $dailySalaryRate;
            }

            if ($potonganIzinRate <= 0) {
                $potonganIzinRate = $dailySalaryRate;
            }

            $normalizedUnpaidAbsenceDeductions = $this->normalizeUnpaidAbsenceDeductions(
                monthlyBaseSalary: $monthlyBaseSalary,
                scheduledDays: $scheduledDaysForPayroll,
                totalHadir: $totalHadir,
                totalIzinDibayar: $totalIzinDibayar,
                totalAlpha: $totalAlpha,
                totalIzinTidakDibayar: $totalIzinTidakDibayar,
                rawPotonganMangkir: $potonganMangkirRate * $totalAlpha,
                rawPotonganIzin: $potonganIzinRate * $totalIzinTidakDibayar,
            );
            $potonganMangkir = $normalizedUnpaidAbsenceDeductions['potongan_mangkir'];
            $potonganIzin = $normalizedUnpaidAbsenceDeductions['potongan_izin'];
        }

        $lateDeductionSummary = $this->resolveLateDeductionSummary($workdayAttendanceRows, $components, $settings);
        $potonganTerlambat = $lateDeductionSummary['amount'];
        $bpjsBaseSalary = max(0, $gajiKehadiran + $tunjanganJabatanTampil);
        $bpjsSummary = $this->resolveBpjsSummary($karyawan, $settings, $components, $bpjsBaseSalary);
        $tunjanganBpjsKesehatanTotal = $bpjsSummary['tunjangan_bpjs_kesehatan_total'];
        $tunjanganBpjsKetenagakerjaanTotal = $bpjsSummary['tunjangan_bpjs_ketenagakerjaan_total'];
        $potonganBpjsKesehatanTotal = $bpjsSummary['potongan_bpjs_kesehatan_total'];
        $potonganBpjsKetenagakerjaanTotal = $bpjsSummary['potongan_bpjs_ketenagakerjaan_total'];

        $thrBaseSalary = $this->resolveThrBaseSalary(
            karyawan: $karyawan,
            period: $period,
            settings: $settings,
            components: $components,
            payrollType: $payrollType,
            fullMonthlyBaseSalary: $fullMonthlyBaseSalary,
            dailySalaryRate: $dailySalaryRate,
            payrollDivisor: $payrollDivisor,
        );
        $thrSummary = $this->resolveThrSummary($karyawan, $period, $settings, $thrBaseSalary, $employmentWindow);
        $thrAmount = $thrSummary['amount'];

        $taxableGrossTotal = $gajiKehadiran
            + $tunjanganJabatanTampil
            + $tunjanganMakanTotal
            + $tunjanganTransportTotal
            + $totalBonus
            + $tunjanganBpjsKesehatanTotal
            + $tunjanganBpjsKetenagakerjaanTotal
            + $totalLemburTarif
            + $totalPenyesuaian
            + $thrAmount;
        $pph21Summary = $this->resolvePph21Summary(
            karyawan: $karyawan,
            period: $period,
            settings: $settings,
            taxableGrossCurrent: $taxableGrossTotal,
            retirementContributionCurrent: $bpjsSummary['retirement_contribution_total'],
            employmentWindow: $employmentWindow,
        );
        $pph21Amount = $pph21Summary['amount'];
        $totalPotonganNonKasbon = $potonganMangkir
            + $potonganIzin
            + $potonganTerlambat
            + $potonganBpjsKesehatanTotal
            + $potonganBpjsKetenagakerjaanTotal
            + $pph21Amount;
        $takeHomeBeforeKasbon = $taxableGrossTotal - $totalPotonganNonKasbon;
        $kasbonPayableCap = max(0, $takeHomeBeforeKasbon);
        $potonganKasbon = $this->clampKasbonAmount($saldoKasbon, $kasbonPayableCap);
        $totalPotongan = $totalPotonganNonKasbon + $potonganKasbon;

        $totalGaji = $takeHomeBeforeKasbon - $potonganKasbon;

        return [
            'karyawan' => $karyawan,
            'has_attendance' => $hasAttendance,
            'period' => $period->copy()->startOfMonth(),
            'components' => $components,
            'payroll_type' => $payrollType,
            'payroll_type_label' => $payrollType === 'harian' ? 'Harian' : 'Bulanan',
            'payroll_divisor' => $payrollDivisor,
            'payable_days' => $payableDays,
            'scheduled_days' => $scheduledDaysForPayroll,
            'scheduled_days_full' => $scheduledDays,
            'prorate_ratio' => $prorateRatio,
            'prorate_method' => $employmentWindow['prorate_method'],
            'prorate_status' => $employmentWindow['status'],
            'employment_active_start' => $employmentWindow['active_start']?->toDateString(),
            'employment_active_end' => $employmentWindow['active_end']?->toDateString(),
            'daily_deduction_rate' => $dailySalaryRate,
            'base_salary_total' => $monthlyBaseSalary,
            'total_hadir' => $totalHadir,
            'total_terlambat' => $totalTerlambat,
            'total_menit_terlambat' => $totalMenitTerlambat,
            'total_pulang_cepat' => $totalPulangCepat,
            'total_menit_pulang_cepat' => $totalMenitPulangCepat,
            'total_menit_lembur' => $totalMenitLembur,
            'total_izin' => $totalIzin,
            'total_cuti' => $totalCuti,
            'total_sakit' => $totalSakit,
            'total_izin_lainnya' => $totalIzinLainnya,
            'total_izin_dibayar' => $totalIzinDibayar,
            'total_izin_tidak_dibayar' => $totalIzinTidakDibayar,
            'total_alpha' => $totalAlpha,
            'total_lembur_tarif' => $totalLemburTarif,
            'total_penyesuaian' => $totalPenyesuaian,
            'gaji_kehadiran' => $gajiKehadiran,
            'tunjangan_makan_total' => $tunjanganMakanTotal,
            'tunjangan_transport_total' => $tunjanganTransportTotal,
            'tunjangan_jabatan_tampil' => $tunjanganJabatanTampil,
            'bonus_pribadi_total' => $bonusPribadiTotal,
            'bonus_team_total' => $bonusTeamTotal,
            'bonus_manual_total' => $manualBonusTotal,
            'premi_kehadiran_total' => $premiKehadiranTotal,
            'premi_kehadiran' => (float) ($karyawan->premi_kehadiran ?? 0),
            'premi_kehadiran_mode' => $premiKehadiran['mode'],
            'premi_kehadiran_mode_label' => $premiKehadiran['mode_label'],
            'premi_kehadiran_toleransi_telat' => $premiKehadiran['toleransi_telat'],
            'premi_kehadiran_toleransi_pulang_cepat' => $premiKehadiran['toleransi_pulang_cepat'],
            'premi_kehadiran_status' => $premiKehadiran['status'],
            'premi_kehadiran_keterangan' => $premiKehadiran['keterangan'],
            'total_bonus' => $totalBonus,
            'bpjs_mode' => $bpjsSummary['mode'],
            'bpjs_mode_label' => $bpjsSummary['mode_label'],
            'tunjangan_bpjs_kesehatan_total' => $tunjanganBpjsKesehatanTotal,
            'tunjangan_bpjs_ketenagakerjaan_total' => $tunjanganBpjsKetenagakerjaanTotal,
            'bpjs_tunjangan_total' => $tunjanganBpjsKesehatanTotal + $tunjanganBpjsKetenagakerjaanTotal,
            'potongan_bpjs_kesehatan_total' => $potonganBpjsKesehatanTotal,
            'potongan_bpjs_ketenagakerjaan_total' => $potonganBpjsKetenagakerjaanTotal,
            'bpjs_potongan_total' => $potonganBpjsKesehatanTotal + $potonganBpjsKetenagakerjaanTotal,
            'bpjs_breakdown' => $bpjsSummary['breakdown'],
            'retirement_contribution_total' => $bpjsSummary['retirement_contribution_total'],
            'saldo_kasbon_awal' => $saldoKasbon,
            'take_home_before_kasbon' => $takeHomeBeforeKasbon,
            'kasbon_payable_cap' => $kasbonPayableCap,
            'manual_kasbon_amount' => null,
            'kasbon_manual_override' => false,
            'potongan_kasbon' => $potonganKasbon,
            'thr_mode' => $thrSummary['mode'],
            'thr_amount' => $thrAmount,
            'thr_ratio' => $thrSummary['ratio'],
            'thr_status' => $thrSummary['status'],
            'thr_base_salary' => $thrSummary['base_salary'],
            'thr_service_months' => $thrSummary['service_months'],
            'thr_manual_amount' => $thrSummary['manual_amount'],
            'thr_payment_date' => $thrSummary['payment_date'],
            'thr_due_date' => $thrSummary['due_date'],
            'potongan_ketidakhadiran_total' => $potonganMangkir + $potonganIzin,
            'potongan_mangkir' => $potonganMangkir,
            'potongan_izin' => $potonganIzin,
            'potongan_terlambat' => $potonganTerlambat,
            'potongan_terlambat_mode' => $lateDeductionSummary['mode'],
            'potongan_terlambat_rate' => $lateDeductionSummary['rate'],
            'potongan_terlambat_daily_cap' => $lateDeductionSummary['daily_cap'],
            'pph21_mode' => $pph21Summary['mode'],
            'pph21_status' => $pph21Summary['status'],
            'pph21_method' => $pph21Summary['method'],
            'pph21_ter_category' => $pph21Summary['ter_category'],
            'pph21_ter_rate' => $pph21Summary['ter_rate'],
            'pph21_gross_basis' => $pph21Summary['gross_basis'],
            'pph21_job_expense' => $pph21Summary['job_expense'],
            'pph21_net_annual' => $pph21Summary['net_annual'],
            'pph21_amount' => $pph21Amount,
            'pph21_ptkp' => $pph21Summary['ptkp'],
            'pph21_pkp' => $pph21Summary['pkp'],
            'pph21_annual_tax' => $pph21Summary['annual_tax'],
            'taxable_gross_total' => $taxableGrossTotal,
            'total_potongan' => $totalPotongan,
            'total_gaji' => $totalGaji,
        ];
    }

    public function forActiveEmployees(Carbon $period): Collection
    {
        $periodStart = $period->copy()->startOfMonth()->toDateString();
        $periodEnd = $period->copy()->endOfMonth()->toDateString();

        return Karyawan::query()
            ->with('latestKasbonMutation')
            ->where(function ($query) use ($periodStart): void {
                $query->where('status', 'aktif')
                    ->orWhere(function ($resignedQuery) use ($periodStart): void {
                        $resignedQuery->whereNotNull('tgl_resign')
                            ->whereDate('tgl_resign', '>=', $periodStart);
                    });
            })
            ->where(function ($query) use ($periodEnd): void {
                $query->whereNull('tgl_join')
                    ->orWhereDate('tgl_join', '<=', $periodEnd);
            })
            ->orderBy('nama_lengkap')
            ->get()
            ->map(fn (Karyawan $karyawan) => $this->forEmployee($karyawan, $period))
            ->filter(fn (array $salary) => $this->shouldIncludeInPayroll($salary))
            ->values();
    }

    public function syncHistory(array $salary, ?PayrollPeriod $payrollPeriod = null): void
    {
        $period = $salary['period']->copy()->startOfMonth()->toDateString();
        $payrollPeriod ??= $this->resolvePayrollPeriod($salary['period']);
        $history = GajiKaryawan::query()
            ->where('karyawan_id', $salary['karyawan']->id)
            ->where(function ($query) use ($period, $payrollPeriod): void {
                $query->where('payroll_period_id', $payrollPeriod->id)
                    ->orWhere(function ($fallbackQuery) use ($period): void {
                        $fallbackQuery->whereNull('payroll_period_id')
                            ->whereDate('bulan', $period);
                    });
            })
            ->first();

        if ($history?->is_finalized) {
            return;
        }

        if ($history && $history->manual_kasbon_amount !== null) {
            $salary = $this->replaceKasbonAmount(
                $salary,
                $this->resolveKasbonOpeningBalance($salary, $history->karyawan),
                (float) $history->manual_kasbon_amount,
                true,
            );
        }

        $payload = [
            'gaji_pokok' => $salary['base_salary_total'],
            'gaji_per_hari' => $salary['daily_deduction_rate'],
            'total_hadir' => $salary['total_hadir'],
            'total_terlambat' => $salary['total_menit_terlambat'],
            'total_izin' => $salary['total_izin'],
            'total_lembur' => $salary['total_lembur_tarif'],
            'thr' => $salary['thr_amount'],
            'gaji_kehadiran' => $salary['gaji_kehadiran'],
            'tunjangan_makan' => $salary['tunjangan_makan_total'],
            'tunjangan_transport' => $salary['tunjangan_transport_total'],
            'potongan_terlambat' => $salary['potongan_terlambat'],
            'pph21' => $salary['pph21_amount'],
            'prorate_ratio' => $salary['prorate_ratio'],
            'total_gaji' => $salary['total_gaji'],
            'status' => 'selesai',
            'payroll_period_id' => $payrollPeriod->id,
            'detail_payload' => $this->snapshot($salary),
            'applied_kasbon_amount' => 0,
            'manual_kasbon_amount' => $salary['manual_kasbon_amount'] ?? null,
        ];

        if ($history) {
            $history->update($payload);

            return;
        }

        GajiKaryawan::query()->create([
            'karyawan_id' => $salary['karyawan']->id,
            'bulan' => $period,
            ...$payload,
        ]);
    }

    public function syncAll(Collection $salaries, ?PayrollPeriod $payrollPeriod = null): void
    {
        $salaries->each(fn (array $salary) => $this->syncHistory($salary, $payrollPeriod));
    }

    public function snapshot(array $salary): array
    {
        $employee = $salary['karyawan'];

        return [
            'karyawan' => [
                'id' => $employee->id,
                'nik' => $employee->nik,
                'nama_lengkap' => $employee->nama_lengkap,
                'jabatan' => $employee->jabatan,
                'departemen' => $employee->departemen,
                'status_ptkp' => $employee->status_ptkp,
                'ter_category' => $employee->ter_category,
                'npwp' => $employee->npwp,
                'tax_counterpart_opt' => $employee->tax_counterpart_opt ?? 'Resident',
                'tax_passport_number' => $employee->tax_passport_number,
                'tax_has_second_employer' => (bool) ($employee->tax_has_second_employer ?? false),
                'tax_prev_withholding_slip_number' => $employee->tax_prev_withholding_slip_number,
                'tax_prev_gross_income' => (float) ($employee->tax_prev_gross_income ?? 0),
                'tax_prev_pph21_paid' => (float) ($employee->tax_prev_pph21_paid ?? 0),
                'tax_prev_retirement_contribution' => (float) ($employee->tax_prev_retirement_contribution ?? 0),
                'tax_certificate' => $employee->tax_certificate ?? 'N/A',
                'nama_bank' => $employee->nama_bank,
                'nama_rekening' => $employee->nama_rekening,
                'rekening' => $employee->rekening,
            ],
            'period' => $salary['period']->copy()->startOfMonth()->toDateString(),
            'components' => $salary['components'],
            'payroll_type' => $salary['payroll_type'],
            'payroll_type_label' => $salary['payroll_type_label'],
            'payroll_divisor' => $salary['payroll_divisor'],
            'payable_days' => $salary['payable_days'],
            'scheduled_days' => $salary['scheduled_days'],
            'scheduled_days_full' => $salary['scheduled_days_full'],
            'prorate_ratio' => $salary['prorate_ratio'],
            'prorate_method' => $salary['prorate_method'],
            'prorate_status' => $salary['prorate_status'],
            'employment_active_start' => $salary['employment_active_start'],
            'employment_active_end' => $salary['employment_active_end'],
            'daily_deduction_rate' => $salary['daily_deduction_rate'],
            'base_salary_total' => $salary['base_salary_total'],
            'total_hadir' => $salary['total_hadir'],
            'total_terlambat' => $salary['total_terlambat'],
            'total_menit_terlambat' => $salary['total_menit_terlambat'],
            'total_pulang_cepat' => $salary['total_pulang_cepat'],
            'total_menit_pulang_cepat' => $salary['total_menit_pulang_cepat'],
            'total_menit_lembur' => $salary['total_menit_lembur'],
            'total_izin' => $salary['total_izin'],
            'total_cuti' => $salary['total_cuti'],
            'total_sakit' => $salary['total_sakit'],
            'total_izin_lainnya' => $salary['total_izin_lainnya'],
            'total_izin_dibayar' => $salary['total_izin_dibayar'],
            'total_izin_tidak_dibayar' => $salary['total_izin_tidak_dibayar'],
            'total_alpha' => $salary['total_alpha'],
            'total_lembur_tarif' => $salary['total_lembur_tarif'],
            'total_penyesuaian' => $salary['total_penyesuaian'],
            'gaji_kehadiran' => $salary['gaji_kehadiran'],
            'tunjangan_makan_total' => $salary['tunjangan_makan_total'],
            'tunjangan_transport_total' => $salary['tunjangan_transport_total'],
            'tunjangan_jabatan_tampil' => $salary['tunjangan_jabatan_tampil'],
            'bonus_pribadi_total' => $salary['bonus_pribadi_total'],
            'bonus_team_total' => $salary['bonus_team_total'],
            'bonus_manual_total' => $salary['bonus_manual_total'],
            'premi_kehadiran_total' => $salary['premi_kehadiran_total'],
            'premi_kehadiran' => $salary['premi_kehadiran'],
            'premi_kehadiran_mode' => $salary['premi_kehadiran_mode'],
            'premi_kehadiran_mode_label' => $salary['premi_kehadiran_mode_label'],
            'premi_kehadiran_toleransi_telat' => $salary['premi_kehadiran_toleransi_telat'],
            'premi_kehadiran_toleransi_pulang_cepat' => $salary['premi_kehadiran_toleransi_pulang_cepat'],
            'premi_kehadiran_status' => $salary['premi_kehadiran_status'],
            'premi_kehadiran_keterangan' => $salary['premi_kehadiran_keterangan'],
            'total_bonus' => $salary['total_bonus'],
            'bpjs_mode' => $salary['bpjs_mode'],
            'bpjs_mode_label' => $salary['bpjs_mode_label'],
            'tunjangan_bpjs_kesehatan_total' => $salary['tunjangan_bpjs_kesehatan_total'],
            'tunjangan_bpjs_ketenagakerjaan_total' => $salary['tunjangan_bpjs_ketenagakerjaan_total'],
            'bpjs_tunjangan_total' => $salary['bpjs_tunjangan_total'],
            'potongan_bpjs_kesehatan_total' => $salary['potongan_bpjs_kesehatan_total'],
            'potongan_bpjs_ketenagakerjaan_total' => $salary['potongan_bpjs_ketenagakerjaan_total'],
            'bpjs_potongan_total' => $salary['bpjs_potongan_total'],
            'bpjs_breakdown' => $salary['bpjs_breakdown'],
            'retirement_contribution_total' => $salary['retirement_contribution_total'],
            'saldo_kasbon_awal' => $salary['saldo_kasbon_awal'],
            'take_home_before_kasbon' => $salary['take_home_before_kasbon'],
            'kasbon_payable_cap' => $salary['kasbon_payable_cap'],
            'manual_kasbon_amount' => $salary['manual_kasbon_amount'] ?? null,
            'kasbon_manual_override' => (bool) ($salary['kasbon_manual_override'] ?? false),
            'potongan_kasbon' => $salary['potongan_kasbon'],
            'thr_mode' => $salary['thr_mode'],
            'thr_amount' => $salary['thr_amount'],
            'thr_ratio' => $salary['thr_ratio'],
            'thr_status' => $salary['thr_status'],
            'thr_base_salary' => $salary['thr_base_salary'],
            'thr_service_months' => $salary['thr_service_months'],
            'thr_manual_amount' => $salary['thr_manual_amount'],
            'thr_payment_date' => $salary['thr_payment_date'],
            'thr_due_date' => $salary['thr_due_date'],
            'potongan_ketidakhadiran_total' => $salary['potongan_ketidakhadiran_total'],
            'potongan_mangkir' => $salary['potongan_mangkir'],
            'potongan_izin' => $salary['potongan_izin'],
            'potongan_terlambat' => $salary['potongan_terlambat'],
            'potongan_terlambat_mode' => $salary['potongan_terlambat_mode'] ?? 'per_event',
            'potongan_terlambat_rate' => (float) ($salary['potongan_terlambat_rate'] ?? 0),
            'potongan_terlambat_daily_cap' => (float) ($salary['potongan_terlambat_daily_cap'] ?? 0),
            'pph21_mode' => $salary['pph21_mode'],
            'pph21_status' => $salary['pph21_status'],
            'pph21_method' => $salary['pph21_method'],
            'pph21_ter_category' => $salary['pph21_ter_category'],
            'pph21_ter_rate' => $salary['pph21_ter_rate'],
            'pph21_gross_basis' => $salary['pph21_gross_basis'],
            'pph21_job_expense' => $salary['pph21_job_expense'],
            'pph21_net_annual' => $salary['pph21_net_annual'],
            'pph21_amount' => $salary['pph21_amount'],
            'pph21_ptkp' => $salary['pph21_ptkp'],
            'pph21_pkp' => $salary['pph21_pkp'],
            'pph21_annual_tax' => $salary['pph21_annual_tax'],
            'taxable_gross_total' => $salary['taxable_gross_total'],
            'total_potongan' => $salary['total_potongan'],
            'total_gaji' => $salary['total_gaji'],
        ];
    }

    public function historyRows(Carbon $period, ?PayrollPeriod $payrollPeriod = null): Collection
    {
        $period = $period->copy()->startOfMonth()->toDateString();

        return GajiKaryawan::query()
            ->with(['karyawan.latestKasbonMutation', 'finalizedBy'])
            ->where(function ($query) use ($period, $payrollPeriod): void {
                if ($payrollPeriod?->id) {
                    $query->where('payroll_period_id', $payrollPeriod->id)
                        ->orWhere(function ($fallbackQuery) use ($period): void {
                            $fallbackQuery->whereNull('payroll_period_id')
                                ->whereDate('bulan', $period);
                        });

                    return;
                }

                $query->whereDate('bulan', $period);
            })
            ->get()
            ->filter(fn (GajiKaryawan $history) => $history->karyawan !== null)
            ->sortBy(fn (GajiKaryawan $history) => strtolower((string) $history->karyawan->nama_lengkap))
            ->values()
            ->map(fn (GajiKaryawan $history) => $this->historyToRow($history));
    }

    public function historySnapshot(GajiKaryawan $history): array
    {
        $employee = $history->karyawan;
        $settings = Setting::query()->find(1);
        $payrollType = $employee ? $this->resolvePayrollType($employee) : 'bulanan';
        $payrollDivisor = $this->resolvePayrollDivisor($settings);

        return [
            'karyawan' => [
                'id' => $employee?->id,
                'nik' => $employee?->nik,
                'nama_lengkap' => $employee?->nama_lengkap,
                'jabatan' => $employee?->jabatan,
                'departemen' => $employee?->departemen,
                'status_ptkp' => $employee?->status_ptkp ?? 'TK/0',
                'ter_category' => $employee?->ter_category,
                'npwp' => $employee?->npwp,
                'tax_counterpart_opt' => $employee?->tax_counterpart_opt ?? 'Resident',
                'tax_passport_number' => $employee?->tax_passport_number,
                'tax_has_second_employer' => (bool) ($employee?->tax_has_second_employer ?? false),
                'tax_prev_withholding_slip_number' => $employee?->tax_prev_withholding_slip_number,
                'tax_prev_gross_income' => (float) ($employee?->tax_prev_gross_income ?? 0),
                'tax_prev_pph21_paid' => (float) ($employee?->tax_prev_pph21_paid ?? 0),
                'tax_prev_retirement_contribution' => (float) ($employee?->tax_prev_retirement_contribution ?? 0),
                'tax_certificate' => $employee?->tax_certificate ?? 'N/A',
                'nama_bank' => $employee?->nama_bank,
                'nama_rekening' => $employee?->nama_rekening,
                'rekening' => $employee?->rekening,
            ],
            'period' => $history->bulan?->copy()->startOfMonth()->toDateString(),
            'components' => [
                'gaji_per_hari' => (float) ($history->gaji_per_hari ?? 0),
                'tunjangan_jabatan' => 0,
                'tunjangan_makan' => 0,
                'tunjangan_transport' => 0,
                'tunjangan_bpjs_kesehatan' => 0,
                'tunjangan_bpjs_ketenagakerjaan' => 0,
                'potongan_izin' => 0,
                'potongan_mangkir' => 0,
                'potongan_terlambat' => 0,
                'potongan_bpjs_kesehatan' => 0,
                'potongan_bpjs_ketenagakerjaan' => 0,
            ],
            'payroll_type' => $payrollType,
            'payroll_type_label' => $payrollType === 'harian' ? 'Harian' : 'Bulanan',
            'payroll_divisor' => $payrollDivisor,
            'payable_days' => (int) ($history->total_hadir ?? 0),
            'scheduled_days' => 0,
            'scheduled_days_full' => 0,
            'prorate_ratio' => (float) ($history->prorate_ratio ?? 1),
            'prorate_method' => 'work_days',
            'prorate_status' => 'locked',
            'employment_active_start' => null,
            'employment_active_end' => null,
            'daily_deduction_rate' => (float) ($history->gaji_per_hari ?? 0),
            'base_salary_total' => (float) ($history->gaji_pokok ?? $history->gaji_kehadiran ?? 0),
            'total_hadir' => (int) ($history->total_hadir ?? 0),
            'total_terlambat' => 0,
            'total_menit_terlambat' => (int) ($history->total_terlambat ?? 0),
            'total_pulang_cepat' => 0,
            'total_menit_pulang_cepat' => 0,
            'total_menit_lembur' => null,
            'total_izin' => (int) ($history->total_izin ?? 0),
            'total_cuti' => 0,
            'total_sakit' => 0,
            'total_izin_lainnya' => 0,
            'total_izin_dibayar' => 0,
            'total_izin_tidak_dibayar' => 0,
            'total_alpha' => 0,
            'total_lembur_tarif' => (float) ($history->total_lembur ?? 0),
            'total_penyesuaian' => 0,
            'gaji_kehadiran' => (float) ($history->gaji_kehadiran ?? 0),
            'tunjangan_makan_total' => (float) ($history->tunjangan_makan ?? 0),
            'tunjangan_transport_total' => (float) ($history->tunjangan_transport ?? 0),
            'tunjangan_jabatan_tampil' => 0,
            'bonus_pribadi_total' => 0,
            'bonus_team_total' => 0,
            'bonus_manual_total' => 0,
            'premi_kehadiran_total' => 0,
            'premi_kehadiran' => (float) ($employee?->premi_kehadiran ?? 0),
            'premi_kehadiran_mode' => $this->attendancePremiumModeValue($employee?->premi_kehadiran_mode ?? null),
            'premi_kehadiran_mode_label' => $this->attendancePremiumModeLabel($employee?->premi_kehadiran_mode ?? null),
            'premi_kehadiran_toleransi_telat' => (int) ($employee?->premi_kehadiran_toleransi_telat ?? 0),
            'premi_kehadiran_toleransi_pulang_cepat' => (int) ($employee?->premi_kehadiran_toleransi_pulang_cepat ?? 0),
            'premi_kehadiran_status' => 'disabled',
            'premi_kehadiran_keterangan' => 'Belum ada hitungan premi kehadiran tersimpan.',
            'total_bonus' => 0,
            'bpjs_mode' => 'off',
            'bpjs_mode_label' => 'Nonaktif',
            'tunjangan_bpjs_kesehatan_total' => 0,
            'tunjangan_bpjs_ketenagakerjaan_total' => 0,
            'bpjs_tunjangan_total' => 0,
            'potongan_bpjs_kesehatan_total' => 0,
            'potongan_bpjs_ketenagakerjaan_total' => 0,
            'bpjs_potongan_total' => 0,
            'bpjs_breakdown' => [],
            'retirement_contribution_total' => 0,
            'saldo_kasbon_awal' => $this->resolveKasbonOpeningBalance([], $employee),
            'take_home_before_kasbon' => (float) ($history->total_gaji ?? 0) + (float) ($history->applied_kasbon_amount ?? 0),
            'kasbon_payable_cap' => max(0, (float) ($history->total_gaji ?? 0) + (float) ($history->applied_kasbon_amount ?? 0)),
            'manual_kasbon_amount' => $history->manual_kasbon_amount !== null ? (float) $history->manual_kasbon_amount : null,
            'kasbon_manual_override' => $history->manual_kasbon_amount !== null,
            'potongan_kasbon' => (float) ($history->applied_kasbon_amount ?? 0),
            'thr_mode' => 'off',
            'thr_amount' => (float) ($history->thr ?? 0),
            'thr_ratio' => 0,
            'thr_status' => 'locked',
            'thr_base_salary' => 0,
            'thr_service_months' => 0,
            'thr_manual_amount' => 0,
            'thr_payment_date' => null,
            'thr_due_date' => null,
            'potongan_ketidakhadiran_total' => 0,
            'potongan_mangkir' => 0,
            'potongan_izin' => 0,
            'potongan_terlambat' => (float) ($history->potongan_terlambat ?? 0),
            'pph21_mode' => 'off',
            'pph21_status' => 'locked',
            'pph21_method' => 'legacy',
            'pph21_ter_category' => null,
            'pph21_ter_rate' => 0,
            'pph21_gross_basis' => 0,
            'pph21_job_expense' => 0,
            'pph21_net_annual' => 0,
            'pph21_amount' => (float) ($history->pph21 ?? 0),
            'pph21_ptkp' => 0,
            'pph21_pkp' => 0,
            'pph21_annual_tax' => 0,
            'tax_counterpart_opt' => $employee?->tax_counterpart_opt ?? 'Resident',
            'tax_passport_number' => $employee?->tax_passport_number,
            'tax_has_second_employer' => (bool) ($employee?->tax_has_second_employer ?? false),
            'tax_prev_withholding_slip_number' => $employee?->tax_prev_withholding_slip_number,
            'tax_prev_gross_income' => (float) ($employee?->tax_prev_gross_income ?? 0),
            'tax_prev_pph21_paid' => (float) ($employee?->tax_prev_pph21_paid ?? 0),
            'tax_prev_retirement_contribution' => (float) ($employee?->tax_prev_retirement_contribution ?? 0),
            'tax_certificate' => $employee?->tax_certificate ?? 'N/A',
            'taxable_gross_total' => 0,
            'total_potongan' => (float) ($history->potongan_terlambat ?? 0) + (float) ($history->applied_kasbon_amount ?? 0) + (float) ($history->pph21 ?? 0),
            'total_gaji' => (float) ($history->total_gaji ?? 0),
        ];
    }

    public function applyKasbonDeduction(GajiKaryawan $history): float
    {
        $history->loadMissing('karyawan');

        if (! $history->karyawan) {
            return 0;
        }

        $alreadyApplied = max(0, (float) ($history->applied_kasbon_amount ?? 0));

        if ($alreadyApplied > 0) {
            return $alreadyApplied;
        }

        $snapshot = $history->detail_payload ?: $this->historySnapshot($history);
        $plannedAmount = max(0, (float) ($snapshot['potongan_kasbon'] ?? 0));

        if ($plannedAmount <= 0) {
            if ((float) ($history->applied_kasbon_amount ?? 0) !== 0.0) {
                $history->update(['applied_kasbon_amount' => 0]);
            }

            return 0;
        }

        $deduction = $this->kasbonService->recordPayrollDeduction($history, $plannedAmount, auth()->id());
        $appliedAmount = (float) ($deduction['applied'] ?? 0);
        $adjustedSnapshot = $this->adjustKasbonSnapshot(
            $snapshot,
            $plannedAmount,
            $appliedAmount,
            (float) ($deduction['balance_before'] ?? $this->resolveKasbonOpeningBalance($snapshot, $history->karyawan))
        );

        $history->update([
            'detail_payload' => $adjustedSnapshot,
            'total_gaji' => (float) ($adjustedSnapshot['total_gaji'] ?? $history->total_gaji ?? 0),
            'applied_kasbon_amount' => $appliedAmount,
        ]);

        return $appliedAmount;
    }

    public function revertKasbonDeduction(GajiKaryawan $history): float
    {
        $history->loadMissing('karyawan');

        if (! $history->karyawan) {
            return 0;
        }

        $appliedAmount = max(0, (float) ($history->applied_kasbon_amount ?? 0));

        if ($appliedAmount <= 0) {
            return 0;
        }

        $reversal = $this->kasbonService->recordPayrollReversal($history, $appliedAmount, auth()->id());
        $snapshot = $history->detail_payload ?: $this->historySnapshot($history);
        $restoredSaldo = (float) ($reversal['balance_after'] ?? 0);
        $restoredSnapshot = $this->replaceKasbonAmount(
            $snapshot,
            $restoredSaldo,
            $history->manual_kasbon_amount !== null ? (float) $history->manual_kasbon_amount : null,
            $history->manual_kasbon_amount !== null,
        );

        $history->update([
            'detail_payload' => $restoredSnapshot,
            'total_gaji' => (float) ($restoredSnapshot['total_gaji'] ?? $history->total_gaji ?? 0),
            'applied_kasbon_amount' => 0,
        ]);

        return $appliedAmount;
    }

    public function updateDraftKasbon(GajiKaryawan $history, ?float $manualAmount): GajiKaryawan
    {
        $history->loadMissing('karyawan');

        if ($history->is_finalized) {
            throw new \DomainException('Payroll ini sudah final dan tidak bisa diubah.');
        }

        if (! $history->karyawan) {
            throw new \DomainException('Karyawan untuk payroll ini tidak ditemukan.');
        }

        $snapshot = $history->detail_payload ?: $this->historySnapshot($history);
        $adjustedSnapshot = $this->replaceKasbonAmount(
            $snapshot,
            $this->kasbonService->currentBalance($history->karyawan),
            $manualAmount,
            $manualAmount !== null,
        );

        $history->update([
            'detail_payload' => $adjustedSnapshot,
            'total_gaji' => (float) ($adjustedSnapshot['total_gaji'] ?? $history->total_gaji ?? 0),
            'manual_kasbon_amount' => $manualAmount !== null
                ? (float) ($adjustedSnapshot['manual_kasbon_amount'] ?? 0)
                : null,
            'applied_kasbon_amount' => 0,
        ]);

        return $history->fresh(['karyawan', 'finalizedBy']);
    }

    private function componentsForEmployee(Karyawan $karyawan, ?Setting $settings = null): array
    {
        $komponen = KomponenGajiKaryawan::query()
            ->where('karyawan_id', $karyawan->id)
            ->first();

        $settings ??= Setting::query()->find(1);
        $dailySalaryRate = $komponen?->getRawOriginal('gaji_per_hari');

        if ($dailySalaryRate === null || (float) $dailySalaryRate <= 0) {
            $employeeDailyRate = $karyawan->getRawOriginal('gaji_per_hari');
            $dailySalaryRate = ($employeeDailyRate !== null && (float) $employeeDailyRate > 0)
                ? (float) $employeeDailyRate
                : (float) ($settings?->gaji_per_hari ?? 0);
        }

        return [
            'gaji_per_hari' => (float) $dailySalaryRate,
            'tunjangan_jabatan' => (float) ($komponen?->tunjangan_jabatan ?? 0),
            'tunjangan_makan' => (float) ($komponen?->tunjangan_makan ?? $settings?->uang_makan ?? 15000),
            'tunjangan_transport' => (float) ($komponen?->tunjangan_transport ?? $settings?->tunjangan_transport ?? 10000),
            'tunjangan_bpjs_kesehatan' => 0.0,
            'tunjangan_bpjs_ketenagakerjaan' => 0.0,
            'potongan_per_menit' => (float) ($komponen?->potongan_per_menit ?? 0),
            'potongan_izin' => (float) ($komponen?->potongan_izin ?? 0),
            'potongan_mangkir' => (float) ($komponen?->potongan_mangkir ?? 0),
            'potongan_terlambat' => (float) ($komponen?->potongan_terlambat ?? $settings?->potongan_terlambat ?? 0),
            'potongan_bpjs_kesehatan' => 0.0,
            'potongan_bpjs_ketenagakerjaan' => 0.0,
        ];
    }

    private function resolvePayrollPeriod(Carbon $period): PayrollPeriod
    {
        $period = $period->copy()->startOfMonth();
        $payrollPeriod = PayrollPeriod::query()
            ->whereDate('periode', $period->toDateString())
            ->first();

        if ($payrollPeriod) {
            return $payrollPeriod;
        }

        return PayrollPeriod::query()->create([
            'periode' => $period->toDateString(),
            'tanggal_mulai' => $period->toDateString(),
            'tanggal_selesai' => $period->copy()->endOfMonth()->toDateString(),
            'cutoff_absensi' => $period->copy()->endOfMonth()->toDateString(),
            'status' => 'draft',
        ]);
    }

    private function shouldIncludeInPayroll(array $salary): bool
    {
        return $salary['has_attendance']
            || $salary['scheduled_days'] > 0
            || $salary['total_izin'] > 0
            || $salary['total_lembur_tarif'] > 0
            || abs((float) $salary['total_penyesuaian']) > 0
            || abs((float) $salary['premi_kehadiran_total']) > 0
            || abs((float) $salary['total_bonus']) > 0
            || abs((float) ($salary['thr_amount'] ?? 0)) > 0
            || abs((float) ($salary['pph21_amount'] ?? 0)) > 0
            || abs((float) $salary['potongan_kasbon']) > 0
            || abs((float) $salary['base_salary_total']) > 0;
    }

    private function historyToRow(GajiKaryawan $history): array
    {
        $snapshot = $history->detail_payload ?: $this->historySnapshot($history);

        return [
            'karyawan' => $history->karyawan,
            'has_attendance' => true,
            'period' => $history->bulan?->copy()->startOfMonth() ?? now()->startOfMonth(),
            'components' => $snapshot['components'] ?? [],
            'payroll_type' => (string) ($snapshot['payroll_type'] ?? ($history->karyawan ? $this->resolvePayrollType($history->karyawan) : 'bulanan')),
            'payroll_type_label' => (string) ($snapshot['payroll_type_label'] ?? ((($snapshot['payroll_type'] ?? null) === 'harian' || $history->karyawan?->tipe_penggajian === 'harian') ? 'Harian' : 'Bulanan')),
            'payroll_divisor' => (int) ($snapshot['payroll_divisor'] ?? $this->resolvePayrollDivisor()),
            'payable_days' => (int) ($snapshot['payable_days'] ?? (($snapshot['total_hadir'] ?? 0) + ($snapshot['total_izin_dibayar'] ?? 0))),
            'scheduled_days' => (int) ($snapshot['scheduled_days'] ?? 0),
            'scheduled_days_full' => (int) ($snapshot['scheduled_days_full'] ?? $snapshot['scheduled_days'] ?? 0),
            'prorate_ratio' => (float) ($snapshot['prorate_ratio'] ?? $history->prorate_ratio ?? 1),
            'prorate_method' => (string) ($snapshot['prorate_method'] ?? 'work_days'),
            'prorate_status' => (string) ($snapshot['prorate_status'] ?? 'locked'),
            'employment_active_start' => $snapshot['employment_active_start'] ?? null,
            'employment_active_end' => $snapshot['employment_active_end'] ?? null,
            'daily_deduction_rate' => (float) ($snapshot['daily_deduction_rate'] ?? $history->gaji_per_hari ?? 0),
            'base_salary_total' => (float) ($snapshot['base_salary_total'] ?? $history->gaji_pokok ?? $history->gaji_kehadiran ?? 0),
            'total_hadir' => (int) ($snapshot['total_hadir'] ?? $history->total_hadir ?? 0),
            'total_terlambat' => (int) ($snapshot['total_terlambat'] ?? 0),
            'total_menit_terlambat' => (int) ($snapshot['total_menit_terlambat'] ?? $history->total_terlambat ?? 0),
            'total_pulang_cepat' => (int) ($snapshot['total_pulang_cepat'] ?? 0),
            'total_menit_pulang_cepat' => (int) ($snapshot['total_menit_pulang_cepat'] ?? 0),
            'total_menit_lembur' => array_key_exists('total_menit_lembur', $snapshot) && $snapshot['total_menit_lembur'] !== null
                ? (int) $snapshot['total_menit_lembur']
                : null,
            'total_izin' => (int) ($snapshot['total_izin'] ?? $history->total_izin ?? 0),
            'total_cuti' => (int) ($snapshot['total_cuti'] ?? 0),
            'total_sakit' => (int) ($snapshot['total_sakit'] ?? 0),
            'total_izin_lainnya' => (int) ($snapshot['total_izin_lainnya'] ?? 0),
            'total_izin_dibayar' => (int) ($snapshot['total_izin_dibayar'] ?? 0),
            'total_izin_tidak_dibayar' => (int) ($snapshot['total_izin_tidak_dibayar'] ?? 0),
            'total_alpha' => (int) ($snapshot['total_alpha'] ?? 0),
            'total_lembur_tarif' => (float) ($snapshot['total_lembur_tarif'] ?? $history->total_lembur ?? 0),
            'total_penyesuaian' => (float) ($snapshot['total_penyesuaian'] ?? 0),
            'gaji_kehadiran' => (float) ($snapshot['gaji_kehadiran'] ?? $history->gaji_kehadiran ?? 0),
            'tunjangan_makan_total' => (float) ($snapshot['tunjangan_makan_total'] ?? $history->tunjangan_makan ?? 0),
            'tunjangan_transport_total' => (float) ($snapshot['tunjangan_transport_total'] ?? $history->tunjangan_transport ?? 0),
            'tunjangan_jabatan_tampil' => (float) ($snapshot['tunjangan_jabatan_tampil'] ?? 0),
            'bonus_pribadi_total' => (float) ($snapshot['bonus_pribadi_total'] ?? 0),
            'bonus_team_total' => (float) ($snapshot['bonus_team_total'] ?? 0),
            'bonus_manual_total' => (float) ($snapshot['bonus_manual_total'] ?? 0),
            'premi_kehadiran_total' => (float) ($snapshot['premi_kehadiran_total'] ?? 0),
            'premi_kehadiran' => (float) ($snapshot['premi_kehadiran'] ?? $history->karyawan?->premi_kehadiran ?? 0),
            'premi_kehadiran_mode' => (string) ($snapshot['premi_kehadiran_mode'] ?? $this->attendancePremiumModeValue($history->karyawan?->premi_kehadiran_mode ?? null)),
            'premi_kehadiran_mode_label' => (string) ($snapshot['premi_kehadiran_mode_label'] ?? $this->attendancePremiumModeLabel($history->karyawan?->premi_kehadiran_mode ?? null)),
            'premi_kehadiran_toleransi_telat' => (int) ($snapshot['premi_kehadiran_toleransi_telat'] ?? $history->karyawan?->premi_kehadiran_toleransi_telat ?? 0),
            'premi_kehadiran_toleransi_pulang_cepat' => (int) ($snapshot['premi_kehadiran_toleransi_pulang_cepat'] ?? $history->karyawan?->premi_kehadiran_toleransi_pulang_cepat ?? 0),
            'premi_kehadiran_status' => (string) ($snapshot['premi_kehadiran_status'] ?? 'disabled'),
            'premi_kehadiran_keterangan' => (string) ($snapshot['premi_kehadiran_keterangan'] ?? 'Premi kehadiran tidak aktif.'),
            'total_bonus' => (float) ($snapshot['total_bonus'] ?? 0),
            'bpjs_mode' => ((string) ($snapshot['bpjs_mode'] ?? 'off')) === 'auto' ? 'auto' : 'off',
            'bpjs_mode_label' => (string) ($snapshot['bpjs_mode_label'] ?? ((((string) ($snapshot['bpjs_mode'] ?? 'off')) === 'auto') ? 'Otomatis' : 'Nonaktif')),
            'tunjangan_bpjs_kesehatan_total' => (float) ($snapshot['tunjangan_bpjs_kesehatan_total'] ?? 0),
            'tunjangan_bpjs_ketenagakerjaan_total' => (float) ($snapshot['tunjangan_bpjs_ketenagakerjaan_total'] ?? 0),
            'bpjs_tunjangan_total' => (float) ($snapshot['bpjs_tunjangan_total'] ?? 0),
            'potongan_bpjs_kesehatan_total' => (float) ($snapshot['potongan_bpjs_kesehatan_total'] ?? 0),
            'potongan_bpjs_ketenagakerjaan_total' => (float) ($snapshot['potongan_bpjs_ketenagakerjaan_total'] ?? 0),
            'bpjs_potongan_total' => (float) ($snapshot['bpjs_potongan_total'] ?? 0),
            'bpjs_breakdown' => (array) ($snapshot['bpjs_breakdown'] ?? []),
            'retirement_contribution_total' => (float) ($snapshot['retirement_contribution_total'] ?? 0),
            'saldo_kasbon_awal' => $this->resolveKasbonOpeningBalance($snapshot, $history->karyawan),
            'take_home_before_kasbon' => (float) ($snapshot['take_home_before_kasbon'] ?? (((float) ($snapshot['total_gaji'] ?? $history->total_gaji ?? 0)) + ((float) ($snapshot['potongan_kasbon'] ?? $history->applied_kasbon_amount ?? 0)))),
            'kasbon_payable_cap' => (float) ($snapshot['kasbon_payable_cap'] ?? max(0, ((float) ($snapshot['take_home_before_kasbon'] ?? (((float) ($snapshot['total_gaji'] ?? $history->total_gaji ?? 0)) + ((float) ($snapshot['potongan_kasbon'] ?? $history->applied_kasbon_amount ?? 0))))))),
            'manual_kasbon_amount' => array_key_exists('manual_kasbon_amount', $snapshot)
                ? ($snapshot['manual_kasbon_amount'] !== null ? (float) $snapshot['manual_kasbon_amount'] : null)
                : ($history->manual_kasbon_amount !== null ? (float) $history->manual_kasbon_amount : null),
            'kasbon_manual_override' => (bool) ($snapshot['kasbon_manual_override'] ?? ($history->manual_kasbon_amount !== null)),
            'potongan_kasbon' => (float) ($snapshot['potongan_kasbon'] ?? $history->applied_kasbon_amount ?? 0),
            'thr_mode' => (string) ($snapshot['thr_mode'] ?? 'off'),
            'thr_amount' => (float) ($snapshot['thr_amount'] ?? $history->thr ?? 0),
            'thr_ratio' => (float) ($snapshot['thr_ratio'] ?? 0),
            'thr_status' => (string) ($snapshot['thr_status'] ?? 'locked'),
            'thr_base_salary' => (float) ($snapshot['thr_base_salary'] ?? 0),
            'thr_service_months' => (float) ($snapshot['thr_service_months'] ?? 0),
            'thr_manual_amount' => (float) ($snapshot['thr_manual_amount'] ?? 0),
            'thr_payment_date' => $snapshot['thr_payment_date'] ?? null,
            'thr_due_date' => $snapshot['thr_due_date'] ?? null,
            'potongan_ketidakhadiran_total' => (float) ($snapshot['potongan_ketidakhadiran_total'] ?? (($snapshot['potongan_mangkir'] ?? 0) + ($snapshot['potongan_izin'] ?? 0))),
            'potongan_mangkir' => (float) ($snapshot['potongan_mangkir'] ?? 0),
            'potongan_izin' => (float) ($snapshot['potongan_izin'] ?? 0),
            'potongan_terlambat' => (float) ($snapshot['potongan_terlambat'] ?? $history->potongan_terlambat ?? 0),
            'potongan_terlambat_mode' => (string) ($snapshot['potongan_terlambat_mode'] ?? 'per_event'),
            'potongan_terlambat_rate' => (float) ($snapshot['potongan_terlambat_rate'] ?? 0),
            'potongan_terlambat_daily_cap' => (float) ($snapshot['potongan_terlambat_daily_cap'] ?? 0),
            'pph21_mode' => (string) ($snapshot['pph21_mode'] ?? 'off'),
            'pph21_status' => (string) ($snapshot['pph21_status'] ?? 'locked'),
            'pph21_method' => (string) ($snapshot['pph21_method'] ?? 'legacy'),
            'pph21_ter_category' => $snapshot['pph21_ter_category'] ?? null,
            'pph21_ter_rate' => (float) ($snapshot['pph21_ter_rate'] ?? 0),
            'pph21_gross_basis' => (float) ($snapshot['pph21_gross_basis'] ?? 0),
            'pph21_job_expense' => (float) ($snapshot['pph21_job_expense'] ?? 0),
            'pph21_net_annual' => (float) ($snapshot['pph21_net_annual'] ?? 0),
            'pph21_amount' => (float) ($snapshot['pph21_amount'] ?? $history->pph21 ?? 0),
            'pph21_ptkp' => (float) ($snapshot['pph21_ptkp'] ?? 0),
            'pph21_pkp' => (float) ($snapshot['pph21_pkp'] ?? 0),
            'pph21_annual_tax' => (float) ($snapshot['pph21_annual_tax'] ?? 0),
            'tax_counterpart_opt' => (string) (($snapshot['karyawan']['tax_counterpart_opt'] ?? null) ?: ($history->karyawan?->tax_counterpart_opt ?? 'Resident')),
            'tax_passport_number' => $snapshot['karyawan']['tax_passport_number'] ?? $history->karyawan?->tax_passport_number,
            'tax_has_second_employer' => (bool) (($snapshot['karyawan']['tax_has_second_employer'] ?? null) ?? ($history->karyawan?->tax_has_second_employer ?? false)),
            'tax_prev_withholding_slip_number' => $snapshot['karyawan']['tax_prev_withholding_slip_number'] ?? $history->karyawan?->tax_prev_withholding_slip_number,
            'tax_prev_gross_income' => (float) (($snapshot['karyawan']['tax_prev_gross_income'] ?? null) ?? ($history->karyawan?->tax_prev_gross_income ?? 0)),
            'tax_prev_pph21_paid' => (float) (($snapshot['karyawan']['tax_prev_pph21_paid'] ?? null) ?? ($history->karyawan?->tax_prev_pph21_paid ?? 0)),
            'tax_prev_retirement_contribution' => (float) (($snapshot['karyawan']['tax_prev_retirement_contribution'] ?? null) ?? ($history->karyawan?->tax_prev_retirement_contribution ?? 0)),
            'tax_certificate' => (string) (($snapshot['karyawan']['tax_certificate'] ?? null) ?: ($history->karyawan?->tax_certificate ?? 'N/A')),
            'taxable_gross_total' => (float) ($snapshot['taxable_gross_total'] ?? 0),
            'total_potongan' => (float) ($snapshot['total_potongan'] ?? ((float) ($history->potongan_terlambat ?? 0) + (float) ($history->applied_kasbon_amount ?? 0) + (float) ($history->pph21 ?? 0))),
            'total_gaji' => (float) ($snapshot['total_gaji'] ?? $history->total_gaji ?? 0),
            'history' => $history,
            'is_finalized' => (bool) $history->is_finalized,
        ];
    }

    private function adjustKasbonSnapshot(array $snapshot, float $plannedAmount, float $appliedAmount, float $saldoKasbonAwal): array
    {
        $plannedAmount = max(0, $plannedAmount);
        $appliedAmount = max(0, $appliedAmount);
        $saldoKasbonAwal = max(0, $saldoKasbonAwal);

        $snapshot['saldo_kasbon_awal'] = $saldoKasbonAwal;
        $snapshot['take_home_before_kasbon'] = (float) ($snapshot['take_home_before_kasbon'] ?? ((float) ($snapshot['total_gaji'] ?? 0) + $plannedAmount));
        $snapshot['kasbon_payable_cap'] = max(0, (float) ($snapshot['kasbon_payable_cap'] ?? $snapshot['take_home_before_kasbon']));
        $snapshot['potongan_kasbon'] = $appliedAmount;
        $snapshot['total_potongan'] = max(
            0,
            (float) ($snapshot['total_potongan'] ?? 0) - $plannedAmount + $appliedAmount
        );
        $snapshot['total_gaji'] = (float) ($snapshot['total_gaji'] ?? 0) + $plannedAmount - $appliedAmount;

        return $snapshot;
    }

    private function clampKasbonAmount(float $saldoKasbonAwal, float $payableCap, ?float $requestedAmount = null): float
    {
        $saldoKasbonAwal = max(0, $saldoKasbonAwal);
        $payableCap = max(0, $payableCap);

        if ($requestedAmount === null) {
            return round(min($saldoKasbonAwal, $payableCap), 2);
        }

        return round(min(max(0, $requestedAmount), $saldoKasbonAwal, $payableCap), 2);
    }

    private function replaceKasbonAmount(
        array $row,
        float $saldoKasbonAwal,
        ?float $requestedAmount = null,
        bool $manualOverride = false,
    ): array {
        $currentAmount = max(0, (float) ($row['potongan_kasbon'] ?? 0));
        $takeHomeBeforeKasbon = (float) ($row['take_home_before_kasbon'] ?? ((float) ($row['total_gaji'] ?? 0) + $currentAmount));
        $payableCap = max(0, (float) ($row['kasbon_payable_cap'] ?? $takeHomeBeforeKasbon));
        $nextAmount = $this->clampKasbonAmount($saldoKasbonAwal, $payableCap, $requestedAmount);

        $row['saldo_kasbon_awal'] = max(0, $saldoKasbonAwal);
        $row['take_home_before_kasbon'] = $takeHomeBeforeKasbon;
        $row['kasbon_payable_cap'] = $payableCap;
        $row['potongan_kasbon'] = $nextAmount;
        $row['total_potongan'] = max(0, ((float) ($row['total_potongan'] ?? 0) - $currentAmount + $nextAmount));
        $row['total_gaji'] = (float) ($row['total_gaji'] ?? 0) + $currentAmount - $nextAmount;
        $row['kasbon_manual_override'] = $manualOverride;
        $row['manual_kasbon_amount'] = $manualOverride ? $nextAmount : null;

        return $row;
    }

    private function resolveKasbonOpeningBalance(array $snapshot = [], ?Karyawan $employee = null): float
    {
        if (array_key_exists('saldo_kasbon_awal', $snapshot)) {
            return max(0, (float) ($snapshot['saldo_kasbon_awal'] ?? 0));
        }

        return $this->kasbonService->currentBalance($employee);
    }

    private function resolveEmploymentWindow(
        Karyawan $karyawan,
        Carbon $period,
        Collection $scheduleCalendar,
        int $scheduledDays,
        ?Setting $settings = null,
    ): array {
        $periodStart = $period->copy()->startOfMonth()->startOfDay();
        $periodEnd = $period->copy()->endOfMonth()->startOfDay();
        $joinDate = $karyawan->tgl_join
            ? Carbon::parse($karyawan->tgl_join)->startOfDay()
            : null;
        $resignDate = $karyawan->tgl_resign
            ? Carbon::parse($karyawan->tgl_resign)->startOfDay()
            : null;

        $activeStart = $joinDate && $joinDate->gt($periodStart)
            ? $joinDate->copy()
            : $periodStart->copy();
        $activeEnd = $resignDate && $resignDate->lt($periodEnd)
            ? $resignDate->copy()
            : $periodEnd->copy();

        $isActiveForPeriod = $activeStart->lte($activeEnd);
        $activeCalendarDays = $isActiveForPeriod
            ? $activeStart->diffInDays($activeEnd) + 1
            : 0;
        $scheduledDaysForPayroll = $scheduleCalendar
            ->filter(function (array $day) use ($activeStart, $activeEnd, $isActiveForPeriod): bool {
                if (! $isActiveForPeriod || ! $day['is_workday']) {
                    return false;
                }

                $date = Carbon::parse($day['date'])->startOfDay();

                return $date->betweenIncluded($activeStart, $activeEnd);
            })
            ->count();
        $prorateMethod = $this->resolveProrateMethod($settings);

        if (! $isActiveForPeriod) {
            $prorateRatio = 0.0;
        } elseif ($prorateMethod === 'calendar_days') {
            $prorateRatio = $periodStart->daysInMonth > 0
                ? round($activeCalendarDays / $periodStart->daysInMonth, 4)
                : 0.0;
        } else {
            $prorateRatio = $scheduledDays > 0
                ? round($scheduledDaysForPayroll / $scheduledDays, 4)
                : 1.0;
        }

        $prorateRatio = max(0, min(1, $prorateRatio));
        $yearStart = $period->copy()->startOfYear()->startOfDay();
        $yearEnd = $period->copy()->endOfYear()->startOfDay();
        $employmentYearStart = $joinDate && $joinDate->gt($yearStart)
            ? $joinDate->copy()
            : $yearStart->copy();
        $employmentYearEnd = $resignDate && $resignDate->lt($yearEnd)
            ? $resignDate->copy()
            : $yearEnd->copy();
        $activeMonthsInYear = 0;
        $monthsWorkedYtd = 0;

        if ($employmentYearStart->lte($employmentYearEnd)) {
            $activeMonthsInYear = (($employmentYearEnd->year - $employmentYearStart->year) * 12)
                + ($employmentYearEnd->month - $employmentYearStart->month)
                + 1;

            $employmentYtdEnd = $activeEnd->lt($periodEnd) ? $activeEnd->copy() : $periodEnd->copy();
            if ($employmentYearStart->lte($employmentYtdEnd)) {
                $monthsWorkedYtd = (($employmentYtdEnd->year - $employmentYearStart->year) * 12)
                    + ($employmentYtdEnd->month - $employmentYearStart->month)
                    + 1;
            }
        }

        return [
            'active_start' => $isActiveForPeriod ? $activeStart : null,
            'active_end' => $isActiveForPeriod ? $activeEnd : null,
            'active_calendar_days' => $activeCalendarDays,
            'scheduled_days_for_payroll' => $scheduledDaysForPayroll,
            'prorate_ratio' => $prorateRatio,
            'prorate_method' => $prorateMethod,
            'status' => $prorateRatio >= 1 ? 'full' : ($prorateRatio > 0 ? 'prorated' : 'inactive'),
            'active_months_in_year' => max(0, $activeMonthsInYear),
            'months_worked_ytd' => max(0, $monthsWorkedYtd),
            'is_final_tax_month' => $period->month === 12
                || ($resignDate && $resignDate->betweenIncluded($periodStart, $periodEnd)),
        ];
    }

    private function resolveProrateMethod(?Setting $settings = null): string
    {
        $method = trim((string) ($settings?->payroll_prorate_method ?? ''));

        return $method === 'calendar_days'
            ? 'calendar_days'
            : 'work_days';
    }

    private function resolveBpjsSummary(Karyawan $karyawan, ?Setting $settings, array $components, float $bpjsBaseSalary): array
    {
        $disabledSummary = [
            'mode' => 'off',
            'mode_label' => 'Nonaktif',
            'tunjangan_bpjs_kesehatan_total' => 0.0,
            'tunjangan_bpjs_ketenagakerjaan_total' => 0.0,
            'potongan_bpjs_kesehatan_total' => 0.0,
            'potongan_bpjs_ketenagakerjaan_total' => 0.0,
            'retirement_contribution_total' => 0.0,
            'breakdown' => [],
        ];

        if (! $settings?->bpjs_auto_enabled || trim((string) ($karyawan->bpjs_mode ?? 'off')) !== 'auto') {
            return $disabledSummary;
        }

        $bpjsBaseSalary = max(0, $bpjsBaseSalary);
        $healthCap = max(0, (float) ($settings->bpjs_kesehatan_salary_cap ?? 0));
        if ($healthCap <= 0) {
            $healthCap = (float) config('payroll_compliance.bpjs.health_salary_cap', 12000000);
        }
        $healthBase = $healthCap > 0 ? min($bpjsBaseSalary, $healthCap) : $bpjsBaseSalary;
        $jpCap = max(0, (float) ($settings->bpjs_jp_salary_cap ?? 0));
        if ($jpCap <= 0) {
            $jpCap = (float) config('payroll_compliance.bpjs.jp_salary_cap', 10547400);
        }
        $jpBase = $jpCap > 0 ? min($bpjsBaseSalary, $jpCap) : $bpjsBaseSalary;
        $jkkCompanyRate = $this->resolveJkkCompanyPercent($settings);

        $healthCompany = round($healthBase * ((float) ($settings->bpjs_kesehatan_company_percent ?? 0) / 100), 2);
        $healthEmployee = round($healthBase * ((float) ($settings->bpjs_kesehatan_employee_percent ?? 0) / 100), 2);
        $jhtCompany = round($bpjsBaseSalary * ((float) ($settings->bpjs_jht_company_percent ?? 0) / 100), 2);
        $jhtEmployee = round($bpjsBaseSalary * ((float) ($settings->bpjs_jht_employee_percent ?? 0) / 100), 2);
        $jpCompany = round($jpBase * ((float) ($settings->bpjs_jp_company_percent ?? 0) / 100), 2);
        $jpEmployee = round($jpBase * ((float) ($settings->bpjs_jp_employee_percent ?? 0) / 100), 2);
        $jkkCompany = round($bpjsBaseSalary * ($jkkCompanyRate / 100), 2);
        $jkmCompany = round($bpjsBaseSalary * ((float) ($settings->bpjs_jkm_company_percent ?? 0) / 100), 2);

        return [
            'mode' => 'auto',
            'mode_label' => 'Otomatis',
            'tunjangan_bpjs_kesehatan_total' => $healthCompany,
            'tunjangan_bpjs_ketenagakerjaan_total' => $jhtCompany + $jpCompany + $jkkCompany + $jkmCompany,
            'potongan_bpjs_kesehatan_total' => $healthEmployee,
            'potongan_bpjs_ketenagakerjaan_total' => $jhtEmployee + $jpEmployee,
            'retirement_contribution_total' => $jhtEmployee + $jpEmployee,
            'breakdown' => [
                'base_salary' => $bpjsBaseSalary,
                'health_base' => $healthBase,
                'health_company' => $healthCompany,
                'health_employee' => $healthEmployee,
                'jht_company' => $jhtCompany,
                'jht_employee' => $jhtEmployee,
                'jp_cap' => $jpCap,
                'jp_base' => $jpBase,
                'jp_company' => $jpCompany,
                'jp_employee' => $jpEmployee,
                'jkk_rate' => $jkkCompanyRate,
                'jkk_company' => $jkkCompany,
                'jkm_company' => $jkmCompany,
            ],
        ];
    }

    private function resolveThrBaseSalary(
        Karyawan $karyawan,
        Carbon $period,
        ?Setting $settings,
        array $components,
        string $payrollType,
        float $fullMonthlyBaseSalary,
        float $dailySalaryRate,
        int $payrollDivisor,
    ): float {
        $fixedAllowanceTotal = $this->resolveThrFixedAllowanceTotal($settings, $components, $payrollDivisor);

        if ($payrollType === 'harian') {
            $averageMonthlyWage = $this->resolveDailyWorkerAverageMonthlyWage($karyawan, $period);

            if ($averageMonthlyWage > 0) {
                return $averageMonthlyWage;
            }

            return round(($dailySalaryRate * max(1, $payrollDivisor)) + $fixedAllowanceTotal, 2);
        }

        return round(max(0, $fullMonthlyBaseSalary) + $fixedAllowanceTotal, 2);
    }

    private function resolveThrFixedAllowanceTotal(?Setting $settings, array $components, int $payrollDivisor): float
    {
        $total = 0.0;

        if ($this->settingBoolean($settings, 'thr_include_tunjangan_jabatan', true)) {
            $total += max(0, (float) ($components['tunjangan_jabatan'] ?? 0));
        }

        if ($this->settingBoolean($settings, 'thr_include_tunjangan_makan', false)) {
            $total += max(0, (float) ($components['tunjangan_makan'] ?? 0)) * max(1, $payrollDivisor);
        }

        if ($this->settingBoolean($settings, 'thr_include_tunjangan_transport', false)) {
            $total += max(0, (float) ($components['tunjangan_transport'] ?? 0)) * max(1, $payrollDivisor);
        }

        return round($total, 2);
    }

    private function settingBoolean(?Setting $settings, string $key, bool $default): bool
    {
        $value = $settings?->getAttribute($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function resolveDailyWorkerAverageMonthlyWage(Karyawan $karyawan, Carbon $period): float
    {
        $periodStart = $period->copy()->startOfMonth();
        $historyStart = $periodStart->copy()->subMonths(12);

        if ($karyawan->tgl_join) {
            $joinMonth = Carbon::parse($karyawan->tgl_join)->startOfMonth();

            if ($joinMonth->gt($historyStart)) {
                $historyStart = $joinMonth;
            }
        }

        $histories = GajiKaryawan::query()
            ->where('karyawan_id', $karyawan->id)
            ->whereDate('bulan', '>=', $historyStart->toDateString())
            ->whereDate('bulan', '<', $periodStart->toDateString())
            ->orderByDesc('bulan')
            ->limit(12)
            ->get();

        $monthlyWages = $histories
            ->map(fn (GajiKaryawan $history): float => $this->resolveHistoricalMonthlyWage($history))
            ->filter(fn (float $amount): bool => $amount > 0);

        if ($monthlyWages->isEmpty()) {
            return 0.0;
        }

        return round((float) $monthlyWages->avg(), 2);
    }

    private function resolveHistoricalMonthlyWage(GajiKaryawan $history): float
    {
        $snapshot = is_array($history->detail_payload) ? $history->detail_payload : [];

        if ($snapshot !== []) {
            return round(
                max(0, (float) ($snapshot['base_salary_total'] ?? $snapshot['gaji_kehadiran'] ?? 0))
                + max(0, (float) ($snapshot['tunjangan_jabatan_tampil'] ?? 0))
                + max(0, (float) ($snapshot['tunjangan_makan_total'] ?? 0))
                + max(0, (float) ($snapshot['tunjangan_transport_total'] ?? 0)),
                2,
            );
        }

        return round(
            max(0, (float) ($history->gaji_kehadiran ?? $history->gaji_pokok ?? 0))
            + max(0, (float) ($history->tunjangan_makan ?? 0))
            + max(0, (float) ($history->tunjangan_transport ?? 0)),
            2,
        );
    }

    private function resolveThrSummary(
        Karyawan $karyawan,
        Carbon $period,
        ?Setting $settings,
        float $thrBaseSalary,
        array $employmentWindow,
    ): array {
        $mode = trim((string) ($karyawan->thr_mode ?? 'off'));
        $paymentMonth = (int) ($settings?->thr_payment_month ?? 0);
        $isEnabled = (bool) ($settings?->thr_auto_enabled ?? false);
        $manualAmount = max(0, (float) ($karyawan->thr_manual_amount ?? 0));
        $paymentDate = $settings?->thr_payment_date
            ? Carbon::parse($settings->thr_payment_date)->startOfDay()
            : null;
        $periodStart = $period->copy()->startOfMonth()->startOfDay();
        $periodEnd = $employmentWindow['active_end'] instanceof Carbon
            ? $employmentWindow['active_end']->copy()
            : $period->copy()->endOfMonth();
        $enabledForPeriod = $paymentDate
            ? $paymentDate->betweenIncluded($periodStart, $period->copy()->endOfMonth()->startOfDay())
            : ($paymentMonth >= 1 && $paymentMonth <= 12 && $period->month === $paymentMonth);
        $referenceDate = $paymentDate ?: $periodEnd->copy();

        if ($periodEnd->lt($referenceDate)) {
            $referenceDate = $periodEnd->copy();
        }

        $result = [
            'mode' => in_array($mode, ['auto', 'manual'], true) ? $mode : 'off',
            'amount' => 0.0,
            'manual_amount' => $manualAmount,
            'ratio' => 0.0,
            'status' => 'off',
            'base_salary' => max(0, $thrBaseSalary),
            'service_months' => 0.0,
            'payment_date' => $paymentDate?->toDateString(),
            'due_date' => $paymentDate?->copy()->subDays(7)->toDateString(),
        ];

        if ($mode === 'manual') {
            if (! $enabledForPeriod) {
                return $result;
            }

            return [
                ...$result,
                'amount' => $manualAmount,
                'ratio' => 1.0,
                'status' => 'manual',
                'service_months' => 0.0,
            ];
        }

        if (! $isEnabled || $mode !== 'auto' || ! $enabledForPeriod) {
            return $result;
        }

        $joinDate = $karyawan->tgl_join
            ? Carbon::parse($karyawan->tgl_join)->startOfDay()
            : null;

        if (! $joinDate) {
            return [
                ...$result,
                'amount' => max(0, $thrBaseSalary),
                'ratio' => 1.0,
                'status' => 'full',
                'service_months' => 12.0,
            ];
        }

        if ($joinDate->gt($referenceDate)) {
            return $result;
        }

        $serviceDays = $joinDate->diffInDays($referenceDate) + 1;

        if ($serviceDays < 30) {
            return [
                ...$result,
                'status' => 'not_eligible',
                'service_months' => round($serviceDays / 30, 2),
            ];
        }

        $serviceMonths = min(12, round($serviceDays / 30, 2));
        $ratio = min(1, round($serviceMonths / 12, 4));
        $amount = round(max(0, $thrBaseSalary) * $ratio, 2);

        return [
            ...$result,
            'amount' => $amount,
            'ratio' => $ratio,
            'status' => $ratio >= 1 ? 'full' : 'prorated',
            'service_months' => $serviceMonths,
        ];
    }

    private function resolvePph21Summary(
        Karyawan $karyawan,
        Carbon $period,
        ?Setting $settings,
        float $taxableGrossCurrent,
        float $retirementContributionCurrent,
        array $employmentWindow,
    ): array {
        $mode = trim((string) ($karyawan->pph21_mode ?? 'off'));
        $result = [
            'mode' => $mode === 'auto' ? 'auto' : 'off',
            'status' => 'off',
            'method' => 'off',
            'ter_category' => null,
            'ter_rate' => 0.0,
            'gross_basis' => 0.0,
            'job_expense' => 0.0,
            'net_annual' => 0.0,
            'amount' => 0.0,
            'ptkp' => 0.0,
            'pkp' => 0.0,
            'annual_tax' => 0.0,
        ];

        if (! $settings?->pph21_auto_enabled || $mode !== 'auto' || $taxableGrossCurrent <= 0) {
            return $result;
        }

        $ptkp = $this->resolvePtkp($karyawan->status_nikah ?? null);
        $terCategory = $this->resolveTerCategory($karyawan->status_nikah ?? null);
        $jobExpensePercent = max(0, (float) ($settings->pph21_job_expense_percent ?? 5));
        $jobExpenseMonthlyCap = max(0, (float) ($settings->pph21_job_expense_monthly_cap ?? 500000));
        $isFinalMonth = (bool) ($employmentWindow['is_final_tax_month'] ?? false);

        if (! $isFinalMonth && $terCategory) {
            $terRate = $this->resolveMonthlyTerRate($taxableGrossCurrent, $terCategory);

            return [
                'mode' => 'auto',
                'status' => 'ter_monthly',
                'method' => 'ter',
                'ter_category' => $terCategory,
                'ter_rate' => $terRate,
                'gross_basis' => round($taxableGrossCurrent, 2),
                'job_expense' => 0.0,
                'net_annual' => 0.0,
                'amount' => round($taxableGrossCurrent * ($terRate / 100), 2),
                'ptkp' => $ptkp,
                'pkp' => 0.0,
                'annual_tax' => 0.0,
            ];
        }

        $priorSummary = $this->resolvePriorPayrollTaxSummary($karyawan, $period);

        if ($isFinalMonth) {
            $annualGross = $priorSummary['taxable_gross_ytd'] + $taxableGrossCurrent;
            $retirementContributionAnnual = $priorSummary['retirement_contribution_ytd'] + $retirementContributionCurrent;
            $monthsBasis = max(1, (int) ($employmentWindow['months_worked_ytd'] ?? 1));
        } else {
            $monthsBasis = max(1, (int) ($employmentWindow['active_months_in_year'] ?? 12));
            $annualGross = $taxableGrossCurrent * $monthsBasis;
            $retirementContributionAnnual = $retirementContributionCurrent * $monthsBasis;
        }

        $jobExpenseAnnualCap = min(6000000, $jobExpenseMonthlyCap * $monthsBasis);
        $jobExpense = min($annualGross * ($jobExpensePercent / 100), $jobExpenseAnnualCap);
        $netAnnual = max(0, $annualGross - $jobExpense - $retirementContributionAnnual);
        $pkp = floor(max(0, $netAnnual - $ptkp) / 1000) * 1000;
        $annualTax = $this->calculateProgressivePph21($pkp);

        if ($isFinalMonth) {
            $amount = $annualTax - $priorSummary['pph21_ytd'];
            $status = 'final_reconcile';
            $method = 'annual_reconcile';
        } else {
            $amount = $monthsBasis > 0 ? ($annualTax / $monthsBasis) : 0;
            $status = 'estimated';
            $method = 'annualized';
        }

        return [
            'mode' => 'auto',
            'status' => $status,
            'method' => $method,
            'ter_category' => $terCategory,
            'ter_rate' => 0.0,
            'gross_basis' => round($annualGross, 2),
            'job_expense' => round($jobExpense, 2),
            'net_annual' => round($netAnnual, 2),
            'amount' => round($amount, 2),
            'ptkp' => $ptkp,
            'pkp' => (float) $pkp,
            'annual_tax' => round($annualTax, 2),
        ];
    }

    private function resolvePriorPayrollTaxSummary(Karyawan $karyawan, Carbon $period): array
    {
        $year = $period->year;
        $currentMonth = $period->month;

        $histories = GajiKaryawan::query()
            ->where('karyawan_id', $karyawan->id)
            ->whereYear('bulan', $year)
            ->whereMonth('bulan', '<', $currentMonth)
            ->get();

        $summary = [
            'taxable_gross_ytd' => max(0, (float) ($karyawan->tax_prev_gross_income ?? 0)),
            'pph21_ytd' => max(0, (float) ($karyawan->tax_prev_pph21_paid ?? 0)),
            'retirement_contribution_ytd' => max(0, (float) ($karyawan->tax_prev_retirement_contribution ?? 0)),
        ];

        foreach ($histories as $history) {
            $snapshot = $history->detail_payload ?: $this->historySnapshot($history);
            $summary['taxable_gross_ytd'] += (float) ($snapshot['taxable_gross_total'] ?? 0);
            $summary['pph21_ytd'] += (float) ($snapshot['pph21_amount'] ?? $history->pph21 ?? 0);
            $summary['retirement_contribution_ytd'] += (float) ($snapshot['retirement_contribution_total'] ?? 0);
        }

        return $summary;
    }

    private function resolvePtkp(?string $statusNikah): float
    {
        $ptkpMap = config('payroll_tax.ptkp', []);
        $normalized = strtoupper(trim((string) ($statusNikah ?? '')));

        return (float) ($ptkpMap[$normalized] ?? $ptkpMap['TK/0']);
    }

    private function resolveTerCategory(?string $statusNikah): ?string
    {
        $categories = config('payroll_tax.ter_categories', []);
        $normalized = strtoupper(trim((string) ($statusNikah ?? '')));

        if ($normalized === '') {
            return $categories['TK/0'] ?? 'A';
        }

        return $categories[$normalized] ?? null;
    }

    private function resolveMonthlyTerRate(float $grossMonthly, string $category): float
    {
        $tables = config('payroll_tax.ter_monthly', []);
        $bands = $tables[strtoupper($category)] ?? [];
        $grossMonthly = max(0, $grossMonthly);

        foreach ($bands as $band) {
            $max = $band['max'] ?? null;

            if ($max === null || $grossMonthly <= (float) $max) {
                return (float) ($band['rate'] ?? 0);
            }
        }

        return 0.0;
    }

    private function calculateProgressivePph21(float $pkp): float
    {
        $pkp = max(0, $pkp);
        $remaining = $pkp;
        $tax = 0.0;
        $brackets = [
            [60000000, 0.05],
            [250000000, 0.15],
            [500000000, 0.25],
            [5000000000, 0.30],
        ];
        $lowerBound = 0;

        foreach ($brackets as [$upperBound, $rate]) {
            if ($remaining <= 0) {
                break;
            }

            $taxableLayer = min($remaining, $upperBound - $lowerBound);
            $tax += $taxableLayer * $rate;
            $remaining -= $taxableLayer;
            $lowerBound = $upperBound;
        }

        if ($remaining > 0) {
            $tax += $remaining * 0.35;
        }

        return round($tax, 2);
    }

    private function resolveAttendancePremium(Karyawan $karyawan, array $metrics): array
    {
        $mode = $this->attendancePremiumModeValue($karyawan->premi_kehadiran_mode ?? null);
        $nominal = max(0, (float) ($karyawan->premi_kehadiran ?? 0));
        $scheduledDays = max(0, (int) ($metrics['scheduled_days'] ?? 0));
        $totalHadir = max(0, (int) ($metrics['total_hadir'] ?? 0));
        $totalIzin = max(0, (int) ($metrics['total_izin'] ?? 0));
        $totalIzinTidakDibayar = max(0, (int) ($metrics['total_izin_tidak_dibayar'] ?? 0));
        $totalAlpha = max(0, (int) ($metrics['total_alpha'] ?? 0));
        $totalTerlambat = max(0, (int) ($metrics['total_terlambat'] ?? 0));
        $totalPulangCepat = max(0, (int) ($metrics['total_pulang_cepat'] ?? 0));
        $toleransiTelat = max(0, (int) ($karyawan->premi_kehadiran_toleransi_telat ?? 0));
        $toleransiPulangCepat = max(0, (int) ($karyawan->premi_kehadiran_toleransi_pulang_cepat ?? 0));

        $result = [
            'mode' => $mode,
            'mode_label' => $this->attendancePremiumModeLabel($mode),
            'amount' => 0.0,
            'status' => 'disabled',
            'keterangan' => 'Premi kehadiran tidak aktif.',
            'toleransi_telat' => $toleransiTelat,
            'toleransi_pulang_cepat' => $toleransiPulangCepat,
        ];

        if ($mode === 'nonaktif' || $nominal <= 0 || $scheduledDays <= 0) {
            if ($nominal > 0 && $scheduledDays <= 0) {
                $result['keterangan'] = 'Belum ada jadwal kerja untuk menghitung premi kehadiran.';
            }

            return $result;
        }

        if ($mode === 'penuh') {
            $eligible = $totalHadir >= $scheduledDays
                && $totalIzin === 0
                && $totalAlpha === 0
                && $totalTerlambat === 0
                && $totalPulangCepat === 0;

            return [
                ...$result,
                'amount' => $eligible ? $nominal : 0.0,
                'status' => $eligible ? 'eligible' : 'rejected',
                'keterangan' => $eligible
                    ? 'Premi hadir penuh cair karena kehadiran lengkap tanpa izin, telat, atau pulang cepat.'
                    : 'Premi hadir penuh hanya cair jika hadir lengkap tanpa izin, telat, dan pulang cepat.',
            ];
        }

        if ($mode === 'toleran') {
            $eligible = $totalAlpha === 0
                && $totalIzinTidakDibayar === 0
                && $totalTerlambat <= $toleransiTelat
                && $totalPulangCepat <= $toleransiPulangCepat;

            return [
                ...$result,
                'amount' => $eligible ? $nominal : 0.0,
                'status' => $eligible ? 'eligible' : 'rejected',
                'keterangan' => $eligible
                    ? "Premi hadir toleran cair. Telat {$totalTerlambat}/{$toleransiTelat} kali, pulang cepat {$totalPulangCepat}/{$toleransiPulangCepat} kali."
                    : "Premi hadir toleran gagal. Telat {$totalTerlambat}/{$toleransiTelat} kali, pulang cepat {$totalPulangCepat}/{$toleransiPulangCepat} kali, alpha {$totalAlpha} hari, izin unpaid {$totalIzinTidakDibayar} hari.",
            ];
        }

        $ratio = min(1, round($totalHadir / max(1, $scheduledDays), 4));
        $amount = round($nominal * $ratio, 2);

        return [
            ...$result,
            'amount' => $amount,
            'status' => $amount > 0 ? 'prorated' : 'rejected',
            'keterangan' => "Premi hadir prorata {$totalHadir}/{$scheduledDays} hari kerja efektif.",
        ];
    }

    private function attendancePremiumModeValue(mixed $mode): string
    {
        $mode = trim((string) $mode);

        return in_array($mode, ['penuh', 'toleran', 'prorata'], true)
            ? $mode
            : 'nonaktif';
    }

    private function attendancePremiumModeLabel(mixed $mode): string
    {
        return match ($this->attendancePremiumModeValue($mode)) {
            'penuh' => 'Hadir Penuh',
            'toleran' => 'Toleran',
            'prorata' => 'Prorata',
            default => 'Nonaktif',
        };
    }

    private function normalizeUnpaidAbsenceDeductions(
        float $monthlyBaseSalary,
        int $scheduledDays,
        int $totalHadir,
        int $totalIzinDibayar,
        int $totalAlpha,
        int $totalIzinTidakDibayar,
        float $rawPotonganMangkir,
        float $rawPotonganIzin,
    ): array {
        $rawPotonganMangkir = max(0, round($rawPotonganMangkir, 2));
        $rawPotonganIzin = max(0, round($rawPotonganIzin, 2));
        $rawTotal = $rawPotonganMangkir + $rawPotonganIzin;

        if ($rawTotal <= 0 || $monthlyBaseSalary <= 0 || $scheduledDays <= 0) {
            return [
                'potongan_mangkir' => $rawPotonganMangkir,
                'potongan_izin' => $rawPotonganIzin,
            ];
        }

        $fullUnpaidMonth = $totalHadir === 0
            && $totalIzinDibayar === 0
            && ($totalAlpha + $totalIzinTidakDibayar) >= $scheduledDays;

        $targetTotal = $rawTotal;

        if ($fullUnpaidMonth) {
            $targetTotal = $monthlyBaseSalary;
        } elseif ($rawTotal > $monthlyBaseSalary) {
            $targetTotal = $monthlyBaseSalary;
        }

        $targetTotal = max(0, round($targetTotal, 2));

        if (abs($targetTotal - $rawTotal) < 0.01) {
            return [
                'potongan_mangkir' => $rawPotonganMangkir,
                'potongan_izin' => $rawPotonganIzin,
            ];
        }

        if ($rawPotonganMangkir <= 0) {
            return [
                'potongan_mangkir' => 0,
                'potongan_izin' => $targetTotal,
            ];
        }

        if ($rawPotonganIzin <= 0) {
            return [
                'potongan_mangkir' => $targetTotal,
                'potongan_izin' => 0,
            ];
        }

        $ratio = $targetTotal / $rawTotal;
        $adjustedMangkir = round($rawPotonganMangkir * $ratio, 2);
        $adjustedIzin = round($targetTotal - $adjustedMangkir, 2);

        return [
            'potongan_mangkir' => max(0, $adjustedMangkir),
            'potongan_izin' => max(0, $adjustedIzin),
        ];
    }

    private function resolveLateDeductionSummary(Collection $attendanceRows, array $components, ?Setting $settings): array
    {
        $perMinuteRate = max(0, (float) ($components['potongan_per_menit'] ?? 0));

        if ($perMinuteRate <= 0) {
            $perMinuteRate = max(0, (float) ($settings?->potongan_per_menit ?? 0));
        }

        $perEventRate = max(0, (float) ($components['potongan_terlambat'] ?? 0));

        if ($perMinuteRate <= 0) {
            return [
                'mode' => 'per_event',
                'rate' => $perEventRate,
                'daily_cap' => 0.0,
                'amount' => round($perEventRate * $attendanceRows->where('status', 'terlambat')->count(), 2),
            ];
        }

        $dailyCap = max(0, (float) ($settings?->max_potongan_harian ?? 0));
        $amount = $attendanceRows
            ->where('status', 'terlambat')
            ->reduce(function (float $carry, Absensi $row) use ($perMinuteRate, $dailyCap): float {
                $minutes = max(0, (int) ($row->menit_terlambat ?? 0));
                $rowAmount = round($minutes * $perMinuteRate, 2);

                if ($dailyCap > 0) {
                    $rowAmount = min($rowAmount, $dailyCap);
                }

                return $carry + $rowAmount;
            }, 0.0);

        return [
            'mode' => 'per_minute',
            'rate' => $perMinuteRate,
            'daily_cap' => $dailyCap,
            'amount' => round($amount, 2),
        ];
    }

    private function resolveJkkCompanyPercent(?Setting $settings): float
    {
        $riskLevel = trim((string) ($settings?->bpjs_jkk_risk_level ?? ''));
        $config = config('payroll_compliance.bpjs.jkk_risk_levels.'.$riskLevel);

        if (is_array($config) && $riskLevel !== 'custom' && array_key_exists('percent', $config) && $config['percent'] !== null) {
            return (float) $config['percent'];
        }

        $configuredRate = max(0, (float) ($settings?->bpjs_jkk_company_percent ?? 0));

        if ($configuredRate > 0) {
            return $configuredRate;
        }

        return (float) (config('payroll_compliance.bpjs.jkk_risk_levels.very_low.percent') ?? 0.24);
    }

    private function resolvePayrollType(Karyawan $karyawan): string
    {
        return $karyawan->tipe_penggajian === 'harian'
            ? 'harian'
            : 'bulanan';
    }

    private function resolvePayrollDivisor(?Setting $settings = null): int
    {
        $settings ??= Setting::query()->find(1);
        $settingsDivisor = (int) ($settings?->payroll_divisor_bulanan ?? 0);

        return $settingsDivisor > 0 ? $settingsDivisor : 26;
    }

    private function resolveDailyPayrollRate(
        Karyawan $karyawan,
        array $components,
        string $payrollType,
        int $payrollDivisor,
        ?Setting $settings = null,
    ): float {
        if ($payrollType === 'bulanan' && (float) ($karyawan->gaji_pokok ?? 0) > 0) {
            return round((float) $karyawan->gaji_pokok / max(1, $payrollDivisor), 2);
        }

        $componentRate = (float) ($components['gaji_per_hari'] ?? 0);
        if ($componentRate > 0) {
            return $componentRate;
        }

        $employeeRate = (float) ($karyawan->gaji_per_hari ?? 0);
        if ($employeeRate > 0) {
            return $employeeRate;
        }

        $settings ??= Setting::query()->find(1);

        if ((float) ($settings?->gaji_per_hari ?? 0) > 0) {
            return (float) $settings->gaji_per_hari;
        }

        if ((float) ($karyawan->gaji_pokok ?? 0) > 0) {
            return round((float) $karyawan->gaji_pokok / max(1, $payrollDivisor), 2);
        }

        return 0;
    }
}
