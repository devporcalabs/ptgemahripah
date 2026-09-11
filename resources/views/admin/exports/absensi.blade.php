<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi - {{ $settings->nama_instansi ?? config('app.name') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1E293B; }
        h1, h2, p { margin: 0; }
        .header { text-align: center; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #CBD5E1; padding: 6px; vertical-align: top; }
        th { background: #E2E8F0; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $settings->nama_instansi ?? config('app.name') }}</h2>
        <p>Laporan Absensi Karyawan</p>
        <p>Tanggal: {{ \Carbon\Carbon::parse($tanggalFilter)->format('d/m/Y') }} | Status: {{ $statusFilter ?: 'Semua' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>NIK</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Jam Masuk</th>
                <th>Jam Keluar</th>
                <th>Shift</th>
                <th>Sumber</th>
                <th>Mesin / Lokasi</th>
                <th>Status</th>
                <th>Terlambat</th>
                <th>Pulang Cepat</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($absensiList as $index => $absen)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($absen->tanggal)->format('d/m/Y') }}</td>
                    <td>{{ $absen->nik }}</td>
                    <td>{{ $absen->nama_lengkap }}</td>
                    <td>{{ $absen->jabatan ?: '-' }}</td>
                    <td class="text-center">{{ $absen->jam_masuk ?: '-' }}</td>
                    <td class="text-center">{{ $absen->jam_keluar ?: '-' }}</td>
                    <td class="text-center">{{ $absen->shift_nama ?: '-' }}</td>
                    <td class="text-center">{{ $absen->schedule_source_label ?? '-' }}</td>
                    <td>{{ ($absen->device_name ?: '-') . ' / ' . ($absen->lokasi_masuk ?: '-') }}</td>
                    <td class="text-center">{{ ucfirst($absen->status) }}</td>
                    <td class="text-center">{{ format_duration_minutes_label($absen->menit_terlambat_display ?? 0) }}</td>
                    <td class="text-center">{{ format_duration_minutes_label($absen->menit_pulang_cepat_display ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
