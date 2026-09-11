<?php

namespace App\Services;

use App\Models\GajiKaryawan;
use App\Models\Karyawan;
use App\Models\Setting;
use Carbon\Carbon;
use DOMDocument;
use Illuminate\Support\Collection;

class BuktiPotongA1Service
{
    public function __construct(
        private readonly SalaryService $salaryService,
    ) {
    }

    public function draftsForYear(int $year): Collection
    {
        $settings = Setting::query()->find(1);
        $now = now();

        return GajiKaryawan::query()
            ->with(['karyawan', 'payrollPeriod'])
            ->whereYear('bulan', $year)
            ->orderBy('bulan')
            ->get()
            ->filter(fn (GajiKaryawan $history): bool => $history->karyawan !== null)
            ->groupBy('karyawan_id')
            ->map(fn (Collection $histories): ?array => $this->buildDraftRow($histories, $year, $settings, $now))
            ->filter()
            ->sortBy(fn (array $row) => sprintf(
                '%s-%s',
                $row['ready'] ? '0' : '1',
                strtolower((string) ($row['employee']['nama_lengkap'] ?? ''))
            ))
            ->values();
    }

    public function draftForEmployee(Karyawan $employee, int $year): ?array
    {
        $settings = Setting::query()->find(1);
        $now = now();

        $histories = GajiKaryawan::query()
            ->with(['karyawan', 'payrollPeriod'])
            ->where('karyawan_id', $employee->id)
            ->whereYear('bulan', $year)
            ->orderBy('bulan')
            ->get();

        if ($histories->isEmpty()) {
            return null;
        }

        return $this->buildDraftRow($histories->values(), $year, $settings, $now);
    }

    public function buildCsvRows(iterable $rows): array
    {
        return collect($rows)->map(function (array $row): array {
            $payload = $row['djp_payload'];

            return [
                'NPWP Pemotong' => $payload['TIN'],
                'Pemberi Kerja Selanjutnya' => $payload['WorkForSecondEmployer'],
                'Masa Pajak Awal' => $payload['TaxPeriodMonthStart'],
                'Masa Pajak Akhir' => $payload['TaxPeriodMonthEnd'],
                'Tahun Pajak' => $payload['TaxPeriodYear'],
                'WNI/WNA' => $payload['CounterpartOpt'],
                'No. Paspor' => $payload['CounterpartPassport'],
                'NPWP / NIK Pegawai' => $payload['CounterpartTin'],
                'Status PTKP' => $payload['TaxExemptOpt'],
                'Posisi' => $payload['CounterpartPosition'],
                'Kode Objek Pajak' => $payload['TaxObjectCode'],
                'Status Bukti Potong' => $payload['StatusOfWithholding'],
                'Jumlah Bulan Bekerja' => $payload['NumberOfMonths'],
                'Gaji' => $payload['SalaryPensionJhtTht'],
                'Opsi Gross Up' => $payload['GrossUpOpt'],
                'Tunjangan PPh' => $payload['IncomeTaxBenefit'],
                'Tunjangan Lainnya / Lembur' => $payload['OtherBenefit'],
                'Honorarium' => $payload['Honorarium'],
                'Asuransi' => $payload['InsurancePaidByEmployer'],
                'Natura' => $payload['Natura'],
                'Tantiem, Bonus, Gratifikasi, THR' => $payload['TantiemBonusThr'],
                'Iuran Pensiun atau Biaya THT/JHT' => $payload['PensionContributionJhtThtFee'],
                'Zakat' => $payload['Zakat'],
                'Nomor Bukti Potong Sebelumnya' => $payload['PrevWhTaxSlip'],
                'Fasilitas Pajak' => $payload['TaxCertificate'],
                'PPh Pasal 21' => $payload['Article21IncomeTax'],
                'ID TKU Pemotong' => $payload['IDPlaceOfBusinessActivity'],
                'Tanggal Pemotongan' => $payload['WithholdingDate'],
            ];
        })->values()->all();
    }

