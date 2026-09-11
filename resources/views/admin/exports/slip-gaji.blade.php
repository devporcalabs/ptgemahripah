<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $snapshot['karyawan']['nama_lengkap'] ?? '-' }}</title>
    <style>
        @page { margin: 26px 30px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 10.5px; line-height: 1.28; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .company-name { font-size: 16px; font-weight: 700; text-transform: uppercase; margin: 0 0 12px; }
        .company-line { font-size: 10px; color: #6B7280; margin: 0 0 5px; }
        .logo-wrap { text-align: right; }
        .logo { max-width: 78px; max-height: 58px; }
        .logo-fallback { font-size: 28px; font-weight: 700; letter-spacing: 1px; color: #111827; }
        .divider { border-top: 2px solid #4B5563; margin: 12px 0 8px; }
        .title { text-align: center; font-size: 17px; font-weight: 700; margin: 0 0 12px; }
        .meta { margin-bottom: 14px; }
        .meta td { width: 50%; vertical-align: top; }
        .meta-table td { padding: 1px 0; vertical-align: top; }
        .meta-label { width: 72px; font-weight: 700; }
        .meta-sep { width: 10px; text-align: center; }
        .section-title { font-style: italic; text-decoration: underline; font-size: 11px; font-weight: 700; margin: 12px 0 8px; }
        .detail-table td { padding: 1px 0; vertical-align: top; }
        .item-label { width: 180px; }
        .item-sep { width: 10px; text-align: center; }
        .item-qty { width: 44px; text-align: right; padding-right: 6px; }
        .item-unit { width: 42px; }
        .item-amount { width: 128px; text-align: right; white-space: nowrap; }
        .subtotal-row td { padding-top: 9px; font-weight: 700; }
        .subtotal-amount { border-top: 1px solid #6B7280; }
        .take-home { margin-top: 10px; }
        .take-home td { padding: 2px 0; vertical-align: middle; }
        .take-home-label { font-weight: 700; }
        .take-home-box { width: 148px; border: 1px solid #4B5563; padding: 2px 9px; font-weight: 700; white-space: nowrap; }
        .footer-note { margin-top: 12px; }
        .footer-note td { padding: 1px 0; }
    </style>
</head>
<body>
    @php
        $employee = $snapshot['karyawan'] ?? [];
        $employeeModel = $history->karyawan;
        $periodDate = \Carbon\Carbon::parse($snapshot['period'] ?? $history->bulan)->startOfMonth();
        $payrollPeriod = $history->payrollPeriod;
        $periodStart = $payrollPeriod?->tanggal_mulai?->copy() ?? $periodDate->copy()->startOfMonth();
        $periodEnd = $payrollPeriod?->tanggal_selesai?->copy() ?? $periodDate->copy()->endOfMonth();
        $joinDate = $employeeModel?->tgl_join;

        $baseSalary = (float) ($snapshot['base_salary_total'] ?? $snapshot['gaji_kehadiran'] ?? 0);
        $tunjanganMakan = (float) ($snapshot['tunjangan_makan_total'] ?? 0);
        $tunjanganTransport = (float) ($snapshot['tunjangan_transport_total'] ?? 0);
        $tunjanganBpjsKesehatan = (float) ($snapshot['tunjangan_bpjs_kesehatan_total'] ?? 0);
        $tunjanganBpjsKetenagakerjaan = (float) ($snapshot['tunjangan_bpjs_ketenagakerjaan_total'] ?? 0);
        $lembur = (float) ($snapshot['total_lembur_tarif'] ?? 0);
        $bonusKehadiran = (float) ($snapshot['premi_kehadiran_total'] ?? 0);
        $bonusPribadi = (float) ($snapshot['bonus_pribadi_total'] ?? 0);
        $bonusTeam = (float) ($snapshot['bonus_team_total'] ?? 0);
        $bonusManual = (float) ($snapshot['bonus_manual_total'] ?? 0);
        $bonusBreakdownVisible = abs($bonusPribadi) > 0 || abs($bonusTeam) > 0;
        $bonusPendapatan = $bonusBreakdownVisible ? ($bonusPribadi + $bonusTeam) : $bonusManual;
        $thrAmount = (float) ($snapshot['thr_amount'] ?? 0);
        $penyesuaian = (float) ($snapshot['total_penyesuaian'] ?? 0);
        $penyesuaianPlus = $penyesuaian > 0 ? $penyesuaian : 0;
        $penyesuaianMinus = $penyesuaian < 0 ? abs($penyesuaian) : 0;
        $pph21 = (float) ($snapshot['pph21_amount'] ?? 0);
        $pph21Credit = $pph21 < 0 ? abs($pph21) : 0;
        $pph21Deduction = $pph21 > 0 ? $pph21 : 0;

        $potonganBpjsKesehatan = (float) ($snapshot['potongan_bpjs_kesehatan_total'] ?? 0);
        $potonganBpjsKetenagakerjaan = (float) ($snapshot['potongan_bpjs_ketenagakerjaan_total'] ?? 0);
        $potonganTerlambat = (float) ($snapshot['potongan_terlambat'] ?? 0);
        $potonganMangkir = (float) ($snapshot['potongan_mangkir'] ?? 0);
        $potonganIzin = (float) ($snapshot['potongan_izin'] ?? 0);
        $potonganKasbon = (float) ($snapshot['potongan_kasbon'] ?? 0);

        $totalPendapatan = $baseSalary
            + $tunjanganMakan
            + $tunjanganTransport
            + $tunjanganBpjsKesehatan
            + $tunjanganBpjsKetenagakerjaan
            + $lembur
            + $bonusKehadiran
            + $bonusPendapatan
            + $thrAmount
            + $penyesuaianPlus
            + $pph21Credit;

        $totalPotongan = $potonganBpjsKesehatan
            + $potonganBpjsKetenagakerjaan
            + $potonganTerlambat
            + $potonganMangkir
            + $potonganIzin
            + $potonganKasbon
            + $penyesuaianMinus
            + $pph21Deduction;

        $totalMenitLembur = (int) ($snapshot['total_menit_lembur'] ?? 0);
        $lemburJam = $totalMenitLembur > 0 ? $totalMenitLembur / 60 : 0;

        $leaveQuota = (int) ($employeeModel?->izin_cuti ?? $employee['izin_cuti'] ?? 0);
        $usedLeaveThisYear = $employeeModel
            ? $employeeModel->izin()
                ->where('status', 'disetujui')
                ->where(function ($query): void {
                    $query->where('jenis_izin', 'cuti')
                        ->orWhereHas('jenisIzin', function ($typeQuery): void {
                            $typeQuery
                                ->where('legacy_code', 'cuti')
                                ->where('deduct_quota', true);
                        });
                })
                ->whereRaw('COALESCE(tanggal_mulai, tanggal_izin, tanggal) <= ?', [$periodEnd->copy()->endOfYear()->toDateString()])
                ->whereRaw('COALESCE(tanggal_selesai, tanggal_mulai, tanggal_izin, tanggal) >= ?', [$periodEnd->copy()->startOfYear()->toDateString()])
                ->sum('jumlah_hari')
            : 0;
        $sisaCuti = max(0, $leaveQuota - $usedLeaveThisYear);

        $companyName = trim((string) ($settings->nama_instansi ?? config('app.name', 'Absensi')));
        $companyAddress = trim((string) ($settings->alamat ?? ''));
        $companyContacts = array_values(array_filter([
            trim((string) ($settings->email ?? '')),
            trim((string) ($settings->telepon ?? '')),
        ]));
        $companyContactLine = implode(' - ', $companyContacts);

        $logoPath = ! empty($settings?->logo) ? public_path('assets/logo/'.$settings->logo) : null;
        $logoExists = $logoPath && file_exists($logoPath);
        $logoFallback = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $companyName), 0, 2) ?: 'HR');

        $formatCurrency = static fn (float $amount): string => 'Rp '.number_format($amount, 0, ',', '.');
        $formatNumber = static function ($number): string {
            $formatted = number_format((float) $number, 2, ',', '.');
            $formatted = rtrim(rtrim($formatted, '0'), ',');

            return $formatted === '' ? '0' : $formatted;
        };
        $formatOvertimeDuration = static function (int $minutes): array {
            if ($minutes <= 0) {
                return ['qty' => '0', 'unit' => 'Menit', 'label' => '0 menit'];
            }

            if ($minutes < 60) {
                return [
                    'qty' => number_format($minutes, 0, ',', '.'),
                    'unit' => 'Menit',
                    'label' => number_format($minutes, 0, ',', '.').' menit',
                ];
            }

            $hours = $minutes / 60;
            $formattedHours = rtrim(rtrim(number_format($hours, 2, ',', '.'), '0'), ',');

            return [
                'qty' => $formattedHours,
                'unit' => 'Jam',
                'label' => $formattedHours.' jam',
            ];
        };
        $lemburDisplay = $formatOvertimeDuration($totalMenitLembur);

        $earningsRows = [
            ['label' => 'Gaji Pokok', 'qty' => null, 'unit' => null, 'amount' => $baseSalary],
            ['label' => 'Tunjangan Makan', 'qty' => (int) ($snapshot['total_hadir'] ?? 0), 'unit' => 'Hari', 'amount' => $tunjanganMakan],
            ['label' => 'Tunjangan Transport', 'qty' => (int) ($snapshot['total_hadir'] ?? 0), 'unit' => 'Hari', 'amount' => $tunjanganTransport],
            ['label' => 'Tunjangan BPJS Kesehatan', 'qty' => null, 'unit' => null, 'amount' => $tunjanganBpjsKesehatan],
            ['label' => 'Tunjangan BPJS Ketenagakerjaan', 'qty' => null, 'unit' => null, 'amount' => $tunjanganBpjsKetenagakerjaan],
            ['label' => 'Lembur', 'qty' => $lemburDisplay['qty'], 'unit' => $lemburDisplay['unit'], 'amount' => $lembur],
            ['label' => 'Bonus 100% Kehadiran', 'qty' => null, 'unit' => null, 'amount' => $bonusKehadiran],
            ['label' => 'Tunjangan Hari Raya', 'qty' => null, 'unit' => null, 'amount' => $thrAmount],
            ['label' => 'Reimbursement', 'qty' => null, 'unit' => null, 'amount' => $penyesuaianPlus],
        ];

        if ($bonusBreakdownVisible) {
            $earningsRows[] = ['label' => 'Bonus Pribadi', 'qty' => null, 'unit' => null, 'amount' => $bonusPribadi];
            $earningsRows[] = ['label' => 'Bonus Team', 'qty' => null, 'unit' => null, 'amount' => $bonusTeam];
        } else {
            $earningsRows[] = ['label' => 'Bonus', 'qty' => null, 'unit' => null, 'amount' => $bonusManual];
        }

        if ($pph21Credit > 0) {
            $earningsRows[] = ['label' => 'Koreksi PPh 21', 'qty' => null, 'unit' => null, 'amount' => $pph21Credit];
        }

        $deductionRows = [
            ['label' => 'Potongan BPJS Kesehatan', 'qty' => null, 'unit' => null, 'amount' => $potonganBpjsKesehatan],
            ['label' => 'Potongan BPJS Ketenagakerjaan', 'qty' => null, 'unit' => null, 'amount' => $potonganBpjsKetenagakerjaan],
            ['label' => 'Keterlambatan', 'qty' => (int) ($snapshot['total_terlambat'] ?? 0), 'unit' => 'Kali', 'amount' => $potonganTerlambat],
            ['label' => 'Mangkir', 'qty' => (int) ($snapshot['total_alpha'] ?? 0), 'unit' => 'Hari', 'amount' => $potonganMangkir],
            ['label' => 'Izin', 'qty' => (int) ($snapshot['total_izin_tidak_dibayar'] ?? 0), 'unit' => 'Hari', 'amount' => $potonganIzin],
            ['label' => 'Kasbon', 'qty' => null, 'unit' => null, 'amount' => $potonganKasbon],
            ['label' => 'Loss', 'qty' => null, 'unit' => null, 'amount' => $penyesuaianMinus],
        ];

        if ($pph21Deduction > 0) {
            $deductionRows[] = ['label' => 'PPh 21', 'qty' => null, 'unit' => null, 'amount' => $pph21Deduction];
        }
    @endphp

    <table class="header">
        <tr>
            <td width="72%">
                <div class="company-name">{{ $companyName }}</div>
                @if ($companyAddress !== '')
                    <div class="company-line">{{ $companyAddress }}</div>
                @endif
                @if ($companyContactLine !== '')
                    <div class="company-line">{{ $companyContactLine }}</div>
                @endif
            </td>
            <td width="28%" class="logo-wrap">
                @if ($logoExists)
                    <img src="{{ $logoPath }}" alt="Logo" class="logo">
                @else
                    <div class="logo-fallback">{{ $logoFallback }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="divider"></div>
    <div class="title">Slip Gaji</div>

    <table class="meta">
        <tr>
            <td>
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Nama</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $employee['nama_lengkap'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Jabatan</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $employee['jabatan'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Rekening</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $employee['rekening'] ?? '-' }}</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Tgl Gabung</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $joinDate ? $joinDate->format('Y-m-d') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Bulan</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $periodDate->translatedFormat('F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Periode</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $periodStart->format('Y-m-d') }} s/d {{ $periodEnd->format('Y-m-d') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">RINCIAN GAJI BULAN INI</div>
    <table class="detail-table">
        @foreach ($earningsRows as $row)
            <tr>
                <td class="item-label">{{ $row['label'] }}</td>
                <td class="item-sep">:</td>
                <td class="item-qty">{{ $row['qty'] !== null && $row['qty'] !== '' ? $row['qty'] : '' }}</td>
                <td class="item-unit">{{ $row['unit'] ?? '' }}</td>
                <td class="item-amount">{{ $formatCurrency((float) $row['amount']) }}</td>
            </tr>
        @endforeach
        <tr class="subtotal-row">
            <td class="item-label">Subtotal</td>
            <td class="item-sep">:</td>
            <td class="item-qty"></td>
            <td class="item-unit"></td>
            <td class="item-amount subtotal-amount">{{ $formatCurrency($totalPendapatan) }}</td>
        </tr>
    </table>

    <div class="section-title">DIKURANGI</div>
    <table class="detail-table">
        @foreach ($deductionRows as $row)
            <tr>
                <td class="item-label">{{ $row['label'] }}</td>
                <td class="item-sep">:</td>
                <td class="item-qty">{{ $row['qty'] !== null && $row['qty'] !== '' ? $row['qty'] : '' }}</td>
                <td class="item-unit">{{ $row['unit'] ?? '' }}</td>
                <td class="item-amount">{{ $formatCurrency((float) $row['amount']) }}</td>
            </tr>
        @endforeach
        <tr class="subtotal-row">
            <td class="item-label">Subtotal</td>
            <td class="item-sep">:</td>
            <td class="item-qty"></td>
            <td class="item-unit"></td>
            <td class="item-amount subtotal-amount">{{ $formatCurrency($totalPotongan) }}</td>
        </tr>
    </table>

    <table class="take-home">
        <tr>
            <td class="take-home-label" width="180">GAJI YANG DITERIMA</td>
            <td class="item-sep" width="10">:</td>
            <td>
                <div class="take-home-box">{{ $formatCurrency((float) ($snapshot['total_gaji'] ?? 0)) }}</div>
            </td>
        </tr>
    </table>

    <table class="footer-note">
        <tr>
            <td width="72">Sisa Cuti</td>
            <td width="10" style="text-align:center;">:</td>
            <td>{{ $sisaCuti }} Kali / Tahun</td>
        </tr>
    </table>
</body>
</html>
