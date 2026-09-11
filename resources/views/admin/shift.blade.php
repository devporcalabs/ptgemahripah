@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Jam & Shift'])

@php
    $openTambahModal = $errors->any() && old('form_type') === 'create';
    $openEditModal = (bool) $editShift || ($errors->any() && old('form_type') === 'edit');
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
            <h3 class="page-subtitle">Daftar Shift Kerja</h3>
            <p class="page-description">Kelola data shift kerja agar karyawan bisa absensi.</p>
        </div>
        <div class="button-group">
            <button type="button" class="btn btn-primary" onclick="openTambahModal()">
                <i class="fas fa-plus"></i> Tambah Shift
            </button>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" class="filter-form" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxCrudFragment">
            <div class="filter-group">
                <label>Cari Data</label>
                <input type="search" name="q" class="filter-input" value="{{ $search ?? '' }}" placeholder="Cari nama shift atau jam...">
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
                <a href="{{ route('admin.jam-shift') }}" class="btn-reset" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table-bordered">
                <thead>
                    <tr class="table-header">
                        <th width="50">No</th>
                        <th>Nama Shift</th>
                        <th>Jam Masuk</th>
                        <th>Jam Keluar</th>
                        <th>Batas Toleransi</th>
                        <th>Scan Awal</th>
                        <th>Status</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shiftList as $index => $shift)
                        <tr>
                            <td class="text-center">{{ ($shiftList->firstItem() ?? 1) + $index }}</td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="width:12px; height:12px; border-radius:999px; background:{{ $shift->kode_warna ?: '#065F46' }};"></span>
                                    <strong>{{ $shift->nama_shift }}</strong>
                                </div>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($shift->jam_keluar)->format('H:i') }}</td>
                            <td>{{ $shift->toleransi ?? 15 }} menit</td>
                            <td>{{ (int) ($shift->checkin_window_before ?? 30) }} menit</td>
                            <td class="text-center">
                                <span class="badge {{ $shift->aktif ? 'badge-success' : 'badge-danger' }}">
                                    {{ $shift->aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('admin.jam-shift.show', $shift) }}" class="btn btn-info">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-warning"
                                        data-id="{{ $shift->id }}"
                                        data-update-url="{{ route('admin.jam-shift.update', $shift) }}"
                                        data-nama-shift="{{ $shift->nama_shift }}"
                                        data-jam-masuk="{{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }}"
                                        data-jam-keluar="{{ \Carbon\Carbon::parse($shift->jam_keluar)->format('H:i') }}"
                                        data-toleransi="{{ $shift->toleransi ?? 15 }}"
                                        data-checkin-window-before="{{ (int) ($shift->checkin_window_before ?? 30) }}"
                                        data-kode-warna="{{ $shift->kode_warna ?: '#065F46' }}"
                                        data-aktif="{{ $shift->aktif ? '1' : '0' }}"
                                        onclick="openEditModal(this)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" action="{{ route('admin.jam-shift.destroy', $shift) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Yakin ingin menghapus shift ini?">
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
                            <td colspan="8" class="empty-state">Belum ada data shift.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
            {{ $shiftList->links('partials.pagination-ajax', ['target' => '#ajaxCrudFragment']) }}
        </div>
    </div>

    <div id="tambahModal" class="modal{{ $openTambahModal ? ' show' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Tambah Shift Baru</h3>
                    <button type="button" class="modal-close" onclick="closeTambahModal()">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.jam-shift.store') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#tambahModal" data-reset-on-success="true">
                    @csrf
                    <input type="hidden" name="form_type" value="create">

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Shift <span class="required">*</span></label>
                            <input type="text" name="nama_shift" class="form-control" value="{{ $openTambahModal ? old('nama_shift') : '' }}" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Jam Masuk <span class="required">*</span></label>
                                <input type="time" name="jam_masuk" class="form-control" value="{{ $openTambahModal ? old('jam_masuk') : '' }}" required>
                            </div>
                            <div class="form-group">
                                <label>Jam Keluar <span class="required">*</span></label>
                                <input type="time" name="jam_keluar" class="form-control" value="{{ $openTambahModal ? old('jam_keluar') : '' }}" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Batas Toleransi (menit)</label>
                                <input type="number" name="toleransi" class="form-control" min="0" max="180" value="{{ $openTambahModal ? old('toleransi', 15) : 15 }}" required>
                                <small class="form-text">Keterlambatan di atas batas ini akan dihitung sebagai terlambat.</small>
                            </div>
                            <div class="form-group">
                                <label>Scan Awal Sebelum Shift (menit)</label>
                                <input type="number" name="checkin_window_before" class="form-control" min="0" max="240" value="{{ $openTambahModal ? old('checkin_window_before', 30) : 30 }}" required>
                                <small class="form-text">Karyawan boleh scan masuk sebelum jam shift sampai batas ini.</small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Warna Shift</label>
                                <input type="color" name="kode_warna" class="form-control" value="{{ $openTambahModal ? old('kode_warna', '#065F46') : '#065F46' }}">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="aktif" class="form-select">
                                    <option value="1" @selected(($openTambahModal ? old('aktif', '1') : '1') === '1')>Aktif</option>
                                    <option value="0" @selected(($openTambahModal ? old('aktif') : null) === '0')>Nonaktif</option>
                                </select>
                            </div>
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
                    <h3>Edit Shift</h3>
                    <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
                </div>
                <form method="POST" id="editForm" action="{{ $editShift ? route('admin.jam-shift.update', $editShift) : '#' }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#editModal">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_type" value="edit">
                    <input type="hidden" name="edit_id" id="edit_id" value="{{ old('edit_id', $editShift->id ?? '') }}">

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Shift <span class="required">*</span></label>
                            <input type="text" name="nama_shift" id="edit_nama_shift" class="form-control" value="{{ old('nama_shift', $editShift->nama_shift ?? '') }}" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Jam Masuk <span class="required">*</span></label>
                                <input type="time" name="jam_masuk" id="edit_jam_masuk" class="form-control" value="{{ old('jam_masuk', isset($editShift) ? \Carbon\Carbon::parse($editShift->jam_masuk)->format('H:i') : '') }}" required>
                            </div>
                            <div class="form-group">
                                <label>Jam Keluar <span class="required">*</span></label>
                                <input type="time" name="jam_keluar" id="edit_jam_keluar" class="form-control" value="{{ old('jam_keluar', isset($editShift) ? \Carbon\Carbon::parse($editShift->jam_keluar)->format('H:i') : '') }}" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Batas Toleransi (menit)</label>
                                <input type="number" name="toleransi" id="edit_toleransi" class="form-control" min="0" max="180" value="{{ old('toleransi', $editShift->toleransi ?? 15) }}" required>
                            </div>
                            <div class="form-group">
                                <label>Scan Awal Sebelum Shift (menit)</label>
                                <input type="number" name="checkin_window_before" id="edit_checkin_window_before" class="form-control" min="0" max="240" value="{{ old('checkin_window_before', $editShift->checkin_window_before ?? 30) }}" required>
                                <small class="form-text">Karyawan boleh scan masuk sebelum jam shift sampai batas ini.</small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Warna Shift</label>
                                <input type="color" name="kode_warna" id="edit_kode_warna" class="form-control" value="{{ old('kode_warna', $editShift->kode_warna ?? '#065F46') }}">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="aktif" id="edit_aktif" class="form-select">
                                    <option value="1" @selected(old('aktif', (string) ($editShift->aktif ?? 1)) === '1')>Aktif</option>
                                    <option value="0" @selected(old('aktif', (string) ($editShift->aktif ?? 1)) === '0')>Nonaktif</option>
                                </select>
                            </div>
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
        const tambahShiftModal = document.getElementById('tambahModal');
        const editShiftModal = document.getElementById('editModal');
        const editShiftForm = document.getElementById('editForm');

        function openTambahModal() {
            tambahShiftModal.classList.add('show');
        }

        function closeTambahModal() {
            tambahShiftModal.classList.remove('show');
        }

        function openEditModal(button) {
            const data = button.dataset;
            document.getElementById('edit_id').value = data.id || '';
            document.getElementById('edit_nama_shift').value = data.namaShift || '';
            document.getElementById('edit_jam_masuk').value = data.jamMasuk || '';
            document.getElementById('edit_jam_keluar').value = data.jamKeluar || '';
            document.getElementById('edit_toleransi').value = data.toleransi ?? 15;
            document.getElementById('edit_checkin_window_before').value = data.checkinWindowBefore ?? 30;
            document.getElementById('edit_kode_warna').value = data.kodeWarna || '#065F46';
            document.getElementById('edit_aktif').value = data.aktif ?? '1';
            editShiftForm.action = data.updateUrl || '#';
            editShiftModal.classList.add('show');
        }

        function closeEditModal() {
            editShiftModal.classList.remove('show');
        }

        window.addEventListener('click', function (event) {
            if (event.target === tambahShiftModal) {
                closeTambahModal();
            }

            if (event.target === editShiftModal) {
                closeEditModal();
            }
        });
    </script>
@endsection
