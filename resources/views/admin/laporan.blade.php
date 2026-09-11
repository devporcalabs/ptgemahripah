@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Laporan'])

@section('styles')
    .stats-grid { grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
    .stat-card { padding: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .stat-card .stat-card-body { padding: 0; }
    .stat-value { font-size: 28px; font-weight: 700; margin-bottom: 4px; color: #1E293B; }
    .stat-card:nth-child(1) .stat-icon { background: #D1FAE5; color: #065F46; }
    .stat-card:nth-child(2) .stat-icon { background: #FEF3C7; color: #D97706; }
    .stat-card:nth-child(3) .stat-icon { background: #DBEAFE; color: #2563EB; }
    .stat-card:nth-child(4) .stat-icon { background: #FEE2E2; color: #DC2626; }
    .table-container { width: 100%; overflow-x: auto; }
    .table-scroll { min-width: max-content; }
    .laporan-table {
        border-collapse: collapse;
        font-size: 11px;
        background: white;
        width: 100%;
    }
    .laporan-table th, .laporan-table td {
        border: 1px solid #D1D5DB;
        padding: 8px 4px;
        vertical-align: middle;
    }
    .laporan-table th {
        background: #F8FAFC;
        color: #065F46;
        font-weight: 600;
        font-size: 11px;
    }
    .laporan-table tbody tr:hover { background: #F8FAFC; }
    .laporan-table tfoot { background: #E5E7EB; font-weight: 600; }
    .col-fixed { position: sticky; left: 0; background: white; z-index: 2; }
    .col-fixed.second { left: 40px; }
    .col-sum { position: sticky; right: 0; background: white; z-index: 2; }
    .laporan-table th.col-fixed, .laporan-table th.col-sum { background: #F8FAFC; z-index: 3; }
    .laporan-table tfoot .col-fixed, .laporan-table tfoot .col-sum { background: #E5E7EB; }
    .nama { font-weight: 600; color: #1E293B; }
    .nik { font-size: 9px; color: #64748B; }
    .bg-hadir { background: #D1FAE5; color: #065F46; font-weight: 600; }
    .bg-terlambat { background: #FEF3C7; color: #D97706; font-weight: 600; }
    .bg-sakit { background: #DBEAFE; color: #2563EB; font-weight: 600; }
    .bg-cuti { background: #E0E7FF; color: #4338CA; font-weight: 600; }
    .bg-izin { background: #F3E8FF; color: #9333EA; font-weight: 600; }
    .bg-alpha { background: #FEE2E2; color: #DC2626; font-weight: 600; }
    .bg-libur { background: #F1F5F9; color: #64748B; }
    .bg-future { background: #F3F4F6; color: #9CA3AF; font-weight: 400; }
    .future-date { background: #F3F4F6 !important; color: #9CA3AF !important; }
    .badge-h, .badge-t, .badge-s, .badge-c, .badge-i, .badge-a, .badge-l, .badge-f {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 10px;
    }
    .badge-h { background: #D1FAE5; color: #065F46; }
    .badge-t { background: #FEF3C7; color: #D97706; }
    .badge-s { background: #DBEAFE; color: #2563EB; }
    .badge-c { background: #E0E7FF; color: #4338CA; }
    .badge-i { background: #F3E8FF; color: #9333EA; }
    .badge-a { background: #FEE2E2; color: #DC2626; }
    .badge-l { background: #F1F5F9; color: #64748B; }
    .badge-f { background: #F3F4F6; color: #9CA3AF; }
    .page-header { margin-bottom: 20px; }
    .filter-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .filter-select {
        padding: 8px 12px;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        font-size: 13px;
        min-width: 180px;
        font-family: inherit;
    }
    .filter-select[type="month"] { min-width: 170px; }
    .filter-select[type="search"] { min-width: 240px; }
    .card-header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .legend {
        background: white;
        border-radius: 12px;
        padding: 12px 20px;
        margin-top: 20px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 20px;
        font-size: 11px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    @media (max-width: 900px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .filter-toolbar { align-items:stretch; }
        .filter-select { width: 100%; min-width: 0; }
    }
    @media (max-width: 640px) {
        .stats-grid { grid-template-columns: 1fr; }
    }
@endsection

@section('content')
    <div id="ajaxFilterFragment">
        <div class="stats-grid">
            @foreach ([
                ['label' => 'Hadir', 'value' => $statistics['hadir'], 'icon' => 'fa-check-circle'],
                ['label' => 'Terlambat', 'value' => $statistics['terlambat'], 'icon' => 'fa-clock'],
                ['label' => 'Izin/Sakit/Cuti', 'value' => $statistics['izin'], 'icon' => 'fa-file-alt'],
                ['label' => 'Alpha', 'value' => $statistics['alpha'], 'icon' => 'fa-times-circle'],
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

        <div class="page-header">
            <div>
                <h3 class="page-subtitle">Laporan Absensi</h3>
                <p class="page-description">Lihat rekap absensi bulanan seluruh karyawan.</p>
            </div>
            <form method="GET" class="filter-toolbar" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxFilterFragment">
                <input type="month" name="bulan" class="filter-select" value="{{ $period->format('Y-m') }}">
                <input type="search" name="q" class="filter-select" value="{{ $search ?? '' }}" placeholder="Cari NIK atau nama karyawan...">
                <select name="per_page" class="filter-select">
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected(($perPage ?? 10) === $size)>{{ $size }} / halaman</option>
                    @endforeach
                </select>
                <a href="{{ route('admin.laporan') }}" class="btn btn-secondary" data-ajax-link="true" data-refresh-target="#ajaxFilterFragment">
                    <i class="fas fa-rotate-left"></i> Reset
                </a>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-calendar-alt" style="color:#065F46; margin-right:8px;"></i>Rekap Absensi {{ $period->translatedFormat('F Y') }}</h3>
                <div class="card-header-actions">
                    <span class="total-data">Total: {{ $rows->total() }} karyawan</span>
                    <a href="{{ route('admin.laporan.export', ['bulan' => $period->format('Y-m'), 'q' => $search ?? '', 'type' => 'excel']) }}" class="btn-export excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="{{ route('admin.laporan.export', ['bulan' => $period->format('Y-m'), 'q' => $search ?? '', 'type' => 'pdf']) }}" class="btn-export pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div class="table-container" data-fragment-loading-scope>
                <div class="table-scroll">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th class="col-fixed" width="40">No</th>
                                <th class="col-fixed second" width="180">Nama / NIK</th>
                                @foreach ($tanggalList as $tanggal)
                                    <th class="text-center {{ $tanggal['date'] > now()->toDateString() ? 'future-date' : '' }}" width="75">{{ $tanggal['day'] }}</th>
                                @endforeach
                                <th class="col-sum" width="45">H</th>
                                <th class="col-sum" width="45">T</th>
                                <th class="col-sum" width="45">I</th>
                                <th class="col-sum" width="45">A</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr>
                                    <td class="text-center col-fixed">{{ ($rows->firstItem() ?? 1) + $index }}</td>
                                    <td class="col-fixed second" style="min-width:180px;">
                                        <div class="nama">{{ $row['nama'] }}</div>
                                        <div class="nik">{{ $row['nik'] }}</div>
                                    </td>
                                    @foreach ($row['data'] as $value)
                                        @php
                                            $cellClass = match (true) {
                                                $value === 'T' => 'bg-terlambat',
                                                $value === 'S' => 'bg-sakit',
                                                $value === 'C' => 'bg-cuti',
                                                $value === 'I' => 'bg-izin',
                                                $value === 'A' => 'bg-alpha',
                                                $value === 'L' => 'bg-libur',
                                                $value === '?' => 'bg-future',
                                                str_contains($value, '/') => 'bg-hadir',
                                                default => '',
                                            };
                                        @endphp
                                        <td class="text-center {{ $cellClass }}">{{ $value }}</td>
                                    @endforeach
                                    <td class="text-center col-sum">{{ $row['summary']['hadir'] }}</td>
                                    <td class="text-center col-sum">{{ $row['summary']['terlambat'] }}</td>
                                    <td class="text-center col-sum">{{ $row['summary']['izin'] }}</td>
                                    <td class="text-center col-sum">{{ $row['summary']['alpha'] }}</td>
                                </tr>
                            @empty
                                <tr class="text-center">
                                    <td colspan="{{ count($tanggalList) + 6 }}">Belum ada data karyawan</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            {{ $rows->links('partials.pagination-ajax', ['target' => '#ajaxFilterFragment']) }}
        </div>
    </div>

    <div class="legend">
        <span><span class="badge-h">HH:MM/-</span> Hadir (Jam Masuk/Pulang)</span>
        <span><span class="badge-t">T</span> Terlambat</span>
        <span><span class="badge-s">S</span> Sakit</span>
        <span><span class="badge-c">C</span> Cuti</span>
        <span><span class="badge-i">I</span> Izin Lainnya</span>
        <span><span class="badge-a">A</span> Alpha (sudah lewat & tidak absen)</span>
        <span><span class="badge-f">?</span> Belum tiba (tanggal belum lewat)</span>
        <span><span class="badge-l">L</span> Libur</span>
    </div>
@endsection
