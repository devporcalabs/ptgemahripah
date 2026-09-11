@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Jabatan'])

@php
    $openTambahModal = $errors->any() && old('form_type') === 'create';
    $openEditModal = (bool) $editJabatan || ($errors->any() && old('form_type') === 'edit');
@endphp

@section('styles')
    .table-bordered { font-size:13px; }
    .table-bordered th, .table-bordered td { padding:10px 12px; }
    .action-buttons { gap:6px; }
@endsection

@section('content')
    <div id="ajaxCrudFragment">
        <div class="page-header">
            <div>
                <h3 class="page-subtitle">Master Jabatan</h3>
                <p class="page-description">Kelola daftar jabatan untuk dipakai pada data karyawan.</p>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-primary" onclick="openTambahModal()">
                    <i class="fas fa-plus"></i> Tambah Jabatan
                </button>
            </div>
        </div>

        <div class="filter-bar">
            <form method="GET" class="filter-form" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxCrudFragment">
                <div class="filter-group">
                    <label>Cari Data</label>
                    <input type="search" name="q" class="filter-input" value="{{ $search ?? '' }}" placeholder="Cari nama jabatan...">
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
                    <a href="{{ route('admin.jabatan') }}" class="btn-reset" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment">Reset</a>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table-bordered">
                    <thead>
                        <tr class="table-header">
                            <th width="50">No</th>
                            <th>Nama Jabatan</th>
                            <th width="180">Dipakai Karyawan</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jabatanList as $index => $jabatan)
                            <tr>
                                <td class="text-center">{{ ($jabatanList->firstItem() ?? 1) + $index }}</td>
                                <td><strong>{{ $jabatan->nama_jabatan }}</strong></td>
                                <td class="text-center">{{ $jabatan->karyawan_count }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button
                                            type="button"
                                            class="btn btn-warning"
                                            data-id="{{ $jabatan->id }}"
                                            data-update-url="{{ route('admin.jabatan.update', $jabatan) }}"
                                            data-nama-jabatan="{{ $jabatan->nama_jabatan }}"
                                            onclick="openEditModal(this)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.jabatan.destroy', $jabatan) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Yakin ingin menghapus jabatan ini?">
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
                                <td colspan="4" class="empty-state">Belum ada data jabatan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $jabatanList->links('partials.pagination-ajax', ['target' => '#ajaxCrudFragment']) }}
        </div>
    </div>

    <div id="tambahModal" class="modal{{ $openTambahModal ? ' show' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Tambah Jabatan</h3>
                    <button type="button" class="modal-close" onclick="closeTambahModal()">&times;</button>
                </div>
                <form method="POST" id="tambahForm" action="{{ route('admin.jabatan.store') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#tambahModal" data-reset-on-success="true">
                    @csrf
                    <input type="hidden" name="form_type" value="create">

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Jabatan <span class="required">*</span></label>
                            <input type="text" name="nama_jabatan" class="form-control" value="{{ $openTambahModal ? old('nama_jabatan') : '' }}" required>
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

    <div id="editModal" class="modal{{ $openEditModal ? ' show' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Jabatan</h3>
                    <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
                </div>
                <form method="POST" id="editForm" action="{{ $editJabatan ? route('admin.jabatan.update', $editJabatan) : '#' }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#editModal">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_type" value="edit">
                    <input type="hidden" name="edit_id" id="edit_id" value="{{ old('edit_id', $editJabatan->id ?? '') }}">

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Jabatan <span class="required">*</span></label>
                            <input type="text" name="nama_jabatan" id="edit_nama_jabatan" class="form-control" value="{{ old('nama_jabatan', $editJabatan->nama_jabatan ?? '') }}" required>
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
        const tambahJabatanModal = document.getElementById('tambahModal');
        const editJabatanModal = document.getElementById('editModal');
        const tambahJabatanForm = document.getElementById('tambahForm');
        const editJabatanForm = document.getElementById('editForm');

        function resetFormState(form) {
            if (!form) {
                return;
            }

            form.reset();
            window.panelAjax?.clearErrors?.(form);
        }

        function openTambahModal() {
            tambahJabatanModal.classList.add('show');
        }

        function closeTambahModal() {
            resetFormState(tambahJabatanForm);
            tambahJabatanModal.classList.remove('show');
        }

        function openEditModal(button) {
            const data = button.dataset;
            document.getElementById('edit_id').value = data.id || '';
            document.getElementById('edit_nama_jabatan').value = data.namaJabatan || '';
            editJabatanForm.action = data.updateUrl || '#';
            editJabatanModal.classList.add('show');
        }

        function closeEditModal() {
            resetFormState(editJabatanForm);
            editJabatanModal.classList.remove('show');
        }

        window.addEventListener('click', function (event) {
            if (event.target === tambahJabatanModal) {
                closeTambahModal();
            }

            if (event.target === editJabatanModal) {
                closeEditModal();
            }
        });
    </script>
@endsection
