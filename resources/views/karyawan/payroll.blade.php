@extends('layouts.panel', ['panel' => 'karyawan', 'pageTitle' => 'Payroll Saya'])

@section('styles')
    .portal-page { display:grid; gap:18px; }
    .stats-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
    .stat-card,.panel-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .stat-card { padding:18px; }
    .stat-label { font-size:12px; color:#64748b; margin-bottom:6px; }
    .stat-value { font-size:24px; font-weight:800; color:#0f172a; }
    .stat-note { margin-top:8px; font-size:12px; color:#475569; }
    .panel-head { padding:16px 18px; border-bottom:1px solid #eef2f7; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
    .panel-title { margin:0; font-size:16px; font-weight:700; color:#0f172a; }
    .panel-subtitle { margin-top:4px; font-size:12px; color:#64748b; }
    .filter-form { display:flex; gap:12px; align-items:end; flex-wrap:wrap; }
    .filter-group label { display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:6px; }
    .panel-body { padding:18px; }
    .badge-soft { display:inline-flex; align-items:center; justify-content:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; }
    .badge-success { background:#dcfce7; color:#166534; }
    .badge-warning { background:#fef3c7; color:#92400e; }
    .badge-info { background:#dbeafe; color:#1d4ed8; }
    .action-buttons { display:flex; align-items:center; justify-content:center; gap:8px; flex-wrap:wrap; }
    .payroll-breakdown-modal-dialog { width:min(860px, calc(100vw - 32px)); max-width:860px; }
    .payroll-breakdown-modal-content { max-height:calc(100vh - 40px); display:flex; flex-direction:column; }
    .payroll-breakdown-modal-body { overflow:auto; padding:20px; display:grid; gap:16px; }
    .payroll-breakdown-summary { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .payroll-breakdown-summary-item { border:1px solid #e2e8f0; border-radius:14px; background:#f8fafc; padding:14px 16px; }
    .payroll-breakdown-summary-label { display:block; margin-bottom:6px; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }
    .payroll-breakdown-summary-value { font-size:18px; font-weight:800; color:#065f46; }
    .payroll-breakdown-sections { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
    .payroll-breakdown-card { border:1px solid #e2e8f0; border-radius:14px; background:#fff; padding:16px; display:grid; gap:12px; }
    .payroll-breakdown-card:last-child { grid-column:1 / -1; }
    .payroll-breakdown-card h4 { margin:0; font-size:13px; font-weight:800; color:#0f172a; }
    .payroll-breakdown-list { display:grid; gap:10px; }
    .payroll-breakdown-item { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; font-size:12px; padding-bottom:8px; border-bottom:1px dashed #e2e8f0; }
    .payroll-breakdown-item:last-child { border-bottom:none; padding-bottom:0; }
    .payroll-breakdown-item-head { display:grid; gap:3px; min-width:0; }
    .payroll-breakdown-item-label { color:#64748b; font-weight:600; }
    .payroll-breakdown-item-note { font-size:11px; color:#94a3b8; line-height:1.45; }
    .payroll-breakdown-item-value { text-align:right; font-weight:700; color:#0f172a; }
    .payroll-breakdown-item-value.is-positive { color:#065f46; }
    .payroll-breakdown-item-value.is-negative { color:#b91c1c; }
    .payroll-breakdown-empty { font-size:12px; color:#94a3b8; }
    .payroll-breakdown-subtitle { margin-top:4px; font-size:12px; color:#64748b; }
    @media (max-width:992px) { .stats-grid { grid-template-columns:1fr 1fr; } }
    @media (max-width:700px) {
        .payroll-breakdown-summary,
        .payroll-breakdown-sections { grid-template-columns:1fr; }
    }
    @media (max-width:640px) { .stats-grid { grid-template-columns:1fr; } }
@endsection

@section('content')
    <div class="portal-page">
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">Periode</div><div class="stat-value">{{ $stats['periode'] }}</div><div class="stat-note">Tahun {{ $year }}</div></div>
            <div class="stat-card"><div class="stat-label">Total Gaji</div><div class="stat-value">Rp {{ number_format($stats['total_gaji'], 0, ',', '.') }}</div><div class="stat-note">Akumulasi tahun berjalan</div></div>
            <div class="stat-card"><div class="stat-label">Total PPh21</div><div class="stat-value">Rp {{ number_format($stats['total_pph21'], 0, ',', '.') }}</div><div class="stat-note">Potongan pajak</div></div>
            <div class="stat-card"><div class="stat-label">Total THR</div><div class="stat-value">Rp {{ number_format($stats['total_thr'], 0, ',', '.') }}</div><div class="stat-note">Akumulasi THR</div></div>
        </div>

        <div class="panel-card">
            <div class="panel-head">
                <div>
                    <h3 class="panel-title">Riwayat Payroll</h3>
                    <div class="panel-subtitle">{{ $employee->nama_lengkap }} &middot; {{ $employee->nik }}</div>
                </div>
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>Tahun</label>
                        <select name="tahun" class="filter-input">
                            @foreach (range(now()->year, max(now()->year - 5, 2024)) as $optionYear)
                                <option value="{{ $optionYear }}" @selected($year === $optionYear)>{{ $optionYear }}</option>
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
                    <div class="button-group"><button type="submit" class="btn btn-primary">Filter</button></div>
                </form>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table-bordered">
                        <thead>
                            <tr class="table-header">
                                <th width="50">No</th>
                                <th width="150">Periode</th>
                                <th width="140">Status</th>
                                <th width="140">THR</th>
                                <th width="140">PPh21</th>
                                <th width="170">Total Gaji</th>
                                <th width="180">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($salaryRows as $index => $row)
                                <tr>
                                    <td class="text-center">{{ ($salaryRows->firstItem() ?? 1) + $index }}</td>
                                    <td>{{ optional($row->bulan)->translatedFormat('F Y') }}</td>
                                    <td class="text-center">
                                        <span class="badge-soft {{ $row->is_finalized ? 'badge-success' : 'badge-warning' }}">{{ $row->is_finalized ? 'Final' : 'Draft' }}</span>
                                    </td>
                                    <td class="text-right">Rp {{ number_format((float) ($row->thr ?? 0), 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format((float) ($row->pph21 ?? 0), 0, ',', '.') }}</td>
                                    <td class="text-right"><strong>Rp {{ number_format((float) ($row->total_gaji ?? 0), 0, ',', '.') }}</strong></td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <button
                                                type="button"
                                                class="btn btn-secondary"
                                                data-payroll-breakdown='@json($row->portal_breakdown_payload)'
                                                aria-label="Lihat detail perhitungan gaji">
                                                Detail
                                            </button>
                                            <a href="{{ route('karyawan.payroll.slip', $row) }}" class="btn btn-info">Slip</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty-state">Belum ada data payroll.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $salaryRows->links() }}
            </div>
        </div>
    </div>

    <div id="portalPayrollBreakdownModal" class="modal" aria-hidden="true">
        <div class="modal-dialog payroll-breakdown-modal-dialog">
            <div class="modal-content payroll-breakdown-modal-content" role="dialog" aria-modal="true" aria-labelledby="portalPayrollBreakdownTitle">
                <div class="modal-header">
                    <div>
                        <h3 id="portalPayrollBreakdownTitle">Rincian Payroll</h3>
                        <div id="portalPayrollBreakdownSubtitle" class="payroll-breakdown-subtitle">-</div>
                    </div>
                    <button type="button" class="modal-close" data-payroll-breakdown-close="true">&times;</button>
                </div>
                <div class="modal-body payroll-breakdown-modal-body">
                    <div class="payroll-breakdown-summary">
                        <div class="payroll-breakdown-summary-item">
                            <span id="portalPayrollBreakdownSummaryLabel" class="payroll-breakdown-summary-label">Gaji Diterima</span>
                            <div id="portalPayrollBreakdownSummaryAmount" class="payroll-breakdown-summary-value">Rp 0</div>
                        </div>
                    </div>
                    <div id="portalPayrollBreakdownSections" class="payroll-breakdown-sections"></div>
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
        (function () {
            const modal = document.getElementById('portalPayrollBreakdownModal');
            const title = document.getElementById('portalPayrollBreakdownTitle');
            const subtitle = document.getElementById('portalPayrollBreakdownSubtitle');
            const summaryLabel = document.getElementById('portalPayrollBreakdownSummaryLabel');
            const summaryAmount = document.getElementById('portalPayrollBreakdownSummaryAmount');
            const sectionsRoot = document.getElementById('portalPayrollBreakdownSections');

            function formatMoney(value) {
                const amount = Number(value || 0);
                const prefix = amount < 0 ? '- Rp ' : 'Rp ';
                return prefix + Math.abs(amount).toLocaleString('id-ID');
            }

            function renderSections(sections) {
                if (!sectionsRoot) {
                    return;
                }

                sectionsRoot.innerHTML = (sections || []).map((section) => {
                    const items = Array.isArray(section.items) ? section.items : [];
                    const tone = section.tone || 'neutral';
                    const content = items.length
                        ? items.map((item) => {
                            const amount = Number(item.amount || 0);
                            const hasText = Object.prototype.hasOwnProperty.call(item, 'text');
                            const displayValue = hasText ? (item.text || '-') : formatMoney(amount);
                            let amountClass = '';

                            if (!hasText) {
                                if (tone === 'expense') {
                                    amountClass = amount > 0 ? 'is-negative' : (amount < 0 ? 'is-positive' : '');
                                } else {
                                    amountClass = amount > 0 ? 'is-positive' : (amount < 0 ? 'is-negative' : '');
                                }
                            }

                            return `
                                <div class="payroll-breakdown-item">
                                    <div class="payroll-breakdown-item-head">
                                        <div class="payroll-breakdown-item-label">${item.label || '-'}</div>
                                        ${item.note ? `<div class="payroll-breakdown-item-note">${item.note}</div>` : ''}
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

            function openModal(payload) {
                if (!modal || !payload) {
                    return;
                }

                if (title) title.textContent = payload.title || 'Rincian Payroll';
                if (subtitle) subtitle.textContent = payload.subtitle || '-';
                if (summaryLabel) summaryLabel.textContent = payload.summary_label || 'Gaji Diterima';
                if (summaryAmount) summaryAmount.textContent = formatMoney(payload.summary_amount || 0);

                renderSections(payload.sections || []);
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal() {
                if (!modal) {
                    return;
                }

                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            }

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest('[data-payroll-breakdown]');
                if (trigger) {
                    try {
                        openModal(JSON.parse(trigger.dataset.payrollBreakdown || '{}'));
                    } catch (error) {
                        console.error(error);
                    }
                    return;
                }

                if (event.target === modal || event.target.closest('[data-payroll-breakdown-close="true"]')) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal?.classList.contains('show')) {
                    closeModal();
                }
            });
        })();
    </script>
@endsection
