@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Payroll'])

@section('styles')
    .filter-card { margin-bottom:20px; }
    .filter-card .card-body { padding:20px; }
    .period-card { margin-bottom:20px; border:1px solid #DBEAFE; }
    .period-card .card-body {
        padding:20px;
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        flex-wrap:wrap;
    }
    .period-info { flex:1 1 520px; display:flex; flex-direction:column; gap:14px; }
    .period-title-row { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .period-title-row h4 { margin:0; font-size:16px; font-weight:800; color:#0F172A; }
    .status-pill {
        display:inline-flex;
        align-items:center;
        padding:6px 10px;
        border-radius:999px;
        font-size:11px;
        font-weight:700;
        letter-spacing:0.02em;
    }
    .status-pill.status-draft { background:#FEF3C7; color:#92400E; }
    .status-pill.status-processing { background:#DBEAFE; color:#1D4ED8; }
    .status-pill.status-finalized { background:#DCFCE7; color:#166534; }
    .period-note { font-size:12px; color:#475569; line-height:1.6; }
    .period-meta-grid {
        display:grid;
        grid-template-columns:repeat(4, minmax(120px, 1fr));
        gap:12px;
    }
    .period-meta-item {
        border:1px solid #E2E8F0;
        background:#F8FAFC;
        border-radius:12px;
        padding:12px 14px;
    }
    .period-meta-label { font-size:11px; color:#64748B; margin-bottom:6px; }
    .period-meta-value { font-size:18px; font-weight:800; color:#0F172A; }
    .period-subtext { font-size:11px; color:#64748B; }
    .period-audit-list {
        display:flex;
        flex-direction:column;
        gap:6px;
        font-size:11px;
        color:#64748B;
    }
    .period-actions {
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        align-items:flex-start;
        justify-content:flex-end;
        min-width:220px;
    }
    .period-actions form { margin:0; }
    .page-header { margin-bottom:16px; }
    .page-header-actions {
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
        margin-left:auto;
        justify-content:flex-end;
    }
    .filter-toolbar {
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
        margin:0;
        justify-content:flex-end;
    }
    .filter-select {
        padding:8px 12px;
        border:1px solid #E2E8F0;
        border-radius:8px;
        font-size:13px;
        min-width:180px;
        font-family:inherit;
    }
    .filter-select[type="month"] { min-width:160px; }
    .filter-select[type="search"] { min-width:240px; }
    .stats-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:20px; }
    .stat-card {
        background:#FFFFFF;
        border:1px solid #E2E8F0;
        border-radius:14px;
        padding:16px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        transition:0.2s;
        box-shadow:0 1px 2px rgba(0,0,0,0.05);
    }
    .stat-card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.1); }
    .stat-card .stat-card-body { padding:0; width:100%; }
    .stat-card.total { border-left:4px solid #3B82F6; }
    .stat-card.finalized { border-left:4px solid #10B981; }
    .stat-card.draft { border-left:4px solid #F59E0B; }
    .stat-card.amount { border-left:4px solid #8B5CF6; }
    .stat-card.total .stat-icon { background:#DBEAFE; color:#2563EB; }
    .stat-card.finalized .stat-icon { background:#D1FAE5; color:#065F46; }
    .stat-card.draft .stat-icon { background:#FEF3C7; color:#D97706; }
    .stat-card.amount .stat-icon { background:#EDE9FE; color:#7C3AED; }
    .stat-label { font-size:12px; color:#64748B; margin-bottom:8px; }
    .stat-value { font-size:22px; font-weight:800; color:#0F172A; }
    .card-header-actions {
        display:flex;
        align-items:center;
        gap:8px;
        flex-wrap:wrap;
    }
    .filter-row { display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end; }
    .filter-group { display:flex; flex-direction:column; gap:5px; }
    .filter-group label { font-size:12px; font-weight:500; color:#374151; }
    .filter-input { width:180px; }
    .btn-filter {
        padding:8px 20px;
        background:#065F46;
        color:white;
        border:none;
        border-radius:8px;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        gap:6px;
        text-decoration:none;
        font-size:12px;
        font-weight:500;
    }
    .btn-filter:hover { background:#0D7C5A; color:white; }
    .export-buttons { display:flex; gap:8px; }
    .btn-excel, .btn-pdf {
        padding:8px 16px;
        border-radius:8px;
        font-size:12px;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        gap:6px;
        color:white;
    }
    .btn-excel { background:#10B981; }
    .btn-excel:hover { background:#059669; color:white; }
    .btn-pdf { background:#EF4444; }
    .btn-pdf:hover { background:#DC2626; color:white; }
    .info-card {
        background:#EFF6FF;
        border-left:4px solid #3B82F6;
        border-radius:10px;
        padding:12px 16px;
        margin-bottom:20px;
        display:flex;
        align-items:center;
        gap:12px;
        font-size:12px;
        color:#1E40AF;
    }
    .payroll-table-wrap {
        width:100%;
        overflow-x:auto;
        overflow-y:hidden;
        -webkit-overflow-scrolling:touch;
    }
    .gaji-table {
        width:100%;
        min-width:1900px;
        border-collapse:separate;
        border-spacing:0;
        font-size:12px;
    }
    .gaji-table th, .gaji-table td {
        border:1px solid #D1D5DB;
        padding:10px 8px;
        vertical-align:top;
        white-space:nowrap;
    }
    .gaji-table th { background:#F8FAFC; color:#065F46; font-weight:600; text-align:left; }
    .gaji-table td { color:#334155; }
    .gaji-table .payroll-subtext {
        white-space:normal;
        min-width:180px;
    }
    .gaji-table .sticky-col-left,
    .gaji-table .sticky-col-right {
        position:sticky;
        background:#FFFFFF;
        z-index:2;
    }
    .gaji-table thead .sticky-col-left,
    .gaji-table thead .sticky-col-right {
        background:#F8FAFC;
        z-index:4;
    }
    .gaji-table .sticky-no {
        left:0;
        min-width:56px;
        max-width:56px;
    }
    .gaji-table .sticky-nik {
        left:56px;
        min-width:116px;
        max-width:116px;
    }
    .gaji-table .sticky-nama {
        left:172px;
        min-width:220px;
    }
    .gaji-table .sticky-aksi {
        right:0;
        min-width:180px;
    }
    .payroll-adjustment-modal-dialog {
        width:min(960px, calc(100vw - 32px));
        max-width:960px;
    }
    .payroll-adjustment-layout {
        display:grid;
        grid-template-columns:minmax(0, 1.3fr) minmax(320px, 0.9fr);
        gap:16px;
        align-items:start;
    }
    .payroll-adjustment-card {
        border:1px solid #E2E8F0;
        border-radius:14px;
        background:#FFFFFF;
        overflow:hidden;
    }
    .payroll-adjustment-card-head {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:14px 16px;
        border-bottom:1px solid #E2E8F0;
        background:#F8FAFC;
    }
    .payroll-adjustment-card-head h4 {
        margin:0;
        font-size:14px;
        font-weight:700;
        color:#0F172A;
    }
    .payroll-adjustment-card-body { padding:16px; }
    .payroll-adjustment-meta {
        display:grid;
        grid-template-columns:repeat(3, minmax(0, 1fr));
        gap:12px;
        margin-bottom:16px;
    }
    .payroll-adjustment-meta-item {
        border:1px solid #E2E8F0;
        border-radius:12px;
        padding:12px 14px;
        background:#F8FAFC;
    }
    .payroll-adjustment-meta-label {
        font-size:11px;
        color:#64748B;
        margin-bottom:6px;
    }
    .payroll-adjustment-meta-value {
        font-size:13px;
        font-weight:700;
        color:#0F172A;
    }
    .payroll-adjustment-list {
        min-height:360px;
        max-height:360px;
        overflow:auto;
        display:flex;
        flex-direction:column;
        gap:12px;
    }
    .payroll-adjustment-item {
        border:1px solid #E2E8F0;
        border-radius:12px;
        padding:14px;
        display:flex;
        flex-direction:column;
        gap:10px;
        background:#FFFFFF;
    }
    .payroll-adjustment-item-head,
    .payroll-adjustment-item-foot {
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:10px;
    }
    .payroll-adjustment-item-title {
        font-size:13px;
        font-weight:700;
        color:#0F172A;
    }
    .payroll-adjustment-item-note {
        font-size:12px;
        color:#475569;
        white-space:normal;
        line-height:1.5;
    }
    .payroll-adjustment-item-time {
        font-size:11px;
        color:#94A3B8;
    }
    .payroll-adjustment-item-amount {
        font-size:14px;
        font-weight:800;
        white-space:nowrap;
    }
    .payroll-adjustment-item-amount.is-plus { color:#065F46; }
    .payroll-adjustment-item-amount.is-minus { color:#B91C1C; }
    .payroll-adjustment-actions {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
    }
    .payroll-adjustment-empty {
        min-height:360px;
        display:flex;
        align-items:center;
        justify-content:center;
        text-align:center;
        border:1px dashed #CBD5E1;
        border-radius:12px;
        color:#64748B;
        font-size:12px;
        background:#F8FAFC;
        padding:20px;
    }
    .payroll-adjustment-lock-note {
        margin-top:12px;
        padding:10px 12px;
        border-radius:10px;
        background:#FEF3C7;
        color:#92400E;
        font-size:12px;
        line-height:1.5;
    }
    .payroll-adjustment-form-actions {
        display:flex;
        justify-content:flex-end;
        gap:8px;
        flex-wrap:wrap;
        margin-top:16px;
    }
    @media (max-width: 992px) {
        .payroll-adjustment-layout,
        .payroll-adjustment-meta {
            grid-template-columns:1fr;
        }
    }
    .gaji-table .sticky-col-left::after {
        content:"";
        position:absolute;
        top:-1px;
        right:-1px;
        width:10px;
        height:calc(100% + 2px);
        pointer-events:none;
        background:linear-gradient(to right, rgba(226,232,240,0), rgba(148,163,184,0.18));
    }
    .gaji-table .sticky-col-right::before {
        content:"";
        position:absolute;
        top:-1px;
        left:-1px;
        width:10px;
        height:calc(100% + 2px);
        pointer-events:none;
        background:linear-gradient(to left, rgba(226,232,240,0), rgba(148,163,184,0.18));
    }
    .legend-card {
        background:white;
        border-radius:12px;
        padding:12px 20px;
        margin-top:20px;
        display:flex;
        flex-wrap:wrap;
        align-items:center;
        gap:20px;
        box-shadow:0 1px 2px rgba(0,0,0,0.05);
    }
    .legend-title { font-size:12px; font-weight:600; color:#1E293B; }
    .legend-items { display:flex; flex-wrap:wrap; gap:20px; }
    .legend-item { display:flex; align-items:center; gap:8px; font-size:11px; color:#334155; }
    .legend-badge { width:16px; height:16px; border-radius:4px; display:inline-block; }
    .legend-card.payroll-standard .legend-item > span:last-child { font-size:0; line-height:0; }
    .legend-card.payroll-standard .legend-item > span:last-child::after { font-size:11px; line-height:1.5; color:#334155; }
    .legend-card.payroll-standard .legend-item:nth-child(1) > span:last-child::after { content:'Payroll bulanan memakai gaji pokok tetap per bulan. Tarif harian dihitung dari gaji pokok dibagi divisor payroll.'; }
    .legend-card.payroll-standard .legend-item:nth-child(2) > span:last-child::after { content:'Payroll harian dibayar dari total hadir + paid leave. Alpha dan izin unpaid otomatis tidak dibayar.'; }
    .legend-card.payroll-standard .legend-item:nth-child(3) > span:last-child::after { content:'Cuti dan sakit yang disetujui diperlakukan sebagai paid leave. Izin lainnya masuk kategori unpaid.'; }
    .legend-card.payroll-standard .legend-item:nth-child(4) > span:last-child::after { content:'Lembur dihitung otomatis dari absensi saat jam pulang terlewati lebih dari 1 jam.'; }
    .legend-card.payroll-standard .legend-item:nth-child(5) > span:last-child::after { content:'BPJS mengikuti tunjangan dan potongan manual yang diatur per karyawan.'; }
    .legend-card.payroll-standard .legend-item:nth-child(6) > span:last-child::after { content:'Penyesuaian diambil dari akumulasi tabel gaji_tambahan pada periode yang sama.'; }
    .table-footer td { background:#F8FAFC; font-weight:600; }
    .payroll-subtext { display:block; margin-top:4px; font-size:11px; color:#64748B; }
    .payroll-value-row {
        display:inline-flex;
        align-items:center;
        gap:8px;
    }
    .payroll-detail-trigger {
        width:18px;
        height:18px;
        border:none;
        background:transparent;
        color:#94A3B8;
        padding:0;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        transition:color 0.2s ease, transform 0.2s ease;
    }
    .payroll-detail-trigger:hover,
    .payroll-detail-trigger:focus {
        color:#2563EB;
        transform:scale(1.08);
        outline:none;
    }
    .payroll-detail-trigger i {
        font-size:15px;
    }
    .payroll-breakdown-modal-dialog {
        width:min(920px, calc(100vw - 32px));
        max-width:920px;
    }
    .payroll-breakdown-modal-content {
        max-height:calc(100vh - 48px);
        display:flex;
        flex-direction:column;
    }
    .payroll-breakdown-modal-body {
        overflow:auto;
        padding:20px;
        display:grid;
        gap:16px;
    }
    .payroll-breakdown-summary {
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:12px;
    }
    .payroll-breakdown-summary-item {
        border:1px solid #E2E8F0;
        border-radius:14px;
        background:#F8FAFC;
        padding:14px 16px;
    }
    .payroll-breakdown-summary-label {
        display:block;
        margin-bottom:6px;
        font-size:11px;
        font-weight:700;
        color:#64748B;
        text-transform:uppercase;
        letter-spacing:.04em;
    }
    .payroll-breakdown-summary-value {
        font-size:18px;
        font-weight:800;
        color:#0F172A;
    }
    .payroll-breakdown-sections {
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:16px;
    }
    .payroll-breakdown-card {
        border:1px solid #E2E8F0;
        border-radius:14px;
        background:#FFFFFF;
        padding:16px;
        display:grid;
        gap:12px;
    }
    .payroll-breakdown-card h4 {
        margin:0;
        font-size:13px;
        font-weight:800;
        color:#0F172A;
    }
    .payroll-breakdown-list {
        display:grid;
        gap:10px;
    }
    .payroll-breakdown-item {
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
        font-size:12px;
        padding-bottom:8px;
        border-bottom:1px dashed #E2E8F0;
    }
    .payroll-breakdown-item:last-child {
        border-bottom:none;
        padding-bottom:0;
    }
    .payroll-breakdown-item.is-child {
        margin-left:14px;
        padding-left:12px;
        border-left:2px solid #E2E8F0;
    }
    .payroll-breakdown-item-head {
        display:grid;
        gap:3px;
        min-width:0;
    }
    .payroll-breakdown-item-label {
        color:#64748B;
        font-weight:600;
    }
    .payroll-breakdown-item-note {
        font-size:11px;
        color:#94A3B8;
        line-height:1.45;
    }
    .payroll-breakdown-item-value {
        text-align:right;
        font-weight:700;
        color:#0F172A;
    }
    .payroll-breakdown-item-value.is-positive {
        color:#065F46;
    }
    .payroll-breakdown-item-value.is-negative {
        color:#B91C1C;
    }
    .payroll-breakdown-empty {
        font-size:12px;
        color:#94A3B8;
    }
    @media (max-width: 900px) {
        .stats-grid { grid-template-columns:repeat(2, 1fr); }
        .period-card .card-body {
            flex-direction:column;
            align-items:stretch;
        }
        .period-info,
        .period-actions {
            width:100%;
            min-width:0;
        }
        .period-actions {
            justify-content:stretch;
        }
        .period-actions > .btn,
        .period-actions > a,
        .period-actions > form {
            flex:1 1 220px;
        }
        .period-actions > form .btn {
            width:100%;
        }
        .page-header-actions,
        .filter-toolbar {
            width:100%;
        }
        .filter-toolbar > * {
            flex:1 1 180px;
            min-width:0;
        }
        .filter-select,
        .filter-toolbar .btn {
            width:100%;
        }
        .filter-row { flex-direction:column; align-items:stretch; }
        .filter-input { width:100%; }
        .gaji-table { min-width:1500px; }
        .period-meta-grid { grid-template-columns:repeat(2, minmax(120px, 1fr)); }
        .payroll-breakdown-summary,
        .payroll-breakdown-sections { grid-template-columns:1fr; }
    }
    @media (max-width: 640px) {
        .stats-grid { grid-template-columns:1fr; }
        .legend-card { flex-direction:column; align-items:flex-start; }
        .period-meta-grid { grid-template-columns:1fr; }
        .period-actions { width:100%; }
        .period-actions > .btn,
        .period-actions > a,
        .period-actions > form {
            flex:1 1 100%;
        }
        .gaji-table { min-width:1280px; }
        .payroll-adjustment-item-head,
        .payroll-adjustment-item-foot {
            flex-direction:column;
            align-items:flex-start;
        }
        .payroll-adjustment-actions {
            width:100%;
        }
        .payroll-adjustment-actions .btn {
            width:100%;
        }
    }
@endsection

@section('content')
    @php
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
        $formatDurationLabel = static fn (int $minutes): string => format_duration_minutes_label($minutes);
    @endphp
    <div id="ajaxFilterFragment">
        @php
            $periodStatusClass = match ($periodSummary['workflow_key']) {
                'finalized' => 'status-finalized',
                'waiting_stage_one', 'waiting_stage_two' => 'status-processing',
                default => 'status-draft',
            };
        @endphp

        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">{{ $salarySummary['employees'] }}</div>
                        <div class="stat-label">Karyawan Digaji</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-group"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card finalized">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">{{ $periodSummary['finalized_rows'] }}</div>
                        <div class="stat-label">Sudah Final</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-circle-check"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card draft">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">{{ $periodSummary['draft_rows'] }}</div>
                        <div class="stat-label">Masih Draft</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card amount">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">Rp {{ number_format($grandTotal, 0, ',', '.') }}</div>
                        <div class="stat-label">Total Payroll</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-header">
            <div>
                <h3 class="page-subtitle">Riwayat Payroll</h3>
                <p class="page-description">Periode {{ $period->translatedFormat('F Y') }} &middot; {{ $periodSummary['workflow_description'] }}</p>
            </div>
            <div class="page-header-actions">
                <form method="GET" action="{{ route('admin.riwayat-gaji') }}" class="filter-toolbar" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxFilterFragment">
                    <input type="month" name="bulan" class="filter-select" value="{{ $period->format('Y-m') }}">
                    <input type="search" name="q" class="filter-select" value="{{ $search ?? '' }}" placeholder="Cari NIK, nama, jabatan...">
                    <select name="per_page" class="filter-select">
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected(($perPage ?? 10) === $size)>{{ $size }} / halaman</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.riwayat-gaji') }}" class="btn-reset" data-ajax-link="true" data-refresh-target="#ajaxFilterFragment">Reset</a>
                </form>
                @if ($periodSummary['workflow_key'] === 'draft')
                    <form method="POST" action="{{ route('admin.riwayat-gaji.period.refresh') }}" data-ajax="true" data-refresh-target="#ajaxFilterFragment">
                        @csrf
                        <input type="hidden" name="bulan" value="{{ $period->format('Y-m') }}">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-rotate"></i> Refresh Draft
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.riwayat-gaji.period.submit') }}" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-confirm="Ajukan draft payroll periode ini ke approval tahap 1? Setelah diajukan, data payroll dibekukan sampai periode dibuka ulang." data-confirm-title="Ajukan Approval Payroll" data-confirm-button="Ya, ajukan" data-confirm-variant="success">
                        @csrf
                        <input type="hidden" name="bulan" value="{{ $period->format('Y-m') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Ajukan Approval
                        </button>
                    </form>
                @elseif ($periodSummary['workflow_key'] === 'waiting_stage_one')
                    <form method="POST" action="{{ route('admin.riwayat-gaji.period.approve-1') }}" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-confirm="Setujui payroll tahap 1 untuk periode ini?" data-confirm-title="Approval Payroll Tahap 1" data-confirm-button="Ya, setujui tahap 1" data-confirm-variant="success">
                        @csrf
                        <input type="hidden" name="bulan" value="{{ $period->format('Y-m') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Approval 1
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.riwayat-gaji.period.reopen') }}" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-confirm="Buka kembali periode ini ke mode draft? Approval yang sudah berjalan akan dibatalkan." data-confirm-title="Buka Periode Payroll" data-confirm-button="Ya, buka draft" data-confirm-variant="warning">
                        @csrf
                        <input type="hidden" name="bulan" value="{{ $period->format('Y-m') }}">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-lock-open"></i> Buka Periode
                        </button>
                    </form>
                @elseif ($periodSummary['workflow_key'] === 'waiting_stage_two')
                    <form method="POST" action="{{ route('admin.riwayat-gaji.period.approve-2') }}" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-confirm="Selesaikan approval tahap 2 dan finalisasi seluruh payroll periode ini?" data-confirm-title="Approval 2 & Finalisasi" data-confirm-button="Ya, finalisasi periode" data-confirm-variant="success">
                        @csrf
                        <input type="hidden" name="bulan" value="{{ $period->format('Y-m') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-circle-check"></i> Approval 2 & Final
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.riwayat-gaji.period.reopen') }}" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-confirm="Buka kembali periode ini ke mode draft? Approval yang sudah berjalan akan dibatalkan." data-confirm-title="Buka Periode Payroll" data-confirm-button="Ya, buka draft" data-confirm-variant="warning">
                        @csrf
                        <input type="hidden" name="bulan" value="{{ $period->format('Y-m') }}">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-lock-open"></i> Buka Periode
                        </button>
                    </form>
                @elseif ($periodSummary['workflow_key'] === 'finalized')
                    <a href="{{ route('admin.riwayat-gaji.period.slips', ['bulan' => $period->format('Y-m')]) }}" class="btn btn-info">
                        <i class="fas fa-file-archive"></i> Slip Massal
                    </a>
                    <form method="POST" action="{{ route('admin.riwayat-gaji.period.reopen') }}" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-confirm="Buka kembali finalisasi payroll untuk seluruh periode ini?" data-confirm-title="Buka Finalisasi Periode" data-confirm-button="Ya, buka periode" data-confirm-variant="warning">
                        @csrf
                        <input type="hidden" name="bulan" value="{{ $period->format('Y-m') }}">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-lock-open"></i> Buka Periode
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <h3><i class="fas fa-money-bill-wave" style="color:#065F46; margin-right:8px;"></i>Daftar Gaji Karyawan Periode {{ $period->translatedFormat('F Y') }}</h3>
                    <span class="status-pill {{ $periodStatusClass }}">
                        {{ $periodSummary['workflow_label'] }}
                    </span>
                </div>
                <div class="card-header-actions">
                    <span class="total-data">Total: {{ $salaryRows->total() }} karyawan</span>
                    <a href="{{ route('admin.riwayat-gaji.export', ['bulan' => $period->format('Y-m'), 'q' => $search ?? '', 'type' => 'excel']) }}" class="btn-excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="{{ route('admin.riwayat-gaji.export', ['bulan' => $period->format('Y-m'), 'q' => $search ?? '', 'type' => 'pdf']) }}" class="btn-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div class="table-responsive payroll-table-wrap">
                <table class="gaji-table">
                    <thead>
                        <tr class="table-header">
                            <th width="50" class="sticky-col-left sticky-no">No</th>
                            <th class="sticky-col-left sticky-nik">NIK</th>
                            <th class="sticky-col-left sticky-nama">Nama Karyawan</th>
                            <th>Jabatan</th>
                            <th class="text-center">Jadwal</th>
                            <th class="text-center">Hadir</th>
                            <th class="text-center">Izin</th>
                            <th class="text-center">Alpha</th>
                            <th class="text-center">Terlambat</th>
                            <th class="text-center">Pulang Cepat</th>
                            <th>Gaji Dasar</th>
                            <th>Tunjangan<br>Jabatan</th>
                            <th>Makan &<br>Transport</th>
                            <th>BPJS<br>Net</th>
                            <th>Lembur</th>
                            <th>Penyesuaian</th>
                            <th>Potongan</th>
                            <th>Total Gaji</th>
                            <th>Status Payroll</th>
                            <th width="180" class="sticky-col-right sticky-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($salaryRows as $index => $salary)
                            @php
                                $employee = $salary['karyawan'];
                                $makanTransport = $salary['tunjangan_makan_total'] + $salary['tunjangan_transport_total'];
                                $totalBonus = $salary['total_bonus'] ?? 0;
                                $bonusManual = $salary['bonus_manual_total'] ?? (($salary['bonus_pribadi_total'] ?? 0) + ($salary['bonus_team_total'] ?? 0));
                                $premiKehadiran = $salary['premi_kehadiran_total'] ?? 0;
                                $thrAmount = $salary['thr_amount'] ?? 0;
                                $potonganKasbon = $salary['potongan_kasbon'] ?? 0;
                                $kasbonBalance = $salary['saldo_kasbon_awal'] ?? 0;
                                $kasbonCap = max(0, (float) ($salary['kasbon_payable_cap'] ?? $salary['take_home_before_kasbon'] ?? (($salary['total_gaji'] ?? 0) + $potonganKasbon)));
                                $manualKasbonAmount = $salary['manual_kasbon_amount'] ?? null;
                                $potonganBpjs = $salary['bpjs_potongan_total'] ?? 0;
                                $pph21Amount = $salary['pph21_amount'] ?? 0;
                                $pph21Method = $salary['pph21_method'] ?? 'off';
                                $pph21Category = $salary['pph21_ter_category'] ?? null;
                                $pph21Rate = (float) ($salary['pph21_ter_rate'] ?? 0);
                                $bpjsNet = ($salary['bpjs_tunjangan_total'] ?? 0) - ($salary['bpjs_potongan_total'] ?? 0);
                                $totalPotongan = $salary['total_potongan'] ?? ($salary['potongan_terlambat'] ?? 0);
                                $pph21Label = match ($pph21Method) {
                                    'ter' => $pph21Category ? 'TER '.$pph21Category.' '.rtrim(rtrim(number_format($pph21Rate, 2, '.', ''), '0'), '.').'%' : 'TER',
                                    'annual_reconcile' => 'Final Tahunan',
                                    'annualized' => 'Estimasi Tahunan',
                                    default => 'Nonaktif',
                                };
                                $attendanceSummary = [];
                                if (($salary['total_alpha'] ?? 0) > 0) {
                                    $attendanceSummary[] = 'Mangkir '.$salary['total_alpha'].' hari';
                                }
                                if (($salary['total_izin_tidak_dibayar'] ?? 0) > 0) {
                                    $attendanceSummary[] = 'Izin unpaid '.($salary['total_izin_tidak_dibayar'] ?? 0).' hari';
                                }
                                if (($salary['total_menit_terlambat'] ?? 0) > 0) {
                                    $attendanceSummary[] = 'Telat '.$formatDurationLabel((int) $salary['total_menit_terlambat']);
                                }
                                if (($salary['total_menit_pulang_cepat'] ?? 0) > 0) {
                                    $attendanceSummary[] = 'Pulang cepat '.$formatDurationLabel((int) $salary['total_menit_pulang_cepat']);
                                }
                                if ($attendanceSummary === []) {
                                    $attendanceSummary[] = 'Kehadiran normal';
                                }

                                $payrollSummary = [];
                                if (((float) ($salary['prorate_ratio'] ?? 1)) < 1) {
                                    $payrollSummary[] = 'Prorata '.number_format(((float) ($salary['prorate_ratio'] ?? 1)) * 100, 0, ',', '.').'%';
                                }
                                if ($totalBonus > 0) {
                                    $payrollSummary[] = 'Bonus Rp '.number_format($totalBonus, 0, ',', '.');
                                }
                                if ($thrAmount > 0) {
                                    $payrollSummary[] = 'THR Rp '.number_format($thrAmount, 0, ',', '.');
                                }
                                if ($potonganKasbon > 0) {
                                    $payrollSummary[] = 'Kasbon Rp '.number_format($potonganKasbon, 0, ',', '.');
                                }
                                if ($pph21Amount != 0.0) {
                                    $payrollSummary[] = 'PPh21 '.($pph21Amount >= 0 ? 'Rp '.number_format($pph21Amount, 0, ',', '.') : '- Rp '.number_format(abs($pph21Amount), 0, ',', '.'));
                                }

                                $premiSummary = null;
                                if ($premiKehadiran > 0) {
                                    $premiSummary = ($salary['premi_kehadiran_mode_label'] ?? 'Premi kehadiran').' Rp '.number_format($premiKehadiran, 0, ',', '.');
                                }
                                $bpjsBreakdown = is_array($salary['bpjs_breakdown'] ?? null) ? $salary['bpjs_breakdown'] : [];
                                $hasStoredLemburDuration = array_key_exists('total_menit_lembur', $salary) && $salary['total_menit_lembur'] !== null;
                                $totalLemburMenit = $hasStoredLemburDuration ? (int) $salary['total_menit_lembur'] : 0;
                                $totalLemburJam = $totalLemburMenit > 0 ? round($totalLemburMenit / 60, 2) : 0;
                                $lemburDurationUnavailable = ! $hasStoredLemburDuration && (float) ($salary['total_lembur_tarif'] ?? 0) > 0;
                                $salaryAdjustments = collect($salary['adjustments'] ?? []);
                                $positiveAdjustmentItems = $salaryAdjustments
                                    ->filter(fn (array $item) => (float) ($item['amount'] ?? 0) > 0)
                                    ->map(fn (array $item) => [
                                        'label' => $item['jenis_label'] ?? 'Koreksi tambah',
                                        'amount' => (float) ($item['amount_abs'] ?? 0),
                                        'note' => $item['keterangan'] ?? null,
                                    ])
                                    ->values()
                                    ->all();
                                $negativeAdjustmentItems = $salaryAdjustments
                                    ->filter(fn (array $item) => (float) ($item['amount'] ?? 0) < 0)
                                    ->map(fn (array $item) => [
                                        'label' => $item['jenis_label'] ?? 'Koreksi kurang',
                                        'amount' => (float) ($item['amount_abs'] ?? 0),
                                        'note' => $item['keterangan'] ?? null,
                                    ])
                                    ->values()
                                    ->all();
                                $adjustmentNetItems = $salaryAdjustments
                                    ->map(fn (array $item) => [
                                        'label' => $item['jenis_label'] ?? 'Koreksi payroll',
                                        'amount' => (float) ($item['amount'] ?? 0),
                                        'note' => $item['keterangan'] ?? null,
                                    ])
                                    ->values()
                                    ->all();

                                $bpjsCompanyItems = array_values(array_filter([
                                    ['label' => 'Tunjangan BPJS Kesehatan', 'amount' => (float) ($salary['tunjangan_bpjs_kesehatan_total'] ?? 0)],
                                    [
                                        'label' => 'Tunjangan BPJS Ketenagakerjaan',
                                        'amount' => (float) ($salary['tunjangan_bpjs_ketenagakerjaan_total'] ?? 0),
                                        'note' => 'Total dari 4 komponen BPJS Ketenagakerjaan',
                                    ],
                                    ['label' => 'Jaminan Hari Tua (Perusahaan)', 'amount' => (float) ($bpjsBreakdown['jht_company'] ?? 0), 'indent' => 1],
                                    ['label' => 'Jaminan Pensiun (Perusahaan)', 'amount' => (float) ($bpjsBreakdown['jp_company'] ?? 0), 'indent' => 1],
                                    ['label' => 'Jaminan Kecelakaan Kerja', 'amount' => (float) ($bpjsBreakdown['jkk_company'] ?? 0), 'indent' => 1],
                                    ['label' => 'Jaminan Kematian', 'amount' => (float) ($bpjsBreakdown['jkm_company'] ?? 0), 'indent' => 1],
                                ], fn ($item) => abs((float) ($item['amount'] ?? 0)) > 0));

                                $bpjsEmployeeItems = array_values(array_filter([
                                    ['label' => 'Potongan BPJS Kesehatan', 'amount' => (float) ($salary['potongan_bpjs_kesehatan_total'] ?? 0)],
                                    [
                                        'label' => 'Potongan BPJS Ketenagakerjaan',
                                        'amount' => (float) ($salary['potongan_bpjs_ketenagakerjaan_total'] ?? 0),
                                        'note' => 'Total dari 2 komponen karyawan',
                                    ],
                                    ['label' => 'Jaminan Hari Tua (Karyawan)', 'amount' => (float) ($bpjsBreakdown['jht_employee'] ?? 0), 'indent' => 1],
                                    ['label' => 'Jaminan Pensiun (Karyawan)', 'amount' => (float) ($bpjsBreakdown['jp_employee'] ?? 0), 'indent' => 1],
                                ], fn ($item) => abs((float) ($item['amount'] ?? 0)) > 0));

                                $bpjsMetaItems = array_values(array_filter([
                                    ['label' => 'Mode BPJS', 'amount' => null, 'text' => (string) ($salary['bpjs_mode_label'] ?? 'Manual')],
                                    ['label' => 'Dasar BPJS', 'amount' => (float) ($bpjsBreakdown['base_salary'] ?? 0)],
                                    ['label' => 'Dasar Kesehatan', 'amount' => (float) ($bpjsBreakdown['health_base'] ?? 0)],
                                    ['label' => 'Dasar JP', 'amount' => (float) ($bpjsBreakdown['jp_base'] ?? 0)],
                                    ['label' => 'Batas JP', 'amount' => (float) ($bpjsBreakdown['jp_cap'] ?? 0)],
                                ], fn ($item) => isset($item['text']) || abs((float) ($item['amount'] ?? 0)) > 0));

                                $bpjsNetDetailPayload = [
                                    'title' => 'Rincian BPJS Net',
                                    'subtitle' => trim($employee->nik.' - '.$employee->nama_lengkap),
                                    'summary_label' => 'BPJS Net',
                                    'summary_amount' => (float) $bpjsNet,
                                    'summary_tone' => $bpjsNet < 0 ? 'expense' : 'income',
                                    'sections' => [
                                        [
                                            'title' => 'Tunjangan / Porsi Perusahaan',
                                            'tone' => 'income',
                                            'items' => $bpjsCompanyItems,
                                        ],
                                        [
                                            'title' => 'Potongan / Porsi Karyawan',
                                            'tone' => 'expense',
                                            'items' => $bpjsEmployeeItems,
                                        ],
                                        [
                                            'title' => 'Info Dasar',
                                            'tone' => 'neutral',
                                            'items' => $bpjsMetaItems,
                                        ],
                                    ],
                                ];

                                $lemburDetailPayload = [
                                    'title' => 'Rincian Lembur',
                                    'subtitle' => trim($employee->nik.' - '.$employee->nama_lengkap),
                                    'summary_label' => 'Total Lembur',
                                    'summary_amount' => (float) ($salary['total_lembur_tarif'] ?? 0),
                                    'summary_tone' => 'income',
                                    'sections' => [
                                        [
                                            'title' => 'Akumulasi Lembur',
                                            'tone' => 'income',
                                            'items' => array_values(array_filter([
                                                [
                                                    'label' => 'Durasi lembur',
                                                    'amount' => null,
                                                    'text' => $lemburDurationUnavailable
                                                        ? 'Durasi belum tersimpan'
                                                        : $formatOvertimeDuration($totalLemburMenit)['label'],
                                                ],
                                                ['label' => 'Nominal lembur', 'amount' => (float) ($salary['total_lembur_tarif'] ?? 0)],
                                                (! $lemburDurationUnavailable && $totalLemburMenit > 0 && (float) ($salary['total_lembur_tarif'] ?? 0) > 0)
                                                    ? ['label' => 'Rata-rata tarif per jam', 'amount' => round(((float) ($salary['total_lembur_tarif'] ?? 0)) / max($totalLemburJam, 0.01), 2)]
                                                    : null,
                                            ], fn ($item) => is_array($item) && (isset($item['text']) || abs((float) ($item['amount'] ?? 0)) > 0))),
                                        ],
                                    ],
                                ];

                                $deductionDetailItemsAttendance = array_values(array_filter([
                                    ['label' => 'Izin unpaid', 'amount' => (float) ($salary['potongan_izin'] ?? 0)],
                                    ['label' => 'Mangkir', 'amount' => (float) ($salary['potongan_mangkir'] ?? 0)],
                                    ['label' => 'Terlambat', 'amount' => (float) ($salary['potongan_terlambat'] ?? 0)],
                                ], fn ($item) => is_array($item) && abs((float) ($item['amount'] ?? 0)) > 0));
                                $deductionDetailItemsAttendance = array_merge($deductionDetailItemsAttendance, $negativeAdjustmentItems);

                                $deductionDetailItemsOther = array_values(array_filter([
                                    ['label' => 'Kasbon', 'amount' => (float) $potonganKasbon],
                                    ['label' => 'BPJS', 'amount' => (float) $potonganBpjs],
                                    ['label' => 'PPh21 '.($pph21Label ?: ''), 'amount' => (float) $pph21Amount],
                                ], fn ($item) => abs((float) ($item['amount'] ?? 0)) > 0));

                                $incomeDetailItems = array_values(array_filter([
                                    ['label' => 'Gaji Kehadiran', 'amount' => (float) ($salary['base_salary_total'] ?? $salary['gaji_kehadiran'] ?? 0)],
                                    ['label' => 'Tunjangan Jabatan', 'amount' => (float) ($salary['tunjangan_jabatan_tampil'] ?? 0)],
                                    ['label' => 'Makan', 'amount' => (float) ($salary['tunjangan_makan_total'] ?? 0)],
                                    ['label' => 'Transport', 'amount' => (float) ($salary['tunjangan_transport_total'] ?? 0)],
                                    ['label' => 'Bonus', 'amount' => (float) $totalBonus],
                                    ['label' => 'Premi Kehadiran', 'amount' => (float) $premiKehadiran],
                                    ['label' => 'THR', 'amount' => (float) $thrAmount],
                                    ['label' => 'Tunjangan BPJS', 'amount' => (float) ($salary['bpjs_tunjangan_total'] ?? 0)],
                                    ['label' => 'Lembur', 'amount' => (float) ($salary['total_lembur_tarif'] ?? 0)],
                                ], fn ($item) => is_array($item) && abs((float) ($item['amount'] ?? 0)) > 0));
                                $incomeDetailItems = array_merge($incomeDetailItems, $positiveAdjustmentItems);

                                $adjustmentDetailPayload = [
                                    'title' => 'Rincian Penyesuaian',
                                    'subtitle' => trim($employee->nik.' - '.$employee->nama_lengkap),
                                    'summary_label' => 'Nilai Penyesuaian',
                                    'summary_amount' => (float) ($salary['total_penyesuaian'] ?? 0),
                                    'summary_tone' => ((float) ($salary['total_penyesuaian'] ?? 0)) < 0 ? 'expense' : 'income',
                                    'sections' => [
                                        [
                                            'title' => 'Koreksi Tambah',
                                            'tone' => 'income',
                                            'items' => $positiveAdjustmentItems,
                                        ],
                                        [
                                            'title' => 'Koreksi Kurang',
                                            'tone' => 'expense',
                                            'items' => $negativeAdjustmentItems,
                                        ],
                                        [
                                            'title' => 'Rekap Bersih',
                                            'tone' => ((float) ($salary['total_penyesuaian'] ?? 0)) < 0 ? 'expense' : 'income',
                                            'items' => $adjustmentNetItems !== []
                                                ? $adjustmentNetItems
                                                : [
                                                    [
                                                        'label' => 'Belum ada koreksi payroll',
                                                        'amount' => 0,
                                                    ],
                                                ],
                                        ],
                                    ],
                                ];

                                $deductionDetailPayload = [
                                    'title' => 'Rincian Potongan',
                                    'subtitle' => trim($employee->nik.' - '.$employee->nama_lengkap),
                                    'summary_label' => 'Total Potongan',
                                    'summary_amount' => (float) $totalPotongan,
                                    'summary_tone' => 'expense',
                                    'sections' => [
                                        [
                                            'title' => 'Kehadiran & Disiplin',
                                            'tone' => 'expense',
                                            'items' => $deductionDetailItemsAttendance,
                                        ],
                                        [
                                            'title' => 'Potongan Lainnya',
                                            'tone' => 'expense',
                                            'items' => $deductionDetailItemsOther,
                                        ],
                                    ],
                                ];

                                $totalSalaryDetailPayload = [
                                    'title' => 'Rincian Total Gaji',
                                    'subtitle' => trim($employee->nik.' - '.$employee->nama_lengkap),
                                    'summary_label' => 'Gaji Diterima',
                                    'summary_amount' => (float) ($salary['total_gaji'] ?? 0),
                                    'summary_tone' => 'income',
                                    'sections' => [
                                        [
                                            'title' => 'Pendapatan',
                                            'tone' => 'income',
                                            'items' => $incomeDetailItems,
                                        ],
                                        [
                                            'title' => 'Potongan',
                                            'tone' => 'expense',
                                            'items' => array_merge($deductionDetailItemsAttendance, $deductionDetailItemsOther),
                                        ],
                                        [
                                            'title' => 'Ringkasan',
                                            'tone' => 'neutral',
                                            'items' => [
                                                ['label' => 'Take home sebelum kasbon', 'amount' => (float) ($salary['take_home_before_kasbon'] ?? 0)],
                                                ['label' => 'Total potongan', 'amount' => -1 * (float) $totalPotongan],
                                                ['label' => 'Gaji diterima', 'amount' => (float) ($salary['total_gaji'] ?? 0)],
                                            ],
                                        ],
                                    ],
                                ];
                            @endphp
                            <tr>
                                <td class="text-center sticky-col-left sticky-no">{{ ($salaryRows->firstItem() ?? 1) + $index }}</td>
                                <td class="sticky-col-left sticky-nik">{{ $employee->nik }}</td>
                                <td class="sticky-col-left sticky-nama">
                                    <strong>{{ $employee->nama_lengkap }}</strong>
                                    <span class="payroll-subtext">
                                        {{ $salary['payroll_type_label'] ?? 'Bulanan' }}
                                        @if (($salary['payroll_type'] ?? 'bulanan') === 'bulanan')
                                            | divisor {{ $salary['payroll_divisor'] ?? 26 }}
                                        @endif
                                    </span>
                                </td>
                                <td>{{ $employee->jabatan ?: '-' }}</td>
                                <td class="text-center">{{ $salary['scheduled_days'] }} hari</td>
                                <td class="text-center">{{ $salary['total_hadir'] }} hari</td>
                                <td class="text-center">{{ $salary['total_izin'] }} hari</td>
                                <td class="text-center" style="color:{{ $salary['total_alpha'] > 0 ? '#DC2626' : '#1E293B' }};">{{ $salary['total_alpha'] }} hari</td>
                                <td class="text-center" style="color:{{ $salary['total_menit_terlambat'] > 0 ? '#DC2626' : '#1E293B' }};">{{ $formatDurationLabel((int) ($salary['total_menit_terlambat'] ?? 0)) }}</td>
                                <td class="text-center" style="color:{{ $salary['total_menit_pulang_cepat'] > 0 ? '#D97706' : '#1E293B' }};">{{ $formatDurationLabel((int) ($salary['total_menit_pulang_cepat'] ?? 0)) }}</td>
                                <td class="text-right">
                                    Rp {{ number_format($salary['base_salary_total'] ?? $salary['gaji_kehadiran'], 0, ',', '.') }}
                                    <span class="payroll-subtext">
                                        @if (($salary['payroll_type'] ?? 'bulanan') === 'harian')
                                            {{ $salary['payable_days'] ?? 0 }} hari × Rp {{ number_format($salary['daily_deduction_rate'] ?? 0, 0, ',', '.') }}
                                        @else
                                            Tarif harian Rp {{ number_format($salary['daily_deduction_rate'] ?? 0, 0, ',', '.') }}
                                        @endif
                                    </span>
                                </td>
                                <td class="text-right">{{ $salary['tunjangan_jabatan_tampil'] > 0 ? 'Rp '.number_format($salary['tunjangan_jabatan_tampil'], 0, ',', '.') : '-' }}</td>
                                <td class="text-right">Rp {{ number_format($makanTransport, 0, ',', '.') }}</td>
                                <td class="text-right" style="color:{{ $bpjsNet < 0 ? '#DC2626' : ($bpjsNet > 0 ? '#065F46' : '#334155') }};">
                                    <div class="payroll-value-row">
                                        <span>{{ $bpjsNet > 0 ? 'Rp '.number_format($bpjsNet, 0, ',', '.') : ($bpjsNet < 0 ? '- Rp '.number_format(abs($bpjsNet), 0, ',', '.') : '-') }}</span>
                                        <button
                                            type="button"
                                            class="payroll-detail-trigger"
                                            data-payroll-breakdown='@json($bpjsNetDetailPayload)'
                                            aria-label="Lihat rincian BPJS net">
                                            <i class="fas fa-question-circle"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="payroll-value-row">
                                        <span>Rp {{ number_format($salary['total_lembur_tarif'], 0, ',', '.') }}</span>
                                        <button
                                            type="button"
                                            class="payroll-detail-trigger"
                                            data-payroll-breakdown='@json($lemburDetailPayload)'
                                            aria-label="Lihat rincian lembur">
                                            <i class="fas fa-question-circle"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-right" style="color:{{ $salary['total_penyesuaian'] < 0 ? '#DC2626' : '#065F46' }};">
                                    <div class="payroll-value-row">
                                        <span>{{ $salary['total_penyesuaian'] != 0.0 ? ($salary['total_penyesuaian'] > 0 ? 'Rp ' : '- Rp ').number_format(abs($salary['total_penyesuaian']), 0, ',', '.') : '-' }}</span>
                                        <button
                                            type="button"
                                            class="payroll-detail-trigger"
                                            data-payroll-breakdown='@json($adjustmentDetailPayload)'
                                            aria-label="Lihat rincian penyesuaian">
                                            <i class="fas fa-question-circle"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-right" style="color:{{ $totalPotongan < 0 ? '#065F46' : '#DC2626' }};">
                                    <div class="payroll-value-row">
                                        <span>{{ $totalPotongan != 0.0 ? ($totalPotongan > 0 ? '- Rp ' : 'Rp ').number_format(abs($totalPotongan), 0, ',', '.') : '-' }}</span>
                                        <button
                                            type="button"
                                            class="payroll-detail-trigger"
                                            data-payroll-breakdown='@json($deductionDetailPayload)'
                                            aria-label="Lihat rincian potongan">
                                            <i class="fas fa-question-circle"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="payroll-value-row">
                                        <strong style="color:#065F46;">Rp {{ number_format($salary['total_gaji'], 0, ',', '.') }}</strong>
                                        <button
                                            type="button"
                                            class="payroll-detail-trigger"
                                            data-payroll-breakdown='@json($totalSalaryDetailPayload)'
                                            aria-label="Lihat rincian total gaji">
                                            <i class="fas fa-question-circle"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $salary['is_finalized'] ? 'badge-success' : 'badge-warning' }}">
                                        {{ $salary['is_finalized'] ? 'Final' : 'Draft' }}
                                    </span>
                                    @if ($salary['history']?->finalized_at)
                                        <span class="payroll-subtext">{{ $salary['history']->finalized_at->format('d/m/Y H:i') }}</span>
                                    @endif
                                </td>
                                <td class="sticky-col-right sticky-aksi">
                                    <div class="action-buttons">
                                        @if ($salary['history'])
                                            @if ($periodSummary['workflow_key'] === 'draft' && ! $salary['is_finalized'])
                                                <button
                                                    type="button"
                                                    class="btn btn-warning"
                                                    data-adjustments-url="{{ route('admin.riwayat-gaji.adjustments', $salary['history']) }}"
                                                    data-store-url="{{ route('admin.riwayat-gaji.adjustments.store', $salary['history']) }}"
                                                    data-employee-name="{{ $employee->nama_lengkap }}"
                                                    data-employee-nik="{{ $employee->nik }}"
                                                    data-period-label="{{ $period->translatedFormat('F Y') }}"
                                                    onclick="openPayrollAdjustmentModal(this)">
                                                    <i class="fas fa-sliders-h"></i> Koreksi
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary"
                                                    data-kasbon-url="{{ route('admin.riwayat-gaji.kasbon', $salary['history']) }}"
                                                    data-employee-name="{{ $employee->nama_lengkap }}"
                                                    data-employee-nik="{{ $employee->nik }}"
                                                    data-kasbon-current="{{ number_format((float) $potonganKasbon, 0, ',', '.') }}"
                                                    data-kasbon-balance="{{ number_format((float) $kasbonBalance, 0, ',', '.') }}"
                                                    data-kasbon-cap="{{ number_format((float) $kasbonCap, 0, ',', '.') }}"
                                                    onclick="openPayrollKasbonModal(this)">
                                                    <i class="fas fa-wallet"></i> Kasbon
                                                </button>
                                            @endif

                                            <a href="{{ route('admin.riwayat-gaji.slip', $salary['history']) }}" class="btn btn-info" target="_blank" rel="noopener noreferrer">
                                                <i class="fas fa-file-invoice"></i> Slip
                                            </a>

                                            @if ($periodSummary['workflow_key'] === 'draft' && $salary['is_finalized'])
                                                <form method="POST" action="{{ route('admin.riwayat-gaji.unfinalize', $salary['history']) }}" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-confirm="Buka kembali finalisasi payroll ini?" data-confirm-title="Buka Finalisasi" data-confirm-button="Ya, buka" data-confirm-variant="warning">
                                                    @csrf
                                                    <button type="submit" class="btn btn-warning">
                                                        <i class="fas fa-lock-open"></i> Buka
                                                    </button>
                                                </form>
                                            @elseif ($periodSummary['workflow_key'] === 'draft' && ! $salary['is_finalized'])
                                                <form method="POST" action="{{ route('admin.riwayat-gaji.finalize', $salary['history']) }}" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-confirm="Finalisasi payroll ini? Setelah final, nilai gaji tidak akan ikut berubah otomatis." data-confirm-title="Finalisasi Payroll" data-confirm-button="Ya, finalisasi" data-confirm-variant="success">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-lock"></i> Final
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="20" class="empty-state">Belum ada data payroll karyawan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($salaryRows->total() > 0)
                        <tfoot>
                            <tr class="table-footer">
                                <td colspan="20" class="text-right"><strong>GRAND TOTAL: Rp {{ number_format($grandTotal, 0, ',', '.') }}</strong></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            {{ $salaryRows->links('partials.pagination-ajax', ['target' => '#ajaxFilterFragment']) }}
        </div>
    </div>

    <div class="legend-card payroll-standard">
        <div class="legend-title">Keterangan:</div>
        <div class="legend-items">
            <div class="legend-item">
                <span class="legend-badge" style="background:#D1FAE5;"></span>
                <span>Gaji Kehadiran &amp; Makan/Transport = Hadir × (Gaji/Hari + Makan + Transport)</span>
            </div>
            <div class="legend-item">
                <span class="legend-badge" style="background:#FEF3C7;"></span>
                <span>Tunjangan Jabatan hanya jika hadir ≥ 20 hari</span>
            </div>
            <div class="legend-item">
                <span class="legend-badge" style="background:#FEE2E2;"></span>
                <span>Potongan Terlambat: terlambat × tarif potongan per menit</span>
            </div>
            <div class="legend-item">
                <span class="legend-badge" style="background:#DBEAFE;"></span>
                <span>Lembur: dihitung otomatis dari absensi saat jam pulang terlewati lebih dari 1 jam</span>
            </div>
            <div class="legend-item">
                <span class="legend-badge" style="background:#DCFCE7;"></span>
                <span>BPJS: bisa manual per karyawan atau otomatis dari persentase global dan mode BPJS pegawai</span>
            </div>
            <div class="legend-item">
                <span class="legend-badge" style="background:#FCE7F3;"></span>
                <span>PPh21: Januari-November memakai TER resmi, lalu Desember atau bulan resign direkonsiliasi dengan hitungan tahunan progresif</span>
            </div>
            <div class="legend-item">
                <span class="legend-badge" style="background:#EDE9FE;"></span>
                <span>THR: otomatis masuk saat bulan THR aktif dan bisa prorata untuk masa kerja di bawah 12 bulan</span>
            </div>
            <div class="legend-item">
                <span class="legend-badge" style="background:#DCFCE7;"></span>
                <span>Penyesuaian: akumulasi data tabel gaji_tambahan pada periode yang sama</span>
            </div>
        </div>
    </div>

    <div id="payrollKasbonModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Atur Potongan Kasbon Payroll</h3>
                    <button type="button" class="modal-close" onclick="closePayrollKasbonModal()">&times;</button>
                </div>
                <form method="POST" id="payrollKasbonForm" action="#" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-close-modal="#payrollKasbonModal">
                    @csrf
                    <input type="hidden" name="kasbon_mode" id="payrollKasbonMode" value="manual">

                    <div class="modal-body">
                        <div class="info-note" style="margin-top:0;">
                            <i class="fas fa-circle-info"></i>
                            <span>Default sistem membayar kasbon penuh sampai batas take-home pay. Gunakan nominal manual jika ingin membatasi pembayaran pada draft payroll ini saja.</span>
                        </div>
                        <div class="form-group">
                            <label>Karyawan</label>
                            <input type="text" id="payrollKasbonEmployee" class="form-control" readonly>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Saldo Kasbon Aktif</label>
                                <input type="text" id="payrollKasbonBalance" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>Batas Maksimal Potong</label>
                                <input type="text" id="payrollKasbonCap" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Nominal Potongan Kasbon</label>
                            <input type="text" name="potongan_kasbon" id="payrollKasbonAmount" class="form-control" placeholder="Contoh: 300000">
                            <small class="form-text">Isi <strong>0</strong> jika ingin menunda pembayaran. Klik <strong>Gunakan Default</strong> untuk kembali ke potongan otomatis penuh.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closePayrollKasbonModal()">Batal</button>
                        <button type="button" class="btn btn-outline" onclick="submitPayrollKasbonAuto()">Gunakan Default</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="payrollAdjustmentModal" class="modal" aria-hidden="true">
        <div class="modal-dialog payroll-adjustment-modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h3>Koreksi Payroll</h3>
                        <p class="page-description" id="payrollAdjustmentModalSubtitle" style="margin:4px 0 0;">Kelola bonus atau potongan khusus untuk payroll periode ini.</p>
                    </div>
                    <button type="button" class="modal-close" onclick="closePayrollAdjustmentModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="payroll-adjustment-meta">
                        <div class="payroll-adjustment-meta-item">
                            <div class="payroll-adjustment-meta-label">Karyawan</div>
                            <div class="payroll-adjustment-meta-value" id="payrollAdjustmentEmployee">-</div>
                        </div>
                        <div class="payroll-adjustment-meta-item">
                            <div class="payroll-adjustment-meta-label">Periode</div>
                            <div class="payroll-adjustment-meta-value" id="payrollAdjustmentPeriod">-</div>
                        </div>
                        <div class="payroll-adjustment-meta-item">
                            <div class="payroll-adjustment-meta-label">Mode</div>
                            <div class="payroll-adjustment-meta-value" id="payrollAdjustmentModeLabel">Draft</div>
                        </div>
                    </div>

                    <div class="payroll-adjustment-layout">
                        <div class="payroll-adjustment-card">
                            <div class="payroll-adjustment-card-head">
                                <h4>Riwayat Koreksi</h4>
                                <button type="button" class="btn btn-outline" id="payrollAdjustmentResetButton" onclick="preparePayrollAdjustmentCreateMode()">Koreksi Baru</button>
                            </div>
                            <div class="payroll-adjustment-card-body">
                                <div id="payrollAdjustmentList" class="payroll-adjustment-list">
                                    <div class="payroll-adjustment-empty">Memuat data koreksi payroll...</div>
                                </div>
                            </div>
                        </div>

                        <div class="payroll-adjustment-card">
                            <div class="payroll-adjustment-card-head">
                                <h4 id="payrollAdjustmentFormTitle">Tambah Koreksi</h4>
                            </div>
                            <div class="payroll-adjustment-card-body">
                                <form method="POST" id="payrollAdjustmentForm" action="#" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-close-modal="#payrollAdjustmentModal">
                                    @csrf
                                    <input type="hidden" name="_method" id="payrollAdjustmentMethod" value="PUT" disabled>

                                    <div class="form-group">
                                        <label>Jenis Koreksi</label>
                                        <select name="jenis" id="payrollAdjustmentType" class="form-control"></select>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Arah</label>
                                            <select name="direction" id="payrollAdjustmentDirection" class="form-control">
                                                <option value="plus">Tambah</option>
                                                <option value="minus">Kurang</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Nominal</label>
                                            <input type="text" name="nominal" id="payrollAdjustmentAmount" class="form-control" placeholder="Contoh: 500000">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Keterangan</label>
                                        <textarea name="keterangan" id="payrollAdjustmentNote" class="form-control" rows="4" placeholder="Contoh: Bonus target Juni 2026"></textarea>
                                    </div>
                                    <div class="payroll-adjustment-lock-note" id="payrollAdjustmentLockNote" style="display:none;">
                                        Koreksi payroll dikunci karena periode tidak lagi draft atau payroll karyawan ini sudah final.
                                    </div>
                                    <div class="payroll-adjustment-form-actions">
                                        <button type="button" class="btn btn-secondary" onclick="closePayrollAdjustmentModal()">Tutup</button>
                                        <button type="button" class="btn btn-outline" onclick="preparePayrollAdjustmentCreateMode()">Reset</button>
                                        <button type="submit" class="btn btn-primary" id="payrollAdjustmentSubmitButton">Simpan Koreksi</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" id="payrollAdjustmentDeleteForm" action="#" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-close-modal="#payrollAdjustmentModal" data-confirm="Hapus koreksi payroll ini?" data-confirm-title="Hapus Koreksi Payroll" data-confirm-button="Ya, hapus" data-confirm-variant="warning" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    <div id="payrollBreakdownModal" class="modal" aria-hidden="true">
        <div class="modal-dialog payroll-breakdown-modal-dialog">
            <div class="modal-content payroll-breakdown-modal-content">
                <div class="modal-header">
                    <h3 id="payrollBreakdownTitle">Rincian Payroll</h3>
                    <button type="button" class="modal-close" data-payroll-breakdown-close="true">&times;</button>
                </div>
                <div class="modal-body payroll-breakdown-modal-body">
                    <div class="payroll-breakdown-summary">
                        <div class="payroll-breakdown-summary-item">
                            <span class="payroll-breakdown-summary-label">Karyawan</span>
                            <div class="payroll-breakdown-summary-value" id="payrollBreakdownSubtitle">-</div>
                        </div>
                        <div class="payroll-breakdown-summary-item">
                            <span class="payroll-breakdown-summary-label" id="payrollBreakdownSummaryLabel">Ringkasan</span>
                            <div class="payroll-breakdown-summary-value" id="payrollBreakdownSummaryAmount">Rp 0</div>
                        </div>
                    </div>
                    <div class="payroll-breakdown-sections" id="payrollBreakdownSections"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-payroll-breakdown-close="true">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const payrollKasbonModal = document.getElementById('payrollKasbonModal');
        const payrollKasbonForm = document.getElementById('payrollKasbonForm');
        const payrollKasbonMode = document.getElementById('payrollKasbonMode');
        const payrollKasbonEmployee = document.getElementById('payrollKasbonEmployee');
        const payrollKasbonBalance = document.getElementById('payrollKasbonBalance');
        const payrollKasbonCap = document.getElementById('payrollKasbonCap');
        const payrollKasbonAmount = document.getElementById('payrollKasbonAmount');
        const payrollAdjustmentModal = document.getElementById('payrollAdjustmentModal');
        const payrollAdjustmentModalSubtitle = document.getElementById('payrollAdjustmentModalSubtitle');
        const payrollAdjustmentEmployee = document.getElementById('payrollAdjustmentEmployee');
        const payrollAdjustmentPeriod = document.getElementById('payrollAdjustmentPeriod');
        const payrollAdjustmentModeLabel = document.getElementById('payrollAdjustmentModeLabel');
        const payrollAdjustmentList = document.getElementById('payrollAdjustmentList');
        const payrollAdjustmentForm = document.getElementById('payrollAdjustmentForm');
        const payrollAdjustmentMethod = document.getElementById('payrollAdjustmentMethod');
        const payrollAdjustmentType = document.getElementById('payrollAdjustmentType');
        const payrollAdjustmentDirection = document.getElementById('payrollAdjustmentDirection');
        const payrollAdjustmentAmount = document.getElementById('payrollAdjustmentAmount');
        const payrollAdjustmentNote = document.getElementById('payrollAdjustmentNote');
        const payrollAdjustmentFormTitle = document.getElementById('payrollAdjustmentFormTitle');
        const payrollAdjustmentSubmitButton = document.getElementById('payrollAdjustmentSubmitButton');
        const payrollAdjustmentResetButton = document.getElementById('payrollAdjustmentResetButton');
        const payrollAdjustmentLockNote = document.getElementById('payrollAdjustmentLockNote');
        const payrollAdjustmentDeleteForm = document.getElementById('payrollAdjustmentDeleteForm');
        const payrollBreakdownModal = document.getElementById('payrollBreakdownModal');
        const payrollBreakdownTitle = document.getElementById('payrollBreakdownTitle');
        const payrollBreakdownSubtitle = document.getElementById('payrollBreakdownSubtitle');
        const payrollBreakdownSummaryLabel = document.getElementById('payrollBreakdownSummaryLabel');
        const payrollBreakdownSummaryAmount = document.getElementById('payrollBreakdownSummaryAmount');
        const payrollBreakdownSections = document.getElementById('payrollBreakdownSections');
        let payrollAdjustmentRecords = [];

        function formatPayrollBreakdownMoney(value) {
            const amount = Number(value || 0);
            const prefix = amount < 0 ? '- Rp ' : 'Rp ';
            return prefix + Math.abs(amount).toLocaleString('id-ID');
        }

        function renderPayrollBreakdownSections(sections) {
            if (!payrollBreakdownSections) {
                return;
            }

            payrollBreakdownSections.innerHTML = (sections || []).map(function (section) {
                const items = Array.isArray(section.items) ? section.items : [];
                const tone = section.tone || 'neutral';
                const content = items.length
                    ? items.map(function (item) {
                        const amount = Number(item.amount || 0);
                        let amountClass = '';
                        const displayValue = Object.prototype.hasOwnProperty.call(item, 'text')
                            ? (item.text || '-')
                            : formatPayrollBreakdownMoney(amount);
                        const isChild = Boolean(item.indent);
                        const note = item.note || '';

                        if (tone === 'expense') {
                            amountClass = amount > 0 ? 'is-negative' : (amount < 0 ? 'is-positive' : '');
                        } else if (tone === 'income') {
                            amountClass = amount > 0 ? 'is-positive' : (amount < 0 ? 'is-negative' : '');
                        } else {
                            amountClass = amount > 0 ? 'is-positive' : (amount < 0 ? 'is-negative' : '');
                        }

                        return `
                            <div class="payroll-breakdown-item ${isChild ? 'is-child' : ''}">
                                <div class="payroll-breakdown-item-head">
                                    <div class="payroll-breakdown-item-label">${item.label || '-'}</div>
                                    ${note ? `<div class="payroll-breakdown-item-note">${note}</div>` : ''}
                                </div>
                                <div class="payroll-breakdown-item-value ${amountClass}">${displayValue}</div>
                            </div>
                        `;
                    }).join('')
                    : '<div class="payroll-breakdown-empty">Tidak ada rincian.</div>';

                return `
                    <div class="payroll-breakdown-card">
                        <h4>${section.title || 'Rincian'}</h4>
                        <div class="payroll-breakdown-list">${content}</div>
                    </div>
                `;
            }).join('');
        }

        function openPayrollBreakdownModal(payload) {
            if (!payrollBreakdownModal || !payload) {
                return;
            }

            const sections = Array.isArray(payload.sections) ? payload.sections : [];

            if (payrollBreakdownTitle) {
                payrollBreakdownTitle.textContent = payload.title || 'Rincian Payroll';
            }
            if (payrollBreakdownSubtitle) {
                payrollBreakdownSubtitle.textContent = payload.subtitle || '-';
            }
            if (payrollBreakdownSummaryLabel) {
                payrollBreakdownSummaryLabel.textContent = payload.summary_label || 'Ringkasan';
            }
            if (payrollBreakdownSummaryAmount) {
                payrollBreakdownSummaryAmount.textContent = formatPayrollBreakdownMoney(payload.summary_amount || 0);
                if (payload.summary_tone === 'expense') {
                    payrollBreakdownSummaryAmount.style.color = '#B91C1C';
                } else if (payload.summary_tone === 'income') {
                    payrollBreakdownSummaryAmount.style.color = '#065F46';
                } else {
                    payrollBreakdownSummaryAmount.style.color = Number(payload.summary_amount || 0) < 0 ? '#B91C1C' : '#065F46';
                }
            }
            renderPayrollBreakdownSections(sections);
            payrollBreakdownModal.classList.add('show');
            payrollBreakdownModal.setAttribute('aria-hidden', 'false');
        }

        function closePayrollBreakdownModal() {
            if (!payrollBreakdownModal) {
                return;
            }

            payrollBreakdownModal.classList.remove('show');
            payrollBreakdownModal.setAttribute('aria-hidden', 'true');
        }

        function formatPayrollInputMoney(value) {
            return Number(value || 0).toLocaleString('id-ID');
        }

        function renderPayrollAdjustmentTypeOptions(options) {
            if (!payrollAdjustmentType) {
                return;
            }

            payrollAdjustmentType.innerHTML = (options || []).map(function (option) {
                return `<option value="${option.value}">${option.label}</option>`;
            }).join('');
        }

        function setPayrollAdjustmentEditableState(editable) {
            if (!payrollAdjustmentForm) {
                return;
            }

            payrollAdjustmentForm.querySelectorAll('input, select, textarea, button').forEach(function (field) {
                if (field === payrollAdjustmentMethod) {
                    return;
                }

                if (field.type === 'button' && field === payrollAdjustmentResetButton) {
                    field.disabled = false;
                    return;
                }

                if (field.type === 'button' && field.classList.contains('modal-close')) {
                    field.disabled = false;
                    return;
                }

                field.disabled = !editable;
            });

            if (payrollAdjustmentLockNote) {
                payrollAdjustmentLockNote.style.display = editable ? 'none' : '';
            }

            if (payrollAdjustmentModeLabel) {
                payrollAdjustmentModeLabel.textContent = editable ? 'Draft' : 'Terkunci';
            }
        }

        function renderPayrollAdjustmentList(adjustments, editable) {
            if (!payrollAdjustmentList) {
                return;
            }

            if (!Array.isArray(adjustments) || adjustments.length === 0) {
                payrollAdjustmentList.innerHTML = '<div class="payroll-adjustment-empty">Belum ada koreksi payroll untuk periode ini.</div>';
                return;
            }

            payrollAdjustmentList.innerHTML = adjustments.map(function (item, index) {
                const amountClass = Number(item.amount || 0) < 0 ? 'is-minus' : 'is-plus';
                const amountLabel = Number(item.amount || 0) < 0
                    ? `- Rp ${Math.abs(Number(item.amount_abs || 0)).toLocaleString('id-ID')}`
                    : `Rp ${Math.abs(Number(item.amount_abs || 0)).toLocaleString('id-ID')}`;
                const note = item.keterangan
                    ? `<div class="payroll-adjustment-item-note">${item.keterangan}</div>`
                    : '';
                const buttons = editable
                    ? `
                        <div class="payroll-adjustment-actions">
                            <button type="button" class="btn btn-warning js-payroll-adjustment-edit" data-index="${index}"><i class="fas fa-edit"></i> Edit</button>
                            <button type="button" class="btn btn-danger js-payroll-adjustment-delete" data-index="${index}"><i class="fas fa-trash"></i> Hapus</button>
                        </div>
                    `
                    : '';

                return `
                    <div class="payroll-adjustment-item">
                        <div class="payroll-adjustment-item-head">
                            <div>
                                <div class="payroll-adjustment-item-title">${item.jenis_label || 'Koreksi Payroll'}</div>
                                ${note}
                            </div>
                            <div class="payroll-adjustment-item-amount ${amountClass}">${amountLabel}</div>
                        </div>
                        <div class="payroll-adjustment-item-foot">
                            <div class="payroll-adjustment-item-time">${item.created_at || '-'}</div>
                            ${buttons}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function preparePayrollAdjustmentCreateMode() {
            if (!payrollAdjustmentForm) {
                return;
            }

            window.panelAjax?.clearErrors?.(payrollAdjustmentForm);
            payrollAdjustmentForm.action = payrollAdjustmentModal?.dataset.storeUrl || '#';
            payrollAdjustmentMethod.value = 'PUT';
            payrollAdjustmentMethod.disabled = true;
            if (payrollAdjustmentType && payrollAdjustmentType.options.length > 0) {
                payrollAdjustmentType.selectedIndex = 0;
            }
            payrollAdjustmentDirection.value = 'plus';
            payrollAdjustmentAmount.value = '';
            payrollAdjustmentNote.value = '';
            payrollAdjustmentFormTitle.textContent = 'Tambah Koreksi';
            payrollAdjustmentSubmitButton.textContent = 'Simpan Koreksi';
        }

        function fillPayrollAdjustmentForm(item) {
            if (!payrollAdjustmentForm || !item) {
                return;
            }

            window.panelAjax?.clearErrors?.(payrollAdjustmentForm);
            payrollAdjustmentForm.action = item.update_url || '#';
            payrollAdjustmentMethod.disabled = false;
            payrollAdjustmentMethod.value = 'PUT';
            payrollAdjustmentType.value = item.jenis || 'bonus_manual';
            payrollAdjustmentDirection.value = item.direction || 'plus';
            payrollAdjustmentAmount.value = formatPayrollInputMoney(item.amount_abs || 0);
            payrollAdjustmentNote.value = item.keterangan || '';
            payrollAdjustmentFormTitle.textContent = 'Edit Koreksi';
            payrollAdjustmentSubmitButton.textContent = 'Simpan Perubahan';
        }

        async function loadPayrollAdjustments() {
            if (!payrollAdjustmentModal || !payrollAdjustmentList) {
                return;
            }

            const listUrl = payrollAdjustmentModal.dataset.listUrl || '';

            if (!listUrl) {
                return;
            }

            payrollAdjustmentList.innerHTML = '<div class="payroll-adjustment-empty">Memuat data koreksi payroll...</div>';

            try {
                const response = await fetch(listUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const payload = await response.json();

                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Data koreksi payroll tidak bisa dimuat.');
                }

                payrollAdjustmentRecords = Array.isArray(payload.adjustments) ? payload.adjustments : [];
                payrollAdjustmentModal.dataset.storeUrl = payload.store_url || payrollAdjustmentModal.dataset.storeUrl || '#';

                if (payrollAdjustmentEmployee) {
                    payrollAdjustmentEmployee.textContent = `${payload.employee?.nik || ''} - ${payload.employee?.nama_lengkap || ''}`.trim() || '-';
                }

                if (payrollAdjustmentPeriod) {
                    payrollAdjustmentPeriod.textContent = payload.period?.label || payrollAdjustmentModal.dataset.periodLabel || '-';
                }

                if (payrollAdjustmentModalSubtitle) {
                    payrollAdjustmentModalSubtitle.textContent = `Kelola bonus atau potongan khusus untuk ${payload.period?.label || payrollAdjustmentModal.dataset.periodLabel || 'periode ini'}.`;
                }

                renderPayrollAdjustmentTypeOptions(payload.type_options || []);
                renderPayrollAdjustmentList(payrollAdjustmentRecords, Boolean(payload.editable));
                setPayrollAdjustmentEditableState(Boolean(payload.editable));
                preparePayrollAdjustmentCreateMode();
            } catch (error) {
                payrollAdjustmentList.innerHTML = `<div class="payroll-adjustment-empty">${error.message || 'Data koreksi payroll tidak bisa dimuat.'}</div>`;
                setPayrollAdjustmentEditableState(false);
            }
        }

        function openPayrollAdjustmentModal(button) {
            if (!payrollAdjustmentModal || !button) {
                return;
            }

            payrollAdjustmentRecords = [];
            payrollAdjustmentModal.dataset.listUrl = button.dataset.adjustmentsUrl || '';
            payrollAdjustmentModal.dataset.storeUrl = button.dataset.storeUrl || '';
            payrollAdjustmentModal.dataset.periodLabel = button.dataset.periodLabel || '';

            if (payrollAdjustmentEmployee) {
                payrollAdjustmentEmployee.textContent = `${button.dataset.employeeNik || ''} - ${button.dataset.employeeName || ''}`.trim() || '-';
            }

            if (payrollAdjustmentPeriod) {
                payrollAdjustmentPeriod.textContent = button.dataset.periodLabel || '-';
            }

            payrollAdjustmentModal.classList.add('show');
            payrollAdjustmentModal.setAttribute('aria-hidden', 'false');
            loadPayrollAdjustments();
        }

        function closePayrollAdjustmentModal() {
            if (!payrollAdjustmentModal) {
                return;
            }

            payrollAdjustmentModal.classList.remove('show');
            payrollAdjustmentModal.setAttribute('aria-hidden', 'true');
            payrollAdjustmentRecords = [];
            preparePayrollAdjustmentCreateMode();
            if (payrollAdjustmentList) {
                payrollAdjustmentList.innerHTML = '<div class="payroll-adjustment-empty">Belum ada koreksi payroll untuk periode ini.</div>';
            }
        }

        function resetPayrollKasbonModal() {
            if (!payrollKasbonForm) {
                return;
            }

            payrollKasbonForm.reset();
            payrollKasbonForm.action = '#';
            payrollKasbonMode.value = 'manual';
            window.panelAjax?.clearErrors?.(payrollKasbonForm);
        }

        function openPayrollKasbonModal(button) {
            const data = button.dataset;
            payrollKasbonForm.action = data.kasbonUrl || '#';
            payrollKasbonEmployee.value = `${data.employeeNik || ''} - ${data.employeeName || ''}`.trim();
            payrollKasbonBalance.value = `Rp ${data.kasbonBalance || '0'}`;
            payrollKasbonCap.value = `Rp ${data.kasbonCap || '0'}`;
            payrollKasbonAmount.value = data.kasbonCurrent || '0';
            payrollKasbonMode.value = 'manual';
            payrollKasbonModal.classList.add('show');
        }

        function closePayrollKasbonModal() {
            resetPayrollKasbonModal();
            payrollKasbonModal.classList.remove('show');
        }

        function submitPayrollKasbonAuto() {
            if (!payrollKasbonForm) {
                return;
            }

            payrollKasbonMode.value = 'auto';
            payrollKasbonForm.requestSubmit();
        }

        if (payrollKasbonForm) {
            payrollKasbonForm.addEventListener('submit', function () {
                if (payrollKasbonMode.value !== 'auto') {
                    payrollKasbonMode.value = 'manual';
                }
            });
        }

        window.addEventListener('click', function (event) {
            if (event.target === payrollKasbonModal) {
                closePayrollKasbonModal();
            }

            if (event.target === payrollAdjustmentModal) {
                closePayrollAdjustmentModal();
            }

            if (event.target === payrollBreakdownModal) {
                closePayrollBreakdownModal();
            }
        });

        document.addEventListener('click', function (event) {
            const breakdownTrigger = event.target.closest('[data-payroll-breakdown]');
            if (breakdownTrigger) {
                try {
                    openPayrollBreakdownModal(JSON.parse(breakdownTrigger.dataset.payrollBreakdown || '{}'));
                } catch (error) {
                    window.panelToast?.show('error', 'Rincian payroll tidak bisa dibuka.');
                }
                return;
            }

            if (event.target.closest('[data-payroll-breakdown-close="true"]')) {
                closePayrollBreakdownModal();
                return;
            }

            const adjustmentEditButton = event.target.closest('.js-payroll-adjustment-edit');
            if (adjustmentEditButton) {
                const record = payrollAdjustmentRecords[Number(adjustmentEditButton.dataset.index || -1)];
                if (record) {
                    fillPayrollAdjustmentForm(record);
                }
                return;
            }

            const adjustmentDeleteButton = event.target.closest('.js-payroll-adjustment-delete');
            if (adjustmentDeleteButton) {
                const record = payrollAdjustmentRecords[Number(adjustmentDeleteButton.dataset.index || -1)];
                if (record && payrollAdjustmentDeleteForm) {
                    payrollAdjustmentDeleteForm.action = record.delete_url || '#';
                    payrollAdjustmentDeleteForm.requestSubmit();
                }
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closePayrollBreakdownModal();
                closePayrollAdjustmentModal();
            }
        });

        window.addEventListener('panel:fragment-refreshed', function (event) {
            if (event.detail?.selector === '#ajaxFilterFragment') {
                closePayrollBreakdownModal();
                closePayrollKasbonModal();
                closePayrollAdjustmentModal();
            }
        });
    </script>
@endsection
