<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pajak PPh21 - {{ $settings->nama_instansi ?? config('app.name') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1E293B; }
        h1, h2, p { margin: 0; }
        .header { text-align: center; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #CBD5E1; padding: 6px; vertical-align: top; }
        th { background: #E2E8F0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $settings->nama_instansi ?? config('app.name') }}</h2>
        <p>Laporan Pajak PPh21</p>
        <p>Periode {{ $period->translatedFormat('F Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>PTKP</th>
                <th>TER</th>
                <th>Metode</th>
                <th>Gross Basis</th>
                <th>Tarif TER</th>
                <th>PPh21 Bulan Ini</th>
                <th>PKP Tahunan</th>
                <th>PPh21 Tahunan</th>
                <th>Status Payroll</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($taxRows as $index => $row)
                @php
                    $employee = $row['karyawan'];
                    $pph21Method = match ($row['pph21_method'] ?? 'off') {
                        'ter' => 'TER Bulanan',
                        'annual_reconcile' => 'Final Tahunan',
                        'annualized' => 'Estimasi Tahunan',
                        default => 'Nonaktif',
                    };
                    $pph21Amount = (float) ($row['pph21_amount'] ?? 0);
                    $terRate = (float) ($row['pph21_ter_rate'] ?? 0);
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $employee->nik }}</td>
                    <td>{{ $employee->nama_lengkap }}</td>
                    <td>{{ $employee->jabatan ?: '-' }}</td>
                    <td>{{ $employee->status_ptkp ?? ($employee->status_nikah ?? 'TK/0') }}</td>
                    <td>{{ $row['pph21_ter_category'] ?? ($employee->ter_category ?? '-') }}</td>
                    <td>{{ $pph21Method }}</td>
                    <td class="text-right">Rp {{ number_format((float) ($row['pph21_gross_basis'] ?? 0), 0, ',', '.') }}</td>
                    <td class="text-center">{{ ($row['pph21_method'] ?? 'off') === 'ter' ? rtrim(rtrim(number_format($terRate, 2, '.', ''), '0'), '.').'%' : '-' }}</td>
                    <td class="text-right">{{ $pph21Amount >= 0 ? 'Rp '.number_format($pph21Amount, 0, ',', '.') : '- Rp '.number_format(abs($pph21Amount), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format((float) ($row['pph21_pkp'] ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format((float) ($row['pph21_annual_tax'] ?? 0), 0, ',', '.') }}</td>
                    <td>{{ ($row['is_finalized'] ?? false) ? 'Final' : 'Draft' }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="7" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($taxSummary['gross_total'], 0, ',', '.') }}</strong></td>
                <td></td>
                <td class="text-right"><strong>Rp {{ number_format($taxSummary['pph21_payable_total'] - $taxSummary['pph21_refund_total'], 0, ',', '.') }}</strong></td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