    public function buildXml(iterable $rows): string
    {
        $rows = collect($rows)->values();

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $root = $document->createElement('A1Bulk');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $document->appendChild($root);

        $tin = (string) ($rows->first()['djp_payload']['TIN'] ?? '');
        $root->appendChild($document->createElement('TIN', $tin));

        $listElement = $document->createElement('ListOfA1');
        $root->appendChild($listElement);

        foreach ($rows as $row) {
            $payload = $row['djp_payload'];
            $a1 = $document->createElement('A1');
            $listElement->appendChild($a1);

            foreach ([
                'WorkForSecondEmployer',
                'TaxPeriodMonthStart',
                'TaxPeriodMonthEnd',
                'TaxPeriodYear',
                'CounterpartOpt',
                'CounterpartPassport',
                'CounterpartTin',
                'TaxExemptOpt',
                'StatusOfWithholding',
                'CounterpartPosition',
                'TaxObjectCode',
                'NumberOfMonths',
                'SalaryPensionJhtTht',
                'GrossUpOpt',
                'IncomeTaxBenefit',
                'OtherBenefit',
                'Honorarium',
                'InsurancePaidByEmployer',
                'Natura',
                'TantiemBonusThr',
                'PensionContributionJhtThtFee',
                'Zakat',
                'PrevWhTaxSlip',
                'TaxCertificate',
                'Article21IncomeTax',
                'IDPlaceOfBusinessActivity',
                'WithholdingDate',
            ] as $field) {
                $value = $payload[$field] ?? null;
                $element = $document->createElement($field);

                if ($value === null || $value === '') {
                    $element->setAttributeNS(
                        'http://www.w3.org/2001/XMLSchema-instance',
                        'xsi:nil',
                        'true'
                    );
                } else {
                    $element->appendChild($document->createTextNode((string) $value));
                }

                $a1->appendChild($element);
            }
        }

        return $document->saveXML() ?: '';
    }

