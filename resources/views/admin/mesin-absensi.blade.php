@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Mesin Absensi'])

@php
    $openTambahModal = $errors->any() && old('form_type') === 'create';
    $openEditModal = (bool) $editDevice || ($errors->any() && old('form_type') === 'edit');
@endphp

@section('styles')
    .device-meta { display:grid; gap:4px; }
    .device-name { font-weight:600; color:#1E293B; }
    .device-note { font-size:11px; color:#64748B; }
    .device-code {
        display:inline-flex;
        align-items:center;
        padding:4px 8px;
        border-radius:8px;
        background:#F8FAFC;
        border:1px solid #E2E8F0;
        font-size:11px;
        color:#334155;
        font-family:monospace;
    }
    .status-stack { display:grid; gap:6px; }
    .device-pill {
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:3px 10px;
        border-radius:999px;
        font-size:10px;
        font-weight:700;
        white-space:nowrap;
    }
    .device-pill.ready { background:#ECFDF5; color:#065F46; }
    .device-pill.not-ready { background:#FFF7ED; color:#C2410C; }
    .stats-grid { margin-bottom:20px; }
    .table-bordered td { vertical-align:middle; }
    .action-buttons { gap:6px; }
    .action-buttons { flex-wrap:wrap; }
    .action-buttons form { display:inline-flex; }
    .action-buttons .btn { padding:6px 10px; }
    .empty-state { text-align:center; color:#64748B; padding:20px; }
    .table-bordered { font-size:13px; }
    .table-bordered th, .table-bordered td { padding:10px 12px; }
    .badge { padding:4px 10px; font-size:11px; }
    .device-card-header {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:14px;
        flex-wrap:wrap;
    }
    .device-card-header-main {
        display:grid;
        gap:4px;
    }
    .device-card-header-tools {
        margin-left:auto;
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }
    .device-card-header-form {
        margin:0;
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }
    .device-card-header-form .filter-group {
        min-width:116px;
        display:flex;
        align-items:center;
        gap:8px;
    }
    .device-card-header-form .filter-group label {
        margin:0;
        font-size:10px;
        white-space:nowrap;
    }
    .device-card-header-form .filter-input {
        min-width:116px;
        padding:7px 10px;
        font-size:12px;
    }
    .device-card-subtitle {
        font-size:11px;
        color:#64748B;
    }
    .device-page-header {
        align-items:flex-end;
        gap:16px;
        flex-wrap:wrap;
    }
    .device-toolbar {
        margin:0;
        display:flex;
        align-items:flex-end;
        justify-content:flex-end;
        gap:12px;
        flex-wrap:wrap;
        margin-left:auto;
    }
    .device-toolbar .filter-group {
        min-width:160px;
    }
    .device-toolbar-actions {
        display:flex;
        align-items:flex-end;
        gap:10px;
        flex-wrap:wrap;
    }
    .device-total-chip {
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:7px 10px;
        border-radius:999px;
        background:#FFFFFF;
        border:1px solid #E2E8F0;
        color:#334155;
        font-size:10px;
        font-weight:700;
        white-space:nowrap;
    }
    @media (max-width: 900px) {
        .device-toolbar {
            width:100%;
            margin-left:0;
            justify-content:flex-start;
        }
        .device-toolbar-actions {
            width:100%;
            margin-left:0;
            justify-content:flex-start;
        }
        .device-card-header-tools {
            width:100%;
            margin-left:0;
            justify-content:flex-start;
        }
    }
@endsection

@section('content')
    <div id="ajaxCrudFragment">
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $deviceStats['total'] }}</div><div class="stat-label">Total Mesin</div></div><div class="stat-icon"><i class="fas fa-microchip"></i></div></div></div>
            <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $deviceStats['active'] }}</div><div class="stat-label">Aktif</div></div><div class="stat-icon"><i class="fas fa-circle-check"></i></div></div></div>
            <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $deviceStats['inactive'] }}</div><div class="stat-label">Nonaktif</div></div><div class="stat-icon"><i class="fas fa-circle-pause"></i></div></div></div>
            <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $deviceStats['pending'] }}</div><div class="stat-label">Pending</div></div><div class="stat-icon"><i class="fas fa-clock-rotate-left"></i></div></div></div>
            <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $deviceStats['revoked'] }}</div><div class="stat-label">Dicabut</div></div><div class="stat-icon"><i class="fas fa-ban"></i></div></div></div>
        </div>

        <div class="page-header device-page-header">
            <div>
                <h3 class="page-subtitle">Daftar Mesin Absensi RFID</h3>
                <p class="page-description">Kelola serial mesin, status aktivasi, serta reset token perangkat absensi.</p>
            </div>
            <form method="GET" class="filter-form device-toolbar" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxCrudFragment">
                <div class="filter-group">
                    <label>Cari Data</label>
                    <input type="search" name="q" class="filter-input" value="{{ $search ?? '' }}" placeholder="Cari nama, serial, MAC, firmware...">
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" class="filter-input">
                        <option value="">Semua Status</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($statusFilter ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="device-toolbar-actions">
                    <a href="{{ route('admin.mesin-absensi') }}" class="btn-reset" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment">Reset</a>
                    <button type="button" class="btn btn-outline" data-loading-text="Memuat..." onclick="refreshDeviceData(this)">
                        <i class="fas fa-rotate-right"></i> Refresh Data
                    </button>
                    <button type="button" class="btn btn-primary" onclick="openTambahModal()">
                        <i class="fas fa-plus"></i> Tambah Mesin
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header device-card-header">
                <div class="device-card-header-main">
                    <h3><i class="fas fa-list" style="color:#065F46; margin-right:8px;"></i>Data Mesin</h3>
                    <span class="device-card-subtitle">Filter, pantau status, dan kelola akses mesin absensi RFID.</span>
                </div>
                <div class="device-card-header-tools">
                    <form method="GET" class="filter-form device-card-header-form" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxCrudFragment">
                        <input type="hidden" name="q" value="{{ $search ?? '' }}">
                        <input type="hidden" name="status" value="{{ $statusFilter ?? '' }}">
                        <div class="filter-group">
                            <label>Tampil</label>
                            <select name="per_page" class="filter-input">
                                @foreach ([10, 25, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected(($perPage ?? 10) === $size)>{{ $size }} / halaman</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                    <span class="device-total-chip">
                        <i class="fas fa-database"></i> Total: {{ $devices->total() }} data
                    </span>
                </div>
            </div>
            <div class="table-responsive" data-fragment-loading-scope>
                <table class="table-bordered">
                    <thead>
                        <tr class="table-header">
                            <th width="50">No</th>
                            <th>Mesin</th>
                            <th>Serial Number</th>
                            <th>MAC Address</th>
                            <th>Firmware</th>
                            <th>Status</th>
                            <th>Log Scan</th>
                            <th>Last Seen</th>
                            <th>Aktivasi</th>
                            <th width="300">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($devices as $index => $device)
                            @php
                                $statusClass = match ($device->status) {
                                    'active' => 'badge-success',
                                    'inactive' => 'badge-warning',
                                    'revoked' => 'badge-danger',
                                    default => 'badge-info',
                                };

                                $isReady = filled($device->mac_address) && filled($device->device_token);
                            @endphp
                            <tr>
                                <td class="text-center">{{ ($devices->firstItem() ?? 1) + $index }}</td>
                                <td>
                                    <div class="device-meta">
                                        <span class="device-name">{{ $device->name ?: 'Mesin Absensi' }}</span>
                                        <span class="device-note">Dibuat {{ $device->created_at?->format('d M Y H:i') ?? '-' }}</span>
                                    </div>
                                </td>
                                <td><span class="device-code">{{ $device->serial_number }}</span></td>
                                <td>{{ $device->mac_address ?: '-' }}</td>
                                <td>{{ $device->firmware_version ?: '-' }}</td>
                                <td>
                                    <div class="status-stack">
                                        <span class="badge {{ $statusClass }}">{{ $statusOptions[$device->status] ?? ucfirst($device->status) }}</span>
                                        <span class="device-pill {{ $isReady ? 'ready' : 'not-ready' }}">
                                            <i class="fas {{ $isReady ? 'fa-link' : 'fa-link-slash' }}"></i>
                                            {{ $isReady ? 'Sudah aktivasi' : 'Belum aktivasi' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">{{ number_format((int) ($device->attendance_logs_count ?? 0), 0, ',', '.') }}</td>
                                <td>
                                    @if ($device->last_seen)
                                        <div class="device-meta">
                                            <span>{{ $device->last_seen->format('d/m/Y H:i:s') }}</span>
                                            <span class="device-note">{{ $device->last_seen->diffForHumans() }}</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($device->activated_at)
                                        <div class="device-meta">
                                            <span>{{ $device->activated_at->format('d/m/Y H:i:s') }}</span>
                                            <span class="device-note">Aktif pertama</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.mesin-absensi.logs', $device) }}" class="btn btn-info">
                                            <i class="fas fa-clock-rotate-left"></i> Log
                                        </a>

                                        <button
                                            type="button"
                                            class="btn btn-warning"
                                            data-id="{{ $device->id }}"
                                            data-update-url="{{ route('admin.mesin-absensi.update', $device) }}"
                                            data-name="{{ $device->name }}"
                                            data-serial-number="{{ $device->serial_number }}"
                                            onclick="openEditModal(this)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>

                                        @if ($device->status === 'active')
                                            <form method="POST" action="{{ route('admin.mesin-absensi.deactivate', $device) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Nonaktifkan mesin absensi ini?" data-confirm-title="Nonaktifkan Mesin" data-confirm-button="Ya, nonaktifkan" data-confirm-variant="warning" data-confirm-icon="fa-circle-pause" data-confirm-subtitle="Mesin tidak akan bisa kirim absensi sampai diaktifkan kembali." data-confirm-note="Token mesin tetap tersimpan, jadi perangkat tidak perlu aktivasi ulang saat diaktifkan kembali.">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary"><i class="fas fa-circle-pause"></i> Nonaktif</button>
                                            </form>
                                        @elseif ($device->status === 'inactive')
                                            <form method="POST" action="{{ route('admin.mesin-absensi.activate', $device) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Aktifkan kembali mesin absensi ini?" data-confirm-title="Aktifkan Mesin" data-confirm-button="Ya, aktifkan" data-confirm-variant="success" data-confirm-icon="fa-circle-check" data-confirm-subtitle="Mesin akan kembali bisa mengirim absensi RFID." data-confirm-note="Mesin harus sudah pernah aktivasi penuh dari perangkat agar bisa diaktifkan kembali.">
                                                @csrf
                                                <button type="submit" class="btn btn-success"><i class="fas fa-circle-check"></i> Aktifkan</button>
                                            </form>
                                        @endif

                                        @if ($device->status !== 'revoked')
                                            <form method="POST" action="{{ route('admin.mesin-absensi.revoke', $device) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Cabut akses mesin absensi ini?" data-confirm-title="Cabut Akses Mesin" data-confirm-button="Ya, cabut" data-confirm-variant="danger" data-confirm-icon="fa-ban" data-confirm-subtitle="Mesin tidak bisa dipakai lagi sampai di-reset dan aktivasi ulang." data-confirm-note="Gunakan aksi ini jika perangkat hilang, diganti, atau tidak lagi dipercaya.">
                                                @csrf
                                                <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Cabut</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.mesin-absensi.reset', $device) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Reset mesin ini ke status pending?" data-confirm-title="Reset Mesin" data-confirm-button="Ya, reset" data-confirm-variant="warning" data-confirm-icon="fa-rotate-left" data-confirm-subtitle="MAC address, token, firmware, dan sesi aktivasi akan dibersihkan." data-confirm-note="Setelah reset, perangkat harus melakukan aktivasi ulang dari mesin.">
                                            @csrf
                                            <button type="submit" class="btn btn-info"><i class="fas fa-rotate-left"></i> Reset</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.mesin-absensi.destroy', $device) }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Hapus mesin absensi ini? Semua log scan terkait juga akan ikut terhapus." data-confirm-title="Hapus Mesin" data-confirm-button="Ya, hapus" data-confirm-variant="danger" data-confirm-icon="fa-trash-can" data-confirm-subtitle="Semua riwayat scan mesin ini juga akan hilang." data-confirm-note="Gunakan hapus hanya jika mesin memang tidak dipakai lagi dan log scan lama tidak diperlukan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="empty-state">Belum ada mesin absensi yang terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $devices->links('partials.pagination-ajax', ['target' => '#ajaxCrudFragment']) }}
        </div>
    </div>

    <div id="tambahModal" class="modal{{ $openTambahModal ? ' show' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Tambah Mesin Absensi</h3>
                    <button type="button" class="modal-close" onclick="closeTambahModal()">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.mesin-absensi.store') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#tambahModal" data-reset-on-success="true">
                    @csrf
                    <input type="hidden" name="form_type" value="create">

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Mesin <span class="required">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $openTambahModal ? old('name') : '' }}" placeholder="Contoh: Mesin Lobby" required>
                        </div>
                        <div class="form-group">
                            <label>Serial Number <span class="required">*</span></label>
                            <input type="text" name="serial_number" class="form-control" value="{{ $openTambahModal ? old('serial_number') : '' }}" placeholder="Contoh: SN-RFID-001" required>
                            <small class="form-text">Serial ini harus sama dengan yang dibaca perangkat RFID saat aktivasi.</small>
                        </div>
                        <div class="info-note">
                            <i class="fas fa-info-circle"></i>
                            <span>Mesin baru akan disimpan dengan status <strong>pending</strong> dan menunggu aktivasi dari perangkat.</span>
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
                    <h3>Edit Mesin Absensi</h3>
                    <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
                </div>
                <form method="POST" id="editForm" action="{{ $editDevice ? route('admin.mesin-absensi.update', $editDevice) : '#' }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#editModal">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_type" value="edit">
                    <input type="hidden" name="edit_id" id="edit_id" value="{{ old('edit_id', $editDevice->id ?? '') }}">

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Mesin <span class="required">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" value="{{ old('name', $editDevice->name ?? '') }}" required>
                        </div>
                        <div class="form-group">
                            <label>Serial Number <span class="required">*</span></label>
                            <input type="text" name="serial_number" id="edit_serial_number" class="form-control" value="{{ old('serial_number', $editDevice->serial_number ?? '') }}" readonly required>
                            <small class="form-text">Serial number tidak bisa diubah dari form edit. Buat mesin baru jika serial perangkat berbeda.</small>
                        </div>
                        <div class="info-note">
                            <i class="fas fa-info-circle"></i>
                            <span>Jika serial diubah, pastikan serial di perangkat juga sesuai saat proses aktivasi berikutnya.</span>
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
        const tambahModal = document.getElementById('tambahModal');
        const editModal = document.getElementById('editModal');
        const editForm = document.getElementById('editForm');

        async function refreshDeviceData(button) {
            if (!window.panelAjax) {
                window.location.reload();
                return;
            }

            window.panelAjax.setActionLoading(button, true);

            try {
                await window.panelAjax.refreshFragment('#ajaxCrudFragment', button);
            } catch (error) {
                window.panelToast?.show('error', error.message || 'Gagal memuat ulang data mesin.');
            } finally {
                window.panelAjax.setActionLoading(button, false);
            }
        }

        function openTambahModal() {
            tambahModal.classList.add('show');
        }

        function closeTambahModal() {
            tambahModal.classList.remove('show');
        }

        function openEditModal(button) {
            const data = button.dataset;
            document.getElementById('edit_id').value = data.id || '';
            document.getElementById('edit_name').value = data.name || '';
            document.getElementById('edit_serial_number').value = data.serialNumber || '';
            editForm.action = data.updateUrl || '#';
            editModal.classList.add('show');
        }

        function closeEditModal() {
            editModal.classList.remove('show');
        }

        window.addEventListener('click', function (event) {
            if (event.target === tambahModal) {
                closeTambahModal();
            }

            if (event.target === editModal) {
                closeEditModal();
            }
        });
    </script>
@endsection
