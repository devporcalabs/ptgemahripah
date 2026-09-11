@extends('layouts.panel', ['panel' => 'karyawan', 'pageTitle' => 'Absensi Saya'])

@section('styles')
    .portal-page { display: grid; gap: 18px; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
    .stat-card, .panel-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }
    .stat-card { padding: 18px; }
    .stat-label { font-size: 12px; color: #64748b; margin-bottom: 6px; }
    .stat-value { font-size: 24px; font-weight: 800; color: #0f172a; }
    .stat-note { margin-top: 8px; font-size: 12px; color: #475569; }
    .panel-head {
        padding: 16px 18px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    .panel-title { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
    .panel-subtitle { margin-top: 4px; font-size: 12px; color: #64748b; }
    .filter-form { display: flex; gap: 12px; align-items: end; flex-wrap: wrap; }
    .filter-group { min-width: 140px; }
    .filter-group label { display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:6px; }
    .panel-body { padding: 18px; }
    .badge-soft {
        display:inline-flex; align-items:center; justify-content:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700;
    }
    .badge-success { background:#dcfce7; color:#166534; }
    .badge-warning { background:#fef3c7; color:#92400e; }
    .badge-danger { background:#fee2e2; color:#b91c1c; }
    .badge-info { background:#dbeafe; color:#1d4ed8; }
    .muted-sm { font-size:12px; color:#64748b; }
    @media (max-width: 992px) { .stats-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 640px) { .stats-grid { grid-template-columns: 1fr; } }
@endsection

@section('content')
    <div class="portal-page">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Absensi</div>
                <div class="stat-value">{{ $stats['hadir'] }}</div>
                <div class="stat-note">{{ $period->translatedFormat('F Y') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Telat</div>
                <div class="stat-value">{{ format_duration_minutes_label($stats['terlambat']) }}</div>
                <div class="stat-note">Akumulasi bulan ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pulang Cepat</div>
                <div class="stat-value">{{ format_duration_minutes_label($stats['pulang_cepat']) }}</div>
                <div class="stat-note">Akumulasi bulan ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Lembur</div>
                <div class="stat-value">{{ format_duration_minutes_label($stats['lembur']) }}</div>
                <div class="stat-note">Akumulasi bulan ini</div>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-head">
                <div>
                    <h3 class="panel-title">Riwayat Absensi</h3>
                    <div class="panel-subtitle">{{ $employee->nama_lengkap }} · {{ $employee->nik }}</div>
                </div>
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>Bulan</label>
                        <input type="month" name="bulan" class="filter-input" value="{{ $period->format('Y-m') }}">
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" class="filter-input">
                            <option value="">Semua</option>
                            @foreach (['hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'izin' => 'Izin', 'cuti' => 'Cuti'] as $value => $label)
                                <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Tampil</label>
                        <select name="per_page" class="filter-input">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / halaman</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table-bordered">
                        <thead>
                            <tr class="table-header">
                                <th width="50">No</th>
                                <th width="140">Tanggal</th>
                                <th width="120">Status</th>
                                <th width="160">Shift</th>
                                <th width="120">Masuk</th>
                                <th width="120">Pulang</th>
                                <th width="140">Telat</th>
                                <th width="140">Pulang Cepat</th>
                                <th width="120">Lembur</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attendanceRows as $index => $attendance)
                                <tr>
                                    <td class="text-center">{{ ($attendanceRows->firstItem() ?? 1) + $index }}</td>
                                    <td>
                                        <strong>{{ optional($attendance->tanggal)->translatedFormat('d M Y') }}</strong>
                                        <div class="muted-sm">{{ $attendance->schedule_source_label }}</div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $badgeClass = match ((string) $attendance->status) {
                                                'terlambat' => 'badge-warning',
                                                'izin', 'cuti' => 'badge-info',
                                                'alpha' => 'badge-danger',
                                                default => 'badge-success',
                                            };
                                        @endphp
                                        <span class="badge-soft {{ $badgeClass }}">{{ ucfirst((string) $attendance->status) }}</span>
                                    </td>
                                    <td>{{ $attendance->shift?->nama_shift ?? '-' }}</td>
                                    <td>{{ $attendance->jam_masuk ?: '-' }}</td>
                                    <td>{{ $attendance->jam_keluar ?: '-' }}</td>
                                    <td>{{ $attendance->late_label }}</td>
                                    <td>{{ $attendance->early_leave_label }}</td>
                                    <td>{{ $attendance->overtime_label }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="empty-state">Belum ada data absensi pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $attendanceRows->links() }}
            </div>
        </div>
    </div>
@endsection
