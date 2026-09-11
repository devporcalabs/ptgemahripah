@extends('layouts.panel', ['panel' => 'karyawan', 'pageTitle' => 'Pajak Saya'])

@section('styles')
    .portal-page { display:grid; gap:18px; }
    .stats-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
    .stat-card,.panel-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .stat-card { padding:18px; }
    .stat-label { font-size:12px; color:#64748b; margin-bottom:6px; }
    .stat-value { font-size:24px; font-weight:800; color:#0f172a; }
    .stat-note { margin-top:8px; font-size:12px; color:#475569; }
    .portal-grid { display:grid; grid-template-columns:minmax(0, 1.1fr) minmax(320px, 0.9fr); gap:18px; }
    .panel-head { padding:16px 18px; border-bottom:1px solid #eef2f7; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
    .panel-title { margin:0; font-size:16px; font-weight:700; color:#0f172a; }
    .panel-subtitle { margin-top:4px; font-size:12px; color:#64748b; }
    .panel-body { padding:18px; }
    .filter-form { display:flex; gap:12px; align-items:end; flex-wrap:wrap; }
    .filter-group label { display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:6px; }
    .info-list { display:grid; gap:12px; }
    .info-item { padding:12px 14px; border:1px solid #e2e8f0; border-radius:12px; }
    .info-label { font-size:12px; color:#64748b; margin-bottom:4px; }
    .info-value { font-size:14px; font-weight:700; color:#0f172a; }
    .info-note { margin-top:6px; font-size:12px; color:#475569; }
    .badge-soft { display:inline-flex; align-items:center; justify-content:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; }
    .badge-success { background:#dcfce7; color:#166534; }
    .badge-warning { background:#fef3c7; color:#92400e; }
    .badge-info { background:#dbeafe; color:#1d4ed8; }
    .badge-danger { background:#fee2e2; color:#b91c1c; }
    .issue-list { margin:10px 0 0; padding-left:18px; color:#475569; font-size:12px; }
    @media (max-width:992px) { .stats-grid, .portal-grid { grid-template-columns:1fr; } }
@endsection

@section('content')
    <div class="portal-page">
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">Periode</div><div class="stat-value">{{ $summary['periods'] }}</div><div class="stat-note">Riwayat pajak tahun {{ $year }}</div></div>
            <div class="stat-card"><div class="stat-label">Gross Pajak</div><div class="stat-value">Rp {{ number_format($summary['gross_total'], 0, ',', '.') }}</div><div class="stat-note">Akumulasi bruto pajak</div></div>
            <div class="stat-card"><div class="stat-label">Potong PPh21</div><div class="stat-value">Rp {{ number_format($summary['pph21_total'], 0, ',', '.') }}</div><div class="stat-note">Akumulasi potongan berjalan</div></div>
            <div class="stat-card"><div class="stat-label">Refund PPh21</div><div class="stat-value">Rp {{ number_format($summary['refund_total'], 0, ',', '.') }}</div><div class="stat-note">Koreksi pajak minus</div></div>
        </div>

        <div class="portal-grid">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h3 class="panel-title">Riwayat PPh21 Bulanan</h3>
                        <div class="panel-subtitle">{{ $employee->nama_lengkap }} · {{ $employee->nik }}</div>
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
                                    <th width="140">Periode</th>
                                    <th width="130">Status</th>
                                    <th width="140">Metode</th>
                                    <th width="140">TER / PTKP</th>
                                    <th width="170">Gross Pajak</th>
                                    <th width="150">PPh21</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($taxRows as $index => $row)
                                    @php
                                        $snapshot = $row->portal_tax_snapshot ?? [];
                                        $pph21Amount = (float) ($snapshot['pph21_amount'] ?? $row->pph21 ?? 0);
                                        $method = match ((string) ($snapshot['pph21_method'] ?? 'off')) {
                                            'ter' => 'TER Bulanan',
                                            'annual_reconcile' => 'Final Tahunan',
                                            'annualized' => 'Estimasi Tahunan',
                                            default => 'Nonaktif',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ ($taxRows->firstItem() ?? 1) + $index }}</td>
                                        <td>{{ optional($row->bulan)->translatedFormat('F Y') }}</td>
                                        <td class="text-center">
                                            <span class="badge-soft {{ $row->is_finalized ? 'badge-success' : 'badge-warning' }}">{{ $row->is_finalized ? 'Final' : 'Draft' }}</span>
                                        </td>
                                        <td>{{ $method }}</td>
                                        <td>
                                            {{ strtoupper((string) ($snapshot['pph21_ter_category'] ?? '-')) }}
                                            <div class="muted-sm">{{ $snapshot['karyawan']['status_ptkp'] ?? $employee->status_ptkp }}</div>
                                        </td>
                                        <td class="text-right">Rp {{ number_format((float) ($snapshot['pph21_gross_basis'] ?? 0), 0, ',', '.') }}</td>
                                        <td class="text-right {{ $pph21Amount < 0 ? 'text-success' : '' }}">
                                            {{ $pph21Amount < 0 ? '-' : '' }}Rp {{ number_format(abs($pph21Amount), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="empty-state">Belum ada data pajak payroll.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $taxRows->links() }}
                </div>
            </div>

            <div class="panel-card">
                <div class="panel-head">
                    <h3 class="panel-title">Ringkasan Bukti Potong A1</h3>
                    <div class="panel-subtitle">Draft tahunan untuk tahun {{ $year }}</div>
                </div>
                <div class="panel-body">
                    @if ($annualDraft)
                        <div class="info-list">
                            <div class="info-item">
                                <div class="info-label">Status Draft</div>
                                <div class="info-value">
                                    <span class="badge-soft {{ $annualDraft['ready'] ? 'badge-success' : 'badge-warning' }}">
                                        {{ $annualDraft['ready'] ? 'Siap Ditinjau' : 'Perlu Review' }}
                                    </span>
                                </div>
                                <div class="info-note">{{ $annualDraft['withholding_status_label'] ?? ($annualDraft['djp_payload']['StatusOfWithholding'] ?? '-') }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Periode Pajak</div>
                                <div class="info-value">{{ $annualDraft['tax_period_label'] ?? (($annualDraft['tax_period_start'] ?? 1).'-'.($annualDraft['tax_period_end'] ?? 12)) }}</div>
                                <div class="info-note">Status PTKP {{ $annualDraft['employee']['status_ptkp'] ?? $employee->status_ptkp }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Bruto Pajak Tahunan</div>
                                <div class="info-value">Rp {{ number_format((float) ($annualDraft['annual_gross_basis'] ?? 0), 0, ',', '.') }}</div>
                                <div class="info-note">Akumulasi bruto kena pajak tahun berjalan.</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">PPh21 Tahunan</div>
                                <div class="info-value">Rp {{ number_format((float) ($annualDraft['pph21_annual_tax'] ?? 0), 0, ',', '.') }}</div>
                                <div class="info-note">PPh21 YTD Rp {{ number_format((float) ($annualDraft['pph21_ytd'] ?? 0), 0, ',', '.') }}</div>
                            </div>
                            @if (! empty($annualDraft['validation_errors']) || ! empty($annualDraft['validation_warnings']))
                                <div class="info-item">
                                    <div class="info-label">Catatan Draft</div>
                                    <div class="info-value">Perlu perhatian</div>
                                    <ul class="issue-list">
                                        @foreach (($annualDraft['validation_errors'] ?? []) as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                        @foreach (($annualDraft['validation_warnings'] ?? []) as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="empty-state">Belum ada draft bukti potong untuk tahun {{ $year }}.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
