@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Pajak PPh21'])

@section('styles')
    .stats-grid { display:grid; grid-template-columns:repeat(5, minmax(0, 1fr)); gap:16px; margin-bottom:20px; }
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
    .stat-card .stat-card-body { padding:0; width:100%; display:flex; justify-content:space-between; align-items:center; gap:12px; }
    .stat-label { font-size:12px; color:#64748B; margin-bottom:8px; }
    .stat-value { font-size:22px; font-weight:800; color:#0F172A; word-break:break-word; }
    .stat-card.period { border-left:4px solid #3B82F6; }
    .stat-card.cutoff { border-left:4px solid #8B5CF6; }
    .stat-card.gross { border-left:4px solid #10B981; }
    .stat-card.payable { border-left:4px solid #EF4444; }
    .stat-card.refund { border-left:4px solid #F59E0B; }
    .stat-card.period .stat-icon { background:#DBEAFE; color:#2563EB; }
    .stat-card.cutoff .stat-icon { background:#EDE9FE; color:#7C3AED; }
    .stat-card.gross .stat-icon { background:#D1FAE5; color:#065F46; }
    .stat-card.payable .stat-icon { background:#FEE2E2; color:#DC2626; }
    .stat-card.refund .stat-icon { background:#FEF3C7; color:#D97706; }
    .stat-icon {
        width:38px;
        height:38px;
        border-radius:12px;
        display:flex;
        align-items:center;
        justify-content:center;
        flex-shrink:0;
        font-size:16px;
    }
    .page-header {
        margin-bottom:20px;
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        gap:16px;
        flex-wrap:wrap;
    }
    .page-header h3 {
        margin:0;
    }
    .page-header p {
        margin:4px 0 0;
        color:#64748B;
        font-size:12px;
    }
    .filter-toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .filter-select {
        padding:8px 12px;
        border:1px solid #E2E8F0;
        border-radius:8px;
        font-size:13px;
        min-width:180px;
        font-family:inherit;
        background:#FFFFFF;
    }
    .filter-select[type="month"],
    .filter-select[type="search"] {
        height:38px;
    }
    .filter-toolbar .btn {
        height:38px;
        display:inline-flex;
        align-items:center;
        gap:6px;
    }
    .tax-table-card .card-body { padding:20px; }
    .tax-table-card .card-header {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:16px;
        flex-wrap:wrap;
    }
    .tax-table-header-left,
    .tax-table-header-right {
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }
    .tax-table-header-right {
        margin-left:auto;
        justify-content:flex-end;
    }
    .tax-table-header-left h3 {
        margin:0;
    }
    .tax-table-header-right .total-data {
        margin:0;
    }
    .tax-table-filter {
        padding-bottom:16px;
        margin-bottom:16px;
        border-bottom:1px solid #E2E8F0;
    }
    .tax-table-footer-pagination {
        margin-top:16px;
    }
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
    .status-pill.status-finalized { background:#DCFCE7; color:#166534; }
    .status-pill.status-processing { background:#DBEAFE; color:#1D4ED8; }
    .export-buttons { display:flex; gap:8px; flex-wrap:wrap; }
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
    .tax-table-wrap {
        width:100%;
        overflow-x:auto;
        overflow-y:hidden;
        -webkit-overflow-scrolling:touch;
    }
    .tax-table {
        width:100%;
        min-width:1600px;
        border-collapse:separate;
        border-spacing:0;
        font-size:12px;
    }
    .tax-table th, .tax-table td {
        border:1px solid #D1D5DB;
        padding:10px 8px;
        vertical-align:top;
        white-space:nowrap;
    }
    .tax-table th { background:#F8FAFC; color:#065F46; font-weight:600; text-align:left; }
    .tax-table td { color:#334155; }
    .tax-subtext { display:block; margin-top:4px; font-size:11px; color:#64748B; white-space:normal; min-width:150px; }
    .method-badge,
    .status-badge {
        display:inline-flex;
        align-items:center;
        border-radius:999px;
        padding:4px 9px;
        font-size:11px;
        font-weight:700;
    }
    .method-badge.ter { background:#DBEAFE; color:#1D4ED8; }
    .method-badge.final { background:#FCE7F3; color:#BE185D; }
    .method-badge.estimated { background:#EDE9FE; color:#6D28D9; }
    .method-badge.off { background:#F1F5F9; color:#475569; }
    .status-badge.finalized { background:#DCFCE7; color:#166534; }
    .status-badge.draft { background:#FEF3C7; color:#92400E; }
    .legend-card {
        background:white;
        border-radius:12px;
        padding:12px 20px;
        margin-top:20px;
        display:flex;
        flex-wrap:wrap;
        gap:20px;
        box-shadow:0 1px 2px rgba(0,0,0,0.05);
    }
    .legend-item { display:flex; align-items:center; gap:8px; font-size:11px; color:#334155; }
    .legend-badge { width:16px; height:16px; border-radius:4px; display:inline-block; }
    .tax-detail-modal-dialog {
        width:min(920px, calc(100vw - 32px));
        max-width:920px;
    }
    .tax-detail-modal-content {
        max-height:calc(100vh - 48px);
        display:flex;
        flex-direction:column;
    }
    .tax-detail-modal-body {
        overflow:auto;
        padding:20px;
    }
    .tax-detail-grid {
        display:grid;
        grid-template-columns:repeat(2, minmax(0, 1fr));
        gap:16px;
    }
    .tax-detail-card {
        border:1px solid #E2E8F0;
        border-radius:14px;
        padding:16px;
        background:#FFFFFF;
    }
    .tax-detail-card h4 {
        margin:0 0 12px;
        font-size:13px;
        font-weight:800;
        color:#0F172A;
    }
    .tax-detail-list {
        display:grid;
        gap:10px;
    }
    .tax-detail-item {
        display:flex;
        justify-content:space-between;
        gap:12px;
        align-items:flex-start;
        font-size:12px;
        border-bottom:1px dashed #E2E8F0;
        padding-bottom:8px;
    }
    .tax-detail-item:last-child {
        border-bottom:none;
        padding-bottom:0;
    }
    .tax-detail-item-label {
        color:#64748B;
        font-weight:600;
    }
    .tax-detail-item-value {
        color:#0F172A;
        font-weight:700;
        text-align:right;
    }
    .tax-detail-note {
        margin-top:12px;
        padding:12px 14px;
        border-radius:12px;
        background:#F8FAFC;
        color:#475569;
        font-size:12px;
        line-height:1.7;
    }
    .tax-action-group {
        display:flex;
        gap:8px;
        align-items:center;
        flex-wrap:wrap;
    }
    @media (max-width: 900px) {
        .stats-grid { grid-template-columns:repeat(2, 1fr); }
        .page-header { align-items:flex-start; }
        .filter-toolbar { width:100%; }
        .filter-toolbar > * {
            flex:1 1 180px;
            min-width:0;
        }
        .filter-select { width:100%; min-width:0; }
        .tax-detail-grid { grid-template-columns:1fr; }
        .tax-table-card .card-header {
            align-items:flex-start;
        }
        .tax-table-header-left,
        .tax-table-header-right {
            width:100%;
        }
        .tax-table-header-right {
            margin-left:0;
            justify-content:flex-start;
        }
        .export-buttons {
            width:100%;
        }
        .export-buttons a {
            flex:1 1 180px;
            justify-content:center;
        }
        .tax-table { min-width:1400px; }
    }
    @media (max-width: 640px) {
        .stats-grid { grid-template-columns:1fr; }
        .tax-table { min-width:1180px; }
        .legend-card {
            flex-direction:column;
            align-items:flex-start;
        }
        .export-buttons {
            flex-direction:column;
        }
        .export-buttons a {
            width:100%;
        }
        .tax-detail-item {
            flex-direction:column;
        }
        .tax-detail-item-value {
            text-align:left;
        }
    }
@endsection

@section('content')
    <div id="ajaxTaxFragment">
        @php
            $workflowKey = $payrollPeriod->status === 'finalized'
                ? 'finalized'
                : (($payrollPeriod->submitted_at || $payrollPeriod->approved_stage_one_at || $payrollPeriod->approved_stage_two_at) ? 'processing' : 'draft');
            $periodStatusClass = match ($workflowKey) {
                'finalized' => 'status-finalized',
                'processing' => 'status-processing',
                default => 'status-draft',
            };
            $exportBaseQuery = [
                'bulan' => $period->format('Y-m'),
                'method' => $methodFilter,
                'status' => $statusFilter,
                'q' => $search,
                'per_page' => $perPage,
            ];
        @endphp

        <div class="stats-grid">
            <div class="stat-card period">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">{{ $period->translatedFormat('F Y') }}</div>
                        <div class="stat-label">Periode</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card cutoff">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">{{ optional($payrollPeriod->cutoff_absensi)->format('d/m/Y') ?? '-' }}</div>
                        <div class="stat-label">Cutoff</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card gross">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">Rp {{ number_format($taxSummary['gross_total'], 0, ',', '.') }}</div>
                        <div class="stat-label">Gross Pajak</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-sack-dollar"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card payable">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">Rp {{ number_format($taxSummary['pph21_payable_total'], 0, ',', '.') }}</div>
                        <div class="stat-label">Potong PPh21</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-arrow-trend-up"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card refund">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">Rp {{ number_format($taxSummary['pph21_refund_total'], 0, ',', '.') }}</div>
                        <div class="stat-label">Refund PPh21</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-rotate-left"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-header">
            <div>
                <h3 class="page-subtitle">Daftar Pajak PPh21</h3>
                <p class="page-description">Filter data pajak, metode perhitungan, dan jumlah baris tampil.</p>
            </div>
            <form method="GET" action="{{ route('admin.pajak-pph21') }}" class="filter-toolbar" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxTaxFragment">
                <input type="month" name="bulan" class="filter-select" value="{{ $period->format('Y-m') }}">
                <select name="method" class="filter-select">
                    @foreach ($methodOptions as $value => $label)
                        <option value="{{ $value }}" @selected($methodFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status" class="filter-select">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" class="filter-select" value="{{ $search }}" placeholder="Cari nama, NIK, jabatan, PTKP">
                <select name="per_page" class="filter-select">
                    @foreach ([15, 25, 50, 100] as $limit)
                        <option value="{{ $limit }}" @selected($perPage === $limit)>{{ $limit }} / halaman</option>
                    @endforeach
                </select>
                <a href="{{ route('admin.pajak-pph21') }}" class="btn btn-secondary" data-ajax-link="true" data-refresh-target="#ajaxTaxFragment">
                    <i class="fas fa-rotate-left"></i> Reset
                </a>
            </form>
        </div>

        <div class="card tax-table-card">
            <div class="card-header">
                <div class="tax-table-header-left">
                    <h3><i class="fas fa-receipt" style="color:#2563EB; margin-right:8px;"></i>Data Pajak PPh21</h3>
                    <span class="status-pill {{ $periodStatusClass }}">
                        {{ $workflowKey === 'finalized' ? 'Final' : ($workflowKey === 'processing' ? 'Diproses' : 'Draft') }}
                    </span>
                </div>
                <div class="tax-table-header-right">
                    <span class="total-data">{{ $taxRows->count() }} baris tampil</span>
                    <div class="export-buttons">
                        <a href="{{ route('admin.pajak-pph21.export', $exportBaseQuery + ['type' => 'excel']) }}" class="btn-excel">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                        <a href="{{ route('admin.pajak-pph21.export', $exportBaseQuery + ['type' => 'pdf']) }}" class="btn-pdf">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="tax-table-wrap table-responsive" data-fragment-loading-scope>
                    <table class="tax-table">
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
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($taxRows as $index => $row)
                                @php
                                    $employee = $row['karyawan'];
                                    $pph21Amount = (float) ($row['pph21_amount'] ?? 0);
                                    $pph21Method = (string) ($row['pph21_method'] ?? 'off');
                                    $pph21MethodLabel = match ($pph21Method) {
                                        'ter' => 'TER Bulanan',
                                        'annual_reconcile' => 'Final Tahunan',
                                        'annualized' => 'Estimasi Tahunan',
                                        default => 'Nonaktif',
                                    };
                                    $methodClass = match ($pph21Method) {
                                        'ter' => 'ter',
                                        'annual_reconcile' => 'final',
                                        'annualized' => 'estimated',
                                        default => 'off',
                                    };
                                    $statusClass = ($row['is_finalized'] ?? false) ? 'finalized' : 'draft';
                                    $terRate = (float) ($row['pph21_ter_rate'] ?? 0);
                                    $taxDetailPayload = [
                                        'employee' => [
                                            'nik' => (string) $employee->nik,
                                            'nama' => (string) $employee->nama_lengkap,
                                            'jabatan' => (string) ($employee->jabatan ?: '-'),
                                            'departemen' => (string) ($employee->departemen ?: '-'),
                                            'status_ptkp' => (string) ($employee->status_ptkp ?? $employee->status_nikah ?? 'TK/0'),
                                            'npwp' => (string) ($employee->npwp ?? '-'),
                                        ],
                                        'period' => $period->translatedFormat('F Y'),
                                        'method' => $pph21Method,
                                        'method_label' => $pph21MethodLabel,
                                        'ter_category' => (string) ($row['pph21_ter_category'] ?? $employee->ter_category ?? '-'),
                                        'ter_rate' => $terRate,
                                        'gross_basis' => (float) ($row['pph21_gross_basis'] ?? 0),
                                        'job_expense' => (float) ($row['pph21_job_expense'] ?? 0),
                                        'net_annual' => (float) ($row['pph21_net_annual'] ?? 0),
                                        'ptkp' => (float) ($row['pph21_ptkp'] ?? 0),
                                        'pkp' => (float) ($row['pph21_pkp'] ?? 0),
                                        'annual_tax' => (float) ($row['pph21_annual_tax'] ?? 0),
                                        'monthly_tax' => $pph21Amount,
                                        'payroll_type' => (string) ($row['payroll_type_label'] ?? 'Bulanan'),
                                        'prorate_ratio' => (float) ($row['prorate_ratio'] ?? 1),
                                        'bpjs_mode' => (string) ($row['bpjs_mode_label'] ?? 'Manual'),
                                        'retirement_contribution_total' => (float) ($row['retirement_contribution_total'] ?? 0),
                                        'taxable_gross_total' => (float) ($row['taxable_gross_total'] ?? 0),
                                        'status' => ($row['is_finalized'] ?? false) ? 'Final' : 'Draft',
                                        'finalized_at' => ! empty($row['history']?->finalized_at) ? $row['history']->finalized_at->format('d/m/Y H:i') : '-',
                                        'bpa1' => [
                                            'counterpart_opt' => (string) ($row['tax_counterpart_opt'] ?? 'Resident'),
                                            'passport_number' => (string) ($row['tax_passport_number'] ?? '-'),
                                            'second_employer' => ! empty($row['tax_has_second_employer']),
                                            'prev_wh_tax_slip' => (string) ($row['tax_prev_withholding_slip_number'] ?? '-'),
                                            'prev_gross_income' => (float) ($row['tax_prev_gross_income'] ?? 0),
                                            'prev_pph21_paid' => (float) ($row['tax_prev_pph21_paid'] ?? 0),
                                            'prev_retirement_contribution' => (float) ($row['tax_prev_retirement_contribution'] ?? 0),
                                            'tax_certificate' => (string) ($row['tax_certificate'] ?? 'N/A'),
                                        ],
                                    ];
                                @endphp
                                <tr>
                                    <td class="text-center">{{ ($taxRows->firstItem() ?? 1) + $index }}</td>
                                    <td>{{ $employee->nik }}</td>
                                    <td>
                                        <strong>{{ $employee->nama_lengkap }}</strong>
                                        <span class="tax-subtext">{{ $employee->departemen ?: '-' }}</span>
                                    </td>
                                    <td>{{ $employee->jabatan ?: '-' }}</td>
                                    <td>{{ $employee->status_ptkp ?? ($employee->status_nikah ?? 'TK/0') }}</td>
                                    <td>{{ $row['pph21_ter_category'] ?? ($employee->ter_category ?? '-') }}</td>
                                    <td>
                                        <span class="method-badge {{ $methodClass }}">{{ $pph21MethodLabel }}</span>
                                        @if ($pph21Method === 'annual_reconcile')
                                            <span class="tax-subtext">Rekonsiliasi masa pajak terakhir</span>
                                        @elseif ($pph21Method === 'ter')
                                            <span class="tax-subtext">Hitung langsung dari bruto bulan berjalan</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        Rp {{ number_format((float) ($row['pph21_gross_basis'] ?? 0), 0, ',', '.') }}
                                        <span class="tax-subtext">Net annual Rp {{ number_format((float) ($row['pph21_net_annual'] ?? 0), 0, ',', '.') }}</span>
                                    </td>
                                    <td class="text-center">{{ $pph21Method === 'ter' ? rtrim(rtrim(number_format($terRate, 2, '.', ''), '0'), '.').'%' : '-' }}</td>
                                    <td class="text-right" style="color:{{ $pph21Amount < 0 ? '#065F46' : ($pph21Amount > 0 ? '#B91C1C' : '#334155') }};">
                                        {{ $pph21Amount > 0 ? 'Rp '.number_format($pph21Amount, 0, ',', '.') : ($pph21Amount < 0 ? '- Rp '.number_format(abs($pph21Amount), 0, ',', '.') : '-') }}
                                        <span class="tax-subtext">{{ $pph21Amount < 0 ? 'Refund / koreksi lebih potong' : 'Potongan payroll bulan ini' }}</span>
                                    </td>
                                    <td class="text-right">Rp {{ number_format((float) ($row['pph21_pkp'] ?? 0), 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format((float) ($row['pph21_annual_tax'] ?? 0), 0, ',', '.') }}</td>
                                    <td>
                                        <span class="status-badge {{ $statusClass }}">{{ ($row['is_finalized'] ?? false) ? 'Final' : 'Draft' }}</span>
                                        @if (! empty($row['history']?->finalized_at))
                                            <span class="tax-subtext">{{ $row['history']->finalized_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="tax-action-group">
                                            <button
                                                type="button"
                                                class="btn btn-secondary"
                                                data-tax-detail="true"
                                                data-tax='@json($taxDetailPayload)'
                                            >
                                                <i class="fas fa-circle-info"></i> Detail
                                            </button>
                                            @if (! empty($row['history']))
                                                <a href="{{ route('admin.riwayat-gaji.slip', $row['history']) }}" class="btn btn-info" target="_blank" rel="noopener noreferrer">
                                                    <i class="fas fa-file-invoice"></i> Slip
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center">Belum ada data pajak pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($taxRows, 'links'))
                    <div class="tax-table-footer-pagination">
                        {{ $taxRows->links('partials.pagination-ajax', ['target' => '#ajaxTaxFragment']) }}
                    </div>
                @endif
            </div>
        </div>

        <div class="legend-card">
            <div class="legend-item">
                <span class="legend-badge" style="background:#DBEAFE;"></span>
                <span>TER bulanan dipakai untuk masa pajak biasa sesuai kategori PTKP pegawai.</span>
            </div>
            <div class="legend-item">
                <span class="legend-badge" style="background:#FCE7F3;"></span>
                <span>Final tahunan dipakai di Desember atau bulan resign untuk rekonsiliasi kumulatif.</span>
            </div>
            <div class="legend-item">
                <span class="legend-badge" style="background:#DCFCE7;"></span>
                <span>Nilai PPh21 negatif berarti ada koreksi lebih potong dan nominal dikembalikan ke payroll.</span>
            </div>
        </div>

        <div id="taxDetailModal" class="modal" aria-hidden="true">
            <div class="modal-dialog tax-detail-modal-dialog">
                <div class="modal-content tax-detail-modal-content">
                    <div class="modal-header">
                        <h3 id="taxDetailTitle">Detail Pajak PPh21</h3>
                        <button type="button" class="modal-close" data-tax-detail-close="true">&times;</button>
                    </div>
                    <div class="tax-detail-modal-body">
                        <div class="tax-detail-grid">
                            <div class="tax-detail-card">
                                <h4>Identitas Pegawai</h4>
                                <div class="tax-detail-list" id="taxDetailEmployee"></div>
                            </div>
                            <div class="tax-detail-card">
                                <h4>Profil Pajak</h4>
                                <div class="tax-detail-list" id="taxDetailProfile"></div>
                            </div>
                            <div class="tax-detail-card">
                                <h4>Basis Hitung</h4>
                                <div class="tax-detail-list" id="taxDetailBasis"></div>
                            </div>
                            <div class="tax-detail-card">
                                <h4>Hasil PPh21</h4>
                                <div class="tax-detail-list" id="taxDetailResult"></div>
                            </div>
                            <div class="tax-detail-card">
                                <h4>Ringkasan BPA1</h4>
                                <div class="tax-detail-list" id="taxDetailBpa1"></div>
                            </div>
                        </div>
                        <div class="tax-detail-note" id="taxDetailNote"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-tax-detail-close="true">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            function money(value) {
                const amount = Number(value || 0);
                if (amount === 0) {
                    return '-';
                }

                const prefix = amount < 0 ? '- Rp ' : 'Rp ';
                return prefix + Math.abs(amount).toLocaleString('id-ID');
            }

            function percentage(value) {
                const amount = Number(value || 0);
                return `${(amount * 100).toLocaleString('id-ID', { maximumFractionDigits: 2 })}%`;
            }

            function renderItems(target, items) {
                if (!target) {
                    return;
                }

                target.innerHTML = items.map(function (item) {
                    return `
                        <div class="tax-detail-item">
                            <div class="tax-detail-item-label">${item.label}</div>
                            <div class="tax-detail-item-value">${item.value}</div>
                        </div>
                    `;
                }).join('');
            }

            function resolveNote(payload) {
                if (payload.method === 'ter') {
                    return `Metode TER bulanan dipakai untuk periode ${payload.period}. PPh21 dihitung langsung dari bruto pajak bulan berjalan dengan kategori ${payload.ter_category || '-'} dan tarif ${Number(payload.ter_rate || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 })}%.`;
                }

                if (payload.method === 'annual_reconcile') {
                    return `Metode final tahunan dipakai karena periode ini adalah masa pajak terakhir pegawai. Sistem menghitung ulang neto setahun, PKP tahunan, lalu membandingkannya dengan PPh21 yang sudah dipotong pada bulan-bulan sebelumnya.`;
                }

                if (payload.method === 'annualized') {
                    return `Metode estimasi tahunan dipakai sebagai fallback. Sistem melakukan annualisasi bruto pajak, mengurangi biaya jabatan dan iuran pensiun, lalu menghitung PPh21 progresif tahunan.`;
                }

                return 'PPh21 untuk baris ini tidak aktif atau berasal dari data lama yang belum memiliki detail metode pajak penuh.';
            }

            function openTaxDetailModal(payload) {
                const modal = document.getElementById('taxDetailModal');
                if (!modal) {
                    return;
                }

                const title = document.getElementById('taxDetailTitle');
                const employeeTarget = document.getElementById('taxDetailEmployee');
                const profileTarget = document.getElementById('taxDetailProfile');
                const basisTarget = document.getElementById('taxDetailBasis');
                const resultTarget = document.getElementById('taxDetailResult');
                const bpa1Target = document.getElementById('taxDetailBpa1');
                const noteTarget = document.getElementById('taxDetailNote');

                if (title) {
                    title.textContent = `Detail Pajak PPh21 — ${payload.employee?.nama || '-'}`;
                }

                renderItems(employeeTarget, [
                    { label: 'NIK', value: payload.employee?.nik || '-' },
                    { label: 'Nama', value: payload.employee?.nama || '-' },
                    { label: 'Jabatan', value: payload.employee?.jabatan || '-' },
                    { label: 'Departemen', value: payload.employee?.departemen || '-' },
                    { label: 'NPWP / NIK Pajak', value: payload.employee?.npwp || '-' },
                ]);

                renderItems(profileTarget, [
                    { label: 'Periode', value: payload.period || '-' },
                    { label: 'Status PTKP', value: payload.employee?.status_ptkp || 'TK/0' },
                    { label: 'Kategori TER', value: payload.ter_category || '-' },
                    { label: 'Metode PPh21', value: payload.method_label || '-' },
                    { label: 'Mode BPJS', value: payload.bpjs_mode || '-' },
                    { label: 'Status Payroll', value: payload.status || '-' },
                ]);

                renderItems(basisTarget, [
                    { label: 'Gross Basis Pajak', value: money(payload.gross_basis) },
                    { label: 'Taxable Gross Total', value: money(payload.taxable_gross_total) },
                    { label: 'Biaya Jabatan', value: money(payload.job_expense) },
                    { label: 'Iuran Pensiun / JHT / JP', value: money(payload.retirement_contribution_total) },
                    { label: 'Net Annual', value: money(payload.net_annual) },
                    { label: 'Prorata Payroll', value: percentage(payload.prorate_ratio) },
                ]);

                renderItems(resultTarget, [
                    { label: 'PTKP', value: money(payload.ptkp) },
                    { label: 'PKP Tahunan', value: money(payload.pkp) },
                    { label: 'Tarif TER', value: payload.method === 'ter' ? `${Number(payload.ter_rate || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 })}%` : '-' },
                    { label: 'PPh21 Tahunan', value: money(payload.annual_tax) },
                    { label: 'PPh21 Bulan Ini', value: money(payload.monthly_tax) },
                    { label: 'Difinalisasi', value: payload.finalized_at || '-' },
                ]);

                renderItems(bpa1Target, [
                    { label: 'Status Subjek', value: payload.bpa1?.counterpart_opt === 'Foreign' ? 'Foreign / Luar Negeri' : 'Resident / Dalam Negeri' },
                    { label: 'No Paspor', value: payload.bpa1?.passport_number || '-' },
                    { label: 'Pemberi Kerja Lain', value: payload.bpa1?.second_employer ? 'Ya' : 'Tidak' },
                    { label: 'No Bukti Potong Sebelumnya', value: payload.bpa1?.prev_wh_tax_slip || '-' },
                    { label: 'Bruto Pajak Sebelumnya', value: money(payload.bpa1?.prev_gross_income) },
                    { label: 'PPh21 Sebelumnya', value: money(payload.bpa1?.prev_pph21_paid) },
                    { label: 'Iuran Pensiun Sebelumnya', value: money(payload.bpa1?.prev_retirement_contribution) },
                    { label: 'Fasilitas Pajak', value: payload.bpa1?.tax_certificate || 'N/A' },
                ]);

                if (noteTarget) {
                    noteTarget.textContent = resolveNote(payload);
                }

                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeTaxDetailModal() {
                const modal = document.getElementById('taxDetailModal');
                if (!modal) {
                    return;
                }

                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            }

            document.addEventListener('click', function (event) {
                const openButton = event.target.closest('[data-tax-detail="true"]');
                if (openButton) {
                    try {
                        const payload = JSON.parse(openButton.dataset.tax || '{}');
                        openTaxDetailModal(payload);
                    } catch (error) {
                        window.panelToast?.show('error', 'Detail pajak tidak bisa dibuka.');
                    }
                    return;
                }

                const closeButton = event.target.closest('[data-tax-detail-close="true"]');
                if (closeButton) {
                    closeTaxDetailModal();
                    return;
                }

                const modal = document.getElementById('taxDetailModal');
                if (modal && event.target === modal) {
                    closeTaxDetailModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeTaxDetailModal();
                }
            });

            window.addEventListener('panel:fragment-refreshed', function (event) {
                if (event.detail?.selector === '#ajaxTaxFragment') {
                    closeTaxDetailModal();
                }
            });
        })();
    </script>
@endsection
