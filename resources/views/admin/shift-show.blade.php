@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Detail Shift'])

@section('styles')
    .shift-hero {
        display:flex;
        justify-content:space-between;
        gap:20px;
        flex-wrap:wrap;
        padding:20px 22px;
        border:1px solid #E2E8F0;
        border-radius:16px;
        background:#FFFFFF;
        margin-bottom:20px;
    }
    .shift-hero-title {
        display:flex;
        align-items:center;
        gap:12px;
        margin-bottom:8px;
    }
    .shift-hero-dot {
        width:18px;
        height:18px;
        border-radius:999px;
        flex:0 0 18px;
    }
    .shift-hero-name { font-size:24px; font-weight:800; color:#0F172A; }
    .shift-hero-meta { display:flex; flex-wrap:wrap; gap:10px; color:#64748B; font-size:13px; }
    .shift-badge {
        display:inline-flex;
        align-items:center;
        padding:6px 12px;
        border-radius:999px;
        font-size:12px;
        font-weight:700;
    }
    .shift-badge.active { background:#DCFCE7; color:#166534; }
    .shift-badge.inactive { background:#FEE2E2; color:#B91C1C; }
    .shift-stats {
        display:grid;
        grid-template-columns:repeat(3, 1fr);
        gap:16px;
        margin-bottom:20px;
    }
    .shift-stat-card {
        background:#FFFFFF;
        border:1px solid #E2E8F0;
        border-radius:14px;
        padding:16px 18px;
    }
    .shift-stat-label { font-size:12px; color:#64748B; margin-bottom:8px; }
    .shift-stat-value { font-size:22px; font-weight:800; color:#0F172A; }
    .shift-filter-card { margin-bottom:20px; }
    .shift-filter-row { display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end; }
    .shift-filter-group { display:flex; flex-direction:column; gap:5px; }
    .shift-filter-group label { font-size:12px; font-weight:600; color:#334155; }
    .shift-filter-input { min-width:220px; }
    .shift-grid { display:grid; gap:20px; }
    .table-note { font-size:12px; color:#64748B; }
    .table-responsive { overflow:auto; }
    .table-bordered { font-size:13px; }
    .table-bordered th, .table-bordered td { padding:10px 12px; }
    @media (max-width: 960px) {
        .shift-stats { grid-template-columns:repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .shift-filter-row { flex-direction:column; align-items:stretch; }
        .shift-filter-input { width:100%; min-width:0; }
        .shift-stats { grid-template-columns:1fr; }
    }
@endsection

@section('content')
    <div id="shiftDetailFragment">
        <div class="page-header">
            <div>
                <h3 class="page-subtitle">Detail Shift</h3>
                <p class="page-description">Ringkasan penggunaan shift pada karyawan dan absensi aktual per periode.</p>
            </div>
            <div class="button-group">
                <a href="{{ route('admin.jam-shift') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="shift-hero">
            <div>
                <div class="shift-hero-title">
                    <span class="shift-hero-dot" style="background:{{ $shift->kode_warna ?: '#065F46' }};"></span>
                    <div class="shift-hero-name">{{ $shift->nama_shift }}</div>
                </div>
                <div class="shift-hero-meta">
                    <span>{{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->jam_keluar)->format('H:i') }}</span>
                    <span>|</span>
                    <span>Toleransi {{ (int) ($shift->toleransi ?? 0) }} menit</span>
                    <span>|</span>
                    <span>Scan Awal {{ (int) ($shift->checkin_window_before ?? 30) }} menit</span>
                    <span>|</span>
                    <span>Periode {{ $period->translatedFormat('F Y') }}</span>
                </div>
            </div>
            <div>
                <span class="shift-badge {{ $shift->aktif ? 'active' : 'inactive' }}">
                    {{ $shift->aktif ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
        </div>

        <div class="shift-stats">
            <div class="shift-stat-card">
                <div class="shift-stat-label">Default di Karyawan</div>
                <div class="shift-stat-value">{{ $shiftStats['default_employee_count'] }}</div>
            </div>
            <div class="shift-stat-card">
                <div class="shift-stat-label">Karyawan Aktif</div>
                <div class="shift-stat-value">{{ $shiftStats['active_employee_count'] }}</div>
            </div>
            <div class="shift-stat-card">
                <div class="shift-stat-label">Absensi Bulan Ini</div>
                <div class="shift-stat-value">{{ $shiftStats['attendance_count'] }}</div>
            </div>
        </div>

        <div class="card shift-filter-card">
            <div class="card-header">
                <h3><i class="fas fa-filter" style="color:#065F46; margin-right:8px;"></i>Filter Detail Shift</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="shift-filter-row" data-ajax="true" data-auto-submit="true" data-refresh-target="#shiftDetailFragment">
                    <div class="shift-filter-group">
                        <label>Bulan</label>
                        <input type="month" name="bulan" class="shift-filter-input form-control" value="{{ $period->format('Y-m') }}">
                    </div>
                    <div class="shift-filter-group">
                        <label>Cari Karyawan</label>
                        <input type="search" name="q" class="shift-filter-input form-control" value="{{ $search }}" placeholder="Cari NIK, nama, jabatan, departemen...">
                    </div>
                    <div class="shift-filter-group">
                        <label>&nbsp;</label>
                        <a href="{{ route('admin.jam-shift.show', $shift) }}" class="btn-reset" data-ajax-link="true" data-refresh-target="#shiftDetailFragment">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="shift-grid">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users" style="color:#065F46; margin-right:8px;"></i>Karyawan dengan Shift Default</h3>
                    <span class="table-note">Menampilkan maksimal 20 karyawan.</span>
                </div>
                <div class="table-responsive">
                    <table class="table-bordered">
                        <thead>
                            <tr class="table-header">
                                <th width="50">No</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Departemen</th>
                                <th width="100">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($defaultEmployees as $index => $employee)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $employee->nik }}</td>
                                    <td><strong>{{ $employee->nama_lengkap }}</strong></td>
                                    <td>{{ $employee->jabatan ?: '-' }}</td>
                                    <td>{{ $employee->departemen ?: '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $employee->status === 'aktif' ? 'badge-success' : 'badge-danger' }}">
                                            {{ ucfirst($employee->status ?? 'nonaktif') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty-state">Belum ada karyawan yang memakai shift ini sebagai shift default.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-fingerprint" style="color:#065F46; margin-right:8px;"></i>Riwayat Absensi pada Shift Ini</h3>
                    <span class="table-note">Menampilkan maksimal 25 riwayat absensi dalam periode aktif.</span>
                </div>
                <div class="table-responsive">
                    <table class="table-bordered">
                        <thead>
                            <tr class="table-header">
                                <th width="50">No</th>
                                <th>Tanggal</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Masuk</th>
                                <th>Keluar</th>
                                <th>Status</th>
                                <th>Terlambat</th>
                                <th>Pulang Cepat</th>
                                <th>Sumber</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentAttendances as $index => $attendance)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $attendance->tanggal?->format('d/m/Y') }}</td>
                                    <td>{{ $attendance->karyawan?->nik ?: '-' }}</td>
                                    <td><strong>{{ $attendance->karyawan?->nama_lengkap ?: '-' }}</strong></td>
                                    <td class="text-center">{{ $attendance->jam_masuk ? \Carbon\Carbon::parse($attendance->jam_masuk)->format('H:i:s') : '-' }}</td>
                                    <td class="text-center">{{ $attendance->jam_keluar ? \Carbon\Carbon::parse($attendance->jam_keluar)->format('H:i:s') : '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $attendance->status === 'terlambat' ? 'badge-warning' : ($attendance->status === 'hadir' ? 'badge-success' : 'badge-info') }}">
                                            {{ ucfirst($attendance->status ?: '-') }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ format_duration_minutes_label($attendance->menit_terlambat ?? 0) }}</td>
                                    <td class="text-center">{{ format_duration_minutes_label($attendance->menit_pulang_cepat ?? 0) }}</td>
                                    <td class="text-center">{{ $attendance->schedule_source ?: 'legacy' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="empty-state">Belum ada riwayat absensi untuk shift ini pada periode aktif.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