    private function buildDraftRow(Collection $histories, int $year, ?Setting $settings, Carbon $now): ?array
    {
        $histories = $histories
            ->sortBy(fn (GajiKaryawan $history) => optional($history->bulan)->timestamp ?? 0)
            ->values();

        $employee = $histories->first()?->karyawan;

        if (! $employee) {
            return null;
        }

        $entries = $histories->map(function (GajiKaryawan $history): array {
            return [
                'history' => $history,
                'period' => $history->bulan instanceof Carbon
                    ? $history->bulan->copy()->startOfMonth()
                    : Carbon::parse($history->bulan)->startOfMonth(),
                'snapshot' => is_array($history->detail_payload)
                    ? $history->detail_payload
                    : $this->salaryService->historySnapshot($history),
            ];
        });

        $lastEntry = $entries->last();
        $lastSnapshot = (array) ($lastEntry['snapshot'] ?? []);
        $payrollType = (string) ($lastSnapshot['payroll_type'] ?? $employee->tipe_penggajian ?? 'bulanan');

        if ($payrollType !== 'bulanan') {
            return null;
        }

        $joinDate = $employee->tgl_join instanceof Carbon
            ? $employee->tgl_join->copy()
            : ($employee->tgl_join ? Carbon::parse($employee->tgl_join) : null);
        $resignDate = $employee->tgl_resign instanceof Carbon
            ? $employee->tgl_resign->copy()
            : ($employee->tgl_resign ? Carbon::parse($employee->tgl_resign) : null);

        $hasResignedInYear = $resignDate?->year === $year;
        $taxPeriodStart = $joinDate && $joinDate->year === $year ? $joinDate->month : 1;
        $taxPeriodEnd = $hasResignedInYear
            ? $resignDate->month
            : ($year < $now->year ? 12 : (int) ($lastEntry['period']->month ?? 12));
        $expectedFinalMonth = $hasResignedInYear ? $taxPeriodEnd : 12;
        $lastMonth = (int) ($lastEntry['period']->month ?? 0);
        $isAnnualReconcile = (string) ($lastSnapshot['pph21_method'] ?? '') === 'annual_reconcile';

        $baseSalaryTotal = 0.0;
        $otherBenefitTotal = 0.0;
        $insurancePaidByEmployer = 0.0;
        $bonusThrTotal = 0.0;
        $retirementContributionTotal = 0.0;
        $pph21Ytd = 0.0;
        $taxableGrossYtd = 0.0;
        $negativeAdjustmentTotal = 0.0;

        foreach ($entries as $entry) {
            $snapshot = (array) $entry['snapshot'];
            $adjustment = (float) ($snapshot['total_penyesuaian'] ?? 0);

            $baseSalaryTotal += max(0, (float) ($snapshot['base_salary_total'] ?? 0));
            $otherBenefitTotal += max(0, (float) ($snapshot['tunjangan_jabatan_tampil'] ?? 0))
                + max(0, (float) ($snapshot['tunjangan_makan_total'] ?? 0))
                + max(0, (float) ($snapshot['tunjangan_transport_total'] ?? 0))
                + max(0, (float) ($snapshot['total_lembur_tarif'] ?? 0));
            $insurancePaidByEmployer += max(0, (float) ($snapshot['bpjs_tunjangan_total'] ?? 0));
            $bonusThrTotal += max(0, (float) ($snapshot['total_bonus'] ?? 0))
                + max(0, (float) ($snapshot['thr_amount'] ?? 0))
                + max(0, $adjustment);
            $retirementContributionTotal += max(0, (float) ($snapshot['retirement_contribution_total'] ?? 0));
            $pph21Ytd += (float) ($snapshot['pph21_amount'] ?? 0);
            $taxableGrossYtd += max(0, (float) ($snapshot['taxable_gross_total'] ?? 0));
            $negativeAdjustmentTotal += abs(min(0, $adjustment));
        }

        $annualGrossBasis = (float) ($lastSnapshot['pph21_gross_basis'] ?? 0);
        if ($annualGrossBasis <= 0) {
            $annualGrossBasis = $taxableGrossYtd;
        }

        $annualTax = (float) ($lastSnapshot['pph21_annual_tax'] ?? 0);
        if ($annualTax <= 0) {
            $annualTax = max(0, $pph21Ytd);
        }

        $employeeSnapshot = (array) ($lastSnapshot['karyawan'] ?? []);
        $counterpartOpt = $this->normalizeCounterpartOpt($employeeSnapshot['tax_counterpart_opt'] ?? $employee->tax_counterpart_opt ?? null);
        $counterpartPassport = $this->nullableTrim($employeeSnapshot['tax_passport_number'] ?? $employee->tax_passport_number ?? null);
        $workForSecondEmployer = $this->normalizeYesNoFlag($employeeSnapshot['tax_has_second_employer'] ?? $employee->tax_has_second_employer ?? false);
        $prevWhTaxSlip = $this->nullableTrim($employeeSnapshot['tax_prev_withholding_slip_number'] ?? $employee->tax_prev_withholding_slip_number ?? null);
        $prevGrossIncome = max(0, (float) (($employeeSnapshot['tax_prev_gross_income'] ?? null) ?? ($employee->tax_prev_gross_income ?? 0)));
        $prevPph21Paid = max(0, (float) (($employeeSnapshot['tax_prev_pph21_paid'] ?? null) ?? ($employee->tax_prev_pph21_paid ?? 0)));
        $prevRetirementContribution = max(0, (float) (($employeeSnapshot['tax_prev_retirement_contribution'] ?? null) ?? ($employee->tax_prev_retirement_contribution ?? 0)));
        $taxCertificate = $this->normalizeTaxCertificate($employeeSnapshot['tax_certificate'] ?? $employee->tax_certificate ?? null);
        $withholderTin = $this->normalizeDigits((string) ($settings?->tax_company_npwp ?? ''));
        $withholderNitku = $this->normalizeDigits((string) ($settings?->tax_company_nitku ?? ''));
        $counterpartTin = $this->normalizeDigits((string) (($employeeSnapshot['npwp'] ?? null) ?: $employee->npwp ?: $employee->nik));

        $validationErrors = [];
        $validationWarnings = [];

        if ($withholderTin === '') {
            $validationErrors[] = 'NPWP pemotong belum diisi pada Payroll Umum.';
        }

        if ($withholderNitku === '') {
            $validationErrors[] = 'ID TKU / NITKU pemotong belum diisi pada Payroll Umum.';
        }

        if ($counterpartOpt === 'Foreign') {
            if ($counterpartPassport === null) {
                $validationWarnings[] = 'Status pegawai Foreign belum mengisi nomor paspor. Review manual sebelum export.';
            }

            if ($counterpartTin === '' && $counterpartPassport === null) {
                $validationErrors[] = 'Identitas pajak pegawai Foreign belum lengkap. Isi NPWP/NIK pajak atau nomor paspor.';
            }
        } elseif ($counterpartTin === '') {
            $validationErrors[] = 'NPWP / NIK pajak karyawan belum diisi.';
        }

        if (! $isAnnualReconcile || $lastMonth < $expectedFinalMonth) {
            $validationErrors[] = $hasResignedInYear
                ? 'Payroll bulan resign belum membentuk rekonsiliasi PPh21 final tahunan.'
                : 'Payroll Desember atau masa pajak akhir belum tersedia / belum final tahunan.';
        }

        if ($negativeAdjustmentTotal > 0) {
            $validationWarnings[] = 'Ada koreksi payroll minus pada tahun ini. Review manual draft A1 disarankan.';
        }

        if ((string) ($employee->jabatan ?? '') === '') {
            $validationWarnings[] = 'Jabatan kosong. Sistem akan mengisi posisi sebagai "Pegawai".';
        }

        if ($workForSecondEmployer === 'Yes') {
            if ($prevGrossIncome <= 0) {
                $validationErrors[] = 'Pegawai pindahan wajib mengisi bruto pajak dari pemberi kerja sebelumnya agar BPA1 dan rekonsiliasi tahunan akurat.';
            }

            if ($prevWhTaxSlip === null) {
                $validationWarnings[] = 'Nomor bukti potong sebelumnya belum diisi. Tetap bisa lanjut jika memang belum diterbitkan perusahaan lama.';
            }
        }

        $statusOfWithholding = $year === $now->year && ! $hasResignedInYear && $taxPeriodEnd < 12
            ? 'Annualized'
            : ($taxPeriodStart === 1 && $taxPeriodEnd === 12 ? 'FullYear' : 'PartialYear');
        $numberOfMonths = $statusOfWithholding === 'FullYear'
            ? 0
            : max(1, (($taxPeriodEnd - $taxPeriodStart) + 1));
        $withholdingDate = ($hasResignedInYear && $resignDate)
            ? $resignDate->toDateString()
            : Carbon::create($year, max(1, $taxPeriodEnd), 1)->endOfMonth()->toDateString();

        $ready = $validationErrors === [];
        $position = trim((string) ($employee->jabatan ?: 'Pegawai'));

        $djpPayload = [
            'TIN' => $withholderTin,
            'WorkForSecondEmployer' => $workForSecondEmployer,
            'TaxPeriodMonthStart' => $taxPeriodStart,
            'TaxPeriodMonthEnd' => $taxPeriodEnd,
            'TaxPeriodYear' => $year,
            'CounterpartOpt' => $counterpartOpt,
            'CounterpartPassport' => $counterpartPassport,
            'CounterpartTin' => $counterpartTin,
            'TaxExemptOpt' => $employee->status_ptkp ?? 'TK/0',
            'StatusOfWithholding' => $statusOfWithholding,
            'CounterpartPosition' => $position,
            'TaxObjectCode' => '21-100-01',
            'NumberOfMonths' => $numberOfMonths,
            'SalaryPensionJhtTht' => $this->roundCurrency($baseSalaryTotal),
            'GrossUpOpt' => 'No',
            'IncomeTaxBenefit' => 0,
            'OtherBenefit' => $this->roundCurrency($otherBenefitTotal),
            'Honorarium' => 0,
            'InsurancePaidByEmployer' => $this->roundCurrency($insurancePaidByEmployer),
            'Natura' => 0,
            'TantiemBonusThr' => $this->roundCurrency($bonusThrTotal),
            'PensionContributionJhtThtFee' => $this->roundCurrency($retirementContributionTotal),
            'Zakat' => 0,
            'PrevWhTaxSlip' => $prevWhTaxSlip,
            'TaxCertificate' => $taxCertificate,
            'Article21IncomeTax' => $this->roundCurrency($annualTax),
            'IDPlaceOfBusinessActivity' => $withholderNitku,
            'WithholdingDate' => $withholdingDate,
        ];

        return [
            'employee' => [
                'id' => $employee->id,
                'nik' => (string) $employee->nik,
                'nama_lengkap' => (string) $employee->nama_lengkap,
                'jabatan' => (string) ($employee->jabatan ?: '-'),
                'departemen' => (string) ($employee->departemen ?: '-'),
                'npwp' => (string) ($employee->npwp ?: $employee->nik),
                'status_ptkp' => (string) ($employee->status_ptkp ?? 'TK/0'),
                'counterpart_opt' => $counterpartOpt,
                'passport_number' => $counterpartPassport,
                'second_employer' => $workForSecondEmployer,
                'prev_wh_tax_slip' => $prevWhTaxSlip,
                'prev_gross_income' => round($prevGrossIncome, 2),
                'prev_pph21_paid' => round($prevPph21Paid, 2),
                'prev_retirement_contribution' => round($prevRetirementContribution, 2),
                'tax_certificate' => $taxCertificate,
                'jenis_karyawan' => (string) ($employee->jenis_karyawan ?? 'tetap'),
                'payroll_type' => $payrollType,
            ],
            'year' => $year,
            'tax_period_start' => $taxPeriodStart,
            'tax_period_end' => $taxPeriodEnd,
            'expected_final_month' => $expectedFinalMonth,
            'last_month' => $lastMonth,
            'status_of_withholding' => $statusOfWithholding,
            'withholding_date' => $withholdingDate,
            'annual_gross_basis' => round($annualGrossBasis, 2),
            'pph21_annual_tax' => round($annualTax, 2),
            'pph21_ytd' => round($pph21Ytd, 2),
            'salary_total' => round($baseSalaryTotal, 2),
            'other_benefit_total' => round($otherBenefitTotal, 2),
            'insurance_total' => round($insurancePaidByEmployer, 2),
            'bonus_thr_total' => round($bonusThrTotal, 2),
            'retirement_contribution_total' => round($retirementContributionTotal, 2),
            'negative_adjustment_total' => round($negativeAdjustmentTotal, 2),
            'last_payroll_method' => (string) ($lastSnapshot['pph21_method'] ?? 'off'),
            'last_payroll_status' => $isAnnualReconcile ? 'Final Tahunan' : 'Belum Final Tahunan',
            'ready' => $ready,
            'validation_errors' => $validationErrors,
            'validation_warnings' => $validationWarnings,
            'ready_label' => $ready ? 'Siap Export' : 'Perlu Review',
            'djp_payload' => $djpPayload,
            'detail_payload' => [
                'employee' => [
                    'nik' => (string) $employee->nik,
                    'nama_lengkap' => (string) $employee->nama_lengkap,
                    'jabatan' => $position,
                    'departemen' => (string) ($employee->departemen ?: '-'),
                    'npwp' => (string) ($employee->npwp ?: $employee->nik),
                    'status_ptkp' => (string) ($employee->status_ptkp ?? 'TK/0'),
                    'counterpart_opt' => $counterpartOpt,
                    'passport_number' => $counterpartPassport,
                    'second_employer' => $workForSecondEmployer,
                    'prev_wh_tax_slip' => $prevWhTaxSlip,
                    'prev_gross_income' => round($prevGrossIncome, 2),
                    'prev_pph21_paid' => round($prevPph21Paid, 2),
                    'prev_retirement_contribution' => round($prevRetirementContribution, 2),
                    'tax_certificate' => $taxCertificate,
                ],
                'company' => [
                    'nama_instansi' => (string) ($settings?->nama_instansi ?? config('app.name')),
                    'npwp' => $withholderTin,
                    'nitku' => $withholderNitku,
                ],
                'period' => [
                    'year' => $year,
                    'start_month' => $taxPeriodStart,
                    'end_month' => $taxPeriodEnd,
                    'withholding_status' => $statusOfWithholding,
                    'number_of_months' => $numberOfMonths,
                    'withholding_date' => $withholdingDate,
                ],
                'components' => [
                    'salary' => round($baseSalaryTotal, 2),
                    'other_benefit' => round($otherBenefitTotal, 2),
                    'insurance_paid_by_employer' => round($insurancePaidByEmployer, 2),
                    'bonus_thr' => round($bonusThrTotal, 2),
                    'retirement_contribution' => round($retirementContributionTotal, 2),
                    'annual_gross_basis' => round($annualGrossBasis, 2),
                    'pph21_annual_tax' => round($annualTax, 2),
                    'pph21_ytd' => round($pph21Ytd, 2),
                    'negative_adjustment_total' => round($negativeAdjustmentTotal, 2),
                ],
                'validation_errors' => $validationErrors,
                'validation_warnings' => $validationWarnings,
            ],
        ];
    }

    private function roundCurrency(float $amount): int
    {
        return (int) round(max(0, $amount), 0);
    }

    private function normalizeCounterpartOpt(?string $value): string
    {
        return trim((string) $value) === 'Foreign'
            ? 'Foreign'
            : 'Resident';
    }

    private function normalizeTaxCertificate(?string $value): string
    {
        return trim((string) $value) === 'DTP'
            ? 'DTP'
            : 'N/A';
    }

    private function normalizeYesNoFlag(mixed $value): string
    {
        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $normalized ? 'Yes' : 'No';
    }

    private function nullableTrim(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized !== ''
            ? $normalized
            : null;
    }

    private function normalizeDigits(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value)) ?? '';
    }
}
