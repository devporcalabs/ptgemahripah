<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\Setting;
use Carbon\Carbon;

class AttendanceOvertimeService
{
    public const MODE_FLAT_HOURLY = 'flat_hourly';
    public const MODE_UU_CIPTA_KERJA = 'uu_cipta_kerja';

    private const MINIMUM_OVERTIME_MINUTES = 60;

    public function __construct(
        private readonly ShiftScheduleResolver $shiftScheduleResolver,
        private readonly MonthlyScheduleService $monthlyScheduleService,
    ) {
    }

    public function recalculate(Absensi $attendance, Karyawan $karyawan): array
    {
        $attendanceDate = $attendance->tanggal instanceof Carbon
            ? $attendance->tanggal->copy()
            : Carbon::parse($attendance->tanggal);

        $schedule = $this->shiftScheduleResolver->resolveForAttendance($karyawan, $attendanceDate);
        $expectedCheckout = $this->resolveExpectedCheckout($attendance, $schedule);
        $actualCheckin = $this->combineAttendanceDateAndTime($attendance, $attendance->jam_masuk);
        $actualCheckout = $this->combineAttendanceDateAndTime($attendance, $attendance->jam_keluar);

        return $this->calculateFromCheckout(
            $expectedCheckout,
            $actualCheckout,
            $karyawan,
            $attendanceDate,
            $schedule,
            $actualCheckin,
        );
    }

    public function expectedCheckout(Absensi $attendance, Karyawan $karyawan): ?Carbon
    {
        $attendanceDate = $attendance->tanggal instanceof Carbon
            ? $attendance->tanggal->copy()
            : Carbon::parse($attendance->tanggal);

        $schedule = $this->shiftScheduleResolver->resolveForAttendance($karyawan, $attendanceDate);

        return $this->resolveExpectedCheckout($attendance, $schedule);
    }

    public function calculateFromCheckout(
        ?Carbon $expectedCheckout,
        ?Carbon $actualCheckout,
        Karyawan $karyawan,
        ?Carbon $workDate = null,
        ?array $schedule = null,
        ?Carbon $actualCheckin = null,
    ): array
    {
        if (! $expectedCheckout instanceof Carbon || ! $actualCheckout instanceof Carbon) {
            $isHoliday = ! ($schedule['is_workday'] ?? true);

            if (! $isHoliday || ! $actualCheckin instanceof Carbon) {
                return $this->emptyResult($expectedCheckout);
            }

            $holidayCheckout = $actualCheckout->copy();
            if ($holidayCheckout->lte($actualCheckin)) {
                $holidayCheckout->addDay();
            }

            $overtimeMinutes = $actualCheckin->diffInMinutes($holidayCheckout);
            if ($overtimeMinutes <= 0) {
                return $this->emptyResult($expectedCheckout);
            }

            return $this->calculateCompensation(
                overtimeMinutes: $overtimeMinutes,
                karyawan: $karyawan,
                expectedCheckout: null,
                workDate: $workDate ?? $holidayCheckout->copy()->startOfDay(),
                schedule: $schedule,
            );
        }

        $isHoliday = ! ($schedule['is_workday'] ?? true);

        if ($isHoliday && $actualCheckin instanceof Carbon) {
            $holidayCheckout = $actualCheckout->copy();
            if ($holidayCheckout->lte($actualCheckin)) {
                $holidayCheckout->addDay();
            }

            $overtimeMinutes = $actualCheckin->diffInMinutes($holidayCheckout);

            if ($overtimeMinutes <= 0) {
                return $this->emptyResult($expectedCheckout);
            }

            return $this->calculateCompensation(
                overtimeMinutes: $overtimeMinutes,
                karyawan: $karyawan,
                expectedCheckout: null,
                workDate: $workDate ?? $holidayCheckout->copy()->startOfDay(),
                schedule: $schedule,
            );
        }

        if ($actualCheckout->lte($expectedCheckout)) {
            return $this->emptyResult($expectedCheckout);
        }

        $overtimeMinutes = $expectedCheckout->diffInMinutes($actualCheckout);

        if ($overtimeMinutes <= self::MINIMUM_OVERTIME_MINUTES) {
            return $this->emptyResult($expectedCheckout);
        }

        return $this->calculateCompensation(
            overtimeMinutes: $overtimeMinutes,
            karyawan: $karyawan,
            expectedCheckout: $expectedCheckout,
            workDate: $workDate ?? $actualCheckout->copy()->startOfDay(),
            schedule: $schedule,
        );
    }

    public function previewManualOvertime(Karyawan $karyawan, Carbon $workDate, float $durationHours): array
    {
        $overtimeMinutes = max(0, (int) round($durationHours * 60));

        if ($overtimeMinutes <= 0) {
            return $this->emptyResult($workDate);
        }

        $schedule = $this->shiftScheduleResolver->resolveForAttendance($karyawan, $workDate->copy());

        return $this->calculateCompensation(
            overtimeMinutes: $overtimeMinutes,
            karyawan: $karyawan,
            expectedCheckout: null,
            workDate: $workDate,
            schedule: $schedule,
        );
    }

