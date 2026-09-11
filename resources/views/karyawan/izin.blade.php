@extends('layouts.panel', ['panel' => 'karyawan', 'pageTitle' => 'Izin / Cuti Saya'])

@php
    $openLeaveModal = $errors->any() && old('form_type') === 'create';
@endphp

@section('styles')
    .portal-page { display:grid; gap:18px; }
    .stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; }
    .stat-card,
    .panel-card {
        background:#fff;
        border:1px solid #e2e8f0;
        border-radius:14px;
        box-shadow:0 1px 2px rgba(15,23,42,.04);
    }
    .stat-card { padding:18px; }
    .stat-label { font-size:12px; color:#64748b; margin-bottom:6px; }
    .stat-value { font-size:24px; font-weight:800; color:#0f172a; }
    .stat-note { margin-top:8px; font-size:12px; color:#475569; }
    .panel-head {
        padding:16px 18px;
        border-bottom:1px solid #eef2f7;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        flex-wrap:wrap;
    }
    .panel-title { margin:0; font-size:16px; font-weight:700; color:#0f172a; }
    .panel-subtitle { margin-top:4px; font-size:12px; color:#64748b; }
    .panel-body { padding:18px; }
    .badge-soft { display:inline-flex; align-items:center; justify-content:center; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; }
    .badge-success { background:#dcfce7; color:#166534; }
    .badge-warning { background:#fef3c7; color:#92400e; }
    .badge-danger { background:#fee2e2; color:#b91c1c; }
    .filter-row { display:flex; gap:12px; align-items:end; flex-wrap:wrap; margin-bottom:16px; }
    .filter-group label { display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:6px; }
    .panel-head-main { min-width:0; }
    .panel-head-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-left:auto; }
    .quota-stat-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
    .quota-stat-title { font-size:14px; font-weight:700; color:#0f172a; line-height:1.35; }
    .quota-stat-chip {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:58px;
        padding:5px 10px;
        border-radius:999px;
        background:#eff6ff;
        color:#2563eb;
        font-size:11px;
        font-weight:700;
    }
    .quota-stat-meta { margin-top:10px; display:grid; gap:6px; font-size:12px; color:#475569; }
    .quota-empty-card {
        display:flex;
        align-items:center;
        justify-content:center;
        min-height:120px;
        text-align:center;
    }
    .leave-modal-dialog { width:min(720px, calc(100vw - 32px)); max-width:720px; }
    .leave-modal-content {
        display:flex;
        flex-direction:column;
        max-height:calc(100vh - 40px);
        border-radius:18px;
    }
    .leave-modal-body {
        overflow-y:auto;
        max-height:calc(100vh - 180px);
        padding:18px;
    }
    .leave-modal-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
    .leave-modal-grid .form-group { margin-bottom:0; }
    .leave-modal-grid-full { grid-column:1 / -1; }
    .leave-modal-note { font-size:11px; color:#64748b; margin-top:6px; }
    @media (max-width:640px) {
        .leave-modal-dialog { width:calc(100vw - 20px); }
        .leave-modal-body { max-height:calc(100vh - 160px); }
        .leave-modal-grid { grid-template-columns:1fr; }
    }
@endsection

@section('content')
    <div class="portal-page">
        <div class="stats-grid">
            @forelse ($quotaSummary as $quota)
                <div class="stat-card">
                    <div class="quota-stat-head">
                        <div class="quota-stat-title">{{ $quota['name'] }}</div>
                        <span class="quota-stat-chip">{{ $quota['remaining'] }} hari</span>
                    </div>
                    <div class="stat-note">{{ $quota['label'] }}</div>
                    <div class="quota-stat-meta">
                        <div>Terpakai {{ $quota['used'] }} hari</div>
                        <div>Hak {{ $quota['entitlement'] }} hari · Tahun {{ now()->year }}</div>
                    </div>
                </div>
            @empty
                <div class="stat-card quota-empty-card">
                    <div>
                        <div class="stat-label">Kuota Izin</div>
                        <div class="stat-note">Belum ada jenis izin yang memakai kuota.</div>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="panel-card">
            <div class="panel-head">
                <div class="panel-head-main">
                    <h3 class="panel-title">Riwayat Pengajuan</h3>
                    <div class="panel-subtitle">{{ $employee->nama_lengkap }} · {{ $employee->nik }} · Total {{ $leaveRows->total() }} pengajuan</div>
                </div>
                <div class="panel-head-actions">
                    <button type="button" class="btn btn-primary" onclick="openPortalLeaveModal()">Ajukan Izin / Cuti</button>
                </div>
            </div>
            <div class="panel-body">
                <form method="GET" class="filter-row">
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" class="filter-input">
                            <option value="">Semua</option>
                            <option value="pending" @selected($statusFilter === 'pending')>Pending</option>
                            <option value="disetujui" @selected($statusFilter === 'disetujui')>Disetujui</option>
                            <option value="ditolak" @selected($statusFilter === 'ditolak')>Ditolak</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Jenis</label>
                        <select name="jenis_izin_id" class="filter-input">
                            <option value="0">Semua</option>
                            @foreach ($leaveTypes as $type)
                                <option value="{{ $type->id }}" @selected($leaveTypeFilter === $type->id)>{{ $type->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Tampil</label>
                        <select name="per_page" class="filter-input">
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / halaman</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="button-group"><button type="submit" class="btn btn-primary">Filter</button></div>
                </form>

                <div class="table-responsive">
                    <table class="table-bordered">
                        <thead>
                            <tr class="table-header">
                                <th width="50">No</th>
                                <th width="170">Tanggal</th>
                                <th width="170">Jenis</th>
                                <th width="120">Status</th>
                                <th>Alasan</th>
                                <th width="120">Bukti</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leaveRows as $index => $leave)
                                <tr>
                                    <td class="text-center">{{ ($leaveRows->firstItem() ?? 1) + $index }}</td>
                                    <td>
                                        <strong>{{ optional($leave->tanggal_mulai_efektif)->translatedFormat('d M Y') }}</strong>
                                        <div class="muted-sm">s/d {{ optional($leave->tanggal_selesai_efektif)->translatedFormat('d M Y') }} · {{ $leave->effective_jumlah_hari }} hari</div>
                                    </td>
                                    <td>{{ $leave->leave_type_name }}</td>
                                    <td class="text-center">
                                        @php
                                            $badgeClass = match ((string) $leave->status) {
                                                'disetujui' => 'badge-success',
                                                'ditolak' => 'badge-danger',
                                                default => 'badge-warning',
                                            };
                                        @endphp
                                        <span class="badge-soft {{ $badgeClass }}">{{ ucfirst((string) $leave->status) }}</span>
                                    </td>
                                    <td>{{ $leave->alasan }}</td>
                                    <td class="text-center">
                                        @if ($leave->bukti)
                                            <a href="{{ asset('storage/bukti_izin/'.$leave->bukti) }}" target="_blank" class="btn btn-info">Lihat</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($leave->status === 'pending')
                                            <form method="POST" action="{{ route('karyawan.izin.destroy', $leave) }}" onsubmit="return confirm('Batalkan pengajuan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">Batal</button>
                                            </form>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="empty-state">Belum ada riwayat pengajuan izin.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $leaveRows->links() }}
            </div>
        </div>

        <div id="portalLeaveModal" class="modal{{ $openLeaveModal ? ' show' : '' }}" aria-hidden="{{ $openLeaveModal ? 'false' : 'true' }}">
            <div class="modal-dialog leave-modal-dialog">
                <div class="modal-content leave-modal-content">
                    <div class="modal-header">
                        <div>
                            <h3>Ajukan Izin / Cuti</h3>
                            <div class="panel-subtitle">Form pengajuan untuk akun Anda sendiri.</div>
                        </div>
                        <button type="button" class="modal-close" onclick="closePortalLeaveModal()">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('karyawan.izin.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="form_type" value="create">
                        <div class="modal-body leave-modal-body">
                            <div class="leave-modal-grid">
                                <div class="form-group leave-modal-grid-full">
                                    <label>Jenis Izin <span class="required">*</span></label>
                                    <select name="jenis_izin_id" class="form-control" required>
                                        <option value="">Pilih jenis izin</option>
                                        @foreach ($leaveTypes as $type)
                                            <option value="{{ $type->id }}" @selected(old('jenis_izin_id') == $type->id)>{{ $type->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Mulai <span class="required">*</span></label>
                                    <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai') }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Tanggal Selesai <span class="required">*</span></label>
                                    <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai') }}" required>
                                </div>
                                <div class="form-group leave-modal-grid-full">
                                    <label>Alasan <span class="required">*</span></label>
                                    <textarea name="alasan" class="form-control" rows="4" required>{{ old('alasan') }}</textarea>
                                </div>
                                <div class="form-group leave-modal-grid-full">
                                    <label>Bukti Pendukung</label>
                                    <input type="file" name="bukti" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                    <div class="leave-modal-note">Format JPG, PNG, PDF. Maksimal 2 MB.</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" onclick="closePortalLeaveModal()">Tutup</button>
                            <button type="submit" class="btn btn-primary">Kirim Pengajuan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function openPortalLeaveModal() {
            const modal = document.getElementById('portalLeaveModal');
            if (!modal) {
                return;
            }

            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closePortalLeaveModal() {
            const modal = document.getElementById('portalLeaveModal');
            if (!modal) {
                return;
            }

            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('portalLeaveModal');
            if (!modal) {
                return;
            }

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closePortalLeaveModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('show')) {
                    closePortalLeaveModal();
                }
            });
        });
    </script>
@endsection
