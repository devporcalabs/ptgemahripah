@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Bukti Potong A1'])

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
        box-shadow:0 1px 2px rgba(0,0,0,0.05);
    }
    .stat-card .stat-card-body { padding:0; width:100%; display:flex; justify-content:space-between; align-items:center; gap:12px; }
    .stat-label { font-size:12px; color:#64748B; margin-bottom:8px; }
    .stat-value { font-size:22px; font-weight:800; color:#0F172A; word-break:break-word; }
    .stat-icon {
        width:38px; height:38px; border-radius:12px; display:flex; align-items:center; justify-content:center;
        flex-shrink:0; font-size:16px;
    }
    .stat-card.year { border-left:4px solid #3B82F6; }
    .stat-card.total { border-left:4px solid #8B5CF6; }
    .stat-card.ready { border-left:4px solid #10B981; }
    .stat-card.issue { border-left:4px solid #EF4444; }
    .stat-card.tax { border-left:4px solid #F59E0B; }
    .stat-card.year .stat-icon { background:#DBEAFE; color:#2563EB; }
    .stat-card.total .stat-icon { background:#EDE9FE; color:#7C3AED; }
    .stat-card.ready .stat-icon { background:#D1FAE5; color:#065F46; }
    .stat-card.issue .stat-icon { background:#FEE2E2; color:#DC2626; }
    .stat-card.tax .stat-icon { background:#FEF3C7; color:#D97706; }
    .page-header {
        margin-bottom:20px; display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap;
    }
    .page-header h3 { margin:0; }
    .page-header p { margin:4px 0 0; color:#64748B; font-size:12px; }
    .filter-toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .filter-select {
        padding:8px 12px; border:1px solid #E2E8F0; border-radius:8px; font-size:13px;
        min-width:180px; font-family:inherit; background:#FFFFFF; height:38px;
    }
    .filter-toolbar .btn { height:38px; display:inline-flex; align-items:center; gap:6px; }
    .table-card .card-body { padding:20px; }
    .table-card .card-header {
        display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;
    }
    .table-header-left, .table-header-right {
        display:flex; align-items:center; gap:10px; flex-wrap:wrap;
    }
    .table-header-right { margin-left:auto; justify-content:flex-end; }
    .export-buttons { display:flex; gap:8px; flex-wrap:wrap; }
    .btn-export-xml, .btn-export-csv {
        padding:8px 16px; border-radius:8px; font-size:12px; text-decoration:none;
        display:inline-flex; align-items:center; gap:6px; color:white;
    }
    .btn-export-xml { background:#2563EB; }
    .btn-export-csv { background:#10B981; }
    .btn-export-xml:hover, .btn-export-csv:hover { color:white; opacity:0.92; }
    .table-wrap {
        width:100%; overflow-x:auto; overflow-y:hidden; -webkit-overflow-scrolling:touch;
    }
    .a1-table {
        width:100%; min-width:1500px; border-collapse:separate; border-spacing:0; font-size:12px;
    }
    .a1-table th, .a1-table td {
        border:1px solid #D1D5DB; padding:10px 8px; vertical-align:top; white-space:nowrap;
    }
    .a1-table th { background:#F8FAFC; color:#065F46; font-weight:600; text-align:left; }
    .a1-table td { color:#334155; }
    .subtext { display:block; margin-top:4px; font-size:11px; color:#64748B; white-space:normal; min-width:150px; }
    .status-badge {
        display:inline-flex; align-items:center; border-radius:999px; padding:4px 9px; font-size:11px; font-weight:700;
    }
    .status-badge.ready { background:#DCFCE7; color:#166534; }
    .status-badge.issue { background:#FEE2E2; color:#B91C1C; }
    .note-list { display:grid; gap:4px; margin-top:6px; }
    .note-pill {
        display:inline-flex; align-items:center; gap:6px; padding:5px 8px; border-radius:10px; font-size:11px; line-height:1.5;
    }
    .note-pill.error { background:#FEF2F2; color:#B91C1C; }
    .note-pill.warning { background:#FFFBEB; color:#B45309; }
    .detail-modal-dialog { width:min(940px, calc(100vw - 32px)); max-width:940px; }
    .detail-modal-content { max-height:calc(100vh - 48px); display:flex; flex-direction:column; }
    .detail-modal-body { overflow:auto; padding:20px; }
    .detail-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
    .detail-card { border:1px solid #E2E8F0; border-radius:14px; padding:16px; background:#FFFFFF; }
    .detail-card h4 { margin:0 0 12px; font-size:13px; font-weight:800; color:#0F172A; }
    .detail-list { display:grid; gap:10px; }
    .detail-item {
        display:flex; justify-content:space-between; gap:12px; align-items:flex-start; font-size:12px;
        border-bottom:1px dashed #E2E8F0; padding-bottom:8px;
    }
    .detail-item:last-child { border-bottom:none; padding-bottom:0; }
    .detail-label { color:#64748B; font-weight:600; }
    .detail-value { color:#0F172A; font-weight:700; text-align:right; }
    .legend-card {
        background:white; border-radius:12px; padding:12px 20px; margin-top:20px;
        display:flex; flex-wrap:wrap; gap:20px; box-shadow:0 1px 2px rgba(0,0,0,0.05);
    }
    .legend-item { display:flex; align-items:center; gap:8px; font-size:11px; color:#334155; }
    .legend-badge { width:16px; height:16px; border-radius:4px; display:inline-block; }
    @media (max-width: 900px) {
        .stats-grid { grid-template-columns:repeat(2, 1fr); }
        .filter-select { width:100%; min-width:0; }
        .detail-grid { grid-template-columns:1fr; }
        .table-header-right { margin-left:0; justify-content:flex-start; }
    }
    @media (max-width: 640px) {
        .stats-grid { grid-template-columns:1fr; }
    }
@endsection

@section('content')
    <div id="ajaxA1Fragment">
        @php
            $exportBaseQuery = [
                'tahun' => $year,
                'status' => $statusFilter,
                'q' => $search,
                'per_page' => $perPage,
            ];
        @endphp

        <div class="stats-grid">
            <div class="stat-card year">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">{{ $year }}</div>
                        <div class="stat-label">Tahun Pajak</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-calendar-days"></i></div>
                </div>
            </div>
            <div class="stat-card total">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">{{ number_format($summary['total_rows']) }}</div>
                        <div class="stat-label">Total Draft</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="stat-card ready">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">{{ number_format($summary['ready_rows']) }}</div>
                        <div class="stat-label">Siap Export</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
                </div>
            </div>
            <div class="stat-card issue">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">{{ number_format($summary['issue_rows']) }}</div>
                        <div class="stat-label">Perlu Review</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-triangle-exclamation"></i></div>
                </div>
            </div>
            <div class="stat-card tax">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">Rp {{ number_format($summary['annual_tax_total'], 0, ',', '.') }}</div>
                        <div class="stat-label">PPh21 Tahunan</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                </div>
            </div>
        </div>

        <div class="page-header">
            <div>
                <h3 class="page-subtitle">Draft Bukti Potong 1721-A1</h3>
                <p class="page-description">Halaman ini menyiapkan draft BPA1 dari payroll tahunan dan bisa export ke CSV/XML Coretax DJP.</p>
            </div>
            <form method="GET" action="{{ route('admin.bukti-potong-a1') }}" class="filter-toolbar" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxA1Fragment">
                <select name="tahun" class="filter-select">
                    @foreach (range(now()->year + 1, max(2020, now()->year - 5)) as $yearOption)
                        <option value="{{ $yearOption }}" @selected((int) $year === (int) $yearOption)>{{ $yearOption }}</option>
                    @endforeach
                </select>
                <select name="status" class="filter-select">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" class="filter-select" value="{{ $search }}" placeholder="Cari nama, NIK, jabatan, departemen">
                <select name="per_page" class="filter-select">
                    @foreach ([15, 25, 50, 100] as $limit)
                        <option value="{{ $limit }}" @selected($perPage === $limit)>{{ $limit }} / halaman</option>
                    @endforeach
                </select>
                <a href="{{ route('admin.bukti-potong-a1') }}" class="btn btn-secondary" data-ajax-link="true" data-refresh-target="#ajaxA1Fragment">
                    <i class="fas fa-rotate-left"></i> Reset
                </a>
            </form>
        </div>

        <div class="card table-card">
            <div class="card-header">
                <div class="table-header-left">
                    <h3><i class="fas fa-file-signature" style="color:#2563EB; margin-right:8px;"></i>Daftar Draft BPA1 Tahun {{ $year }}</h3>
                </div>
                <div class="table-header-right">
                    <span class="total-data">{{ method_exists($a1Rows, 'count') ? $a1Rows->count() : count($a1Rows) }} baris tampil</span>
                    <div class="export-buttons">
                        <a href="{{ route('admin.bukti-potong-a1.export', $exportBaseQuery + ['type' => 'csv']) }}" class="btn-export-csv">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                        <a href="{{ route('admin.bukti-potong-a1.export', $exportBaseQuery + ['type' => 'xml']) }}" class="btn-export-xml">
                            <i class="fas fa-code"></i> Export XML
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-wrap table-responsive" data-fragment-loading-scope>
                    <table class="a1-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIK</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Masa Pajak</th>
                                <th>Status Draft</th>
                                <th>Gross Basis Tahunan</th>
                                <th>PPh21 Tahunan</th>
                                <th>NPWP / NIK Pajak</th>
                                <th>Catatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($a1Rows as $index => $row)
                                @php
                                    $detailPayload = $row['detail_payload'];
                                    $statusClass = $row['ready'] ? 'ready' : 'issue';
                                @endphp
                                <tr>
                                    <td class="text-center">{{ (method_exists($a1Rows, 'firstItem') ? ($a1Rows->firstItem() ?? 1) : 1) + $index }}</td>
                                    <td>{{ $row['employee']['nik'] }}</td>
                                    <td>
                                        <strong>{{ $row['employee']['nama_lengkap'] }}</strong>
                                        <span class="subtext">{{ $row['employee']['departemen'] ?: '-' }}</span>
                                    </td>
                                    <td>{{ $row['employee']['jabatan'] ?: '-' }}</td>
                                    <td>
                                        {{ sprintf('%02d', $row['tax_period_start']) }} - {{ sprintf('%02d', $row['tax_period_end']) }}/{{ $row['year'] }}
                                        <span class="subtext">{{ $row['status_of_withholding'] }} · Tgl potong {{ \Carbon\Carbon::parse($row['withholding_date'])->format('d/m/Y') }}</span>
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $statusClass }}">{{ $row['ready_label'] }}</span>
                                        <span class="subtext">{{ $row['last_payroll_status'] }}</span>
                                    </td>
                                    <td class="text-right">Rp {{ number_format($row['annual_gross_basis'], 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($row['pph21_annual_tax'], 0, ',', '.') }}</td>
                                    <td>
                                        {{ $row['employee']['npwp'] ?: '-' }}
                                        <span class="subtext">{{ $row['employee']['status_ptkp'] }}</span>
                                    </td>
                                    <td>
                                        <div class="note-list">
                                            @foreach (array_slice($row['validation_errors'], 0, 2) as $error)
                                                <span class="note-pill error"><i class="fas fa-circle-exclamation"></i>{{ $error }}</span>
                                            @endforeach
                                            @if (empty($row['validation_errors']) && empty($row['validation_warnings']))
                                                <span class="note-pill" style="background:#F8FAFC; color:#475569;"><i class="fas fa-circle-info"></i>Siap mengikuti skema XML DJP.</span>
                                            @endif
                                            @foreach (array_slice($row['validation_warnings'], 0, 1) as $warning)
                                                <span class="note-pill warning"><i class="fas fa-triangle-exclamation"></i>{{ $warning }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-secondary"
                                            data-a1-detail="true"
                                            data-a1='@json($detailPayload)'
                                        >
                                            <i class="fas fa-circle-info"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">Belum ada draft BPA1 untuk tahun pajak ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($a1Rows, 'links'))
                    <div style="margin-top:16px;">
                        {{ $a1Rows->links('partials.pagination-ajax', ['target' => '#ajaxA1Fragment']) }}
                    </div>
                @endif
            </div>
        </div>

        <div class="legend-card">
            <div class="legend-item">
                <span class="legend-badge" style="background:#DCFCE7;"></span>
                <span>Siap Export berarti identitas pemotong terisi dan payroll masa pajak akhir sudah menghasilkan PPh21 final tahunan.</span>
            </div>
            <div class="legend-item">
                <span class="legend-badge" style="background:#FEE2E2;"></span>
                <span>Perlu Review berarti ada data wajib yang belum lengkap atau Desember / bulan resign belum membentuk rekonsiliasi final.</span>
            </div>
            <div class="legend-item">
                <span class="legend-badge" style="background:#FEF3C7;"></span>
                <span>Koreksi payroll minus tahunan tidak otomatis dipetakan ke kolom khusus DJP, jadi sistem menandainya sebagai review manual.</span>
            </div>
        </div>

        <div id="a1DetailModal" class="modal" aria-hidden="true">
            <div class="modal-dialog detail-modal-dialog">
                <div class="modal-content detail-modal-content">
                    <div class="modal-header">
                        <h3 id="a1DetailTitle">Detail Draft BPA1</h3>
                        <button type="button" class="modal-close" data-a1-detail-close="true">&times;</button>
                    </div>
                    <div class="detail-modal-body">
                        <div class="detail-grid">
                            <div class="detail-card">
                                <h4>Identitas Pegawai</h4>
                                <div class="detail-list" id="a1DetailEmployee"></div>
                            </div>
                            <div class="detail-card">
                                <h4>Identitas Pemotong</h4>
                                <div class="detail-list" id="a1DetailCompany"></div>
                            </div>
                            <div class="detail-card">
                                <h4>Masa Pajak</h4>
                                <div class="detail-list" id="a1DetailPeriod"></div>
                            </div>
                            <div class="detail-card">
                                <h4>Komponen BPA1</h4>
                                <div class="detail-list" id="a1DetailComponents"></div>
                            </div>
                        </div>
                        <div class="detail-grid" style="margin-top:16px;">
                            <div class="detail-card">
                                <h4>Validasi Wajib</h4>
                                <div class="detail-list" id="a1DetailErrors"></div>
                            </div>
                            <div class="detail-card">
                                <h4>Catatan Review</h4>
                                <div class="detail-list" id="a1DetailWarnings"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-a1-detail-close="true">Tutup</button>
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
                return amount === 0 ? 'Rp 0' : `Rp ${amount.toLocaleString('id-ID')}`;
            }

            function renderItems(target, items) {
                if (!target) {
                    return;
                }

                target.innerHTML = items.map(function (item) {
                    return `
                        <div class="detail-item">
                            <div class="detail-label">${item.label}</div>
                            <div class="detail-value">${item.value}</div>
                        </div>
                    `;
                }).join('');
            }

            function renderMessages(target, items, emptyText) {
                if (!target) {
                    return;
                }

                if (!Array.isArray(items) || items.length === 0) {
                    target.innerHTML = `
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">${emptyText}</div>
                        </div>
                    `;
                    return;
                }

                target.innerHTML = items.map(function (message, index) {
                    return `
                        <div class="detail-item">
                            <div class="detail-label">#${index + 1}</div>
                            <div class="detail-value">${message}</div>
                        </div>
                    `;
                }).join('');
            }

            function openA1DetailModal(payload) {
                const modal = document.getElementById('a1DetailModal');
                if (!modal) {
                    return;
                }

                document.getElementById('a1DetailTitle').textContent = `Detail Draft BPA1 — ${payload.employee?.nama_lengkap || '-'}`;

                renderItems(document.getElementById('a1DetailEmployee'), [
                    { label: 'NIK', value: payload.employee?.nik || '-' },
                    { label: 'Nama', value: payload.employee?.nama_lengkap || '-' },
                    { label: 'Jabatan', value: payload.employee?.jabatan || '-' },
                    { label: 'Departemen', value: payload.employee?.departemen || '-' },
                    { label: 'NPWP / NIK Pajak', value: payload.employee?.npwp || '-' },
                    { label: 'Status PTKP', value: payload.employee?.status_ptkp || 'TK/0' },
                ]);

                renderItems(document.getElementById('a1DetailCompany'), [
                    { label: 'Instansi', value: payload.company?.nama_instansi || '-' },
                    { label: 'NPWP Pemotong', value: payload.company?.npwp || '-' },
                    { label: 'ID TKU / NITKU', value: payload.company?.nitku || '-' },
                ]);

                renderItems(document.getElementById('a1DetailPeriod'), [
                    { label: 'Tahun Pajak', value: payload.period?.year || '-' },
                    { label: 'Masa Pajak', value: `${String(payload.period?.start_month || '').padStart(2, '0')} - ${String(payload.period?.end_month || '').padStart(2, '0')}` },
                    { label: 'Status Bukti Potong', value: payload.period?.withholding_status || '-' },
                    { label: 'Tanggal Potong', value: payload.period?.withholding_date || '-' },
                ]);

                renderItems(document.getElementById('a1DetailComponents'), [
                    { label: 'Gaji', value: money(payload.components?.salary) },
                    { label: 'Tunjangan / Lembur', value: money(payload.components?.other_benefit) },
                    { label: 'Asuransi / BPJS Ditanggung Perusahaan', value: money(payload.components?.insurance_paid_by_employer) },
                    { label: 'Bonus / THR / Koreksi Plus', value: money(payload.components?.bonus_thr) },
                    { label: 'Iuran Pensiun / JHT / JP Karyawan', value: money(payload.components?.retirement_contribution) },
                    { label: 'Gross Basis Tahunan', value: money(payload.components?.annual_gross_basis) },
                    { label: 'PPh21 Tahunan', value: money(payload.components?.pph21_annual_tax) },
                    { label: 'PPh21 Dipotong YTD', value: money(payload.components?.pph21_ytd) },
                    { label: 'Koreksi Minus Tahunan', value: money(payload.components?.negative_adjustment_total) },
                ]);

                renderMessages(document.getElementById('a1DetailErrors'), payload.validation_errors, 'Tidak ada error wajib.');
                renderMessages(document.getElementById('a1DetailWarnings'), payload.validation_warnings, 'Tidak ada catatan review.');

                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeA1DetailModal() {
                const modal = document.getElementById('a1DetailModal');
                if (!modal) {
                    return;
                }

                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            }

            document.addEventListener('click', function (event) {
                const openButton = event.target.closest('[data-a1-detail="true"]');
                if (openButton) {
                    try {
                        openA1DetailModal(JSON.parse(openButton.dataset.a1 || '{}'));
                    } catch (error) {
                        window.panelToast?.show('error', 'Detail draft BPA1 tidak bisa dibuka.');
                    }
                    return;
                }

                const closeButton = event.target.closest('[data-a1-detail-close="true"]');
                if (closeButton) {
                    closeA1DetailModal();
                    return;
                }

                const modal = document.getElementById('a1DetailModal');
                if (modal && event.target === modal) {
                    closeA1DetailModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeA1DetailModal();
                }
            });

            window.addEventListener('panel:fragment-refreshed', function (event) {
                if (event.detail?.selector === '#ajaxA1Fragment') {
                    closeA1DetailModal();
                }
            });
        })();
    </script>
@endsection
