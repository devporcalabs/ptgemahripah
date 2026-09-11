<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Gaji - {{ $settings->nama_instansi ?? config('app.name') }}</title>
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
        <p>Laporan Gaji Karyawan</p>
        <p>Periode {{ $period->translatedFormat('F Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Jadwal</th>
                <th>Hadir</th>
                <th>Izin</th>
                <th>Alpha</th>
                <th>Terlambat</th>
                <th>Pulang Cepat</th>
                <th>Gaji Dasar</th>
                <th>Tunj. Jabatan</th>
                <th>Makan & Transport</th>
                <th>BPJS Net</th>
                <th>Lembur</th>
                <th>Penyesuaian</th>
                <th>Bonus, Premi & THR</th>
                <th>Potongan</th>
                <th>Total Gaji</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($salaryRows as $index => $salary)
                @php
                    $employee = $salary['karyawan'];
                    $makanTransport = $salary['tunjangan_makan_total'] + $salary['tunjangan_transport_total'];
                    $bpjsNet = ($salary['bpjs_tunjangan_total'] ?? 0) - ($salary['bpjs_potongan_total'] ?? 0);
                    $bonusTotal = ($salary['total_bonus'] ?? 0) + ($salary['thr_amount'] ?? 0);
                    $totalPotongan = $salary['total_potongan'] ?? ($salary['potongan_terlambat'] ?? 0);
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $employee->nik }}</td>
                    <td>{{ $employee->nama_lengkap }}</td>
                    <td>{{ $employee->jabatan ?: '-' }}</td>
                    <td class="text-center">{{ $salary['scheduled_days'] }} hari</td>
                    <td class="text-center">{{ $salary['total_hadir'] }} hari</td>
                    <td class="text-center">{{ $salary['total_izin'] }} hari</td>
                    <td class="text-center">{{ $salary['total_alpha'] }} hari</td>
                    <td class="text-center">{{ format_duration_minutes_label($salary['total_menit_terlambat'] ?? 0) }}</td>
                    <td class="text-center">{{ format_duration_minutes_label($salary['total_menit_pulang_cepat'] ?? 0) }}</td>
                    <td class="text-right">Rp {{ number_format($salary['base_salary_total'] ?? $salary['gaji_kehadiran'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ $salary['tunjangan_jabatan_tampil'] > 0 ? 'Rp '.number_format($salary['tunjangan_jabatan_tampil'], 0, ',', '.') : '-' }}</td>
                    <td class="text-right">Rp {{ number_format($makanTransport, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $bpjsNet > 0 ? 'Rp '.number_format($bpjsNet, 0, ',', '.') : ($bpjsNet < 0 ? '- Rp '.number_format(abs($bpjsNet), 0, ',', '.') : '-') }}</td>
                    <td class="text-right">Rp {{ number_format($salary['total_lembur_tarif'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ $salary['total_penyesuaian'] > 0 ? 'Rp '.number_format($salary['total_penyesuaian'], 0, ',', '.') : ($salary['total_penyesuaian'] < 0 ? '- Rp '.number_format(abs($salary['total_penyesuaian']), 0, ',', '.') : '-') }}</td>
                    <td class="text-right">Rp {{ number_format($bonusTotal, 0, ',', '.') }}</td>
                    <td class="text-right">{{ $totalPotongan != 0.0 ? ($totalPotongan > 0 ? '- Rp ' : 'Rp ').number_format(abs($totalPotongan), 0, ',', '.') : '-' }}</td>
                    <td class="text-right"><strong>Rp {{ number_format($salary['total_gaji'], 0, ',', '.') }}</strong></td>
                </tr>
            @endforeach
            <tr>
                <td colspan="18" class="text-right"><strong>GRAND TOTAL</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($grandTotal, 0, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
