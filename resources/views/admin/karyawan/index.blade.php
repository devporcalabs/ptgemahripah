@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Kelola Karyawan'])

@php
    $openTambahModal = $errors->any() && old('form_type') === 'create';
    $openEditModal = (bool) $editKaryawan || ($errors->any() && old('form_type') === 'edit');
    $openImportModal = $errors->any() && old('form_type') === 'import';
    $openResignModal = $errors->any() && old('form_type') === 'resign';
    $employeeWizardStepLabels = ['Profil Dasar', 'Kepegawaian', 'Personal & Legal', 'Kontrak & Rekening', 'Payroll & Kuota Izin'];
@endphp

@section('styles')
    .stats-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: 0.2s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .stat-card .stat-card-body {
        padding: 0;
        width: 100%;
    }
    .stat-card.total { border-left: 4px solid #3B82F6; }
    .stat-card.tetap { border-left: 4px solid #10B981; }
    .stat-card.kontrak { border-left: 4px solid #F59E0B; }
    .stat-card.magang { border-left: 4px solid #8B5CF6; }
    .stat-card.total .stat-icon { background: #DBEAFE; color: #2563EB; }
    .stat-card.tetap .stat-icon { background: #D1FAE5; color: #065F46; }
    .stat-card.kontrak .stat-icon { background: #FEF3C7; color: #D97706; }
    .stat-card.magang .stat-icon { background: #EDE9FE; color: #7C3AED; }
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 4px;
        color: #1E293B;
    }
    .stat-label {
        font-size: 12px;
        color: #64748B;
        font-weight: 600;
    }
    .table-bordered { font-size:13px; }
    .table-bordered th, .table-bordered td { padding:10px 12px; }
    .badge { padding:4px 10px; font-size:11px; }
    .action-buttons { gap:6px; flex-wrap:wrap; }
    .employee-action-stack {
        display: grid;
        gap: 6px;
    }
    .employee-action-row {
        display: grid;
        gap: 5px;
    }
    .employee-action-row.top {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .employee-action-row.bottom {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .employee-action-row .btn,
    .employee-action-row form,
    .employee-action-row form .btn {
        width: 100%;
    }
    .employee-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }
    .employee-card-header-main {
        display: grid;
        gap: 4px;
    }
    .employee-card-subtitle {
        font-size: 11px;
        color: #64748B;
    }
    .employee-card-header-tools {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .employee-card-header-form {
        margin: 0;
        display: flex;
        align-items: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }
    .employee-card-header-form .filter-group {
        min-width: 160px;
    }
    .employee-card-header-form .filter-group label {
        font-size: 11px;
    }
    .employee-card-header-form .filter-input {
        min-width: 160px;
    }
    .rfid-empty {
        display:inline-flex;
        align-items:center;
        padding:4px 9px;
        border-radius:999px;
        background:#FFF7ED;
        color:#C2410C;
        font-size:10px;
        font-weight:700;
    }
    .rfid-input-group {
        display:flex;
        align-items:stretch;
        gap:8px;
        width: 100%;
    }
    .rfid-input-group .form-control {
        flex:1;
        min-width:0;
    }
    .rfid-pair-button {
        flex-shrink:0;
        min-width:126px;
        justify-content:center;
    }
    .rfid-feedback {
        display:block;
        margin-top:6px;
        min-height:18px;
        font-size:11px;
        color:#64748B;
    }
    .rfid-feedback.is-waiting {
        color:#1D4ED8;
    }
    .rfid-feedback.is-success {
        color:#065F46;
    }
    .rfid-feedback.is-error {
        color:#B91C1C;
    }
    .modal-dialog-employee {
        width: min(1040px, calc(100vw - 32px));
        max-width: 1040px;
        height: 760px;
        max-height: calc(100vh - 32px);
    }
    .modal-dialog-employee .modal-content {
        height: 100%;
        max-height: 100%;
        display: flex;
        flex-direction: column;
    }
    .modal-dialog-employee form {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
    }
    .modal-dialog-employee .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
    }
    .employee-wizard {
        display: grid;
        gap: 16px;
    }
    .employee-wizard-progress {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 8px;
    }
    .employee-wizard-progress--header {
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 6px;
    }
    .employee-wizard-progress-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        background: #F8FAFC;
        color: #64748B;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.25;
    }
    .employee-wizard-progress--header .employee-wizard-progress-item {
        padding: 8px 10px;
        gap: 8px;
        border-radius: 12px;
        font-size: 11px;
    }
    .employee-wizard-progress-item.is-active {
        border-color: #A7F3D0;
        background: #ECFDF5;
        color: #065F46;
    }
    .employee-wizard-progress-item.is-complete {
        border-color: #BFDBFE;
        background: #EFF6FF;
        color: #1D4ED8;
    }
    .employee-wizard-progress-index {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid currentColor;
        background: #FFFFFF;
        font-size: 12px;
        font-weight: 800;
    }
    .employee-wizard-progress--header .employee-wizard-progress-index {
        width: 22px;
        height: 22px;
        font-size: 11px;
    }
    .employee-wizard-progress-label {
        min-width: 0;
    }
    .employee-wizard-modal-header {
        align-items: flex-start;
        gap: 12px;
        padding-bottom: 12px;
    }
    .employee-wizard-modal-title {
        flex: 1;
        min-width: 0;
        display: grid;
        gap: 8px;
    }
    .employee-wizard-modal-title h3 {
        margin: 0;
        font-size: 18px;
        line-height: 1.2;
    }
    .employee-wizard-panel {
        display: none;
    }
    .employee-wizard-panel.is-active {
        display: block;
    }
    .employee-wizard-panel-card {
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        display: grid;
        gap: 16px;
    }
    .employee-wizard-panel-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        padding-bottom: 4px;
    }
    .employee-wizard-panel-head h4 {
        margin: 0;
        font-size: 15px;
        font-weight: 800;
        color: #0F172A;
    }
    .employee-wizard-panel-head p {
        margin: 4px 0 0;
        color: #64748B;
        font-size: 12px;
        line-height: 1.5;
    }
    .employee-wizard-footer {
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 10px;
    }
    .employee-wizard-footer-group {
        display: none;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        width: 100%;
    }
    .employee-wizard-footer-group.is-active {
        display: flex;
    }
    .employee-wizard-footer .btn {
        min-width: 118px;
    }
    .modal-dialog-resign {
        width: min(560px, calc(100vw - 32px));
        max-width: 560px;
    }
    .resign-employee-box {
        display:grid;
        gap:6px;
        padding:14px 16px;
        border:1px solid #FECACA;
        border-radius:16px;
        background:#FEF2F2;
        margin-bottom:16px;
    }
    .resign-employee-name {
        font-size:15px;
        font-weight:800;
        color:#991B1B;
    }
    .resign-employee-meta {
        font-size:12px;
        color:#7F1D1D;
    }
    .detail-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
    .detail-form-grid.full-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .detail-form-grid > * {
        align-self: start;
    }
    .detail-form-grid .full-width {
        grid-column: 1 / -1;
    }
    .readonly-field {
        background: #F1F5F9 !important;
        color: #475569 !important;
        cursor: not-allowed !important;
    }
    .section-note {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 14px;
        background: #EFF6FF;
        color: #1D4ED8;
        border: 1px solid #BFDBFE;
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 0;
    }
    .section-note i {
        margin-top: 2px;
    }
    .bank-info-card {
        display: grid;
        gap: 14px;
        padding: 14px 16px;
        border: 1px solid #BFDBFE;
        border-radius: 16px;
        background: #F8FBFF;
    }
    .bank-info-card-head {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }
    .bank-info-card-head i {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #DBEAFE;
        color: #1D4ED8;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .bank-info-card-head h5 {
        margin: 0;
        font-size: 13px;
        font-weight: 800;
        color: #0F172A;
    }
    .bank-info-card-head p {
        margin: 3px 0 0;
        font-size: 11px;
        color: #64748B;
        line-height: 1.5;
    }
    .payroll-group-grid {
        display: grid;
        gap: 16px;
    }
    .payroll-group-card.is-addition {
        border-color: #BBF7D0;
        background: #F0FDF4;
    }
    .payroll-group-card.is-addition .bank-info-card-head i {
        background: #DCFCE7;
        color: #15803D;
    }
    .payroll-group-card.is-deduction {
        border-color: #FECACA;
        background: #FEF2F2;
    }
    .payroll-group-card.is-deduction .bank-info-card-head i {
        background: #FEE2E2;
        color: #DC2626;
    }
    .weekday-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
    }
    .weekday-option {
        display: block;
        cursor: pointer;
        position: relative;
    }
    .weekday-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .weekday-option span {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 8px 10px;
        border-radius: 12px;
        border: 1px solid #DCE6F0;
        background: #FFFFFF;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        transition: all 0.2s ease;
    }
    .weekday-option input:checked + span {
        border-color: #10B981;
        background: #ECFDF5;
        color: #047857;
        box-shadow: inset 0 0 0 1px #A7F3D0;
    }
    .rfid-status-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: start;
        gap: 16px;
    }
    .rfid-status-field {
        align-self: start;
    }
    .shift-rotation-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .shift-rotation-grid .form-group {
        margin-bottom: 0;
    }
    .money-suffix {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
    }
    .money-suffix label {
        grid-column: 1;
        grid-row: 1;
    }
    .money-suffix .form-control {
        grid-column: 1;
        grid-row: 2;
        padding-right: 78px;
    }
    .money-suffix .suffix {
        grid-column: 1;
        grid-row: 2;
        justify-self: end;
        align-self: center;
        margin-right: 12px;
        color: #64748B;
        font-size: 12px;
        pointer-events: none;
        z-index: 1;
    }
    .mini-help {
        margin-top: 6px;
        color: #64748B;
        font-size: 11px;
        line-height: 1.5;
    }
    .form-text {
        display: block;
        margin-top: 6px;
        color: #64748B;
        font-size: 11px;
        line-height: 1.5;
    }
    @media (max-width: 900px) {
        .rfid-input-group {
            flex-direction:column;
        }
        .rfid-pair-button {
            width:100%;
            min-width:0;
        }
        .employee-card-header-tools,
        .employee-card-header-form {
            width: 100%;
        }
        .employee-card-header-form > * {
            flex: 1 1 180px;
            min-width: 0;
        }
        .employee-card-header-form .filter-group,
        .employee-card-header-form .filter-input,
        .employee-card-header-form .btn {
            width: 100%;
        }
        .employee-action-row.top {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .modal-dialog-employee {
            width: calc(100vw - 16px);
            height: calc(100vh - 16px);
        }
        .employee-wizard-progress,
        .detail-form-grid,
        .detail-form-grid.full-3,
        .shift-rotation-grid,
        .weekday-grid,
        .rfid-status-row {
            grid-template-columns: 1fr;
        }
        .employee-wizard-progress--header {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .employee-wizard-modal-header {
            padding-bottom: 10px;
        }
    }
    @media (max-width: 560px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .employee-action-row.top,
        .employee-action-row.bottom {
            grid-template-columns: 1fr;
        }
        .employee-wizard-progress--header {
            grid-template-columns: 1fr;
        }
        .employee-card-header-tools {
            width: 100%;
            margin-left: 0;
            justify-content: flex-start;
        }
        .employee-card-header-form {
            width: 100%;
        }
        .employee-wizard-footer-group {
            width: 100%;
            justify-content: flex-end;
        }
        .employee-wizard-footer .btn {
            min-width: 0;
        }
    }
@endsection

@section('content')
    <div id="ajaxCrudFragment">
        <div class="stats-grid">
            @foreach ([
                ['key' => 'total', 'label' => 'Total Karyawan', 'value' => $employeeStats['total'] ?? 0, 'icon' => 'fa-users'],
                ['key' => 'tetap', 'label' => 'Karyawan Tetap', 'value' => $employeeStats['tetap'] ?? 0, 'icon' => 'fa-user-check'],
                ['key' => 'kontrak', 'label' => 'Karyawan Kontrak', 'value' => $employeeStats['kontrak'] ?? 0, 'icon' => 'fa-file-signature'],
                ['key' => 'magang', 'label' => 'Karyawan Magang', 'value' => $employeeStats['magang'] ?? 0, 'icon' => 'fa-user-graduate'],
            ] as $item)
                <div class="stat-card {{ $item['key'] }}">
                    <div class="stat-card-body">
                        <div>
                            <div class="stat-value">{{ number_format((int) $item['value'], 0, ',', '.') }}</div>
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
                <h3 class="page-subtitle">Daftar Karyawan</h3>
                <p class="page-description">Kelola informasi karyawan dalam sistem.</p>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-primary" onclick="openTambahModal()">
                    <i class="fas fa-plus"></i> Tambah
                </button>
                <button type="button" class="btn btn-info" onclick="openImportModal()">
                    <i class="fas fa-upload"></i> Import CSV
                </button>
                <a href="{{ route('admin.karyawan.template') }}" class="btn btn-outline">
                    <i class="fas fa-download"></i> Template CSV
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header employee-card-header">
                <div class="employee-card-header-main">
                    <h3><i class="fas fa-users" style="color:#065F46; margin-right:8px;"></i>Data Karyawan</h3>
                    <span class="employee-card-subtitle">Filter, pantau, dan kelola seluruh data karyawan aktif.</span>
                </div>
                <div class="employee-card-header-tools">
                    <form method="GET" class="filter-form employee-card-header-form" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxCrudFragment">
                        <div class="filter-group">
                            <label>Cari Data</label>
                            <input type="search" name="q" class="filter-input" value="{{ $search ?? '' }}" placeholder="Cari NIK, nama, jabatan, departemen...">
                        </div>
                        <div class="filter-group">
                            <label>Jenis Karyawan</label>
                            <select name="jenis_karyawan" class="filter-input">
                                <option value="">Semua jenis</option>
                                @foreach (($employmentTypeOptions ?? []) as $employmentType)
                                    @php
                                        $employmentTypeLabel = match ($employmentType) {
                                            'kontrak' => 'Kontrak',
                                            'magang' => 'Magang',
                                            default => 'Tetap',
                                        };
                                    @endphp
                                    <option value="{{ $employmentType }}" @selected(($employmentTypeFilter ?? '') === $employmentType)>{{ $employmentTypeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Tampil</label>
                            <select name="per_page" class="filter-input">
                                @foreach ([10, 25, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected(($perPage ?? 10) === $size)>{{ $size }} / halaman</option>
                                @endforeach
                            </select>
                        </div>
                        <a href="{{ route('admin.karyawan') }}" class="btn-reset" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment">Reset</a>
                    </form>
                    <span class="total-data">Total: {{ $karyawanList->total() }} data</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table-bordered">
                    <thead>
                        <tr class="table-header">
                            <th width="50">No</th>
                            <th>NIK</th>
                            <th>UID RFID</th>
                            <th>Nama Lengkap</th>
                            <th>Jabatan</th>
                            <th>Departemen</th>
                            <th>No Telepon</th>
                            <th>Jenis Karyawan</th>
                            <th>Status</th>
                            <th width="300">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($karyawanList as $index => $karyawan)
                            <tr>
                                <td class="text-center">{{ ($karyawanList->firstItem() ?? 1) + $index }}</td>
                                <td>{{ $karyawan->nik }}</td>
                                <td>
                                    @if ($karyawan->rfid_uid)
                                        <code>{{ $karyawan->rfid_uid }}</code>
                                    @else
                                        <span class="rfid-empty">Belum dipairing</span>
                                    @endif
                                </td>
                                <td><strong>{{ $karyawan->nama_lengkap }}</strong></td>
                                <td>{{ $karyawan->jabatan ?: '-' }}</td>
                                <td>{{ $karyawan->departemen ?: '-' }}</td>
                                <td>{{ $karyawan->telepon ?: $karyawan->no_telp ?: '-' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $karyawan->jenis_karyawan_badge_class }}">
                                        {{ $karyawan->jenis_karyawan_label }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $karyawan->employment_status_badge_class }}">
                                        {{ $karyawan->employment_status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="employee-action-stack">
                                        @php
                                            $employeePayload = [
                                                'id' => $karyawan->id,
                                                'nik' => $karyawan->nik,
                                                'nama_lengkap' => $karyawan->nama_lengkap,
                                                'email' => $karyawan->email,
                                                'telepon' => $karyawan->telepon ?? $karyawan->no_telp,
                                                'rfid_uid' => $karyawan->rfid_uid,
                                                'jenis_karyawan' => $karyawan->jenis_karyawan,
                                                'jabatan_id' => $karyawan->jabatan_id,
                                                'departemen_id' => $karyawan->departemen_id,
                                                'lokasi_gps_id' => $karyawan->lokasi_gps_id,
                                                'shift_id' => $karyawan->shift_id,
                                                'jenis_jam_kerja' => $karyawan->jenis_jam_kerja,
                                                'shift_rotation_mode' => $karyawan->shift_rotation_mode,
                                                'shift_rotation_ids' => $karyawan->shift_rotation_ids ?? [],
                                                'shift_rotation_start' => optional($karyawan->shift_rotation_start)->format('Y-m-d'),
                                                'tgl_join' => optional($karyawan->tgl_join)->format('Y-m-d'),
                                                'durasi_kerja_fleksibel' => $karyawan->durasi_kerja_fleksibel,
                                                'status' => $karyawan->status ?? 'aktif',
                                                'alamat' => $karyawan->alamat,
                                                'tgl_lahir' => optional($karyawan->tgl_lahir)->format('Y-m-d'),
                                                'gender' => $karyawan->gender,
                                                'status_nikah' => $karyawan->status_nikah,
                                                'ktp' => $karyawan->ktp,
                                                'kartu_keluarga' => $karyawan->kartu_keluarga,
                                                'sim' => $karyawan->sim,
                                                'bpjs_kesehatan' => $karyawan->bpjs_kesehatan,
                                                'bpjs_ketenagakerjaan' => $karyawan->bpjs_ketenagakerjaan,
                                                'npwp' => $karyawan->npwp,
                                                'tax_counterpart_opt' => $karyawan->tax_counterpart_opt ?? 'Resident',
                                                'tax_passport_number' => $karyawan->tax_passport_number,
                                                'tax_has_second_employer' => $karyawan->tax_has_second_employer ? '1' : '0',
                                                'tax_prev_withholding_slip_number' => $karyawan->tax_prev_withholding_slip_number,
                                                'tax_prev_gross_income' => $karyawan->tax_prev_gross_income,
                                                'tax_prev_pph21_paid' => $karyawan->tax_prev_pph21_paid,
                                                'tax_prev_retirement_contribution' => $karyawan->tax_prev_retirement_contribution,
                                                'tax_certificate' => $karyawan->tax_certificate ?? 'N/A',
                                                'no_pkwt' => $karyawan->no_pkwt,
                                                'no_kontrak' => $karyawan->no_kontrak,
                                                'masa_berlaku' => optional($karyawan->masa_berlaku)->format('Y-m-d'),
                                                'tanggal_mulai_pkwt' => optional($karyawan->tanggal_mulai_pkwt)->format('Y-m-d'),
                                                'tanggal_berakhir_pkwt' => optional($karyawan->tanggal_berakhir_pkwt)->format('Y-m-d'),
                                                'nama_bank' => $karyawan->nama_bank,
                                                'rekening' => $karyawan->rekening,
                                                'nama_rekening' => $karyawan->nama_rekening,
                                                'izin_cuti' => $karyawan->izin_cuti,
                                                'izin_lainnya' => $karyawan->izin_lainnya,
                                                'izin_telat' => $karyawan->izin_telat,
                                                'izin_pulang_cepat' => $karyawan->izin_pulang_cepat,
                                                'bpjs_mode' => $karyawan->bpjs_mode === 'auto' ? 'auto' : 'off',
                                                'pph21_mode' => $karyawan->pph21_mode,
                                                'thr_mode' => $karyawan->thr_mode,
                                                'thr_manual_amount' => $karyawan->thr_manual_amount,
                                                'tipe_penggajian' => $karyawan->tipe_penggajian,
                                                'gaji_pokok' => $karyawan->gaji_pokok,
                                                'gaji_per_hari' => $karyawan->gaji_per_hari,
                                                'bonus_pribadi' => $karyawan->bonus_pribadi,
                                                'bonus_team' => $karyawan->bonus_team,
                                                'premi_kehadiran' => $karyawan->premi_kehadiran,
                                                'premi_kehadiran_mode' => $karyawan->premi_kehadiran_mode,
                                                'premi_kehadiran_toleransi_telat' => $karyawan->premi_kehadiran_toleransi_telat,
                                                'premi_kehadiran_toleransi_pulang_cepat' => $karyawan->premi_kehadiran_toleransi_pulang_cepat,
                                                'payroll_profile' => [
                                                    'bpjs_mode' => $karyawan->bpjs_mode === 'auto' ? 'auto' : 'off',
                                                    'pph21_mode' => $karyawan->pph21_mode,
                                                    'thr_mode' => $karyawan->thr_mode,
                                                    'thr_manual_amount' => $karyawan->thr_manual_amount,
                                                    'tipe_penggajian' => $karyawan->tipe_penggajian,
                                                    'gaji_pokok' => $karyawan->gaji_pokok,
                                                    'gaji_per_hari' => $karyawan->komponenGaji?->gaji_per_hari ?? $karyawan->gaji_per_hari,
                                                    'tunjangan_jabatan' => $karyawan->komponenGaji?->tunjangan_jabatan,
                                                    'tunjangan_makan' => $karyawan->komponenGaji?->tunjangan_makan,
                                                    'tunjangan_transport' => $karyawan->komponenGaji?->tunjangan_transport,
                                                    'potongan_izin' => $karyawan->komponenGaji?->potongan_izin,
                                                    'potongan_mangkir' => $karyawan->komponenGaji?->potongan_mangkir,
                                                    'potongan_terlambat' => $karyawan->komponenGaji?->potongan_terlambat,
                                                    'tarif_lembur_per_jam' => $karyawan->komponenGaji?->tarif_lembur_per_jam,
                                                    'bonus_pribadi' => $karyawan->bonus_pribadi,
                                                    'bonus_team' => $karyawan->bonus_team,
                                                    'premi_kehadiran' => $karyawan->premi_kehadiran,
                                                    'premi_kehadiran_mode' => $karyawan->premi_kehadiran_mode,
                                                    'premi_kehadiran_toleransi_telat' => $karyawan->premi_kehadiran_toleransi_telat,
                                                    'premi_kehadiran_toleransi_pulang_cepat' => $karyawan->premi_kehadiran_toleransi_pulang_cepat,
                                                ],
                                            ];
                                        @endphp
                                        <div class="employee-action-row top">
                                            <a href="{{ route('admin.karyawan.show', $karyawan) }}" class="btn btn-info">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                            <button
                                                type="button"
                                                class="btn btn-warning"
                                                data-update-url="{{ route('admin.karyawan.update', $karyawan) }}"
                                                data-employee='@json($employeePayload)'
                                                onclick="openEditModal(this)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-secondary"
                                                data-resign-url="{{ route('admin.karyawan.resign.store', $karyawan) }}"
                                                data-employee-name="{{ $karyawan->nama_lengkap }}"
                                                data-employee-nik="{{ $karyawan->nik }}"
                                                onclick="openResignModal(this)">
                                                <i class="fas fa-user-minus"></i> Resign
                                            </button>
                                        </div>
                                        <div class="employee-action-row bottom">
                                            <form method="POST" action="{{ route('admin.karyawan.reset-password', $karyawan) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Reset password karyawan ini ke 123456?">
                                                @csrf
                                                <button type="submit" class="btn btn-info">
                                                    <i class="fas fa-key"></i> Reset PW
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.karyawan.destroy', $karyawan) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Yakin ingin menghapus karyawan ini?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="empty-state">Belum ada data karyawan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $karyawanList->links('partials.pagination-ajax', ['target' => '#ajaxCrudFragment']) }}
        </div>
    </div>

    <div id="tambahModal" class="modal{{ $openTambahModal ? ' show' : '' }}">
        <div class="modal-dialog modal-dialog-employee">
            <div class="modal-content">
                <form method="POST" id="tambahForm" action="{{ route('admin.karyawan.store') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#tambahModal" data-reset-on-success="true">
                    @csrf
                    <input type="hidden" name="form_type" value="create">
                    <div class="modal-header employee-wizard-modal-header">
                        <div class="employee-wizard-modal-title">
                            <h3>Tambah Karyawan Baru</h3>
                            @include('admin.karyawan.partials.wizard-progress', [
                                'stepLabels' => $employeeWizardStepLabels,
                                'activeStep' => 0,
                            ])
                        </div>
                        <button type="button" class="modal-close" onclick="closeTambahModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        @include('admin.karyawan.partials.form-wizard', [
                            'formType' => 'create',
                            'employee' => null,
                            'payrollProfile' => $createPayrollDefaults,
                            'payrollDefaults' => $createPayrollDefaults,
                        ])
                    </div>
                    <div class="modal-footer employee-wizard-footer">
                        <div class="employee-wizard-footer-group is-active" data-wizard-footer-group="start">
                            <button type="button" class="btn btn-primary" data-wizard-next>Selanjutnya</button>
                        </div>
                        <div class="employee-wizard-footer-group" data-wizard-footer-group="middle">
                            <button type="button" class="btn btn-secondary" data-wizard-prev>Sebelumnya</button>
                            <button type="button" class="btn btn-primary" data-wizard-next>Selanjutnya</button>
                        </div>
                        <div class="employee-wizard-footer-group" data-wizard-footer-group="end">
                            <button type="button" class="btn btn-secondary" data-wizard-prev>Sebelumnya</button>
                            <button type="submit" class="btn btn-primary" data-wizard-submit>Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="importModal" class="modal{{ $openImportModal ? ' show' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Import Data Karyawan (CSV)</h3>
                    <button type="button" class="modal-close" onclick="closeImportModal()">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.karyawan.import') }}" enctype="multipart/form-data" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#importModal" data-reset-on-success="true">
                    @csrf
                    <input type="hidden" name="form_type" value="import">

                    <div class="modal-body">
                        <div class="info-note" style="margin-bottom:16px;">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <strong>Panduan Import CSV:</strong><br>
                                - Pemisah kolom menggunakan <strong>titik koma (;)</strong><br>
                                - Urutan kolom: NIK;Nama Lengkap;Email;Telepon;Jabatan;Departemen;Alamat<br>
                                - Download template untuk contoh format
                            </div>
                        </div>
                        <div class="form-group">
                            <label>File CSV <span class="required">*</span></label>
                            <input type="file" name="file_csv" class="form-control-file" accept=".csv,.txt" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal{{ $openEditModal ? ' show' : '' }}">
        <div class="modal-dialog modal-dialog-employee">
            <div class="modal-content">
                <form method="POST" id="editForm" action="{{ $editKaryawan ? route('admin.karyawan.update', $editKaryawan) : '#' }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#editModal">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_type" value="edit">
                    <input type="hidden" name="edit_id" id="edit_id" value="{{ old('edit_id', $editKaryawan->id ?? '') }}">
                    <div class="modal-header employee-wizard-modal-header">
                        <div class="employee-wizard-modal-title">
                            <h3>Edit Karyawan</h3>
                            @include('admin.karyawan.partials.wizard-progress', [
                                'stepLabels' => $employeeWizardStepLabels,
                                'activeStep' => 0,
                            ])
                        </div>
                        <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        @include('admin.karyawan.partials.form-wizard', [
                            'formType' => 'edit',
                            'employee' => $editKaryawan,
                            'payrollProfile' => $editPayrollProfile,
                            'payrollDefaults' => $createPayrollDefaults,
                        ])
                    </div>
                    <div class="modal-footer employee-wizard-footer">
                        <div class="employee-wizard-footer-group is-active" data-wizard-footer-group="start">
                            <button type="button" class="btn btn-primary" data-wizard-next>Selanjutnya</button>
                        </div>
                        <div class="employee-wizard-footer-group" data-wizard-footer-group="middle">
                            <button type="button" class="btn btn-secondary" data-wizard-prev>Sebelumnya</button>
                            <button type="button" class="btn btn-primary" data-wizard-next>Selanjutnya</button>
                        </div>
                        <div class="employee-wizard-footer-group" data-wizard-footer-group="end">
                            <button type="button" class="btn btn-secondary" data-wizard-prev>Sebelumnya</button>
                            <button type="submit" class="btn btn-primary" data-wizard-submit>Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="resignModal" class="modal{{ $openResignModal ? ' show' : '' }}">
        <div class="modal-dialog modal-dialog-resign">
            <div class="modal-content">
                <form method="POST" id="resignForm" action="#" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#resignModal" data-reset-on-success="true">
                    @csrf
                    <input type="hidden" name="form_type" value="resign">
                    <div class="modal-header">
                        <h3>Proses Karyawan Resign</h3>
                        <button type="button" class="modal-close" onclick="closeResignModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="resign-employee-box">
                            <div class="resign-employee-name" id="resignEmployeeName">-</div>
                            <div class="resign-employee-meta" id="resignEmployeeMeta">NIK: -</div>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Resign <span class="required">*</span></label>
                            <input type="date" name="tgl_resign" id="resignDateField" class="form-control" value="{{ old('tgl_resign', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="form-group">
                            <label>Alasan Resign</label>
                            <textarea name="alasan_resign" class="form-control" rows="4" placeholder="Contoh: kontrak selesai, mengundurkan diri, pensiun...">{{ old('alasan_resign') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeResignModal()">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-user-minus"></i> Simpan Resign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const rfidPairingStartUrl = @json(route('admin.karyawan.rfid-pairing.start'));
        const rfidPairingPollUrl = @json(route('admin.karyawan.rfid-pairing.poll'));

        const rfidPairingState = {
            active: false,
            afterId: 0,
            employeeId: '',
            targetInputId: '',
            feedbackTargetId: '',
            buttonElement: null,
            targetInputElement: null,
            originalReadOnly: false,
            timeoutSeconds: 30,
            expiresAt: 0,
            pollTimer: null,
            timeoutTimer: null,
            requestInFlight: false,
        };

        function getTambahModal() {
            return document.getElementById('tambahModal');
        }

        function getTambahForm() {
            return document.getElementById('tambahForm');
        }

        function getImportModal() {
            return document.getElementById('importModal');
        }

        function getEditModal() {
            return document.getElementById('editModal');
        }

        function getEditForm() {
            return document.getElementById('editForm');
        }

        const createPayrollDefaults = @json($createPayrollDefaults);
        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        function getWizardPanels(form) {
            return Array.from(form.querySelectorAll('[data-wizard-panel]'));
        }

        function getWizardProgressItems(form) {
            return Array.from(form.querySelectorAll('[data-wizard-progress-item]'));
        }

        function getWizardButtons(form, selector) {
            return form.querySelector(selector);
        }

        function setWizardButtonsHidden(form, selector, hidden) {
            form.querySelectorAll(selector).forEach(function (button) {
                button.hidden = hidden;
            });
        }

        function getWizardCurrentStep(form) {
            const value = Number(form.dataset.wizardStep || '0');

            return Number.isNaN(value) ? 0 : value;
        }

        function setWizardCurrentStep(form, stepIndex) {
            form.dataset.wizardStep = String(stepIndex);
        }

        function getWizardStepFromField(field) {
            const panel = field?.closest('[data-wizard-panel]');

            if (!panel) {
                return -1;
            }

            const stepIndex = Number(panel.dataset.wizardPanel || '-1');

            return Number.isNaN(stepIndex) ? -1 : stepIndex;
        }

        function getFirstWizardErrorStep(form) {
            const invalidField = form.querySelector('.is-invalid');

            if (!invalidField) {
                return -1;
            }

            return getWizardStepFromField(invalidField);
        }

        function setFormValue(form, fieldName, value) {
            const field = form.querySelector(`[name="${fieldName.replace(/"/g, '\\"')}"]`);

            if (!field) {
                return;
            }

            field.value = value ?? '';
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function setCheckboxGroupValue(form, fieldName, values) {
            const normalizedValues = Array.isArray(values)
                ? values.map((item) => String(item))
                : [];

            form.querySelectorAll(`[name="${fieldName.replace(/"/g, '\\"')}"]`).forEach(function (field) {
                field.checked = normalizedValues.includes(field.value);
                field.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }

        function getMoneyFormatter() {
            return new Intl.NumberFormat('id-ID');
        }

        function formatMoneyValue(value) {
            if (value === null || value === undefined || value === '') {
                return '';
            }

            const numericValue = Number(value);

            if (Number.isNaN(numericValue)) {
                return '';
            }

            return getMoneyFormatter().format(numericValue);
        }

        function bindMoneyFields() {
            const formatter = getMoneyFormatter();

            document.querySelectorAll('.money-field').forEach(function (field) {
                if (field.dataset.moneyBound === 'true') {
                    return;
                }

                field.dataset.moneyBound = 'true';

                const formatField = function () {
                    const raw = field.value.replace(/[^\d]/g, '');

                    if (raw === '') {
                        field.value = '';
                        return;
                    }

                    field.value = formatter.format(Number(raw));
                };

                field.addEventListener('focus', function () {
                    field.value = field.value.replace(/\./g, '');
                });

                field.addEventListener('blur', formatField);
                formatField();
            });
        }

        function updateWizardNavigation(form) {
            const panels = getWizardPanels(form);
            const currentStep = Math.min(Math.max(getWizardCurrentStep(form), 0), Math.max(panels.length - 1, 0));
        const lastStep = Math.max(panels.length - 1, 0);
            const footerGroups = {
                start: form.querySelector('[data-wizard-footer-group="start"]'),
                middle: form.querySelector('[data-wizard-footer-group="middle"]'),
                end: form.querySelector('[data-wizard-footer-group="end"]'),
            };

            setWizardCurrentStep(form, currentStep);

            panels.forEach(function (panel, index) {
                panel.classList.toggle('is-active', index === currentStep);
            });

            getWizardProgressItems(form).forEach(function (item, index) {
                item.classList.toggle('is-active', index === currentStep);
                item.classList.toggle('is-complete', index < currentStep);
            });

            setWizardButtonsHidden(form, '[data-wizard-prev]', currentStep === 0);
            setWizardButtonsHidden(form, '[data-wizard-next]', currentStep >= lastStep);
            setWizardButtonsHidden(form, '[data-wizard-submit]', currentStep < lastStep);

            if (footerGroups.start) {
                footerGroups.start.classList.toggle('is-active', currentStep === 0);
            }

            if (footerGroups.middle) {
                footerGroups.middle.classList.toggle('is-active', currentStep > 0 && currentStep < lastStep);
            }

            if (footerGroups.end) {
                footerGroups.end.classList.toggle('is-active', currentStep === lastStep);
            }
        }

        function updateWorkTypeFields(form) {
            const workTypeField = form.querySelector('[name="jenis_jam_kerja"]');
            const workType = workTypeField?.value || 'tetap';
            const isFlexible = workType === 'fleksibel';
            const isRolling = workType === 'rolling';
            const joinField = form.querySelector('[name="tgl_join"]');
            const joinValue = joinField?.value ?? '';

            form.querySelectorAll('.js-flex-work-field').forEach(function (fieldGroup) {
                fieldGroup.style.display = isFlexible ? '' : 'none';

                fieldGroup.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = !isFlexible;
                });
            });

            form.querySelectorAll('.js-rolling-work-field').forEach(function (fieldGroup) {
                fieldGroup.style.display = isRolling ? '' : 'none';

                fieldGroup.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = !isRolling;
                });
            });

            form.querySelectorAll('.js-shift-default-field').forEach(function (fieldGroup) {
                fieldGroup.style.display = (isFlexible || isRolling) ? 'none' : '';

                fieldGroup.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = isFlexible || isRolling;
                });
            });

            if (joinField && joinField.value !== joinValue) {
                joinField.value = joinValue;
            }

        }

        function updateEmploymentTypeFields(form) {
            const employmentTypeField = form.querySelector('[name="jenis_karyawan"]');
            const employmentType = employmentTypeField?.value || 'tetap';
            const showContractFields = employmentType === 'kontrak' || employmentType === 'magang';

            form.querySelectorAll('.js-contract-employment-field').forEach(function (fieldGroup) {
                fieldGroup.style.display = showContractFields ? '' : 'none';

                fieldGroup.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = !showContractFields;
                });
            });
        }

        function updatePayrollTypeFields(form) {
            const payrollTypeField = form.querySelector('[name="tipe_penggajian"]');
            const payrollType = payrollTypeField?.value || 'bulanan';
            const isDailyPayroll = payrollType === 'harian';

            form.querySelectorAll('.js-payroll-bulanan-field').forEach(function (fieldGroup) {
                fieldGroup.style.display = isDailyPayroll ? 'none' : '';

                fieldGroup.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = isDailyPayroll;
                });
            });

            form.querySelectorAll('.js-payroll-harian-field').forEach(function (fieldGroup) {
                fieldGroup.style.display = isDailyPayroll ? '' : 'none';

                fieldGroup.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = !isDailyPayroll;
                });
            });
        }

        function updateAttendancePremiumFields(form) {
            const premiumModeField = form.querySelector('[name="premi_kehadiran_mode"]');
            const premiumMode = premiumModeField?.value || 'nonaktif';
            const isTolerant = premiumMode === 'toleran';
            const isDisabled = premiumMode === 'nonaktif';

            form.querySelectorAll('.js-premi-amount-field').forEach(function (fieldGroup) {
                fieldGroup.style.display = isDisabled ? 'none' : '';
            });

            form.querySelectorAll('.js-premi-toleran-field').forEach(function (fieldGroup) {
                fieldGroup.style.display = isTolerant ? '' : 'none';

                fieldGroup.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = !isTolerant;
                });
            });
        }

        function updateThrFields(form) {
            const thrModeField = form.querySelector('[name="thr_mode"]');
            const thrMode = thrModeField?.value || 'auto';
            const isManual = thrMode === 'manual';

            form.querySelectorAll('.js-thr-manual-field').forEach(function (fieldGroup) {
                fieldGroup.style.display = isManual ? '' : 'none';

                fieldGroup.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = !isManual;
                });
            });
        }

        function updateTaxIdentityFields(form) {
            const counterpartField = form.querySelector('[name="tax_counterpart_opt"]');
            const isForeign = (counterpartField?.value || 'Resident') === 'Foreign';

            form.querySelectorAll('.js-tax-passport-field').forEach(function (fieldGroup) {
                fieldGroup.style.display = isForeign ? '' : 'none';

                if (!isForeign) {
                    fieldGroup.querySelectorAll('input, select, textarea').forEach(function (field) {
                        field.value = '';
                    });
                }
            });
        }

        function updatePriorEmployerFields(form) {
            const secondEmployerField = form.querySelector('[name="tax_has_second_employer"]');
            const isEnabled = (secondEmployerField?.value || '0') === '1';

            form.querySelectorAll('.js-tax-prior-employer-field').forEach(function (fieldGroup) {
                const isFormGroup = fieldGroup.classList.contains('form-group');
                fieldGroup.style.display = isEnabled ? '' : 'none';

                if (!isEnabled && isFormGroup) {
                    fieldGroup.querySelectorAll('input, select, textarea').forEach(function (field) {
                        if (field.classList.contains('money-field')) {
                            field.value = '0';
                        } else {
                            field.value = '';
                        }
                    });
                }
            });
        }

        function fillEmployeeForm(form, employee = {}, options = {}) {
            const payrollDefaults = options.payrollDefaults || createPayrollDefaults;
            const payrollProfile = {
                ...payrollDefaults,
                ...(employee.payroll_profile || employee.payrollProfile || {}),
            };
            const shiftRotationIds = Array.isArray(employee.shift_rotation_ids) ? employee.shift_rotation_ids : [];
            const pairButton = form.querySelector('.rfid-pair-button');

            setFormValue(form, 'nik', employee.nik ?? '');
            setFormValue(form, 'nama_lengkap', employee.nama_lengkap ?? '');
            setFormValue(form, 'email', employee.email ?? '');
            setFormValue(form, 'telepon', employee.telepon ?? employee.no_telp ?? '');
            setFormValue(form, 'status', employee.status ?? 'aktif');
            setFormValue(form, 'jenis_karyawan', employee.jenis_karyawan ?? 'tetap');
            setFormValue(form, 'jabatan_id', employee.jabatan_id ?? '');
            setFormValue(form, 'departemen_id', employee.departemen_id ?? '');
            setFormValue(form, 'lokasi_gps_id', employee.lokasi_gps_id ?? '');
            setFormValue(form, 'jenis_jam_kerja', employee.jenis_jam_kerja ?? 'tetap');
            setFormValue(form, 'shift_id', employee.shift_id ?? '');
            setFormValue(form, 'shift_rotation_mode', employee.shift_rotation_mode ?? 'daily');
            setFormValue(form, 'shift_rotation_start', employee.shift_rotation_start ?? '');
            setFormValue(form, 'tgl_join', employee.tgl_join ?? '');
            setFormValue(form, 'durasi_kerja_fleksibel', employee.durasi_kerja_fleksibel ?? 8);
            setFormValue(form, 'rfid_uid', employee.rfid_uid ?? '');
            setFormValue(form, 'tgl_lahir', employee.tgl_lahir ?? '');
            setFormValue(form, 'gender', employee.gender ?? '');
            setFormValue(form, 'status_nikah', employee.status_nikah ?? '');
            setFormValue(form, 'ktp', employee.ktp ?? '');
            setFormValue(form, 'kartu_keluarga', employee.kartu_keluarga ?? '');
            setFormValue(form, 'sim', employee.sim ?? '');
            setFormValue(form, 'bpjs_kesehatan', employee.bpjs_kesehatan ?? '');
            setFormValue(form, 'bpjs_ketenagakerjaan', employee.bpjs_ketenagakerjaan ?? '');
            setFormValue(form, 'npwp', employee.npwp ?? '');
            setFormValue(form, 'tax_counterpart_opt', employee.tax_counterpart_opt ?? 'Resident');
            setFormValue(form, 'tax_passport_number', employee.tax_passport_number ?? '');
            setFormValue(form, 'tax_has_second_employer', employee.tax_has_second_employer ?? '0');
            setFormValue(form, 'tax_prev_withholding_slip_number', employee.tax_prev_withholding_slip_number ?? '');
            setFormValue(form, 'tax_prev_gross_income', formatMoneyValue(employee.tax_prev_gross_income ?? 0));
            setFormValue(form, 'tax_prev_pph21_paid', formatMoneyValue(employee.tax_prev_pph21_paid ?? 0));
            setFormValue(form, 'tax_prev_retirement_contribution', formatMoneyValue(employee.tax_prev_retirement_contribution ?? 0));
            setFormValue(form, 'tax_certificate', employee.tax_certificate ?? 'N/A');
            setFormValue(form, 'alamat', employee.alamat ?? '');
            setFormValue(form, 'no_pkwt', employee.no_pkwt ?? '');
            setFormValue(form, 'no_kontrak', employee.no_kontrak ?? '');
            setFormValue(form, 'masa_berlaku', employee.masa_berlaku ?? '');
            setFormValue(form, 'tanggal_mulai_pkwt', employee.tanggal_mulai_pkwt ?? '');
            setFormValue(form, 'tanggal_berakhir_pkwt', employee.tanggal_berakhir_pkwt ?? '');
            setFormValue(form, 'nama_bank', employee.nama_bank ?? '');
            setFormValue(form, 'rekening', employee.rekening ?? '');
            setFormValue(form, 'nama_rekening', employee.nama_rekening ?? '');
            setFormValue(form, 'izin_cuti', employee.izin_cuti ?? 0);
            setFormValue(form, 'izin_lainnya', employee.izin_lainnya ?? 0);
            setFormValue(form, 'izin_telat', employee.izin_telat ?? 0);
            setFormValue(form, 'izin_pulang_cepat', employee.izin_pulang_cepat ?? 0);
            setFormValue(form, 'bpjs_mode', employee.bpjs_mode ?? payrollProfile.bpjs_mode ?? 'auto');
            setFormValue(form, 'pph21_mode', employee.pph21_mode ?? payrollProfile.pph21_mode ?? 'auto');
            setFormValue(form, 'thr_mode', employee.thr_mode ?? payrollProfile.thr_mode ?? 'auto');
            setFormValue(form, 'thr_manual_amount', formatMoneyValue(employee.thr_manual_amount ?? payrollProfile.thr_manual_amount ?? 0));
            setFormValue(form, 'tipe_penggajian', employee.tipe_penggajian ?? payrollProfile.tipe_penggajian ?? 'bulanan');
            setFormValue(form, 'gaji_pokok', formatMoneyValue(employee.gaji_pokok ?? payrollProfile.gaji_pokok ?? 0));
            setFormValue(form, 'gaji_per_hari', formatMoneyValue(employee.gaji_per_hari ?? payrollProfile.gaji_per_hari ?? 0));
            setFormValue(form, 'tunjangan_jabatan', formatMoneyValue(payrollProfile.tunjangan_jabatan ?? 0));
            setFormValue(form, 'tunjangan_makan', formatMoneyValue(payrollProfile.tunjangan_makan ?? 0));
            setFormValue(form, 'tunjangan_transport', formatMoneyValue(payrollProfile.tunjangan_transport ?? 0));
            setFormValue(form, 'potongan_izin', formatMoneyValue(payrollProfile.potongan_izin ?? 0));
            setFormValue(form, 'potongan_mangkir', formatMoneyValue(payrollProfile.potongan_mangkir ?? 0));
            setFormValue(form, 'potongan_terlambat', formatMoneyValue(payrollProfile.potongan_terlambat ?? 0));
            setFormValue(form, 'tarif_lembur_per_jam', formatMoneyValue(payrollProfile.tarif_lembur_per_jam ?? 0));
            setFormValue(form, 'bonus_pribadi', formatMoneyValue(employee.bonus_pribadi ?? payrollProfile.bonus_pribadi ?? 0));
            setFormValue(form, 'bonus_team', formatMoneyValue(employee.bonus_team ?? payrollProfile.bonus_team ?? 0));
            setFormValue(form, 'premi_kehadiran', formatMoneyValue(employee.premi_kehadiran ?? payrollProfile.premi_kehadiran ?? 0));
            setFormValue(form, 'premi_kehadiran_mode', employee.premi_kehadiran_mode ?? payrollProfile.premi_kehadiran_mode ?? 'nonaktif');
            setFormValue(form, 'premi_kehadiran_toleransi_telat', employee.premi_kehadiran_toleransi_telat ?? payrollProfile.premi_kehadiran_toleransi_telat ?? 0);
            setFormValue(form, 'premi_kehadiran_toleransi_pulang_cepat', employee.premi_kehadiran_toleransi_pulang_cepat ?? payrollProfile.premi_kehadiran_toleransi_pulang_cepat ?? 0);

            for (let slot = 0; slot < 3; slot += 1) {
                setFormValue(form, `shift_rotation_ids[${slot}]`, shiftRotationIds[slot] ?? '');
            }

            if (pairButton) {
                pairButton.dataset.employeeId = employee.id ?? '';
            }

            updateEmploymentTypeFields(form);
            updateWorkTypeFields(form);
            updatePayrollTypeFields(form);
            updateAttendancePremiumFields(form);
            updateThrFields(form);
            updateTaxIdentityFields(form);
            updatePriorEmployerFields(form);
            bindMoneyFields();
        }

        function goToWizardStep(form, stepIndex, options = {}) {
            const panels = getWizardPanels(form);
            const lastStep = Math.max(panels.length - 1, 0);
            const currentStep = Math.min(Math.max(getWizardCurrentStep(form), 0), lastStep);
            const nextStep = Math.min(Math.max(Number(stepIndex) || 0, 0), lastStep);

            if (!options.force && nextStep > currentStep) {
                const currentPanel = panels[currentStep];
                const invalidField = currentPanel?.querySelector(':invalid');

                if (invalidField) {
                    invalidField.reportValidity();
                    invalidField.focus();
                    return false;
                }
            }

            setWizardCurrentStep(form, nextStep);
            updateWizardNavigation(form);

            return true;
        }

        function resetTambahModalState() {
            const form = getTambahForm();

            if (!form) {
                renderPairingFeedback('create_rfid_feedback');
                return;
            }

            window.panelAjax?.clearErrors?.(form);
            fillEmployeeForm(form, {
                status: 'aktif',
                jenis_karyawan: 'tetap',
                jenis_jam_kerja: 'tetap',
                shift_rotation_mode: 'daily',
                tgl_join: '{{ now()->format('Y-m-d') }}',
                durasi_kerja_fleksibel: 8,
                payroll_profile: createPayrollDefaults,
                izin_cuti: 0,
                izin_lainnya: 0,
                izin_telat: 0,
                izin_pulang_cepat: 0,
                shift_rotation_ids: [],
            }, {
                payrollDefaults: createPayrollDefaults,
            });
            setWizardCurrentStep(form, 0);
            updateWizardNavigation(form);
            renderPairingFeedback('create_rfid_feedback');
        }

        function resetEditModalState() {
            const form = getEditForm();

            if (!form) {
                renderPairingFeedback('edit_rfid_feedback');
                return;
            }

            window.panelAjax?.clearErrors?.(form);
            form.action = '#';
            fillEmployeeForm(form, {
                status: 'aktif',
                jenis_karyawan: 'tetap',
                jenis_jam_kerja: 'tetap',
                shift_rotation_mode: 'daily',
                durasi_kerja_fleksibel: 8,
                payroll_profile: createPayrollDefaults,
                izin_cuti: 0,
                izin_lainnya: 0,
                izin_telat: 0,
                izin_pulang_cepat: 0,
                shift_rotation_ids: [],
            }, {
                payrollDefaults: createPayrollDefaults,
            });
            setFormValue(form, 'edit_id', '');
            setWizardCurrentStep(form, 0);
            updateWizardNavigation(form);
            renderPairingFeedback('edit_rfid_feedback');
        }

        function resetResignModalState() {
            const form = document.getElementById('resignForm');

            if (!form) {
                return;
            }

            window.panelAjax?.clearErrors?.(form);
            form.reset();
            form.action = '#';
            document.getElementById('resignEmployeeName').textContent = '-';
            document.getElementById('resignEmployeeMeta').textContent = 'NIK: -';

            const dateField = document.getElementById('resignDateField');
            if (dateField) {
                dateField.value = '{{ now()->format('Y-m-d') }}';
            }
        }

        function openTambahModal() {
            stopRfidPairing();
            resetTambahModalState();
            getTambahModal()?.classList.add('show');
        }

        function closeTambahModal() {
            stopRfidPairing();
            resetTambahModalState();
            getTambahModal()?.classList.remove('show');
        }

        function openImportModal() {
            getImportModal()?.classList.add('show');
        }

        function closeImportModal() {
            getImportModal()?.classList.remove('show');
        }

        function parseEmployeePayload(button) {
            try {
                return JSON.parse(button.dataset.employee || '{}');
            } catch (error) {
                return {};
            }
        }

        function openEditModal(button) {
            const employee = parseEmployeePayload(button);
            const editForm = getEditForm();

            stopRfidPairing();

            if (editForm) {
                window.panelAjax?.clearErrors?.(editForm);
                editForm.action = button.dataset.updateUrl || '#';
                fillEmployeeForm(editForm, employee, {
                    payrollDefaults: createPayrollDefaults,
                });
                setFormValue(editForm, 'edit_id', employee.id ?? '');
                setWizardCurrentStep(editForm, 0);
                updateWizardNavigation(editForm);
            }

            getEditModal()?.classList.add('show');
        }

        function closeEditModal() {
            stopRfidPairing();
            resetEditModalState();
            getEditModal()?.classList.remove('show');
        }

        function openResignModal(button) {
            const form = document.getElementById('resignForm');

            if (!form || !button) {
                return;
            }

            resetResignModalState();
            form.action = button.dataset.resignUrl || '#';
            document.getElementById('resignEmployeeName').textContent = button.dataset.employeeName || '-';
            document.getElementById('resignEmployeeMeta').textContent = `NIK: ${button.dataset.employeeNik || '-'}`;
            document.getElementById('resignModal')?.classList.add('show');
        }

        function closeResignModal() {
            resetResignModalState();
            document.getElementById('resignModal')?.classList.remove('show');
        }

        if (window.panelAjax && !window.panelAjax.employeeModalCloseWrapped) {
            const originalCloseRelatedModal = window.panelAjax.closeRelatedModal.bind(window.panelAjax);

            window.panelAjax.closeRelatedModal = function (form) {
                originalCloseRelatedModal(form);

                if (form?.dataset?.closeModal === '#tambahModal') {
                    resetTambahModalState();
                }

                if (form?.dataset?.closeModal === '#editModal') {
                    resetEditModalState();
                }

                if (form?.dataset?.closeModal === '#resignModal') {
                    resetResignModalState();
                }
            };

            window.panelAjax.employeeModalCloseWrapped = true;
        }

        function initEmployeeWizard(form) {
            if (!form || form.dataset.wizardBound === 'true') {
                return;
            }

            form.dataset.wizardBound = 'true';
            setWizardCurrentStep(form, 0);
            updateWizardNavigation(form);
            updateEmploymentTypeFields(form);
            updateWorkTypeFields(form);
            updatePayrollTypeFields(form);
            updateAttendancePremiumFields(form);
            updateThrFields(form);
            updateTaxIdentityFields(form);
            updatePriorEmployerFields(form);

            const employmentTypeField = form.querySelector('[name="jenis_karyawan"]');
            const workTypeField = form.querySelector('[name="jenis_jam_kerja"]');
            const payrollTypeField = form.querySelector('[name="tipe_penggajian"]');
            const premiumModeField = form.querySelector('[name="premi_kehadiran_mode"]');
            const thrModeField = form.querySelector('[name="thr_mode"]');
            const counterpartField = form.querySelector('[name="tax_counterpart_opt"]');
            const secondEmployerField = form.querySelector('[name="tax_has_second_employer"]');

            if (employmentTypeField) {
                employmentTypeField.addEventListener('change', function () {
                    updateEmploymentTypeFields(form);
                });
            }

            if (workTypeField) {
                workTypeField.addEventListener('change', function () {
                    updateWorkTypeFields(form);
                });
            }

            if (payrollTypeField) {
                payrollTypeField.addEventListener('change', function () {
                    updatePayrollTypeFields(form);
                });
            }

            if (premiumModeField) {
                premiumModeField.addEventListener('change', function () {
                    updateAttendancePremiumFields(form);
                });
            }

            if (thrModeField) {
                thrModeField.addEventListener('change', function () {
                    updateThrFields(form);
                });
            }

            if (counterpartField) {
                counterpartField.addEventListener('change', function () {
                    updateTaxIdentityFields(form);
                });
            }

            if (secondEmployerField) {
                secondEmployerField.addEventListener('change', function () {
                    updatePriorEmployerFields(form);
                });
            }

            form.querySelectorAll('[data-wizard-prev]').forEach(function (button) {
                button.addEventListener('click', function () {
                    goToWizardStep(form, getWizardCurrentStep(form) - 1, { force: true });
                });
            });

            form.querySelectorAll('[data-wizard-next]').forEach(function (button) {
                button.addEventListener('click', function () {
                    goToWizardStep(form, getWizardCurrentStep(form) + 1);
                });
            });

            const firstErrorStep = getFirstWizardErrorStep(form);

            if (firstErrorStep >= 0) {
                goToWizardStep(form, firstErrorStep, { force: true });
            }
        }

        function getFeedbackElementById(feedbackId) {
            if (!feedbackId) {
                return null;
            }

            return document.getElementById(feedbackId);
        }

        function renderPairingFeedback(feedbackId, message = '', variant = 'default') {
            const feedback = getFeedbackElementById(feedbackId);

            if (!feedback) {
                return;
            }

            feedback.textContent = message || feedback.dataset.defaultText || '';
            feedback.classList.remove('is-waiting', 'is-error', 'is-success');

            if (variant === 'waiting') {
                feedback.classList.add('is-waiting');
            }

            if (variant === 'success') {
                feedback.classList.add('is-success');
            }

            if (variant === 'error') {
                feedback.classList.add('is-error');
            }
        }

        function setPairingButtonLoading(button, loading) {
            if (!button) {
                return;
            }

            if (window.panelAjax?.setActionLoading) {
                window.panelAjax.setActionLoading(button, loading);
                return;
            }

            button.disabled = loading;
        }

        function stopRfidPairing(options = {}) {
            const {
                keepFeedback = false,
                message = '',
                variant = 'default',
                toastLevel = '',
                toastMessage = '',
            } = options;

            const button = rfidPairingState.buttonElement;
            const feedbackId = rfidPairingState.feedbackTargetId;
            const targetInput = rfidPairingState.targetInputElement;

            if (rfidPairingState.pollTimer) {
                window.clearInterval(rfidPairingState.pollTimer);
            }

            if (rfidPairingState.timeoutTimer) {
                window.clearTimeout(rfidPairingState.timeoutTimer);
            }

            setPairingButtonLoading(button, false);

            if (targetInput) {
                targetInput.readOnly = rfidPairingState.originalReadOnly;
                targetInput.removeAttribute('aria-busy');
            }

            if (feedbackId) {
                if (keepFeedback) {
                    renderPairingFeedback(feedbackId, message, variant);
                } else {
                    renderPairingFeedback(feedbackId);
                }
            }

            if (toastLevel && toastMessage) {
                window.panelToast?.show(toastLevel, toastMessage);
            }

            rfidPairingState.active = false;
            rfidPairingState.afterId = 0;
            rfidPairingState.employeeId = '';
            rfidPairingState.targetInputId = '';
            rfidPairingState.feedbackTargetId = '';
            rfidPairingState.buttonElement = null;
            rfidPairingState.targetInputElement = null;
            rfidPairingState.originalReadOnly = false;
            rfidPairingState.timeoutSeconds = 30;
            rfidPairingState.expiresAt = 0;
            rfidPairingState.pollTimer = null;
            rfidPairingState.timeoutTimer = null;
            rfidPairingState.requestInFlight = false;
        }

        async function pollRfidPairing() {
            if (!rfidPairingState.active || rfidPairingState.requestInFlight) {
                return;
            }

            rfidPairingState.requestInFlight = true;

            try {
                const params = new URLSearchParams({
                    after_id: String(rfidPairingState.afterId || 0),
                });

                if (rfidPairingState.employeeId) {
                    params.set('employee_id', rfidPairingState.employeeId);
                }

                const response = await fetch(`${rfidPairingPollUrl}?${params.toString()}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Gagal membaca scan RFID terbaru.');
                }

                if (!rfidPairingState.active) {
                    return;
                }

                if (typeof payload.after_id === 'number') {
                    rfidPairingState.afterId = payload.after_id;
                }

                if (!payload.found) {
                    return;
                }

                if (payload.status === 'owned_by_other') {
                    renderPairingFeedback(
                        rfidPairingState.feedbackTargetId,
                        payload.message || 'UID RFID sudah dipakai karyawan lain. Silakan scan kartu lain.',
                        'error'
                    );
                    return;
                }

                if (payload.status === 'ready' || payload.status === 'owned_by_current') {
                    const targetInput = document.getElementById(rfidPairingState.targetInputId);

                    if (targetInput) {
                        targetInput.value = payload.uid || '';
                        targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                        targetInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    stopRfidPairing({
                        keepFeedback: true,
                        message: payload.message || 'UID RFID berhasil diambil dari hasil scan.',
                        variant: 'success',
                        toastLevel: 'success',
                        toastMessage: payload.message || 'UID RFID berhasil diisi otomatis.',
                    });
                }
            } catch (error) {
                renderPairingFeedback(
                    rfidPairingState.feedbackTargetId,
                    error.message || 'Gagal membaca scan RFID terbaru.',
                    'error'
                );
            } finally {
                rfidPairingState.requestInFlight = false;
            }
        }

        async function startRfidPairing(button) {
            const data = button.dataset;
            stopRfidPairing();

            const targetInput = document.getElementById(data.targetInput || '');

            rfidPairingState.buttonElement = button;
            rfidPairingState.feedbackTargetId = data.feedbackTarget || '';
            rfidPairingState.targetInputId = data.targetInput || '';
            rfidPairingState.employeeId = data.employeeId || '';
            rfidPairingState.targetInputElement = targetInput;
            rfidPairingState.originalReadOnly = Boolean(targetInput?.readOnly);

            if (targetInput) {
                targetInput.readOnly = true;
                targetInput.setAttribute('aria-busy', 'true');
            }

            setPairingButtonLoading(button, true);
            renderPairingFeedback(
                rfidPairingState.feedbackTargetId,
                'Menunggu scan kartu RFID dari mesin absensi...',
                'waiting'
            );

            try {
                const formData = new FormData();

                if (rfidPairingState.employeeId) {
                    formData.append('employee_id', rfidPairingState.employeeId);
                }

                const response = await fetch(rfidPairingStartUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const payload = await response.json();

                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Tidak bisa memulai pairing RFID.');
                }

                rfidPairingState.active = true;
                rfidPairingState.afterId = Number(payload.anchor_id || 0);
                rfidPairingState.timeoutSeconds = Number(payload.timeout_seconds || 30);
                rfidPairingState.expiresAt = Date.now() + (rfidPairingState.timeoutSeconds * 1000);

                rfidPairingState.pollTimer = window.setInterval(pollRfidPairing, 1000);
                rfidPairingState.timeoutTimer = window.setTimeout(() => {
                    stopRfidPairing({
                        keepFeedback: true,
                        message: 'Waktu pairing RFID habis. Klik Pair RFID lalu scan ulang.',
                        variant: 'error',
                        toastLevel: 'warning',
                        toastMessage: 'Waktu pairing RFID habis. Silakan coba lagi.',
                    });
                }, rfidPairingState.timeoutSeconds * 1000);

                await pollRfidPairing();
            } catch (error) {
                stopRfidPairing({
                    keepFeedback: true,
                    message: error.message || 'Tidak bisa memulai pairing RFID.',
                    variant: 'error',
                    toastLevel: 'error',
                    toastMessage: error.message || 'Tidak bisa memulai pairing RFID.',
                });
            }
        }

        if (window.panelAjax?.renderErrors) {
            const originalRenderErrors = window.panelAjax.renderErrors.bind(window.panelAjax);

            window.panelAjax.renderErrors = function (form, errors) {
                originalRenderErrors(form, errors);

                if (!form?.querySelector('[data-wizard-panel]')) {
                    return;
                }

                const firstErrorStep = getFirstWizardErrorStep(form);

                if (firstErrorStep >= 0) {
                    goToWizardStep(form, firstErrorStep, { force: true });
                }

                updateWorkTypeFields(form);
                updatePayrollTypeFields(form);
            };
        }

        initEmployeeWizard(getTambahForm());
        initEmployeeWizard(getEditForm());
        bindMoneyFields();

        window.addEventListener('click', function (event) {
            if (event.target === getTambahModal()) {
                closeTambahModal();
            }

            if (event.target === getImportModal()) {
                closeImportModal();
            }

            if (event.target === getEditModal()) {
                closeEditModal();
            }

            if (event.target === document.getElementById('resignModal')) {
                closeResignModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            if (getTambahModal()?.classList.contains('show')) {
                closeTambahModal();
                return;
            }

            if (getEditModal()?.classList.contains('show')) {
                closeEditModal();
                return;
            }

            if (document.getElementById('resignModal')?.classList.contains('show')) {
                closeResignModal();
            }
        });
    </script>
@endsection
