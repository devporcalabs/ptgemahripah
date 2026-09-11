@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Dashboard'])

@section('content')
    <div class="summary-banner">
        <i class="fas fa-house"></i>
        <span>Selamat datang, <strong>{{ auth()->user()->nama_lengkap }}</strong>. Pantau dan kelola kebutuhan perusahaan dalam satu tempat.</span>
    </div>

    <div class="stats-grid">
        @foreach ([
            ['label' => 'Total Karyawan', 'value' => $totalKaryawan, 'icon' => 'fa-users'],
            ['label' => 'Absen Hari Ini', 'value' => $absensiHariIni, 'icon' => 'fa-calendar-check'],
            ['label' => 'Hadir Bulan Ini', 'value' => $totalHadirBulanIni, 'icon' => 'fa-check-circle'],
            ['label' => 'Izin Pending', 'value' => $totalIzinPending, 'icon' => 'fa-file-alt'],
        ] as $item)
            <div class="stat-card">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">{{ $item['value'] }}</div>
                        <div class="stat-label">{{ $item['label'] }}</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas {{ $item['icon'] }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="content-grid-2">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-history" style="color:#065F46; margin-right:8px;"></i>Absensi Terbaru</h3>
            </div>
            <div class="table-responsive">
                <table class="table-bordered">
                    <thead>
                        <tr class="table-header">
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($absensiTerbaru as $absen)
                            <tr>
                                <td>{{ $absen->nik }}</td>
                                <td>{{ $absen->nama_lengkap }}</td>
                                <td>{{ \Carbon\Carbon::parse($absen->tanggal)->format('d/m/Y') }}</td>
                                <td>{{ $absen->jam_masuk ? \Carbon\Carbon::parse($absen->jam_masuk)->format('H:i') : '-' }}</td>
                                <td>
                                    <span class="badge {{ $absen->status === 'hadir' ? 'badge-success' : 'badge-warning' }}">
                                        {{ ucfirst($absen->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state">Belum ada data absensi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-envelope-open-text" style="color:#065F46; margin-right:8px;"></i>Izin Pending</h3>
            </div>
            <div class="table-responsive">
                <table class="table-bordered">
                    <thead>
                        <tr class="table-header">
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($izinTerbaru as $izin)
                            <tr>
                                <td>{{ $izin->nik }}</td>
                                <td>{{ $izin->nama_lengkap }}</td>
                                <td>{{ $izin->tanggal_izin ? \Carbon\Carbon::parse($izin->tanggal_izin)->format('d/m/Y') : '-' }}</td>
                                <td>{{ ucfirst($izin->jenis_izin ?? '-') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty-state">Tidak ada izin pending.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
