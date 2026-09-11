<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi Bulanan - {{ $settings->nama_instansi ?? config('app.name') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1E293B; }
        h1, h2, p { margin: 0; }
        .header { text-align: center; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #CBD5E1; padding: 4px; vertical-align: top; }
        th { background: #E2E8F0; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $settings->nama_instansi ?? config('app.name') }}</h2>
        <p>Laporan Absensi Bulanan</p>
        <p>Periode {{ $period->translatedFormat('F Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Karyawan</th>
                @foreach ($tanggalList as $tanggal)
                    <th>{{ $tanggal['day'] }}</th>
                @endforeach
                <th>H</th>
                <th>T</th>
                <th>I</th>
                <th>A</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['nama'] }}</td>
                    @foreach ($row['data'] as $value)
                        <td class="text-center">{{ $value }}</td>
                    @endforeach
                    <td class="text-center">{{ $row['summary']['hadir'] }}</td>
                    <td class="text-center">{{ $row['summary']['terlambat'] }}</td>
                    <td class="text-center">{{ $row['summary']['izin'] }}</td>
                    <td class="text-center">{{ $row['summary']['alpha'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="{{ count($tanggalList) + 2 }}" class="text-center"><strong>GRAND TOTAL</strong></td>
                <td class="text-center">{{ $statistics['hadir'] }}</td>
                <td class="text-center">{{ $statistics['terlambat'] }}</td>
                <td class="text-center">{{ $statistics['izin'] }}</td>
                <td class="text-center">{{ $statistics['alpha'] }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
