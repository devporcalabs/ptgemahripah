@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Log Scan Mesin'])

@section('styles')
    .device-summary {
        display:grid;
        grid-template-columns:1.4fr 1fr;
        gap:16px;
        margin-bottom:20px;
    }
    .device-summary-grid {
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:12px;
    }
    .device-info-item {
        display:grid;
        gap:4px;
        padding:12px 14px;
        border:1px solid #E2E8F0;
        border-radius:12px;
        background:#F8FAFC;
    }
    .device-info-item span {
        font-size:11px;
        color:#64748B;
    }
    .device-info-item strong {
        font-size:13px;
        color:#0F172A;
    }
    .stats-grid { margin-bottom:20px; }
    .filter-card { margin-bottom:20px; }
    .filter-card .card-body { padding:20px; }
    .filter-form { gap:16px; }
    .filter-group { display:flex; flex-direction:column; gap:5px; }
    .filter-input { min-width:160px; }
    .table-bordered { font-size:13px; }
    .table-bordered th, .table-bordered td { padding:10px 12px; }
    .badge { padding:4px 10px; font-size:11px; }
    .empty-state { text-align:center; color:#64748B; padding:20px; }
    .uid-code {
        display:inline-flex;
        align-items:center;
        padding:4px 8px;
        border-radius:8px;
        background:#F8FAFC;
        border:1px solid #E2E8F0;
        font-size:11px;
        color:#334155;
        font-family:monospace;
    }
    .name-stack {
        display:grid;
        gap:4px;
    }
    .name-stack small {
        font-size:11px;
        color:#64748B;
    }
    @media (max-width: 980px) {
        .device-summary { grid-template-columns:1fr; }
        .device-summary-grid { grid-template-columns:1fr; }
    }
    @media (max-width: 760px) {
        .filter-form { flex-direction:column; align-items:stretch; }
        .filter-input { width:100%; }
    }
@endsection

@section('content')
    <div id="ajaxLogFragment">
        <div class="page-header">
            <div>
                <h3 class="page-subtitle">Log Scan RFID</h3>
                <p class="page-description">Riwayat scan kartu yang masuk dari mesin <strong>{{ $device->name ?: $device->serial_number }}</strong>.</p>
            </div>
            <div class="button-group">
                <a href="{{ route('admin.mesin-absensi') }}" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="device-summary">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-microchip" style="color:#065F46; margin-right:8px;"></i>Informasi Mesin</h3>
                </div>
                <div class="card-body device-summary-grid">
                    <div class="device-info-item">
                        <span>Nama Mesin</span>
                        <strong>{{ $device->name ?: '-' }}</strong>
                    </div>
                    <div class="device-info-item">
                        <span>Serial Number</span>
                        <strong>{{ $device->serial_number }}</strong>
                    </div>
                    <div class="device-info-item">
                        <span>Status</span>
                        <strong>{{ ucfirst($device->status) }}</strong>
                    </div>
                    <div class="device-info-item">
                        <span>Firmware</span>
                        <strong>{{ $device->firmware_version ?: '-' }}</strong>
                    </div>
                    <div class="device-info-item">
                        <span>MAC Address</span>
                        <strong>{{ $device->mac_address ?: '-' }}</strong>
                    </div>
                    <div class="device-info-item">
                        <span>Last Seen</span>
                        <strong>{{ $device->last_seen?->format('d/m/Y H:i:s') ?? '-' }}</strong>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $logStats['total'] }}</div><div class="stat-label">Total Scan</div></div><div class="stat-icon"><i class="fas fa-list"></i></div></div></div>
                <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $logStats['today'] }}</div><div class="stat-label">Scan Hari Ini</div></div><div class="stat-icon"><i class="fas fa-calendar-day"></i></div></div></div>
                <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $logStats['unique_cards'] }}</div><div class="stat-label">UID Unik</div></div><div class="stat-icon"><i class="fas fa-id-card"></i></div></div></div>
                <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $logStats['unlinked'] }}</div><div class="stat-label">Belum Tertaut</div></div><div class="stat-icon"><i class="fas fa-link-slash"></i></div></div></div>
            </div>
        </div>

        <div class="card filter-card">
            <div class="card-header">
                <h3><i class="fas fa-filter" style="color:#065F46; margin-right:8px;"></i>Filter Log Scan</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="filter-form" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxLogFragment">
                    <div class="filter-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="filter-input" value="{{ $tanggalFilter }}">
                    </div>
                    <div class="filter-group">
                        <label>Cari Data</label>
                        <input type="search" name="q" class="filter-input" value="{{ $search ?? '' }}" placeholder="Cari UID, NIK, nama, jabatan...">
                    </div>
                    <div class="filter-group">
                        <label>Tampil</label>
                        <select name="per_page" class="filter-input">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected(($perPage ?? 25) === $size)>{{ $size }} / halaman</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="button-group">
                        <a href="{{ route('admin.mesin-absensi.logs', $device) }}" class="btn-reset" data-ajax-link="true" data-refresh-target="#ajaxLogFragment">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-clock-rotate-left" style="color:#065F46; margin-right:8px;"></i>Riwayat Scan</h3>
                <span class="total-data">Total: {{ $logs->total() }} data</span>
            </div>
            <div class="table-responsive" data-fragment-loading-scope>
                <table class="table-bordered">
                    <thead>
                        <tr class="table-header">
                            <th width="50">No</th>
                            <th>Waktu Scan</th>
                            <th>UID RFID</th>
                            <th>NIK</th>
                            <th>Nama Karyawan</th>
                            <th>Jabatan / Departemen</th>
                            <th>Status Tautan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $index => $log)
                            @php
                                $linked = filled($log->karyawan_id);
                            @endphp
                            <tr>
                                <td class="text-center">{{ ($logs->firstItem() ?? 1) + $index }}</td>
                                <td>
                                    <div class="name-stack">
                                        <strong>{{ $log->scanned_at?->format('d/m/Y H:i:s') ?? '-' }}</strong>
                                        <small>{{ $log->scanned_at?->diffForHumans() ?? '-' }}</small>
                                    </div>
                                </td>
                                <td><span class="uid-code">{{ $log->uid }}</span></td>
                                <td>{{ $log->nik ?: '-' }}</td>
                                <td>
                                    <div class="name-stack">
                                        <strong>{{ $log->nama_lengkap ?: 'Belum tertaut' }}</strong>
                                        @if (! $linked)
                                            <small>UID ini belum dipasang di data karyawan.</small>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $log->jabatan ?: '-' }}{{ $log->departemen ? ' / '.$log->departemen : '' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $linked ? 'badge-success' : 'badge-warning' }}">
                                        {{ $linked ? 'Tertaut' : 'Belum tertaut' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-state">Belum ada log scan untuk mesin ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $logs->links('partials.pagination-ajax', ['target' => '#ajaxLogFragment']) }}
        </div>
    </div>
@endsection
