@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Karyawan Resign'])

@php
    $formatCurrency = static fn (float $amount): string => 'Rp '.number_format($amount, 0, ',', '.');
@endphp

@section('styles')
    .resign-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 18px;
    }
    .resign-stat-card {
        border: 1px solid #E2E8F0;
        border-radius: 18px;
        background: #FFFFFF;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }
    .resign-stat-value {
        font-size: 28px;
        line-height: 1;
        font-weight: 800;
        color: #0F172A;
        margin-bottom: 6px;
    }
    .resign-stat-label {
        font-size: 12px;
        color: #64748B;
        font-weight: 700;
    }
    .resign-stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 16px;
        background: #FEF2F2;
        color: #DC2626;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .resign-meta {
        display: grid;
        gap: 4px;
        font-size: 12px;
        color: #64748B;
    }
    .resign-meta strong {
        color: #0F172A;
    }
    .clearance-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }
    .clearance-pill.is-draft {
        background: #E2E8F0;
        color: #334155;
    }
    .clearance-pill.is-process {
        background: #FEF3C7;
        color: #92400E;
    }
    .clearance-pill.is-done {
        background: #DCFCE7;
        color: #166534;
    }
    .clearance-mini {
        display: grid;
        gap: 5px;
        min-width: 160px;
    }
    .clearance-subtext {
        font-size: 11px;
        color: #64748B;
    }
    .resign-balance.positive {
        color: #B91C1C;
        font-weight: 800;
    }
    .resign-balance.zero {
        color: #166534;
        font-weight: 800;
    }
    .resign-table th:nth-child(9),
    .resign-table td:nth-child(9),
    .resign-table th:nth-child(10),
    .resign-table td:nth-child(10) {
        display: none;
    }
    .resign-table th:nth-child(8),
    .resign-table td:nth-child(8) {
        width: 140px;
    }
    .resign-table td:nth-child(8) .clearance-mini {
        min-width: 0;
        justify-items: center;
        text-align: center;
    }
    .resign-table th:last-child,
    .resign-table td:last-child {
        width: 220px;
    }
    .resign-action-stack {
        display: grid;
        gap: 8px;
    }
    .resign-action-row {
        display: flex;
        gap: 8px;
    }
    .resign-action-row.top > * {
        flex: 1 1 0;
    }
    .resign-action-row.top .btn,
    .resign-action-row.top form,
    .resign-action-row.top form .btn {
        width: 100%;
    }
    .resign-action-row.bottom {
        justify-content: center;
    }
    .resign-action-row.bottom .btn {
        min-width: 110px;
    }
    .resign-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }
    .resign-card-header-main {
        display: grid;
        gap: 4px;
    }
    .resign-card-subtitle {
        font-size: 11px;
        color: #64748B;
    }
    .resign-card-header-tools {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .resign-card-header-form {
        margin: 0;
        display: flex;
        align-items: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }
    .resign-card-header-form .filter-group {
        min-width: 160px;
    }
    .resign-card-header-form .filter-group label {
        font-size: 11px;
    }
    .resign-card-header-form .filter-input {
        min-width: 160px;
    }
    .modal-dialog-clearance {
        width: min(760px, calc(100vw - 32px));
        max-width: 760px;
        height: 700px;
        max-height: calc(100vh - 32px);
    }
    .modal-dialog-clearance .modal-content {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .modal-dialog-clearance form {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .modal-dialog-clearance .modal-body {
        flex: 1 1 auto;
        overflow-y: auto;
    }
    .clearance-employee-box {
        display: grid;
        gap: 6px;
        padding: 16px 18px;
        border: 1px solid #FECACA;
        border-radius: 16px;
        background: #FEF2F2;
        margin-bottom: 16px;
    }
    .clearance-employee-name {
        font-size: 16px;
        font-weight: 800;
        color: #991B1B;
    }
    .clearance-employee-meta {
        font-size: 12px;
        color: #7F1D1D;
    }
    .clearance-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }
    .clearance-summary-card {
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        background: #F8FAFC;
        padding: 14px 16px;
        display: grid;
        gap: 6px;
    }
    .clearance-summary-card-label {
        font-size: 11px;
        color: #64748B;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .clearance-summary-card-value {
        font-size: 15px;
        color: #0F172A;
        font-weight: 800;
    }
    .clearance-checklist {
        display: grid;
        gap: 12px;
    }
    .clearance-check-item {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 14px 16px;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        background: #FFFFFF;
    }
    .clearance-check-item.is-system {
        background: #F8FAFC;
        border-color: #CBD5E1;
    }
    .clearance-check-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin-top: 2px;
        accent-color: #16A34A;
        flex-shrink: 0;
    }
    .clearance-check-item input[type="checkbox"]:disabled {
        cursor: not-allowed;
        opacity: .9;
    }
    .clearance-check-copy {
        display: grid;
        gap: 4px;
    }
    .clearance-check-copy strong {
        font-size: 13px;
        color: #0F172A;
    }
    .clearance-check-copy span {
        font-size: 12px;
        color: #64748B;
        line-height: 1.55;
    }
    .clearance-lock-note {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        color: #475569;
        font-weight: 700;
    }
    .clearance-status-preview {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        padding: 14px 16px;
        border-radius: 16px;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        margin: 18px 0 16px;
    }
    .clearance-status-preview-copy {
        display: grid;
        gap: 4px;
    }
    .clearance-status-preview-copy strong {
        color: #0F172A;
        font-size: 13px;
    }
    .clearance-status-preview-copy span {
        color: #64748B;
        font-size: 12px;
    }
    @media (max-width: 1200px) {
        .resign-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 992px) {
        .resign-stats,
        .clearance-summary-grid {
            grid-template-columns: 1fr;
        }
        .resign-card-header-tools {
            width: 100%;
            margin-left: 0;
            justify-content: flex-start;
        }
    }
@endsection

@section('content')
    <div id="ajaxCrudFragment">
        <div class="page-header">
            <div>
                <h3 class="page-subtitle">Karyawan Resign</h3>
                <p class="page-description">Kelola arsip resign, status clearance, payroll akhir, dan penutupan administrasi.</p>
            </div>
        </div>

        <div class="resign-stats">
            <div class="resign-stat-card">
                <div>
                    <div class="resign-stat-value">{{ number_format((int) ($stats['total'] ?? 0), 0, ',', '.') }}</div>
                    <div class="resign-stat-label">Total Karyawan Resign</div>
                </div>
                <div class="resign-stat-icon"><i class="fas fa-users-slash"></i></div>
            </div>
            <div class="resign-stat-card">
                <div>
                    <div class="resign-stat-value">{{ number_format((int) ($stats['bulan_ini'] ?? 0), 0, ',', '.') }}</div>
                    <div class="resign-stat-label">Resign Bulan Ini</div>
                </div>
                <div class="resign-stat-icon"><i class="fas fa-calendar-xmark"></i></div>
            </div>
            <div class="resign-stat-card">
                <div>
                    <div class="resign-stat-value">{{ number_format((int) ($stats['tahun_ini'] ?? 0), 0, ',', '.') }}</div>
                    <div class="resign-stat-label">Resign Tahun Ini</div>
                </div>
                <div class="resign-stat-icon"><i class="fas fa-folder-minus"></i></div>
            </div>
            <div class="resign-stat-card">
                <div>
                    <div class="resign-stat-value">{{ number_format((int) ($stats['clearance_selesai'] ?? 0), 0, ',', '.') }}</div>
                    <div class="resign-stat-label">Clearance Selesai</div>
                </div>
                <div class="resign-stat-icon"><i class="fas fa-clipboard-check"></i></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header resign-card-header">
                <div class="resign-card-header-main">
                    <h3><i class="fas fa-users-slash" style="color:#065F46; margin-right:8px;"></i>Data Karyawan Resign</h3>
                    <span class="resign-card-subtitle">Filter, pantau clearance, dan kelola arsip resign karyawan.</span>
                </div>
                <div class="resign-card-header-tools">
                    <form method="GET" class="filter-form resign-card-header-form" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxCrudFragment">
                        <div class="filter-group">
                            <label>Cari Data</label>
                            <input type="search" name="q" class="filter-input" value="{{ $search ?? '' }}" placeholder="Cari NIK, nama, jabatan, alasan resign...">
                        </div>
                        <div class="filter-group">
                            <label>Tampil</label>
                            <select name="per_page" class="filter-input">
                                @foreach ([10, 25, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected(($perPage ?? 10) === $size)>{{ $size }} / halaman</option>
                                @endforeach
                            </select>
                        </div>
                        <a href="{{ route('admin.karyawan.resign') }}" class="btn-reset" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment">Reset</a>
                    </form>
                    <span class="total-data">Total: {{ $karyawanList->total() }} data</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table-bordered resign-table">
                    <thead>
                        <tr class="table-header">
                            <th width="50">No</th>
                            <th>NIK</th>
                            <th>Nama Lengkap</th>
                            <th>Jabatan / Departemen</th>
                            <th>Tanggal Resign</th>
                            <th>Masa Kerja</th>
                            <th>Alasan</th>
                            <th>Clearance</th>
                            <th>Payroll Akhir</th>
                            <th>Kasbon</th>
                            <th width="220">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($karyawanList as $index => $karyawan)
                            @php
                                $snapshot = $karyawan->clearance_snapshot ?? [];
                                $clearancePayload = [
                                    'update_url' => route('admin.karyawan.clearance.update', $karyawan),
                                    'employee_name' => $karyawan->nama_lengkap,
                                    'employee_nik' => $karyawan->nik,
                                    'employee_jabatan' => $karyawan->jabatan ?: '-',
                                    'employee_departemen' => $karyawan->departemen ?: '-',
                                    'tgl_resign' => $karyawan->tgl_resign?->format('d/m/Y') ?: '-',
                                    'payroll_exists' => (bool) ($snapshot['payroll_exists'] ?? false),
                                    'payroll_final' => (bool) ($snapshot['payroll_final'] ?? false),
                                    'payroll_month_label' => $snapshot['payroll_month_label'] ?? '-',
                                    'payroll_total' => (float) ($snapshot['payroll_total'] ?? 0),
                                    'kasbon_balance' => (float) ($snapshot['kasbon_balance'] ?? 0),
                                    'clearance_status_label' => $snapshot['clearance_status_label'] ?? $karyawan->clearance_status_label,
                                    'clearance_progress' => $snapshot['clearance_progress'] ?? $karyawan->clearance_progress_label,
                                    'clearance_payroll_final' => (bool) ($snapshot['clearance_payroll_final'] ?? $karyawan->clearance_payroll_final ?? false),
                                    'clearance_kasbon_resolved' => (bool) ($snapshot['clearance_kasbon_resolved'] ?? $karyawan->clearance_kasbon_resolved ?? false),
                                    'clearance_asset_returned' => (bool) ($karyawan->clearance_asset_returned ?? false),
                                    'clearance_access_revoked' => (bool) ($karyawan->clearance_access_revoked ?? false),
                                    'clearance_document_completed' => (bool) ($karyawan->clearance_document_completed ?? false),
                                    'clearance_notes' => $karyawan->clearance_notes ?? '',
                                ];
                            @endphp
                            <tr>
                                <td class="text-center">{{ ($karyawanList->firstItem() ?? 1) + $index }}</td>
                                <td>{{ $karyawan->nik }}</td>
                                <td>
                                    <strong>{{ $karyawan->nama_lengkap }}</strong>
                                </td>
                                <td>
                                    <div>{{ $karyawan->jabatan ?: '-' }}</div>
                                    <div class="resign-meta"><span>{{ $karyawan->departemen ?: '-' }}</span></div>
                                </td>
                                <td>
                                    <div><strong>{{ $karyawan->tgl_resign?->format('d/m/Y') ?: '-' }}</strong></div>
                                    <div class="resign-meta"><span>Join {{ $karyawan->tgl_join?->format('d/m/Y') ?: '-' }}</span></div>
                                </td>
                                <td>{{ $karyawan->masa_kerja_label }}</td>
                                <td>{{ $karyawan->alasan_resign ?: '-' }}</td>
                                <td>
                                    <div class="clearance-mini">
                                        <span class="clearance-pill {{ ($snapshot['clearance_status'] ?? $karyawan->clearance_status) === 'selesai' ? 'is-done' : (($snapshot['clearance_status'] ?? $karyawan->clearance_status) === 'proses' ? 'is-process' : 'is-draft') }}">
                                            <i class="fas {{ ($snapshot['clearance_status'] ?? $karyawan->clearance_status) === 'selesai' ? 'fa-circle-check' : (($snapshot['clearance_status'] ?? $karyawan->clearance_status) === 'proses' ? 'fa-spinner' : 'fa-clipboard') }}"></i>
                                            {{ $snapshot['clearance_status_label'] ?? $karyawan->clearance_status_label }}
                                        </span>
                                        <span class="clearance-subtext">{{ $snapshot['clearance_progress'] ?? $karyawan->clearance_progress_label }} checklist selesai</span>
                                    </div>
                                </td>
                                <td>
                                    @if ($snapshot['payroll_exists'] ?? false)
                                        <div class="clearance-mini">
                                            <span class="clearance-pill {{ ($snapshot['payroll_final'] ?? false) ? 'is-done' : 'is-process' }}">
                                                <i class="fas {{ ($snapshot['payroll_final'] ?? false) ? 'fa-receipt' : 'fa-hourglass-half' }}"></i>
                                                {{ ($snapshot['payroll_final'] ?? false) ? 'Sudah Final' : 'Belum Final' }}
                                            </span>
                                            <span class="clearance-subtext">{{ $snapshot['payroll_month_label'] ?? '-' }} · {{ $formatCurrency((float) ($snapshot['payroll_total'] ?? 0)) }}</span>
                                        </div>
                                    @else
                                        <div class="clearance-mini">
                                            <span class="clearance-pill is-draft"><i class="fas fa-file-circle-xmark"></i> Belum Dibuat</span>
                                            <span class="clearance-subtext">{{ $snapshot['payroll_month_label'] ?? '-' }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="clearance-mini">
                                        <span class="{{ (float) ($snapshot['kasbon_balance'] ?? 0) > 0 ? 'resign-balance positive' : 'resign-balance zero' }}">
                                            {{ $formatCurrency((float) ($snapshot['kasbon_balance'] ?? 0)) }}
                                        </span>
                                        <span class="clearance-subtext">{{ (float) ($snapshot['kasbon_balance'] ?? 0) > 0 ? 'Masih ada saldo kasbon aktif' : 'Sudah lunas' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="resign-action-stack">
                                        <div class="resign-action-row top">
                                            <a href="{{ route('admin.karyawan.show', ['karyawan' => $karyawan, 'from' => 'resign']) }}" class="btn btn-info">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                            <form method="POST" action="{{ route('admin.karyawan.reactivate', $karyawan) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Aktifkan kembali karyawan ini ke daftar karyawan aktif?" data-confirm-title="Aktifkan Kembali Karyawan" data-confirm-button="Ya, aktifkan" data-confirm-variant="success" data-confirm-icon="fa-user-check" data-confirm-subtitle="Data resign akan dibersihkan dan karyawan akan kembali aktif." data-confirm-note="Pastikan memang ingin membuka kembali akses kerja, absensi, dan payroll karyawan ini.">
                                                @csrf
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-rotate-left"></i> Aktifkan
                                                </button>
                                            </form>
                                        </div>
                                        <div class="resign-action-row bottom">
                                            <button type="button" class="btn btn-warning" data-clearance='@json($clearancePayload)' onclick="openClearanceModal(this)">
                                                <i class="fas fa-clipboard-check"></i> Clearance
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="empty-state">Belum ada data karyawan resign.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $karyawanList->links('partials.pagination-ajax', ['target' => '#ajaxCrudFragment']) }}
        </div>
    </div>

    <div id="clearanceModal" class="modal">
        <div class="modal-dialog modal-dialog-clearance">
            <div class="modal-content">
                <form method="POST" id="clearanceForm" action="#" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#clearanceModal">
                    @csrf
                    <div class="modal-header">
                        <h3>Clearance Resign</h3>
                        <button type="button" class="modal-close" onclick="closeClearanceModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="clearance-employee-box">
                            <div class="clearance-employee-name" id="clearanceEmployeeName">-</div>
                            <div class="clearance-employee-meta" id="clearanceEmployeeMeta">-</div>
                        </div>

                        <div class="clearance-summary-grid">
                            <div class="clearance-summary-card">
                                <div class="clearance-summary-card-label">Payroll Akhir</div>
                                <div class="clearance-summary-card-value" id="clearancePayrollSummary">-</div>
                                <div class="clearance-subtext" id="clearancePayrollSubtext">-</div>
                            </div>
                            <div class="clearance-summary-card">
                                <div class="clearance-summary-card-label">Saldo Kasbon</div>
                                <div class="clearance-summary-card-value" id="clearanceKasbonSummary">-</div>
                                <div class="clearance-subtext" id="clearanceKasbonSubtext">-</div>
                            </div>
                            <div class="clearance-summary-card">
                                <div class="clearance-summary-card-label">Status Saat Ini</div>
                                <div class="clearance-summary-card-value" id="clearanceStatusSummary">-</div>
                                <div class="clearance-subtext" id="clearanceProgressSummary">-</div>
                            </div>
                        </div>

                        <div class="clearance-checklist">
                            <label class="clearance-check-item is-system">
                                <input type="checkbox" name="clearance_payroll_final" value="1" disabled>
                                <div class="clearance-check-copy">
                                    <strong>Payroll Final Selesai</strong>
                                    <span>Diambil otomatis dari payroll bulan resign. Tidak bisa dicentang manual dari clearance.</span>
                                    <span class="clearance-lock-note"><i class="fas fa-lock"></i> Mengikuti status final payroll sistem</span>
                                </div>
                            </label>
                            <label class="clearance-check-item is-system">
                                <input type="checkbox" name="clearance_kasbon_resolved" value="1" disabled>
                                <div class="clearance-check-copy">
                                    <strong>Kasbon Sudah Beres</strong>
                                    <span>Diambil otomatis dari saldo kasbon aktif. Jika saldo masih ada, checklist ini tetap terbuka.</span>
                                    <span class="clearance-lock-note"><i class="fas fa-lock"></i> Mengikuti saldo kasbon sistem</span>
                                </div>
                            </label>
                            <label class="clearance-check-item">
                                <input type="checkbox" name="clearance_asset_returned" value="1">
                                <div class="clearance-check-copy">
                                    <strong>Aset Kantor Sudah Kembali</strong>
                                    <span>Contoh: laptop, HP operasional, seragam, kartu akses, atau perlengkapan kerja lain.</span>
                                </div>
                            </label>
                            <label class="clearance-check-item">
                                <input type="checkbox" name="clearance_access_revoked" value="1">
                                <div class="clearance-check-copy">
                                    <strong>Akses Sistem Sudah Ditutup</strong>
                                    <span>Termasuk akses login panel, RFID, WhatsApp, dan akun internal lain yang berkaitan dengan pekerjaan.</span>
                                </div>
                            </label>
                            <label class="clearance-check-item">
                                <input type="checkbox" name="clearance_document_completed" value="1">
                                <div class="clearance-check-copy">
                                    <strong>Dokumen Resign Lengkap</strong>
                                    <span>Surat resign, berita acara, administrasi HR, dan dokumen pendukung lainnya sudah lengkap.</span>
                                </div>
                            </label>
                        </div>

                        <div class="clearance-status-preview">
                            <div class="clearance-status-preview-copy">
                                <strong>Status Clearance</strong>
                                <span>Status dihitung dari checklist manual ditambah status payroll final dan kasbon dari sistem.</span>
                            </div>
                            <span id="clearanceStatusBadge" class="clearance-pill is-draft">Draft</span>
                        </div>

                        <div class="form-group">
                            <label>Catatan Clearance</label>
                            <textarea name="clearance_notes" id="clearanceNotesField" class="form-control" rows="5" placeholder="Catatan tambahan untuk payroll akhir, aset, kasbon, atau dokumen resign."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeClearanceModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Clearance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function getClearanceModal() {
            return document.getElementById('clearanceModal');
        }

        function getClearanceForm() {
            return document.getElementById('clearanceForm');
        }

        function parseClearancePayload(button) {
            try {
                return JSON.parse(button.dataset.clearance || '{}');
            } catch (error) {
                return {};
            }
        }

        function formatClearanceCurrency(value) {
            const amount = Number(value || 0);
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }

        function resolveDerivedClearanceStatus(form) {
            const flags = [
                form.querySelector('[name="clearance_payroll_final"]')?.checked,
                form.querySelector('[name="clearance_kasbon_resolved"]')?.checked,
                form.querySelector('[name="clearance_asset_returned"]')?.checked,
                form.querySelector('[name="clearance_access_revoked"]')?.checked,
                form.querySelector('[name="clearance_document_completed"]')?.checked,
            ];

            const completed = flags.filter(Boolean).length;

            if (completed === flags.length) {
                return {
                    value: 'selesai',
                    label: 'Selesai',
                    className: 'is-done',
                    progress: `${completed}/${flags.length} checklist selesai`,
                };
            }

            if (completed > 0) {
                return {
                    value: 'proses',
                    label: 'Proses',
                    className: 'is-process',
                    progress: `${completed}/${flags.length} checklist selesai`,
                };
            }

            return {
                value: 'draft',
                label: 'Draft',
                className: 'is-draft',
                progress: `0/${flags.length} checklist selesai`,
            };
        }

        function updateClearanceStatusPreview() {
            const form = getClearanceForm();
            const badge = document.getElementById('clearanceStatusBadge');
            const progressTarget = document.getElementById('clearanceProgressSummary');

            if (!form || !badge || !progressTarget) {
                return;
            }

            const state = resolveDerivedClearanceStatus(form);
            badge.className = `clearance-pill ${state.className}`;
            badge.textContent = state.label;
            progressTarget.textContent = state.progress;
        }

        function resetClearanceModalState() {
            const form = getClearanceForm();

            if (!form) {
                return;
            }

            window.panelAjax?.clearErrors?.(form);
            form.reset();
            form.action = '#';
            document.getElementById('clearanceEmployeeName').textContent = '-';
            document.getElementById('clearanceEmployeeMeta').textContent = '-';
            document.getElementById('clearancePayrollSummary').textContent = '-';
            document.getElementById('clearancePayrollSubtext').textContent = '-';
            document.getElementById('clearanceKasbonSummary').textContent = '-';
            document.getElementById('clearanceKasbonSubtext').textContent = '-';
            document.getElementById('clearanceStatusSummary').textContent = '-';
            document.getElementById('clearanceProgressSummary').textContent = '-';
            document.getElementById('clearanceNotesField').value = '';
            updateClearanceStatusPreview();
        }

        function openClearanceModal(button) {
            const payload = parseClearancePayload(button);
            const form = getClearanceForm();

            if (!form) {
                return;
            }

            resetClearanceModalState();
            form.action = payload.update_url || '#';
            document.getElementById('clearanceEmployeeName').textContent = payload.employee_name || '-';
            document.getElementById('clearanceEmployeeMeta').textContent = `NIK ${payload.employee_nik || '-'} · ${payload.employee_jabatan || '-'} · ${payload.employee_departemen || '-'} · Resign ${payload.tgl_resign || '-'}`;
            document.getElementById('clearancePayrollSummary').textContent = payload.payroll_final ? 'Sudah Final' : (payload.payroll_exists ? 'Belum Final' : 'Belum Dibuat');
            document.getElementById('clearancePayrollSubtext').textContent = `${payload.payroll_month_label || '-'} · ${formatClearanceCurrency(payload.payroll_total || 0)}`;
            document.getElementById('clearanceKasbonSummary').textContent = formatClearanceCurrency(payload.kasbon_balance || 0);
            document.getElementById('clearanceKasbonSubtext').textContent = Number(payload.kasbon_balance || 0) > 0 ? 'Masih ada saldo kasbon aktif.' : 'Saldo kasbon sudah lunas.';
            document.getElementById('clearanceStatusSummary').textContent = payload.clearance_status_label || 'Draft';
            document.getElementById('clearanceProgressSummary').textContent = payload.clearance_progress || '0/5 checklist selesai';
            document.getElementById('clearanceNotesField').value = payload.clearance_notes || '';

            form.querySelector('[name="clearance_payroll_final"]').checked = Boolean(payload.clearance_payroll_final);
            form.querySelector('[name="clearance_kasbon_resolved"]').checked = Boolean(payload.clearance_kasbon_resolved);
            form.querySelector('[name="clearance_asset_returned"]').checked = Boolean(payload.clearance_asset_returned);
            form.querySelector('[name="clearance_access_revoked"]').checked = Boolean(payload.clearance_access_revoked);
            form.querySelector('[name="clearance_document_completed"]').checked = Boolean(payload.clearance_document_completed);

            updateClearanceStatusPreview();
            getClearanceModal()?.classList.add('show');
        }

        function closeClearanceModal() {
            resetClearanceModalState();
            getClearanceModal()?.classList.remove('show');
        }

        if (window.panelAjax && !window.panelAjax.clearanceModalCloseWrapped) {
            const originalCloseRelatedModal = window.panelAjax.closeRelatedModal.bind(window.panelAjax);

            window.panelAjax.closeRelatedModal = function (form) {
                originalCloseRelatedModal(form);

                if (form?.dataset?.closeModal === '#clearanceModal') {
                    resetClearanceModalState();
                }
            };

            window.panelAjax.clearanceModalCloseWrapped = true;
        }

        document.addEventListener('change', function (event) {
            if (event.target.closest('#clearanceForm')) {
                updateClearanceStatusPreview();
            }
        });

        window.addEventListener('click', function (event) {
            if (event.target === getClearanceModal()) {
                closeClearanceModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && getClearanceModal()?.classList.contains('show')) {
                closeClearanceModal();
            }
        });
    </script>
@endsection
