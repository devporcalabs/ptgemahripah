@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Jenis Cuti'])

@php
    $openTambahModal = $errors->any() && old('form_type') === 'create';
    $openEditModal = (bool) $editJenisIzin || ($errors->any() && old('form_type') === 'edit');
    $openPolicyModal = $errors->any() && old('policy_form') === 'leave_quota_policy';

    $resolveQuotaMode = function ($jenisIzin): string {
        if (! $jenisIzin) {
            return 'none';
        }

        if ($jenisIzin->quota_field === 'izin_cuti') {
            return 'employee_cuti';
        }

        if ($jenisIzin->quota_field === 'izin_lainnya') {
            return 'employee_izin';
        }

        if ((int) ($jenisIzin->annual_quota_days ?? 0) > 0) {
            return 'annual_manual';
        }

        return 'none';
    };
@endphp

@section('styles')
    .table-bordered { font-size:13px; }
    .table-bordered th, .table-bordered td { padding:10px 12px; vertical-align:top; }
    .action-buttons { gap:6px; }
    .meta-stack { display:flex; flex-direction:column; gap:4px; }
    .meta-note { font-size:12px; color:#6b7280; }
    .badge-inline { display:inline-flex; align-items:center; padding:4px 8px; border-radius:999px; font-size:11px; font-weight:700; line-height:1.2; }
    .badge-success-soft { background:#dcfce7; color:#166534; }
    .badge-danger-soft { background:#fee2e2; color:#991b1b; }
    .badge-warning-soft { background:#fef3c7; color:#92400e; }
    .quota-manual-group[hidden] { display:none !important; }
@endsection

@section('content')
    <div id="ajaxCrudFragment">
        @php
            $currentLeavePolicy = old('leave_quota_policy', $leaveSettings->leave_quota_policy ?? \App\Models\Setting::LEAVE_QUOTA_POLICY_ANNUAL_RESET);
            $showCarryLimitField = $currentLeavePolicy === \App\Models\Setting::LEAVE_QUOTA_POLICY_CARRY_LIMITED;
        @endphp

        <div class="page-header">
            <div>
                <h3 class="page-subtitle">Master Jenis Cuti</h3>
                <p class="page-description">Kelola tipe izin dan cuti untuk form pengajuan karyawan.</p>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="openPolicyModal()">
                    <i class="fas fa-sliders-h"></i> Aturan Kuota
                </button>
                <button type="button" class="btn btn-primary" onclick="openTambahModal()">
                    <i class="fas fa-plus"></i> Tambah Jenis Cuti
                </button>
            </div>
        </div>

        <div class="filter-bar">
            <form method="GET" class="filter-form" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxCrudFragment">
                <div class="filter-group">
                    <label>Cari Data</label>
                    <input type="search" name="q" class="filter-input" value="{{ $search ?? '' }}" placeholder="Cari nama, kode, atau deskripsi...">
                </div>
                <div class="filter-group">
                    <label>Tampil</label>
                    <select name="per_page" class="filter-input">
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected(($perPage ?? 10) === $size)>{{ $size }} / halaman</option>
                        @endforeach
                    </select>
                </div>
                <div class="button-group">
                    <a href="{{ route('admin.jenis-izin') }}" class="btn-reset" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment">Reset</a>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table-bordered">
                    <thead>
                        <tr class="table-header">
                            <th width="50">No</th>
                            <th width="220">Jenis Cuti</th>
                            <th width="130">Mapping</th>
                            <th width="150">Pembayaran</th>
                            <th width="180">Kuota</th>
                            <th width="120">Maks / Ajuan</th>
                            <th width="110">Lampiran</th>
                            <th width="110">Status</th>
                            <th width="120">Dipakai</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jenisIzinList as $index => $jenisIzin)
                            @php
                                $quotaMode = $resolveQuotaMode($jenisIzin);
                                $quotaLabel = match ($quotaMode) {
                                    'employee_cuti' => 'Kuota cuti karyawan',
                                    'employee_izin' => 'Kuota izin karyawan',
                                    'annual_manual' => (($jenisIzin->annual_quota_days ?? 0) > 0 ? $jenisIzin->annual_quota_days.' hari / tahun' : '-'),
                                    default => 'Tanpa kuota',
                                };
                                $maxDays = (int) ($jenisIzin->max_days_per_request ?? 0);
                            @endphp
                            <tr>
                                <td class="text-center">{{ ($jenisIzinList->firstItem() ?? 1) + $index }}</td>
                                <td>
                                    <div class="meta-stack">
                                        <strong>{{ $jenisIzin->nama }}</strong>
                                        <span class="meta-note">Kode: {{ $jenisIzin->kode }}</span>
                                        @if ($jenisIzin->description)
                                            <span class="meta-note">{{ $jenisIzin->description }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $legacyCodeOptions[$jenisIzin->legacy_code] ?? ucfirst($jenisIzin->legacy_code) }}</td>
                                <td>
                                    <span class="badge-inline {{ $jenisIzin->is_paid ? 'badge-success-soft' : 'badge-danger-soft' }}">
                                        {{ $jenisIzin->is_paid ? 'Dibayar' : 'Tidak Dibayar' }}
                                    </span>
                                </td>
                                <td>{{ $quotaLabel }}</td>
                                <td>{{ $maxDays > 0 ? $maxDays.' hari' : '-' }}</td>
                                <td>{{ $jenisIzin->require_attachment ? 'Wajib' : 'Opsional' }}</td>
                                <td>
                                    <span class="badge-inline {{ $jenisIzin->is_active ? 'badge-success-soft' : 'badge-warning-soft' }}">
                                        {{ $jenisIzin->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $jenisIzin->izin_count }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button
                                            type="button"
                                            class="btn btn-warning"
                                            data-id="{{ $jenisIzin->id }}"
                                            data-update-url="{{ route('admin.jenis-izin.update', $jenisIzin) }}"
                                            data-nama="{{ $jenisIzin->nama }}"
                                            data-kode="{{ $jenisIzin->kode }}"
                                            data-description="{{ $jenisIzin->description }}"
                                            data-legacy-code="{{ $jenisIzin->legacy_code }}"
                                            data-is-paid="{{ $jenisIzin->is_paid ? '1' : '0' }}"
                                            data-quota-mode="{{ $quotaMode }}"
                                            data-annual-quota-days="{{ $jenisIzin->annual_quota_days }}"
                                            data-max-days-per-request="{{ $jenisIzin->max_days_per_request }}"
                                            data-require-attachment="{{ $jenisIzin->require_attachment ? '1' : '0' }}"
                                            data-is-active="{{ $jenisIzin->is_active ? '1' : '0' }}"
                                            data-sort-order="{{ $jenisIzin->sort_order }}"
                                            onclick="openEditModal(this)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.jenis-izin.destroy', $jenisIzin) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Yakin ingin menghapus jenis cuti ini?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="empty-state">Belum ada master jenis cuti.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $jenisIzinList->links('partials.pagination-ajax', ['target' => '#ajaxCrudFragment']) }}
        </div>
    </div>

    <div id="tambahModal" class="modal{{ $openTambahModal ? ' show' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Tambah Jenis Cuti</h3>
                    <button type="button" class="modal-close" onclick="closeTambahModal()">&times;</button>
                </div>
                <form method="POST" id="tambahForm" action="{{ route('admin.jenis-izin.store') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#tambahModal" data-reset-on-success="true">
                    @csrf
                    <input type="hidden" name="form_type" value="create">

                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Jenis Cuti <span class="required">*</span></label>
                                <input type="text" name="nama" class="form-control" value="{{ $openTambahModal ? old('nama') : '' }}" required>
                            </div>
                            <div class="form-group">
                                <label>Kode <span class="required">*</span></label>
                                <input type="text" name="kode" class="form-control" value="{{ $openTambahModal ? old('kode') : '' }}" placeholder="contoh: cuti_tahunan" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Mapping Payroll <span class="required">*</span></label>
                                <select name="legacy_code" class="form-control" required>
                                    @foreach ($legacyCodeOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($openTambahModal ? old('legacy_code') : 'cuti') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Urutan Tampil</label>
                                <input type="number" name="sort_order" class="form-control" min="0" max="9999" value="{{ $openTambahModal ? old('sort_order', 0) : 0 }}">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Pembayaran <span class="required">*</span></label>
                                <select name="is_paid" class="form-control" required>
                                    <option value="1" @selected((string) ($openTambahModal ? old('is_paid', '1') : '1') === '1')>Dibayar</option>
                                    <option value="0" @selected((string) ($openTambahModal ? old('is_paid') : '0') === '0')>Tidak Dibayar</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status <span class="required">*</span></label>
                                <select name="is_active" class="form-control" required>
                                    <option value="1" @selected((string) ($openTambahModal ? old('is_active', '1') : '1') === '1')>Aktif</option>
                                    <option value="0" @selected((string) ($openTambahModal ? old('is_active') : '0') === '0')>Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Aturan Kuota <span class="required">*</span></label>
                                <select name="quota_mode" class="form-control js-quota-mode" data-manual-target="#createAnnualQuotaGroup" required>
                                    @foreach ($quotaModeOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($openTambahModal ? old('quota_mode', 'none') : 'none') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group quota-manual-group" id="createAnnualQuotaGroup" @if (($openTambahModal ? old('quota_mode', 'none') : 'none') !== 'annual_manual') hidden @endif>
                                <label>Kuota per Tahun</label>
                                <input type="number" name="annual_quota_days" class="form-control" min="0" max="365" value="{{ $openTambahModal ? old('annual_quota_days') : '' }}" placeholder="hari kerja">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Maksimal per Pengajuan</label>
                                <input type="number" name="max_days_per_request" class="form-control" min="0" max="365" value="{{ $openTambahModal ? old('max_days_per_request') : '' }}" placeholder="0 = tanpa batas">
                            </div>
                            <div class="form-group">
                                <label>Lampiran <span class="required">*</span></label>
                                <select name="require_attachment" class="form-control" required>
                                    <option value="0" @selected((string) ($openTambahModal ? old('require_attachment', '0') : '0') === '0')>Opsional</option>
                                    <option value="1" @selected((string) ($openTambahModal ? old('require_attachment') : '0') === '1')>Wajib</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Opsional">{{ $openTambahModal ? old('description') : '' }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeTambahModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="policyModal" class="modal{{ $openPolicyModal ? ' show' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Aturan Kuota Cuti Tahunan</h3>
                    <button type="button" class="modal-close" onclick="closePolicyModal()">&times;</button>
                </div>
                <form method="POST" id="policyForm" action="{{ route('admin.jenis-izin.policy') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#policyModal">
                    @csrf
                    <input type="hidden" name="policy_form" value="leave_quota_policy">

                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Mode Akumulasi Cuti Tahunan</label>
                                <select name="leave_quota_policy" class="form-control js-leave-policy-mode" data-manual-target="#leaveCarryLimitGroup" required>
                                    @foreach ($leaveQuotaPolicyOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($currentLeavePolicy === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text">Berlaku hanya untuk jenis cuti yang memakai <strong>Kuota Cuti Karyawan</strong>.</small>
                            </div>
                            <div class="form-group leave-carry-limit-group" id="leaveCarryLimitGroup" @if (! $showCarryLimitField) hidden @endif>
                                <label>Maksimal Carry Over</label>
                                <input type="number" name="leave_carryover_max_days" class="form-control" min="0" max="365" value="{{ old('leave_carryover_max_days', $leaveSettings->leave_carryover_max_days ?? 6) }}">
                                <small class="form-text">Dipakai hanya pada mode carry over terbatas.</small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Berlaku Mulai Tahun</label>
                                <input type="number" name="leave_quota_policy_effective_year" class="form-control" min="2020" max="2100" value="{{ old('leave_quota_policy_effective_year', $leaveSettings->leave_quota_policy_effective_year ?? now()->year) }}" required>
                                <small class="form-text">Tahun ini dipakai sebagai titik mulai perhitungan agar saldo lama tidak otomatis dianggap carry over.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closePolicyModal()">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Aturan Cuti</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal{{ $openEditModal ? ' show' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Jenis Cuti</h3>
                    <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
                </div>
                <form method="POST" id="editForm" action="{{ $editJenisIzin ? route('admin.jenis-izin.update', $editJenisIzin) : '#' }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#editModal">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_type" value="edit">
                    <input type="hidden" name="edit_id" id="edit_id" value="{{ old('edit_id', $editJenisIzin->id ?? '') }}">

                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Jenis Cuti <span class="required">*</span></label>
                                <input type="text" name="nama" id="edit_nama" class="form-control" value="{{ old('nama', $editJenisIzin->nama ?? '') }}" required>
                            </div>
                            <div class="form-group">
                                <label>Kode <span class="required">*</span></label>
                                <input type="text" name="kode" id="edit_kode" class="form-control" value="{{ old('kode', $editJenisIzin->kode ?? '') }}" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Mapping Payroll <span class="required">*</span></label>
                                <select name="legacy_code" id="edit_legacy_code" class="form-control" required>
                                    @foreach ($legacyCodeOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('legacy_code', $editJenisIzin->legacy_code ?? 'cuti') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Urutan Tampil</label>
                                <input type="number" name="sort_order" id="edit_sort_order" class="form-control" min="0" max="9999" value="{{ old('sort_order', $editJenisIzin->sort_order ?? 0) }}">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Pembayaran <span class="required">*</span></label>
                                <select name="is_paid" id="edit_is_paid" class="form-control" required>
                                    <option value="1" @selected((string) old('is_paid', (int) ($editJenisIzin->is_paid ?? 1)) === '1')>Dibayar</option>
                                    <option value="0" @selected((string) old('is_paid', (int) ($editJenisIzin->is_paid ?? 1)) === '0')>Tidak Dibayar</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status <span class="required">*</span></label>
                                <select name="is_active" id="edit_is_active" class="form-control" required>
                                    <option value="1" @selected((string) old('is_active', (int) ($editJenisIzin->is_active ?? 1)) === '1')>Aktif</option>
                                    <option value="0" @selected((string) old('is_active', (int) ($editJenisIzin->is_active ?? 1)) === '0')>Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Aturan Kuota <span class="required">*</span></label>
                                <select name="quota_mode" id="edit_quota_mode" class="form-control js-quota-mode" data-manual-target="#editAnnualQuotaGroup" required>
                                    @foreach ($quotaModeOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('quota_mode', $resolveQuotaMode($editJenisIzin)) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group quota-manual-group" id="editAnnualQuotaGroup" @if (old('quota_mode', $resolveQuotaMode($editJenisIzin)) !== 'annual_manual') hidden @endif>
                                <label>Kuota per Tahun</label>
                                <input type="number" name="annual_quota_days" id="edit_annual_quota_days" class="form-control" min="0" max="365" value="{{ old('annual_quota_days', $editJenisIzin->annual_quota_days ?? '') }}" placeholder="hari kerja">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Maksimal per Pengajuan</label>
                                <input type="number" name="max_days_per_request" id="edit_max_days_per_request" class="form-control" min="0" max="365" value="{{ old('max_days_per_request', $editJenisIzin->max_days_per_request ?? '') }}" placeholder="0 = tanpa batas">
                            </div>
                            <div class="form-group">
                                <label>Lampiran <span class="required">*</span></label>
                                <select name="require_attachment" id="edit_require_attachment" class="form-control" required>
                                    <option value="0" @selected((string) old('require_attachment', (int) ($editJenisIzin->require_attachment ?? 0)) === '0')>Opsional</option>
                                    <option value="1" @selected((string) old('require_attachment', (int) ($editJenisIzin->require_attachment ?? 0)) === '1')>Wajib</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3" placeholder="Opsional">{{ old('description', $editJenisIzin->description ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const tambahJenisIzinModal = document.getElementById('tambahModal');
        const policyJenisIzinModal = document.getElementById('policyModal');
        const editJenisIzinModal = document.getElementById('editModal');
        const tambahJenisIzinForm = document.getElementById('tambahForm');
        const policyJenisIzinForm = document.getElementById('policyForm');
        const editJenisIzinForm = document.getElementById('editForm');

        function resetFormState(form) {
            if (!form) {
                return;
            }

            form.reset();
            syncQuotaVisibility(form);
            window.panelAjax?.clearErrors?.(form);
        }

        function syncQuotaVisibility(form) {
            if (!form) {
                return;
            }

            const selector = form.querySelector('.js-quota-mode');

            if (!selector) {
                return;
            }

            const manualTarget = document.querySelector(selector.dataset.manualTarget);
            const annualInput = manualTarget?.querySelector('input[name=\"annual_quota_days\"]');
            const isManual = selector.value === 'annual_manual';

            if (manualTarget) {
                manualTarget.hidden = !isManual;
            }

            if (!isManual && annualInput) {
                annualInput.value = '';
            }
        }

        function openTambahModal() {
            syncQuotaVisibility(tambahJenisIzinForm);
            tambahJenisIzinModal.classList.add('show');
        }

        function closeTambahModal() {
            resetFormState(tambahJenisIzinForm);
            tambahJenisIzinModal.classList.remove('show');
        }

        function syncLeavePolicyVisibility(form) {
            if (!form) {
                return;
            }

            const selector = form.querySelector('.js-leave-policy-mode');

            if (!selector) {
                return;
            }

            const manualTarget = document.querySelector(selector.dataset.manualTarget);
            const carryInput = manualTarget?.querySelector('input[name="leave_carryover_max_days"]');
            const isLimited = selector.value === '{{ \App\Models\Setting::LEAVE_QUOTA_POLICY_CARRY_LIMITED }}';

            if (manualTarget) {
                manualTarget.hidden = !isLimited;
            }

            if (!isLimited && carryInput) {
                carryInput.value = '0';
            }
        }

        function openPolicyModal() {
            syncLeavePolicyVisibility(policyJenisIzinForm);
            policyJenisIzinModal.classList.add('show');
        }

        function closePolicyModal() {
            resetFormState(policyJenisIzinForm);
            syncLeavePolicyVisibility(policyJenisIzinForm);
            policyJenisIzinModal.classList.remove('show');
        }

        function openEditModal(button) {
            const data = button.dataset;
            document.getElementById('edit_id').value = data.id || '';
            document.getElementById('edit_nama').value = data.nama || '';
            document.getElementById('edit_kode').value = data.kode || '';
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_legacy_code').value = data.legacyCode || 'cuti';
            document.getElementById('edit_is_paid').value = data.isPaid || '1';
            document.getElementById('edit_quota_mode').value = data.quotaMode || 'none';
            document.getElementById('edit_annual_quota_days').value = data.annualQuotaDays || '';
            document.getElementById('edit_max_days_per_request').value = data.maxDaysPerRequest || '';
            document.getElementById('edit_require_attachment').value = data.requireAttachment || '0';
            document.getElementById('edit_is_active').value = data.isActive || '1';
            document.getElementById('edit_sort_order').value = data.sortOrder || '0';
            editJenisIzinForm.action = data.updateUrl || '#';
            syncQuotaVisibility(editJenisIzinForm);
            editJenisIzinModal.classList.add('show');
        }

        function closeEditModal() {
            resetFormState(editJenisIzinForm);
            editJenisIzinModal.classList.remove('show');
        }

        document.querySelectorAll('.js-quota-mode').forEach(function (element) {
            element.addEventListener('change', function () {
                syncQuotaVisibility(this.form);
            });
        });

        document.querySelectorAll('.js-leave-policy-mode').forEach(function (element) {
            element.addEventListener('change', function () {
                syncLeavePolicyVisibility(this.form);
            });
        });

        window.addEventListener('click', function (event) {
            if (event.target === tambahJenisIzinModal) {
                closeTambahModal();
            }

            if (event.target === policyJenisIzinModal) {
                closePolicyModal();
            }

            if (event.target === editJenisIzinModal) {
                closeEditModal();
            }
        });

        syncQuotaVisibility(tambahJenisIzinForm);
        syncLeavePolicyVisibility(policyJenisIzinForm);
        syncQuotaVisibility(editJenisIzinForm);
    </script>
@endsection
