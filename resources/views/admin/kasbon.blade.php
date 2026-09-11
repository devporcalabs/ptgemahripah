@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Kasbon'])

@php
    $openTambahModal = $errors->any() && old('form_type') === 'create';
    $employeePickerOptions = $employeeOptions->map(function ($employeeOption) {
        return [
            'id' => (string) $employeeOption->id,
            'nik' => (string) $employeeOption->nik,
            'name' => (string) $employeeOption->nama_lengkap,
            'label' => trim($employeeOption->nik . ' - ' . $employeeOption->nama_lengkap),
        ];
    })->values();
    $selectedFilterEmployee = $employeeOptions->firstWhere('id', (int) $employeeId);
    $selectedFilterEmployeeLabel = $selectedFilterEmployee
        ? trim($selectedFilterEmployee->nik . ' - ' . $selectedFilterEmployee->nama_lengkap)
        : '';
    $defaultEmployeeId = (int) old('karyawan_id', $employeeId);
    $defaultEmployee = $employeeOptions->firstWhere('id', $defaultEmployeeId);
    $defaultEmployeeLabel = $defaultEmployee
        ? trim($defaultEmployee->nik . ' - ' . $defaultEmployee->nama_lengkap)
        : '';
@endphp

@section('styles')
    .kasbon-amount-plus { color:#065F46; font-weight:700; }
    .kasbon-amount-minus { color:#B91C1C; font-weight:700; }
    .kasbon-balance-card .table-bordered td,
    .kasbon-balance-card .table-bordered th { padding:10px 12px; }
    .kasbon-balance-chip {
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:4px 10px;
        border-radius:999px;
        background:#ECFDF5;
        color:#065F46;
        font-size:11px;
        font-weight:700;
    }
    .kasbon-ref {
        display:block;
        margin-top:4px;
        font-size:11px;
        color:#64748B;
        white-space:normal;
    }
    .kasbon-last-transaction {
        min-width: 160px;
    }
    .kasbon-employee-picker {
        position: relative;
    }
    .kasbon-employee-picker .filter-input,
    .kasbon-employee-picker .form-control {
        width: 100%;
    }
    .kasbon-employee-input-readonly {
        cursor: not-allowed;
    }
    .kasbon-employee-dropdown {
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
    .kasbon-employee-picker.is-open .kasbon-employee-dropdown {
        display: block;
    }
    .kasbon-employee-option {
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
    .kasbon-employee-option:hover,
    .kasbon-employee-option:focus {
        background: #F8FAFC;
        outline: none;
    }
    .kasbon-employee-option strong {
        font-size: 13px;
        color: #0F172A;
        font-weight: 600;
    }
    .kasbon-employee-option span {
        font-size: 11px;
        color: #64748B;
    }
    .kasbon-employee-empty {
        padding: 10px 12px;
        font-size: 12px;
        color: #64748B;
    }
    .modal-dialog-kasbon-history {
        width: 94%;
        max-width: 1120px;
    }
    .modal-dialog-kasbon-history .modal-content {
        height: min(78vh, 760px);
        display:flex;
        flex-direction:column;
    }
    .modal-dialog-kasbon-history .modal-body {
        flex:1;
        min-height:0;
        display:flex;
        flex-direction:column;
        overflow:hidden;
    }
    .history-modal-summary {
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
        gap:12px;
        margin-bottom:16px;
    }
    .history-modal-summary-item {
        border:1px solid #E2E8F0;
        border-radius:12px;
        padding:12px 14px;
        background:#F8FAFC;
    }
    .history-modal-summary-label {
        display:block;
        margin-bottom:6px;
        font-size:11px;
        font-weight:700;
        color:#64748B;
        text-transform:uppercase;
        letter-spacing:.04em;
    }
    .history-modal-placeholder {
        padding:16px;
        border:1px dashed #CBD5E1;
        border-radius:12px;
        font-size:13px;
        color:#64748B;
        text-align:center;
        background:#F8FAFC;
    }
    #kasbonHistoryFragment {
        display:flex;
        flex-direction:column;
        flex:1;
        min-height:0;
    }
    .history-modal-filter {
        margin-bottom:16px;
        flex-shrink:0;
    }
    .history-modal-table-wrap {
        flex:1;
        min-height:0;
        overflow:auto;
    }
    .history-modal-pagination {
        padding-top:12px;
        flex-shrink:0;
    }
@endsection

@section('content')
    <div id="ajaxCrudFragment">
        <div class="page-header">
            <div>
                <h3 class="page-subtitle">Kasbon Karyawan</h3>
                <p class="page-description">Saat payroll diproses, sistem akan otomatis memotong kasbon dari gaji, dan admin masih bisa menyesuaikan nominal potongannya bila diperlukan.</p>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-primary" onclick="openKasbonModal()">
                    <i class="fas fa-plus"></i> Tambah Mutasi Kasbon
                </button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">Rp {{ number_format($stats['total_saldo'] ?? 0, 0, ',', '.') }}</div>
                        <div class="stat-label">Total Saldo Kasbon Aktif</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">{{ $stats['employee_with_saldo'] ?? 0 }}</div>
                        <div class="stat-label">Karyawan Punya Saldo</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">Rp {{ number_format($stats['total_plus'] ?? 0, 0, ',', '.') }}</div>
                        <div class="stat-label">Mutasi Masuk {{ $period->translatedFormat('F Y') }}</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-circle-plus"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-body">
                    <div>
                        <div class="stat-value">Rp {{ number_format($stats['total_minus'] ?? 0, 0, ',', '.') }}</div>
                        <div class="stat-label">Mutasi Keluar {{ $period->translatedFormat('F Y') }}</div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-circle-minus"></i></div>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <form method="GET" class="filter-form" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxCrudFragment">
                <div class="filter-group">
                    <label>Bulan</label>
                    <input type="month" name="bulan" class="filter-input" value="{{ $period->format('Y-m') }}">
                </div>
                <div class="filter-group">
                    <label>Karyawan</label>
                    <input type="hidden" name="karyawan_id" id="kasbon_filter_karyawan_id" value="{{ $employeeId ?: '' }}">
                    <div class="kasbon-employee-picker" id="kasbonFilterEmployeePicker">
                        <input
                            type="text"
                            id="kasbon_filter_karyawan_search"
                            class="filter-input"
                            value="{{ $selectedFilterEmployeeLabel }}"
                            placeholder="Semua karyawan"
                            autocomplete="off">
                        <div class="kasbon-employee-dropdown" id="kasbonFilterEmployeeDropdown"></div>
                    </div>
                </div>
                <div class="filter-group">
                    <label>Cari</label>
                    <input type="search" name="q" class="filter-input" value="{{ $search }}" placeholder="Cari NIK, nama, catatan...">
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
                    <a href="{{ route('admin.kasbon') }}" class="btn-reset" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment">Reset</a>
                </div>
            </form>
        </div>

        <div class="card kasbon-balance-card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3><i class="fas fa-layer-group" style="color:#065F46; margin-right:8px;"></i>Saldo Kasbon Aktif</h3>
                <span class="total-data">{{ $employeeBalances->count() }} karyawan tampil</span>
            </div>
            <div class="table-responsive">
                <table class="table-bordered">
                    <thead>
                        <tr class="table-header">
                            <th width="50">No</th>
                            <th>NIK</th>
                            <th>Nama Karyawan</th>
                            <th width="220">Transaksi Terakhir</th>
                            <th width="180">Saldo Aktif</th>
                            <th width="240">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employeeBalances as $index => $employeeBalance)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $employeeBalance->nik }}</td>
                                <td>
                                    <strong>{{ $employeeBalance->nama_lengkap }}</strong>
                                </td>
                                <td class="kasbon-last-transaction">
                                    <strong>{{ \Carbon\Carbon::parse($employeeBalance->last_transaction_at)->translatedFormat('d M Y, H:i') }} WIB</strong>
                                    <span class="kasbon-ref">
                                        {{ $employeeBalance->last_transaction_direction === 'plus' ? '+' : '-' }}
                                        Rp {{ number_format((float) $employeeBalance->last_transaction_amount, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <span class="kasbon-balance-chip">Rp {{ number_format((float) $employeeBalance->kasbon_balance, 0, ',', '.') }}</span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button
                                            type="button"
                                            class="btn btn-primary"
                                            data-employee-id="{{ $employeeBalance->id }}"
                                            data-employee-label="{{ $employeeBalance->nik }} - {{ $employeeBalance->nama_lengkap }}"
                                            onclick="openKasbonModal(this)">
                                            <i class="fas fa-plus"></i> Mutasi
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-secondary"
                                            data-employee-label="{{ $employeeBalance->nik }} - {{ $employeeBalance->nama_lengkap }}"
                                            data-history-url="{{ route('admin.kasbon.history', $employeeBalance->id) }}"
                                            onclick="openKasbonHistoryModal(this)">
                                            <i class="fas fa-clock-rotate-left"></i> Riwayat
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">Belum ada saldo kasbon aktif untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div id="kasbonModal" class="modal{{ $openTambahModal ? ' show' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Tambah Mutasi Kasbon</h3>
                    <button type="button" class="modal-close" onclick="closeKasbonModal()">&times;</button>
                </div>
                <form method="POST" id="kasbonForm" action="{{ route('admin.kasbon.store') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#kasbonModal" data-reset-on-success="true">
                    @csrf
                    <input type="hidden" name="form_type" value="create">

                    <div class="modal-body">
                        <div class="form-group" id="kasbonEmployeeFieldGroup">
                            <label>Karyawan <span class="required">*</span></label>
                            <input type="hidden" name="karyawan_id" id="kasbon_karyawan_id" value="{{ $defaultEmployeeId > 0 ? $defaultEmployeeId : '' }}">
                            <div class="kasbon-employee-picker" id="kasbonEmployeePicker">
                                <input
                                    type="text"
                                    id="kasbon_karyawan_search"
                                    class="form-control{{ $errors->has('karyawan_id') ? ' is-invalid' : '' }}"
                                    value="{{ $defaultEmployeeLabel }}"
                                    placeholder="Cari NIK atau nama karyawan"
                                    autocomplete="off">
                                <div class="kasbon-employee-dropdown" id="kasbonEmployeeDropdown"></div>
                            </div>
                            @error('karyawan_id')
                                <div class="invalid-feedback" data-generated="true">{{ $message }}</div>
                            @enderror
                            <small class="form-text">Ketik NIK atau nama karyawan, lalu pilih dari daftar yang muncul.</small>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tanggal <span class="required">*</span></label>
                                <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', now()->toDateString()) }}" required>
                            </div>
                            <div class="form-group">
                                <label>Arah Mutasi <span class="required">*</span></label>
                                <select name="arah" id="kasbon_arah" class="form-control" required>
                                    <option value="plus" @selected(old('arah', 'plus') === 'plus')>Penambahan Kasbon (+)</option>
                                    <option value="minus" @selected(old('arah') === 'minus')>Pengurangan / Pembayaran (-)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Nominal <span class="required">*</span></label>
                            <input type="text" name="nominal" class="form-control" value="{{ old('nominal') }}" placeholder="Contoh: 500000" required>
                            <small class="form-text">Gunakan angka rupiah tanpa simbol. Contoh: <strong>500000</strong>.</small>
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="catatan" class="form-control" placeholder="Contoh: Kasbon operasional minggu ke-2">{{ old('catatan') }}</textarea>
                        </div>
                        <div class="info-note">
                            <i class="fas fa-circle-info"></i>
                            <span>Mutasi <strong>+</strong> menambah saldo utang kasbon. Mutasi <strong>-</strong> mengurangi saldo kasbon dan akan ditolak jika melebihi saldo aktif saat ini. Tanggal mutasi juga tidak boleh lebih awal dari mutasi terakhir.</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeKasbonModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="kasbonHistoryModal" class="modal">
        <div class="modal-dialog modal-dialog-kasbon-history">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="kasbonHistoryModalTitle">Riwayat Mutasi Kasbon</h3>
                    <button type="button" class="modal-close" onclick="closeKasbonHistoryModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="kasbonHistoryFragment" data-fragment-loading-scope>
                        <div class="history-modal-placeholder">
                            Pilih karyawan dari tabel saldo kasbon aktif untuk melihat riwayat mutasinya.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const kasbonModal = document.getElementById('kasbonModal');
        const kasbonForm = document.getElementById('kasbonForm');
        const kasbonEmployeeField = document.getElementById('kasbon_karyawan_id');
        const kasbonEmployeeFieldGroup = document.getElementById('kasbonEmployeeFieldGroup');
        const kasbonEmployeePicker = document.getElementById('kasbonEmployeePicker');
        const kasbonEmployeeSearchField = document.getElementById('kasbon_karyawan_search');
        const kasbonEmployeeDropdown = document.getElementById('kasbonEmployeeDropdown');
        const kasbonFilterForm = document.querySelector('#ajaxCrudFragment .filter-form[data-auto-submit="true"]');
        const kasbonFilterEmployeeField = document.getElementById('kasbon_filter_karyawan_id');
        const kasbonFilterEmployeePicker = document.getElementById('kasbonFilterEmployeePicker');
        const kasbonFilterEmployeeSearchField = document.getElementById('kasbon_filter_karyawan_search');
        const kasbonFilterEmployeeDropdown = document.getElementById('kasbonFilterEmployeeDropdown');
        const kasbonHistoryModal = document.getElementById('kasbonHistoryModal');
        const kasbonHistoryModalTitle = document.getElementById('kasbonHistoryModalTitle');
        const kasbonHistoryFragment = document.getElementById('kasbonHistoryFragment');
        const kasbonEmployeeDefaultId = '{{ $employeeId ?: '' }}';
        const kasbonEmployeeOptions = @json($employeePickerOptions);
        const kasbonHistoryPlaceholder = `
            <div class="history-modal-placeholder">
                Pilih karyawan dari tabel saldo kasbon aktif untuk melihat riwayat mutasinya.
            </div>
        `;

        function escapeKasbonHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getKasbonEmployeeById(id) {
            return kasbonEmployeeOptions.find((employee) => String(employee.id) === String(id)) || null;
        }

        function findKasbonEmployeeByExactKeyword(keyword) {
            const normalizedKeyword = String(keyword || '').trim().toLowerCase();

            if (!normalizedKeyword) {
                return null;
            }

            return kasbonEmployeeOptions.find((employee) =>
                employee.label.toLowerCase() === normalizedKeyword
                || employee.nik.toLowerCase() === normalizedKeyword
            ) || null;
        }

        function syncKasbonEmployeeErrorState() {
            if (!kasbonEmployeeFieldGroup || !kasbonEmployeeField || !kasbonEmployeeSearchField) {
                return;
            }

            const generatedFeedbacks = Array.from(
                kasbonEmployeeFieldGroup.querySelectorAll('.invalid-feedback[data-generated="true"]')
            );
            const visibleFeedback = kasbonEmployeeFieldGroup.querySelector('.kasbon-employee-feedback');
            const hiddenInvalid = kasbonEmployeeField.classList.contains('is-invalid');

            kasbonEmployeeSearchField.classList.toggle('is-invalid', hiddenInvalid || generatedFeedbacks.length > 0);

            if (generatedFeedbacks.length > 0) {
                const lastFeedback = generatedFeedbacks[generatedFeedbacks.length - 1];

                generatedFeedbacks.forEach((feedback) => feedback.remove());

                const renderedFeedback = visibleFeedback || document.createElement('div');
                renderedFeedback.className = 'invalid-feedback kasbon-employee-feedback';
                renderedFeedback.dataset.generated = 'true';
                renderedFeedback.textContent = lastFeedback.textContent || 'Karyawan wajib dipilih.';

                kasbonEmployeeSearchField.insertAdjacentElement('afterend', renderedFeedback);
                kasbonEmployeeSearchField.classList.add('is-invalid');

                return;
            }

            if (!hiddenInvalid && visibleFeedback) {
                visibleFeedback.remove();
                kasbonEmployeeSearchField.classList.remove('is-invalid');
            }
        }

        function setKasbonEmployeeSelection(id = '', label = '') {
            if (kasbonEmployeeField) {
                kasbonEmployeeField.value = id ? String(id) : '';
            }

            if (kasbonEmployeeSearchField) {
                kasbonEmployeeSearchField.value = label || '';
            }

            syncKasbonEmployeeErrorState();
        }

        function closeKasbonEmployeeDropdown() {
            kasbonEmployeePicker?.classList.remove('is-open');
        }

        function renderKasbonEmployeeDropdown(keyword = '') {
            if (!kasbonEmployeeDropdown) {
                return;
            }

            const normalizedKeyword = keyword.trim().toLowerCase();
            const filteredEmployees = kasbonEmployeeOptions.filter((employee) => {
                if (!normalizedKeyword) {
                    return true;
                }

                return employee.label.toLowerCase().includes(normalizedKeyword)
                    || employee.nik.toLowerCase().includes(normalizedKeyword)
                    || employee.name.toLowerCase().includes(normalizedKeyword);
            }).slice(0, 20);

            if (filteredEmployees.length === 0) {
                kasbonEmployeeDropdown.innerHTML = '<div class="kasbon-employee-empty">Karyawan tidak ditemukan.</div>';
                return;
            }

            kasbonEmployeeDropdown.innerHTML = filteredEmployees.map((employee) => `
                <button
                    type="button"
                    class="kasbon-employee-option"
                    data-employee-id="${escapeKasbonHtml(employee.id)}"
                    data-employee-label="${escapeKasbonHtml(employee.label)}">
                    <strong>${escapeKasbonHtml(employee.nik)}</strong>
                    <span>${escapeKasbonHtml(employee.name)}</span>
                </button>
            `).join('');
        }

        function renderKasbonFilterEmployeeDropdown(keyword = '') {
            if (!kasbonFilterEmployeeDropdown) {
                return;
            }

            const normalizedKeyword = keyword.trim().toLowerCase();
            const filteredEmployees = kasbonEmployeeOptions.filter((employee) => {
                if (!normalizedKeyword) {
                    return true;
                }

                return employee.label.toLowerCase().includes(normalizedKeyword)
                    || employee.nik.toLowerCase().includes(normalizedKeyword)
                    || employee.name.toLowerCase().includes(normalizedKeyword);
            }).slice(0, 20);

            const allOption = `
                <button
                    type="button"
                    class="kasbon-employee-option"
                    data-employee-id=""
                    data-employee-label="">
                    <strong>Semua Karyawan</strong>
                    <span>Tampilkan seluruh data kasbon</span>
                </button>
            `;

            if (filteredEmployees.length === 0) {
                kasbonFilterEmployeeDropdown.innerHTML = allOption + '<div class="kasbon-employee-empty">Karyawan tidak ditemukan.</div>';
                return;
            }

            kasbonFilterEmployeeDropdown.innerHTML = allOption + filteredEmployees.map((employee) => `
                <button
                    type="button"
                    class="kasbon-employee-option"
                    data-employee-id="${escapeKasbonHtml(employee.id)}"
                    data-employee-label="${escapeKasbonHtml(employee.label)}">
                    <strong>${escapeKasbonHtml(employee.nik)}</strong>
                    <span>${escapeKasbonHtml(employee.name)}</span>
                </button>
            `).join('');
        }

        function openKasbonEmployeeDropdown() {
            if (!kasbonEmployeePicker || !kasbonEmployeeSearchField || kasbonEmployeeSearchField.readOnly) {
                return;
            }

            renderKasbonEmployeeDropdown(kasbonEmployeeSearchField.value);
            kasbonEmployeePicker.classList.add('is-open');
        }

        function setKasbonEmployeeReadonly(isReadonly) {
            if (!kasbonEmployeeSearchField) {
                return;
            }

            kasbonEmployeeSearchField.readOnly = isReadonly;
            kasbonEmployeeSearchField.classList.toggle('kasbon-employee-input-readonly', isReadonly);

            if (isReadonly) {
                closeKasbonEmployeeDropdown();
            }
        }

        function syncKasbonEmployeeSelectionFromText() {
            if (!kasbonEmployeeSearchField || !kasbonEmployeeField) {
                return;
            }

            const inputValue = kasbonEmployeeSearchField.value.trim().toLowerCase();

            if (inputValue === '') {
                kasbonEmployeeField.value = '';
                return;
            }

            const exactEmployee = findKasbonEmployeeByExactKeyword(inputValue);

            if (exactEmployee) {
                setKasbonEmployeeSelection(exactEmployee.id, exactEmployee.label);
                return;
            }

            const currentEmployee = getKasbonEmployeeById(kasbonEmployeeField.value);

            if (!currentEmployee || currentEmployee.label.toLowerCase() !== inputValue) {
                kasbonEmployeeField.value = '';
            }
        }

        function setKasbonFilterEmployeeSelection(id = '', label = '') {
            if (kasbonFilterEmployeeField) {
                kasbonFilterEmployeeField.value = id ? String(id) : '';
            }

            if (kasbonFilterEmployeeSearchField) {
                kasbonFilterEmployeeSearchField.value = label || '';
            }
        }

        function closeKasbonFilterEmployeeDropdown() {
            kasbonFilterEmployeePicker?.classList.remove('is-open');
        }

        function openKasbonFilterEmployeeDropdown() {
            if (!kasbonFilterEmployeePicker || !kasbonFilterEmployeeSearchField) {
                return;
            }

            renderKasbonFilterEmployeeDropdown(kasbonFilterEmployeeSearchField.value);
            kasbonFilterEmployeePicker.classList.add('is-open');
        }

        function submitKasbonFilterForm() {
            if (!kasbonFilterForm) {
                return;
            }

            if (typeof kasbonFilterForm.requestSubmit === 'function') {
                kasbonFilterForm.requestSubmit();
                return;
            }

            kasbonFilterForm.submit();
        }

        function syncKasbonFilterSelectionFromText(shouldSubmit = false) {
            if (!kasbonFilterEmployeeSearchField || !kasbonFilterEmployeeField) {
                return;
            }

            const currentEmployee = getKasbonEmployeeById(kasbonFilterEmployeeField.value);
            const inputValue = kasbonFilterEmployeeSearchField.value.trim();
            const previousId = kasbonFilterEmployeeField.value;

            if (inputValue === '') {
                setKasbonFilterEmployeeSelection('', '');

                if (shouldSubmit && previousId !== '') {
                    submitKasbonFilterForm();
                }

                return;
            }

            const exactEmployee = findKasbonEmployeeByExactKeyword(inputValue);

            if (exactEmployee) {
                setKasbonFilterEmployeeSelection(exactEmployee.id, exactEmployee.label);

                if (shouldSubmit && previousId !== String(exactEmployee.id)) {
                    submitKasbonFilterForm();
                }

                return;
            }

            setKasbonFilterEmployeeSelection(
                currentEmployee?.id || '',
                currentEmployee?.label || ''
            );
        }

        function resetKasbonForm() {
            if (!kasbonForm) {
                return;
            }

            kasbonForm.reset();
            window.panelAjax?.clearErrors?.(kasbonForm);
            if (kasbonEmployeeField) {
                const defaultEmployee = getKasbonEmployeeById(kasbonEmployeeDefaultId);
                setKasbonEmployeeSelection(
                    defaultEmployee?.id || '',
                    defaultEmployee?.label || ''
                );
            }
            setKasbonEmployeeReadonly(false);
            closeKasbonEmployeeDropdown();
        }

        function openKasbonModal(button = null) {
            resetKasbonForm();

            if (button?.dataset?.employeeId) {
                setKasbonEmployeeSelection(
                    button.dataset.employeeId,
                    button.dataset.employeeLabel || ''
                );
                setKasbonEmployeeReadonly(true);
            }

            kasbonModal.classList.add('show');
        }

        function closeKasbonModal() {
            resetKasbonForm();
            kasbonModal.classList.remove('show');
        }

        async function openKasbonHistoryModal(button) {
            const historyUrl = button?.dataset?.historyUrl || '';
            const employeeLabel = button?.dataset?.employeeLabel || 'Riwayat Mutasi Kasbon';

            if (! historyUrl || ! kasbonHistoryModal || ! kasbonHistoryFragment) {
                return;
            }

            kasbonHistoryModalTitle.textContent = `Riwayat Mutasi Kasbon - ${employeeLabel}`;
            kasbonHistoryModal.classList.add('show');

            try {
                await window.panelAjax?.loadFragment?.(
                    historyUrl,
                    '#kasbonHistoryFragment',
                    false,
                    button
                );
            } catch (error) {
                if (kasbonHistoryFragment) {
                    kasbonHistoryFragment.innerHTML = `
                        <div class="history-modal-placeholder">
                            Gagal memuat riwayat mutasi kasbon.
                        </div>
                    `;
                }
            }
        }

        function closeKasbonHistoryModal() {
            if (! kasbonHistoryModal || ! kasbonHistoryFragment) {
                return;
            }

            kasbonHistoryModal.classList.remove('show');
            kasbonHistoryModalTitle.textContent = 'Riwayat Mutasi Kasbon';
            kasbonHistoryFragment.innerHTML = kasbonHistoryPlaceholder;
        }

        window.addEventListener('click', function (event) {
            if (event.target === kasbonModal) {
                closeKasbonModal();
            }

            if (event.target === kasbonHistoryModal) {
                closeKasbonHistoryModal();
            }

            if (kasbonEmployeePicker && !kasbonEmployeePicker.contains(event.target)) {
                closeKasbonEmployeeDropdown();
            }

            if (kasbonFilterEmployeePicker && !kasbonFilterEmployeePicker.contains(event.target)) {
                closeKasbonFilterEmployeeDropdown();
            }
        });

        kasbonEmployeeSearchField?.addEventListener('focus', function () {
            openKasbonEmployeeDropdown();
        });

        kasbonEmployeeSearchField?.addEventListener('click', function () {
            openKasbonEmployeeDropdown();
        });

        kasbonEmployeeSearchField?.addEventListener('input', function (event) {
            if (event.target.readOnly) {
                return;
            }

            if (kasbonEmployeeField) {
                kasbonEmployeeField.value = '';
            }

            renderKasbonEmployeeDropdown(event.target.value);
            kasbonEmployeePicker?.classList.add('is-open');
            syncKasbonEmployeeErrorState();
        });

        kasbonEmployeeSearchField?.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeKasbonEmployeeDropdown();
                return;
            }

            if (event.key === 'Enter' && !event.target.readOnly) {
                const firstOption = kasbonEmployeeDropdown?.querySelector('.kasbon-employee-option');
                if (firstOption) {
                    event.preventDefault();
                    firstOption.click();
                }
            }
        });

        kasbonEmployeeSearchField?.addEventListener('blur', function () {
            window.setTimeout(function () {
                syncKasbonEmployeeSelectionFromText();
                closeKasbonEmployeeDropdown();
            }, 120);
        });

        kasbonEmployeeDropdown?.addEventListener('click', function (event) {
            const option = event.target.closest('.kasbon-employee-option');
            if (!option) {
                return;
            }

            setKasbonEmployeeSelection(
                option.dataset.employeeId || '',
                option.dataset.employeeLabel || ''
            );
            closeKasbonEmployeeDropdown();
            kasbonEmployeeSearchField?.focus();
        });

        kasbonForm?.addEventListener('submit', function () {
            syncKasbonEmployeeSelectionFromText();
        });

        kasbonFilterEmployeeSearchField?.addEventListener('focus', function () {
            openKasbonFilterEmployeeDropdown();
        });

        kasbonFilterEmployeeSearchField?.addEventListener('click', function (event) {
            event.stopPropagation();
            openKasbonFilterEmployeeDropdown();
        });

        kasbonFilterEmployeeSearchField?.addEventListener('input', function (event) {
            event.stopPropagation();
            renderKasbonFilterEmployeeDropdown(event.target.value);
            kasbonFilterEmployeePicker?.classList.add('is-open');
        });

        kasbonFilterEmployeeSearchField?.addEventListener('change', function (event) {
            event.stopPropagation();
        });

        kasbonFilterEmployeeSearchField?.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeKasbonFilterEmployeeDropdown();
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                const firstOption = kasbonFilterEmployeeDropdown?.querySelector('.kasbon-employee-option');
                if (firstOption) {
                    firstOption.click();
                }
            }
        });

        kasbonFilterEmployeeSearchField?.addEventListener('blur', function () {
            window.setTimeout(function () {
                syncKasbonFilterSelectionFromText(true);
                closeKasbonFilterEmployeeDropdown();
            }, 120);
        });

        kasbonFilterEmployeeDropdown?.addEventListener('click', function (event) {
            const option = event.target.closest('.kasbon-employee-option');
            if (!option) {
                return;
            }

            setKasbonFilterEmployeeSelection(
                option.dataset.employeeId || '',
                option.dataset.employeeLabel || ''
            );
            closeKasbonFilterEmployeeDropdown();
            submitKasbonFilterForm();
        });

        if (kasbonEmployeeFieldGroup && window.MutationObserver) {
            const kasbonEmployeeObserver = new MutationObserver(syncKasbonEmployeeErrorState);
            kasbonEmployeeObserver.observe(kasbonEmployeeFieldGroup, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class'],
            });
        }

        syncKasbonEmployeeErrorState();
        syncKasbonFilterSelectionFromText(false);
    </script>
@endsection
