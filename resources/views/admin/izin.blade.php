@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Manajemen Izin'])

@php
    $openIzinModal = $errors->any() && old('form_type') === 'create';
    $employeePickerOptions = $karyawanList->map(function ($karyawan) {
        return [
            'id' => (string) $karyawan->id,
            'nik' => (string) $karyawan->nik,
            'name' => (string) $karyawan->nama_lengkap,
            'label' => trim($karyawan->nik . ' - ' . $karyawan->nama_lengkap),
        ];
    })->values();
    $defaultEmployeeId = (string) old('karyawan_id', '');
    $defaultEmployee = $karyawanList->firstWhere('id', (int) $defaultEmployeeId);
    $defaultEmployeeLabel = $defaultEmployee
        ? trim($defaultEmployee->nik . ' - ' . $defaultEmployee->nama_lengkap)
        : '';
@endphp

@section('styles')
    .stats-grid { grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        transition: 0.2s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        text-decoration: none;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .stat-card.total { border-left: 4px solid #3B82F6; }
    .stat-card.pending { border-left: 4px solid #F59E0B; }
    .stat-card.approved { border-left: 4px solid #10B981; }
    .stat-card.rejected { border-left: 4px solid #EF4444; }
    .stat-card .stat-card-body { padding: 0; width: 100%; }
    .stat-card .stat-value { color: #1E293B; }
    .stat-card.total .stat-icon { background: #DBEAFE; color: #2563EB; }
    .stat-card.pending .stat-icon { background: #FEF3C7; color: #D97706; }
    .stat-card.approved .stat-icon { background: #D1FAE5; color: #065F46; }
    .stat-card.rejected .stat-icon { background: #FEE2E2; color: #DC2626; }
    .page-header { margin-bottom: 20px; }
    .filter-toolbar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: wrap;
        margin-left: auto;
    }
    .filter-select {
        padding: 8px 12px;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        font-size: 13px;
        min-width: 170px;
        font-family: inherit;
    }
    .table-bordered td { vertical-align: middle; }
    .action-buttons { display: flex; gap: 6px; flex-wrap: wrap; }
    .btn-approve, .btn-reject, .btn-delete, .btn-note {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 500;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }
    .btn-approve { background: #D1FAE5; color: #065F46; }
    .btn-approve:hover { background: #065F46; color: white; }
    .btn-reject { background: #FEF3C7; color: #D97706; }
    .btn-reject:hover { background: #D97706; color: white; }
    .btn-delete { background: #FEE2E2; color: #EF4444; }
    .btn-delete:hover { background: #EF4444; color: white; }
    .btn-note { background: #EFF6FF; color: #3B82F6; }
    .btn-note:hover { background: #3B82F6; color: white; }
    .btn-view {
        background: #F1F5F9;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-view:hover { background: #CBD5E1; }
    .modal-sm { max-width: 400px; }
    .izin-employee-picker {
        position: relative;
    }
    .izin-employee-picker .form-control {
        width: 100%;
    }
    .izin-employee-dropdown {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        display: none;
        max-height: 240px;
        overflow: auto;
        padding: 6px;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        background: #FFFFFF;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.14);
        z-index: 30;
    }
    .izin-employee-picker.is-open .izin-employee-dropdown {
        display: block;
    }
    .izin-employee-option {
        width: 100%;
        border: none;
        background: transparent;
        border-radius: 10px;
        padding: 10px 12px;
        text-align: left;
        cursor: pointer;
        display: grid;
        gap: 2px;
    }
    .izin-employee-option:hover,
    .izin-employee-option:focus {
        background: #F8FAFC;
        outline: none;
    }
    .izin-employee-option strong {
        font-size: 13px;
        color: #0F172A;
        font-weight: 600;
    }
    .izin-employee-option span {
        font-size: 11px;
        color: #64748B;
    }
    .izin-employee-empty {
        padding: 10px 12px;
        font-size: 12px;
        color: #64748B;
    }
    .modal-dialog-izin {
        width: min(800px, calc(100vw - 32px));
        max-width: 800px;
    }
    .modal-content-izin {
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 40px);
        border-radius: 18px;
    }
    .modal-body-izin {
        overflow-y: auto;
        max-height: calc(100vh - 180px);
        padding-right: 16px;
    }
    .modal-body-izin::-webkit-scrollbar { width: 8px; }
    .modal-body-izin::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 999px; }
    .modal-body-izin::-webkit-scrollbar-track { background: #F8FAFC; }
    .izin-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
    .izin-form-grid .form-group {
        margin-bottom: 0;
    }
    .izin-form-grid-full {
        grid-column: 1 / -1;
    }
    .leave-range { display: flex; flex-direction: column; gap: 2px; }
    .leave-range small, .leave-helper small { color: #64748B; font-size: 11px; }
    .leave-badge-stack { display: inline-flex; flex-wrap: wrap; gap: 6px; }
    .leave-type-hint {
        margin-top: 10px;
        padding: 10px 12px;
        border-radius: 10px;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        color: #475569;
        font-size: 12px;
        line-height: 1.5;
    }
    .leave-type-hint strong { color: #0F172A; }
    .quota-preview-box {
        margin-top: 10px;
        padding: 12px 14px;
        border-radius: 10px;
        border: 1px solid #E2E8F0;
        background: #F8FAFC;
        font-size: 12px;
        color: #334155;
        line-height: 1.55;
    }
    .quota-preview-box.is-loading { color: #475569; }
    .quota-preview-box.is-success { background: #F0FDF4; border-color: #BBF7D0; color: #166534; }
    .quota-preview-box.is-warning { background: #FFFBEB; border-color: #FDE68A; color: #92400E; }
    .quota-preview-box.is-error { background: #FEF2F2; border-color: #FECACA; color: #991B1B; }
    .quota-preview-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 6px;
    }
    .quota-preview-title { font-weight: 700; color: #0F172A; }
    .quota-preview-status {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }
    .quota-preview-status.is-success { background: #DCFCE7; color: #166534; }
    .quota-preview-status.is-warning { background: #FEF3C7; color: #92400E; }
    .quota-preview-status.is-error { background: #FEE2E2; color: #991B1B; }
    .quota-preview-years { margin-top: 8px; display: flex; flex-direction: column; gap: 6px; }
    .quota-preview-year { padding-top: 6px; border-top: 1px dashed rgba(148, 163, 184, 0.4); }
    .quota-preview-year:first-child { border-top: 0; padding-top: 0; }
    .quota-preview-note { margin-top: 8px; font-size: 11px; color: #64748B; }
    @media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) {
        .page-header { flex-direction: column; align-items: stretch; }
        .stats-grid { grid-template-columns: 1fr; }
        .form-row { grid-template-columns: 1fr; }
        .filter-toolbar {
            justify-content: stretch;
            margin-left: 0;
        }
        .modal-dialog-izin {
            width: calc(100vw - 20px);
        }
        .izin-form-grid {
            grid-template-columns: 1fr;
            gap: 14px;
        }
        .modal-body-izin {
            max-height: calc(100vh - 160px);
            padding-right: 20px;
        }
    }
@endsection

@section('content')
    <div id="ajaxCrudFragment">
        <div class="stats-grid">
            @foreach ([
                ['key' => 'total', 'label' => 'Total Izin', 'value' => $totalPending + $totalDisetujui + $totalDitolak, 'route' => route('admin.izin'), 'icon' => 'fa-file-alt'],
                ['key' => 'pending', 'label' => 'Menunggu', 'value' => $totalPending, 'route' => route('admin.izin', ['status' => 'pending']), 'icon' => 'fa-hourglass-half'],
                ['key' => 'approved', 'label' => 'Disetujui', 'value' => $totalDisetujui, 'route' => route('admin.izin', ['status' => 'disetujui']), 'icon' => 'fa-circle-check'],
                ['key' => 'rejected', 'label' => 'Ditolak', 'value' => $totalDitolak, 'route' => route('admin.izin', ['status' => 'ditolak']), 'icon' => 'fa-circle-xmark'],
            ] as $card)
                <a href="{{ $card['route'] }}" class="stat-card {{ $card['key'] }}" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment">
                    <div class="stat-card-body">
                        <div>
                            <div class="stat-value">{{ $card['value'] }}</div>
                            <div class="stat-label">{{ $card['label'] }}</div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas {{ $card['icon'] }}"></i>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="page-header">
            <div>
                <h3 class="page-subtitle">Daftar Pengajuan Izin & Cuti</h3>
                <p class="page-description">Kelola izin berbasis jenis izin, rentang tanggal, dan hitungan hari kerja karyawan.</p>
            </div>
            <form method="GET" action="{{ route('admin.izin') }}" class="filter-toolbar" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxCrudFragment">
                <select name="status" class="filter-select">
                    <option value="">Semua Status</option>
                    @foreach (['pending' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="jenis_izin_id" class="filter-select">
                    <option value="">Semua Jenis Izin</option>
                    @foreach ($jenisIzinList as $jenisIzin)
                        <option value="{{ $jenisIzin->id }}" @selected($leaveTypeFilter === $jenisIzin->id)>{{ $jenisIzin->nama }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" class="filter-select" value="{{ $search ?? '' }}" placeholder="Cari NIK, nama, alasan...">
                <select name="per_page" class="filter-select">
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected(($perPage ?? 10) === $size)>{{ $size }} / halaman</option>
                    @endforeach
                </select>
                <a href="{{ route('admin.izin') }}" class="btn btn-secondary" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment">
                    <i class="fas fa-rotate-left"></i> Reset
                </a>
                <button type="button" class="btn btn-primary" onclick="openIzinModal()">
                    <i class="fas fa-plus"></i> Tambah Izin
                </button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Data Pengajuan Izin</h3>
                <span class="total-data">Total: {{ $izinList->total() }} data</span>
            </div>
            <div class="table-responsive">
                <table class="table-bordered">
                    <thead>
                        <tr class="table-header">
                            <th width="40">No</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Periode Izin</th>
                            <th>Durasi</th>
                            <th>NIK</th>
                            <th>Nama Karyawan</th>
                            <th>Jabatan</th>
                            <th>Jenis Izin</th>
                            <th>Sifat</th>
                            <th>Alasan</th>
                            <th>Bukti</th>
                            <th>Status</th>
                            <th>Disetujui Oleh</th>
                            <th>Catatan Admin</th>
                            <th width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($izinList as $index => $izin)
                            @php
                                $jenisBadgeClass = $izin->jenisIzin?->badge_class ?? match ($izin->legacy_jenis_izin_code) {
                                    'sakit' => 'badge-info',
                                    'cuti' => 'badge-primary',
                                    default => 'badge-secondary',
                                };
                                $statusClass = match ($izin->status) {
                                    'disetujui' => 'badge-success',
                                    'ditolak' => 'badge-danger',
                                    default => 'badge-warning',
                                };
                                $statusLabel = match ($izin->status) {
                                    'disetujui' => 'Disetujui',
                                    'ditolak' => 'Ditolak',
                                    default => 'Menunggu',
                                };
                                $startDate = $izin->tanggal_mulai_efektif;
                                $endDate = $izin->tanggal_selesai_efektif;
                            @endphp
                            <tr>
                                <td class="text-center">{{ ($izinList->firstItem() ?? 1) + $index }}</td>
                                <td>{{ $izin->created_at ? \Carbon\Carbon::parse($izin->created_at)->format('d/m/Y H:i') : '-' }}</td>
                                <td>
                                    <div class="leave-range">
                                        <span>{{ $startDate ? $startDate->format('d/m/Y') : '-' }}</span>
                                        @if ($startDate && $endDate && $endDate->ne($startDate))
                                            <small>s/d {{ $endDate->format('d/m/Y') }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $izin->effective_jumlah_hari }} hari kerja</td>
                                <td>{{ $izin->karyawan->nik ?? '-' }}</td>
                                <td><strong>{{ $izin->karyawan->nama_lengkap ?? '-' }}</strong></td>
                                <td>{{ $izin->karyawan->jabatan ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $jenisBadgeClass }}">{{ $izin->leave_type_name }}</span>
                                </td>
                                <td>
                                    <div class="leave-badge-stack">
                                        <span class="badge {{ $izin->leave_is_paid ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $izin->leave_is_paid ? 'Dibayar' : 'Tidak Dibayar' }}
                                        </span>
                                        @if ($izin->leave_deduct_quota)
                                            <span class="badge badge-warning">Pakai Kuota</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $izin->alasan }}</td>
                                <td class="text-center">
                                    @if ($izin->bukti)
                                        <button type="button" class="btn-view" onclick="openViewModal('{{ asset('storage/bukti_izin/'.$izin->bukti) }}', '{{ strtolower(pathinfo($izin->bukti, PATHINFO_EXTENSION)) }}', @js($izin->karyawan->nama_lengkap ?? 'Bukti Izin'))">
                                            <i class="fas fa-paperclip"></i> Lihat
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center"><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                <td>{{ $izin->approvedBy->nama_lengkap ?? '-' }}</td>
                                <td>
                                    @if ($izin->status === 'pending')
                                        <button type="button" class="btn-note" data-action="{{ route('admin.izin.note', $izin) }}" data-note="{{ $izin->catatan_admin }}" onclick="openCatatanModal(this)">
                                            <i class="fas fa-edit"></i> Tambah
                                        </button>
                                    @else
                                        {{ $izin->catatan_admin ?: '-' }}
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        @if ($izin->status === 'pending')
                                            <form method="POST" action="{{ route('admin.izin.approve', $izin) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Setujui pengajuan izin ini?">
                                                @csrf
                                                <button type="submit" class="btn-approve">
                                                    <i class="fas fa-check"></i> Setuju
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.izin.reject', $izin) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Tolak pengajuan izin ini?">
                                                @csrf
                                                <button type="submit" class="btn-reject">
                                                    <i class="fas fa-times"></i> Tolak
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.izin.destroy', $izin) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Hapus data izin ini?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-delete">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="text-center">
                                <td colspan="15">Belum ada data pengajuan izin</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $izinList->links('partials.pagination-ajax', ['target' => '#ajaxCrudFragment']) }}
        </div>
    </div>

    <div id="tambahModal" class="modal{{ $openIzinModal ? ' show' : '' }}">
        <div class="modal-dialog modal-dialog-izin">
            <div class="modal-content modal-content-izin">
                <div class="modal-header">
                    <h3>Tambah Pengajuan Izin</h3>
                    <button type="button" class="modal-close" onclick="closeIzinModal()">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.izin.store') }}" enctype="multipart/form-data" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#tambahModal" data-reset-on-success="true">
                    @csrf
                    <input type="hidden" name="form_type" value="create">
                    <div class="modal-body modal-body-izin">
                        <div class="izin-form-grid">
                            <div class="form-group" id="izinEmployeeFieldGroup">
                                <label>Karyawan</label>
                                <input type="hidden" name="karyawan_id" id="karyawanField" value="{{ $defaultEmployeeId }}">
                                <div class="izin-employee-picker" id="izinEmployeePicker">
                                    <input
                                        type="text"
                                        id="karyawanFieldSearch"
                                        class="form-control"
                                        value="{{ $defaultEmployeeLabel }}"
                                        placeholder="Cari NIK atau nama karyawan"
                                        autocomplete="off"
                                        required>
                                    <div class="izin-employee-dropdown" id="izinEmployeeDropdown"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Jenis Izin</label>
                                <select name="jenis_izin_id" id="jenisIzinField" class="form-select" required>
                                    <option value="">-- Pilih Jenis Izin --</option>
                                    @foreach ($jenisIzinList as $jenisIzin)
                                        <option
                                            value="{{ $jenisIzin->id }}"
                                            data-name="{{ $jenisIzin->nama }}"
                                            data-description="{{ $jenisIzin->description }}"
                                            data-paid="{{ $jenisIzin->is_paid ? '1' : '0' }}"
                                            data-quota="{{ $jenisIzin->uses_quota ? '1' : '0' }}"
                                            data-quota-label="{{ $jenisIzin->quota_label }}"
                                            data-quota-summary="{{ $jenisIzin->quota_summary }}"
                                            data-max-request="{{ (int) ($jenisIzin->max_days_per_request ?? 0) }}"
                                            data-max-request-summary="{{ $jenisIzin->max_request_summary }}"
                                            data-attachment="{{ $jenisIzin->require_attachment ? '1' : '0' }}"
                                            @selected((int) old('jenis_izin_id') === $jenisIzin->id)
                                        >
                                            {{ $jenisIzin->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group izin-form-grid-full">
                                <div id="jenisIzinInfo" class="leave-type-hint">
                                    Pilih jenis izin untuk melihat aturan kuota, bukti pendukung, dan sifat pembayarannya.
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai') }}" required>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai') }}" required>
                            </div>
                            <div class="leave-helper izin-form-grid-full">
                            <small>Hari kerja pada rentang ini akan dihitung otomatis saat disimpan. Hari Minggu dan hari libur global tidak ikut dihitung sebagai durasi izin.</small>
                            </div>
                            <div class="izin-form-grid-full">
                                <div id="quotaPreviewBox" class="quota-preview-box">
                                    Pilih karyawan, jenis izin, dan rentang tanggal untuk melihat preview kuota.
                                </div>
                            </div>
                            <div class="form-group izin-form-grid-full">
                                <label>Alasan Izin</label>
                                <textarea name="alasan" class="form-control" rows="4" placeholder="Alasan pengajuan izin..." required>{{ old('alasan') }}</textarea>
                            </div>
                            <div class="form-group izin-form-grid-full">
                                <label>Bukti Pendukung</label>
                                <input type="file" name="bukti" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="form-text">Format: JPG, PNG, PDF. Maksimal 2MB. Beberapa jenis izin mewajibkan lampiran.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeIzinModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Ajukan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="viewModal" class="modal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="viewTitle">Bukti Izin</h3>
                    <button type="button" class="modal-close" onclick="closeViewModal()">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <img id="viewImage" src="" alt="Bukti Izin" style="max-width:100%; max-height:400px; display:none;">
                    <div id="viewPdf" style="display:none;">
                        <embed id="viewPdfEmbed" src="" width="100%" height="400" type="application/pdf">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="catatanModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Catatan Admin</h3>
                    <button type="button" class="modal-close" onclick="closeCatatanModal()">&times;</button>
                </div>
                <form method="POST" id="catatanForm" action="#" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#catatanModal">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="catatan_admin" id="catatanText" class="form-control" rows="4" placeholder="Tulis catatan untuk karyawan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeCatatanModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const izinModal = document.getElementById('tambahModal');
        const viewModal = document.getElementById('viewModal');
        const catatanModal = document.getElementById('catatanModal');
        const catatanForm = document.getElementById('catatanForm');
        const karyawanFieldGroup = document.getElementById('izinEmployeeFieldGroup');
        const karyawanField = document.getElementById('karyawanField');
        const karyawanFieldSearch = document.getElementById('karyawanFieldSearch');
        const izinEmployeePicker = document.getElementById('izinEmployeePicker');
        const izinEmployeeDropdown = document.getElementById('izinEmployeeDropdown');
        const jenisIzinField = document.getElementById('jenisIzinField');
        const jenisIzinInfo = document.getElementById('jenisIzinInfo');
        const quotaPreviewBox = document.getElementById('quotaPreviewBox');
        const tanggalMulaiField = document.querySelector('input[name="tanggal_mulai"]');
        const tanggalSelesaiField = document.querySelector('input[name="tanggal_selesai"]');
        const quotaPreviewUrl = @js(route('admin.izin.preview-kuota'));
        const izinEmployeeOptions = @json($employeePickerOptions);
        let quotaPreviewTimeout = null;
        let quotaPreviewAbortController = null;

        function openIzinModal() {
            izinModal.classList.add('show');
            syncLeaveTypeInfo();
            queueQuotaPreview();
        }

        function closeIzinModal() {
            izinModal.classList.remove('show');
            resetQuotaPreview();
        }

        function escapeIzinHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getIzinEmployeeById(id) {
            return izinEmployeeOptions.find((employee) => String(employee.id) === String(id)) || null;
        }

        function findIzinEmployeeByExactKeyword(keyword) {
            const normalizedKeyword = String(keyword || '').trim().toLowerCase();

            if (!normalizedKeyword) {
                return null;
            }

            return izinEmployeeOptions.find((employee) =>
                employee.label.toLowerCase() === normalizedKeyword
                || employee.nik.toLowerCase() === normalizedKeyword
            ) || null;
        }

        function syncIzinEmployeeErrorState() {
            if (!karyawanFieldGroup || !karyawanField || !karyawanFieldSearch) {
                return;
            }

            const generatedFeedbacks = Array.from(
                karyawanFieldGroup.querySelectorAll('.invalid-feedback[data-generated="true"]')
            );
            const visibleFeedback = karyawanFieldGroup.querySelector('.izin-employee-feedback');
            const hiddenInvalid = karyawanField.classList.contains('is-invalid');

            karyawanFieldSearch.classList.toggle('is-invalid', hiddenInvalid || generatedFeedbacks.length > 0);

            if (generatedFeedbacks.length > 0) {
                const lastFeedback = generatedFeedbacks[generatedFeedbacks.length - 1];

                generatedFeedbacks.forEach((feedback) => feedback.remove());

                const renderedFeedback = visibleFeedback || document.createElement('div');
                renderedFeedback.className = 'invalid-feedback izin-employee-feedback';
                renderedFeedback.dataset.generated = 'true';
                renderedFeedback.textContent = lastFeedback.textContent || 'Karyawan wajib dipilih.';

                karyawanFieldSearch.insertAdjacentElement('afterend', renderedFeedback);
                karyawanFieldSearch.classList.add('is-invalid');
                return;
            }

            if (!hiddenInvalid && visibleFeedback) {
                visibleFeedback.remove();
                karyawanFieldSearch.classList.remove('is-invalid');
            }
        }

        function setIzinEmployeeSelection(id = '', label = '') {
            if (karyawanField) {
                karyawanField.value = id ? String(id) : '';
            }

            if (karyawanFieldSearch) {
                karyawanFieldSearch.value = label || '';
            }

            syncIzinEmployeeErrorState();
        }

        function closeIzinEmployeeDropdown() {
            izinEmployeePicker?.classList.remove('is-open');
        }

        function renderIzinEmployeeDropdown(keyword = '') {
            if (!izinEmployeeDropdown) {
                return;
            }

            const normalizedKeyword = keyword.trim().toLowerCase();
            const filteredEmployees = izinEmployeeOptions.filter((employee) => {
                if (!normalizedKeyword) {
                    return true;
                }

                return employee.label.toLowerCase().includes(normalizedKeyword)
                    || employee.nik.toLowerCase().includes(normalizedKeyword)
                    || employee.name.toLowerCase().includes(normalizedKeyword);
            }).slice(0, 20);

            if (filteredEmployees.length === 0) {
                izinEmployeeDropdown.innerHTML = '<div class="izin-employee-empty">Karyawan tidak ditemukan.</div>';
                return;
            }

            izinEmployeeDropdown.innerHTML = filteredEmployees.map((employee) => `
                <button
                    type="button"
                    class="izin-employee-option"
                    data-employee-id="${escapeIzinHtml(employee.id)}"
                    data-employee-label="${escapeIzinHtml(employee.label)}">
                    <strong>${escapeIzinHtml(employee.nik)}</strong>
                    <span>${escapeIzinHtml(employee.name)}</span>
                </button>
            `).join('');
        }

        function openIzinEmployeeDropdown() {
            if (!izinEmployeePicker || !karyawanFieldSearch) {
                return;
            }

            renderIzinEmployeeDropdown(karyawanFieldSearch.value);
            izinEmployeePicker.classList.add('is-open');
        }

        function syncIzinEmployeeSelectionFromText() {
            if (!karyawanFieldSearch || !karyawanField) {
                return;
            }

            const inputValue = karyawanFieldSearch.value.trim().toLowerCase();

            if (inputValue === '') {
                setIzinEmployeeSelection('', '');
                return;
            }

            const exactEmployee = findIzinEmployeeByExactKeyword(inputValue);

            if (exactEmployee) {
                setIzinEmployeeSelection(exactEmployee.id, exactEmployee.label);
                return;
            }

            const currentEmployee = getIzinEmployeeById(karyawanField.value);

            if (!currentEmployee || currentEmployee.label.toLowerCase() !== inputValue) {
                karyawanField.value = '';
            }
        }

        function openViewModal(src, ext, name) {
            const image = document.getElementById('viewImage');
            const pdfWrap = document.getElementById('viewPdf');
            const pdf = document.getElementById('viewPdfEmbed');
            document.getElementById('viewTitle').innerText = 'Bukti Izin - ' + name;

            if (ext === 'pdf') {
                image.style.display = 'none';
                image.src = '';
                pdfWrap.style.display = 'block';
                pdf.src = src;
            } else {
                pdfWrap.style.display = 'none';
                pdf.src = '';
                image.style.display = 'block';
                image.src = src;
            }

            viewModal.classList.add('show');
        }

        function closeViewModal() {
            viewModal.classList.remove('show');
            document.getElementById('viewImage').src = '';
            document.getElementById('viewPdfEmbed').src = '';
            document.getElementById('viewPdf').style.display = 'none';
        }

        function openCatatanModal(button) {
            catatanForm.action = button.dataset.action || '#';
            document.getElementById('catatanText').value = button.dataset.note || '';
            catatanModal.classList.add('show');
        }

        function closeCatatanModal() {
            catatanModal.classList.remove('show');
        }

        function syncLeaveTypeInfo() {
            if (!jenisIzinField || !jenisIzinInfo) {
                return;
            }

            const option = jenisIzinField.options[jenisIzinField.selectedIndex];

            if (!option || !option.value) {
                jenisIzinInfo.innerHTML = 'Pilih jenis izin untuk melihat aturan kuota, bukti pendukung, dan sifat pembayarannya.';
                return;
            }

            const typeName = option.dataset.name || 'Jenis izin ini';
            const description = option.dataset.description || '';
            const isPaid = option.dataset.paid === '1';
            const deductQuota = option.dataset.quota === '1';
            const quotaLabel = option.dataset.quotaLabel || 'kuota';
            const quotaSummary = option.dataset.quotaSummary || '';
            const maxRequestSummary = option.dataset.maxRequestSummary || '';
            const requireAttachment = option.dataset.attachment === '1';

            jenisIzinInfo.innerHTML = `
                <strong>${typeName}</strong><br>
                ${description ? `${description} ` : ''}
                ${isPaid ? 'Masuk kategori izin dibayar.' : 'Masuk kategori izin tidak dibayar.'}
                ${deductQuota ? ` Pengajuan ini memakai ${quotaLabel.toLowerCase()}.` : ' Pengajuan ini tidak memakai kuota karyawan.'}
                ${quotaSummary ? ` ${quotaSummary}` : ''}
                ${maxRequestSummary ? ` ${maxRequestSummary}` : ''}
                ${requireAttachment ? ' Bukti pendukung wajib dilampirkan.' : ' Bukti pendukung bersifat opsional.'}
            `;
        }

        function setQuotaPreviewState(type, html) {
            if (!quotaPreviewBox) {
                return;
            }

            quotaPreviewBox.classList.remove('is-loading', 'is-success', 'is-warning', 'is-error');

            if (type) {
                quotaPreviewBox.classList.add(type);
            }

            quotaPreviewBox.innerHTML = html;
        }

        function resetQuotaPreview() {
            if (quotaPreviewTimeout) {
                clearTimeout(quotaPreviewTimeout);
                quotaPreviewTimeout = null;
            }

            if (quotaPreviewAbortController) {
                quotaPreviewAbortController.abort();
                quotaPreviewAbortController = null;
            }

            setQuotaPreviewState('', 'Pilih karyawan, jenis izin, dan rentang tanggal untuk melihat preview kuota.');
        }

        function renderQuotaPreviewStatus(payload) {
            if (!payload || payload.ready !== true) {
                setQuotaPreviewState('', payload?.message || 'Pilih karyawan, jenis izin, dan rentang tanggal untuk melihat preview kuota.');
                return;
            }

            const years = Array.isArray(payload.years) ? payload.years : [];
            const minimumRemaining = years.length
                ? Math.min(...years.map((year) => Number(year.remaining_after_request ?? 0)))
                : Number(payload.workday_count ?? 0);

            let stateClass = 'is-success';
            let statusLabel = 'Cukup';

            if (!payload.enough_quota) {
                stateClass = 'is-error';
                statusLabel = 'Tidak Cukup';
            } else if (payload.uses_quota && minimumRemaining <= 1) {
                stateClass = 'is-warning';
                statusLabel = 'Mepet';
            }

            const yearHtml = years.length
                ? `<div class="quota-preview-years">${years.map((year) => `
                    <div class="quota-preview-year">
                        <strong>${year.year}</strong> · diminta ${year.requested_days} hari · tersedia ${year.available_days} hari
                        ${payload.uses_quota ? `<br><span>Jatah ${year.base_quota} · carry over ${year.carried_over} · sudah terpakai ${year.used_days} · sisa setelah diajukan ${year.remaining_after_request} hari</span>` : ''}
                    </div>
                `).join('')}</div>`
                : '';

            const noteHtml = payload.note
                ? `<div class="quota-preview-note">${payload.note}</div>`
                : '';

            setQuotaPreviewState(
                stateClass,
                `<div class="quota-preview-head">
                    <div class="quota-preview-title">${payload.quota_label || 'Kuota'}</div>
                    <span class="quota-preview-status ${stateClass}">${statusLabel}</span>
                </div>
                ${payload.message || ''}
                <div style="margin-top:6px;">Durasi terhitung: <strong>${payload.workday_count || 0} hari kerja</strong></div>
                ${yearHtml}
                ${noteHtml}`
            );
        }

        function renderQuotaPreview(payload) {
            if (!payload || payload.ready !== true) {
                setQuotaPreviewState('', payload?.message || 'Pilih karyawan, jenis izin, dan rentang tanggal untuk melihat preview kuota.');
                return;
            }

            const years = Array.isArray(payload.years) ? payload.years : [];
            const yearHtml = years.length
                ? `<div class="quota-preview-years">${years.map((year) => `
                    <div class="quota-preview-year">
                        <strong>${year.year}</strong> · diminta ${year.requested_days} hari · tersedia ${year.available_days} hari
                        ${payload.uses_quota ? `<br><span>Jatah ${year.base_quota} · carry over ${year.carried_over} · sudah terpakai ${year.used_days} · sisa setelah diajukan ${year.remaining_after_request} hari</span>` : ''}
                    </div>
                `).join('')}</div>`
                : '';

            const noteHtml = payload.note
                ? `<div class="quota-preview-note">${payload.note}</div>`
                : '';

            const stateClass = payload.enough_quota ? 'is-success' : 'is-warning';

            setQuotaPreviewState(
                stateClass,
                `<strong>${payload.quota_label || 'Kuota'}</strong><br>
                ${payload.message || ''}
                <div style="margin-top:6px;">Durasi terhitung: <strong>${payload.workday_count || 0} hari kerja</strong></div>
                ${yearHtml}
                ${noteHtml}`
            );
        }

        async function updateQuotaPreview() {
            if (!quotaPreviewBox) {
                return;
            }

            const params = new URLSearchParams({
                karyawan_id: karyawanField?.value || '',
                jenis_izin_id: jenisIzinField?.value || '',
                tanggal_mulai: tanggalMulaiField?.value || '',
                tanggal_selesai: tanggalSelesaiField?.value || '',
            });

            if (!params.get('karyawan_id') || !params.get('jenis_izin_id') || !params.get('tanggal_mulai') || !params.get('tanggal_selesai')) {
                setQuotaPreviewState('', 'Pilih karyawan, jenis izin, dan rentang tanggal untuk melihat preview kuota.');
                return;
            }

            if (quotaPreviewAbortController) {
                quotaPreviewAbortController.abort();
            }

            quotaPreviewAbortController = new AbortController();
            setQuotaPreviewState('is-loading', 'Memeriksa kuota...');

            try {
                const response = await fetch(`${quotaPreviewUrl}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: quotaPreviewAbortController.signal,
                    credentials: 'same-origin',
                });

                const payload = await response.json();

                if (!response.ok) {
                    setQuotaPreviewState('is-error', payload?.message || 'Preview kuota gagal diproses.');
                    return;
                }

                renderQuotaPreviewStatus(payload);
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                setQuotaPreviewState('is-error', 'Preview kuota gagal diproses.');
            }
        }

        function queueQuotaPreview() {
            if (quotaPreviewTimeout) {
                clearTimeout(quotaPreviewTimeout);
            }

            quotaPreviewTimeout = setTimeout(updateQuotaPreview, 250);
        }

        if (jenisIzinField) {
            jenisIzinField.addEventListener('change', function () {
                syncLeaveTypeInfo();
                queueQuotaPreview();
            });
            syncLeaveTypeInfo();
        }

        if (karyawanFieldSearch) {
            karyawanFieldSearch.addEventListener('focus', openIzinEmployeeDropdown);
            karyawanFieldSearch.addEventListener('click', openIzinEmployeeDropdown);
            karyawanFieldSearch.addEventListener('input', function () {
                karyawanField.value = '';
                syncIzinEmployeeErrorState();
                renderIzinEmployeeDropdown(this.value);
                izinEmployeePicker?.classList.add('is-open');
                queueQuotaPreview();
            });
            karyawanFieldSearch.addEventListener('blur', function () {
                setTimeout(() => {
                    syncIzinEmployeeSelectionFromText();
                    closeIzinEmployeeDropdown();
                    queueQuotaPreview();
                }, 120);
            });
        }

        izinEmployeeDropdown?.addEventListener('click', function (event) {
            const option = event.target.closest('.izin-employee-option');

            if (!option) {
                return;
            }

            setIzinEmployeeSelection(option.dataset.employeeId || '', option.dataset.employeeLabel || '');
            closeIzinEmployeeDropdown();
            queueQuotaPreview();
        });

        [karyawanField, tanggalMulaiField, tanggalSelesaiField].forEach(function (field) {
            if (!field) {
                return;
            }

            field.addEventListener('change', queueQuotaPreview);
            field.addEventListener('input', queueQuotaPreview);
        });

        window.addEventListener('click', function (event) {
            if (event.target === izinModal) {
                closeIzinModal();
            }

            if (izinEmployeePicker && !izinEmployeePicker.contains(event.target)) {
                closeIzinEmployeeDropdown();
            }

            if (event.target === viewModal) {
                closeViewModal();
            }

            if (event.target === catatanModal) {
                closeCatatanModal();
            }
        });

        syncIzinEmployeeErrorState();
        queueQuotaPreview();
    </script>
@endsection