    private function resolveExpectedCheckout(Absensi $attendance, array $schedule): ?Carbon
    {
        if (($schedule['jenis_jam_kerja'] ?? null) === 'fleksibel') {
            $durationHours = (float) ($schedule['durasi_kerja_fleksibel'] ?? 8);
            $checkinTime = $this->combineAttendanceDateAndTime($attendance, $attendance->jam_masuk);

            if ($checkinTime instanceof Carbon && $durationHours > 0) {
                return $checkinTime->copy()->addMinutes((int) round($durationHours * 60));
            }
        }

        return $schedule['expected_checkout'] ?? null;
    }

    private function combineAttendanceDateAndTime(Absensi $attendance, ?string $time): ?Carbon
    {
        $time = trim((string) $time);
        if ($time === '' || ! $attendance->tanggal) {
            return null;
        }

        return Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $attendance->tanggal->toDateString().' '.(strlen($time) === 5 ? $time.':00' : $time),
            config('app.timezone'),
        );
    }

    public function resolveHourlyOvertimeRate(Karyawan $karyawan): float
    {
        $karyawan->loadMissing('komponenGaji');

        $rawCustomRate = $karyawan->komponenGaji?->getRawOriginal('tarif_lembur_per_jam');
        if ($rawCustomRate !== null) {
            return max(0.0, (float) $karyawan->komponenGaji?->tarif_lembur_per_jam);
        }

        $dailySalary = (float) ($karyawan->komponenGaji?->gaji_per_hari ?? 0);
        if ($dailySalary <= 0) {
            $dailySalary = (float) ($karyawan->gaji_per_hari ?? 0);
        }
        if ($dailySalary <= 0 && (float) ($karyawan->gaji_pokok ?? 0) > 0) {
            $settings = Setting::query()->find(1);
            $divisor = $this->resolvePayrollDivisor($settings);

            $dailySalary = round((float) $karyawan->gaji_pokok / max(1, $divisor), 2);
        }
        if ($dailySalary <= 0) {
            $dailySalary = 100000;
        }

        return ($dailySalary / 8) * 1.5;
    }

    public function resolveOvertimeMode(): string
    {
        $mode = trim((string) (Setting::query()->find(1)?->overtime_calculation_mode ?? self::MODE_FLAT_HOURLY));

        return in_array($mode, [self::MODE_FLAT_HOURLY, self::MODE_UU_CIPTA_KERJA], true)
            ? $mode
            : self::MODE_FLAT_HOURLY;
    }

    public function resolveOvertimeModeLabel(?string $mode = null): string
    {
        return match ($mode ?? $this->resolveOvertimeMode()) {
            self::MODE_UU_CIPTA_KERJA => 'Aturan UU Cipta Kerja',
            default => 'Jam Tetap',
        };
    }

    private function calculateCompensation(
        int $overtimeMinutes,
        Karyawan $karyawan,
        ?Carbon $expectedCheckout,
        ?Carbon $workDate,
        ?array $schedule = null,
    ): array {
        $overtimeHours = round($overtimeMinutes / 60, 2);
        $mode = $this->resolveOvertimeMode();

        if ($mode === self::MODE_UU_CIPTA_KERJA) {
            return $this->calculateLegalCompensation(
                overtimeMinutes: $overtimeMinutes,
                overtimeHours: $overtimeHours,
                karyawan: $karyawan,
                expectedCheckout: $expectedCheckout,
                workDate: $workDate,
                schedule: $schedule,
            );
        }

        $ratePerHour = $this->resolveHourlyOvertimeRate($karyawan);
        $totalAmount = round($ratePerHour * $overtimeHours);

        return [
            'mode' => $mode,
            'mode_label' => $this->resolveOvertimeModeLabel($mode),
            'menit_lembur' => $overtimeMinutes,
            'jam_lembur' => $overtimeHours,
            'tarif_per_jam' => round($ratePerHour, 2),
            'tarif_lembur' => $totalAmount,
            'expected_checkout' => $expectedCheckout,
            'calculation_summary' => 'Rp '.number_format($ratePerHour, 0, ',', '.').' × '.rtrim(rtrim(number_format($overtimeHours, 2, ',', '.'), '0'), ',').' jam',
        ];
    }

    private function calculateLegalCompensation(
        int $overtimeMinutes,
        float $overtimeHours,
        Karyawan $karyawan,
        ?Carbon $expectedCheckout,
        ?Carbon $workDate,
        ?array $schedule = null,
    ): array {
        $workDate ??= now()->startOfDay();
        $schedule ??= $this->shiftScheduleResolver->resolveForAttendance($karyawan, $workDate->copy());
        $isRestDay = ! ($schedule['is_workday'] ?? true);
        $hourlyWage = $this->resolveLegalHourlyWage($karyawan);
        $multiplier = $this->resolveLegalMultiplier($overtimeMinutes, $karyawan, $isRestDay, $workDate);
        $totalAmount = round($hourlyWage * $multiplier);
        $workdayTypeLabel = $isRestDay ? 'Hari libur / istirahat mingguan' : 'Hari kerja';

        return [
            'mode' => self::MODE_UU_CIPTA_KERJA,
            'mode_label' => $this->resolveOvertimeModeLabel(self::MODE_UU_CIPTA_KERJA),
            'menit_lembur' => $overtimeMinutes,
            'jam_lembur' => $overtimeHours,
            'tarif_per_jam' => round($hourlyWage, 2),
            'tarif_lembur' => $totalAmount,
            'expected_checkout' => $expectedCheckout,
            'calculation_summary' => $workdayTypeLabel.' · faktor '.rtrim(rtrim(number_format($multiplier, 2, ',', '.'), '0'), ',').' × upah sejam Rp '.number_format($hourlyWage, 0, ',', '.'),
        ];
    }

    private function resolveLegalHourlyWage(Karyawan $karyawan): float
    {
        $karyawan->loadMissing('komponenGaji');

        $monthlyWage = max(0, (float) ($karyawan->gaji_pokok ?? 0))
            + max(0, (float) ($karyawan->komponenGaji?->tunjangan_jabatan ?? 0));

        if ($monthlyWage <= 0) {
            $dailySalary = (float) ($karyawan->komponenGaji?->gaji_per_hari ?? 0);

            if ($dailySalary <= 0) {
                $dailySalary = (float) ($karyawan->gaji_per_hari ?? 0);
            }

            if ($dailySalary <= 0) {
                $settings = Setting::query()->find(1);
                $dailySalary = (float) ($settings?->gaji_per_hari ?? 0);
            }

            if ($dailySalary > 0) {
                $settings = Setting::query()->find(1);
                $divisor = $this->resolvePayrollDivisor($settings);

                $monthlyWage = round($dailySalary * max(1, $divisor), 2);
            }
        }

        return $monthlyWage > 0
            ? round($monthlyWage / 173, 2)
            : 0.0;
    }

    private function resolveLegalMultiplier(int $overtimeMinutes, Karyawan $karyawan, bool $isRestDay, ?Carbon $workDate = null): float
    {
        if (! $isRestDay) {
            $firstHourMinutes = min(60, $overtimeMinutes);
            $nextMinutes = max(0, $overtimeMinutes - 60);

            return round(($firstHourMinutes / 60) * 1.5 + ($nextMinutes / 60) * 2, 4);
        }

        $isFiveDayWeek = $this->resolveWeeklyHolidayCount($karyawan, $workDate) >= 2;
        $remainingMinutes = $overtimeMinutes;
        $multiplier = 0.0;

        if ($isFiveDayWeek) {
            $firstBand = min($remainingMinutes, 8 * 60);
            $multiplier += ($firstBand / 60) * 2;
            $remainingMinutes -= $firstBand;

            $secondBand = min(max(0, $remainingMinutes), 60);
            $multiplier += ($secondBand / 60) * 3;
            $remainingMinutes -= $secondBand;

            if ($remainingMinutes > 0) {
                $multiplier += ($remainingMinutes / 60) * 4;
            }

            return round($multiplier, 4);
        }

        $firstBand = min($remainingMinutes, 7 * 60);
        $multiplier += ($firstBand / 60) * 2;
        $remainingMinutes -= $firstBand;

        $secondBand = min(max(0, $remainingMinutes), 60);
        $multiplier += ($secondBand / 60) * 3;
        $remainingMinutes -= $secondBand;

        if ($remainingMinutes > 0) {
            $multiplier += ($remainingMinutes / 60) * 4;
        }

        return round($multiplier, 4);
    }

    private function resolveWeeklyHolidayCount(Karyawan $karyawan, ?Carbon $referenceDate = null): int
    {
        $referenceDate ??= now();
        $calendar = $this->monthlyScheduleService->calendarForEmployee($karyawan, $referenceDate->copy()->startOfMonth());

        $weeklyHolidays = $calendar
            ->groupBy('day_name')
            ->filter(fn ($days) => $days->isNotEmpty() && $days->every(fn (array $day) => ! ($day['is_workday'] ?? true)))
            ->count();

        return max(1, $weeklyHolidays);
    }

    private function resolvePayrollDivisor(?Setting $settings = null): int
    {
        $settings ??= Setting::query()->find(1);
        $divisor = (int) ($settings?->payroll_divisor_bulanan ?? 0);

        return $divisor > 0 ? $divisor : 26;
    }

    private function emptyResult(?Carbon $expectedCheckout): array
    {
        return [
            'mode' => $this->resolveOvertimeMode(),
            'mode_label' => $this->resolveOvertimeModeLabel(),
            'menit_lembur' => 0,
            'jam_lembur' => 0.0,
            'tarif_per_jam' => 0.0,
            'tarif_lembur' => 0,
            'expected_checkout' => $expectedCheckout,
            'calculation_summary' => '-',
        ];
    }
}
