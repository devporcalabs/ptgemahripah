@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Riwayat Absensi'])

@section('styles')
    .stats-grid { grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
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
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .stat-card .stat-card-body { padding: 0; width: 100%; }
    .stat-card.total { border-left: 4px solid #3B82F6; }
    .stat-card.present { border-left: 4px solid #10B981; }
    .stat-card.permission { border-left: 4px solid #F59E0B; }
    .stat-card.late { border-left: 4px solid #EF4444; }
    .stat-card.total .stat-icon { background: #DBEAFE; color: #2563EB; }
    .stat-card.present .stat-icon { background: #D1FAE5; color: #065F46; }
    .stat-card.permission .stat-icon { background: #FEF3C7; color: #D97706; }
    .stat-card.late .stat-icon { background: #FEE2E2; color: #DC2626; }
    .page-header { margin-bottom: 20px; }
    .filter-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .filter-select {
        padding: 8px 12px;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        font-size: 13px;
        min-width: 180px;
        font-family: inherit;
    }
    .filter-select[type="date"] { min-width: 160px; }
    .filter-select[type="search"] { min-width: 240px; }
    .card { margin-bottom:20px; }
    .card-header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .modal-header { padding:14px 18px; }
    .modal-header h3 { font-size:15px; }
    .modal-body { padding:16px; }
    .table-meta { display:block; margin-top:4px; font-size:11px; color:#64748B; }
    .correction-note { display:block; margin-top:4px; font-size:11px; color:#64748B; line-height:1.45; }
    .action-buttons-inline { display:flex; gap:6px; flex-wrap:wrap; justify-content:center; }
    @media (max-width: 900px) {
        .filter-toolbar { align-items:stretch; }
        .filter-select { width:100%; min-width:0; }
    }
    @media (max-width: 640px) {
        .stats-grid { grid-template-columns:1fr; }
    }
@endsection

@section('content')
    <div id="ajaxFilterFragment">
        <div class="stats-grid">
            @foreach ([
                ['key' => 'total', 'label' => 'Total Karyawan', 'value' => $totalKaryawan, 'icon' => 'fa-users'],
                ['key' => 'present', 'label' => 'Hadir', 'value' => $totalHadir, 'icon' => 'fa-user-check'],
                ['key' => 'permission', 'label' => 'Izin / Sakit', 'value' => $totalIzinSakit, 'icon' => 'fa-file-alt'],
                ['key' => 'late', 'label' => 'Terlambat', 'value' => $totalTerlambat, 'icon' => 'fa-clock'],
            ] as $item)
                <div class="stat-card {{ $item['key'] }}">
                    <div class="stat-card-body">
                        <div>
                            <div class="stat-value">{{ $item['value'] }}</div>
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
                <h3 class="page-subtitle">Riwayat Absensi</h3>
                <p class="page-description">Lihat dan kelola riwayat absensi seluruh karyawan.</p>
            </div>
            <form method="GET" class="filter-toolbar" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxFilterFragment">
                <input type="date" name="tanggal" class="filter-select" value="{{ $tanggalFilter }}">
                <select name="status" class="filter-select">
                    <option value="">Semua Status</option>
                    @foreach (['hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'izin' => 'Izin', 'cuti' => 'Cuti'] as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" class="filter-select" value="{{ $search ?? '' }}" placeholder="Cari NIK, nama, jabatan, lokasi...">
                <select name="per_page" class="filter-select">
                    @foreach ([15, 25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected(($perPage ?? 15) === $size)>{{ $size }} / halaman</option>
                    @endforeach
                </select>
                <a href="{{ route('admin.absensi') }}" class="btn btn-secondary" data-ajax-link="true" data-refresh-target="#ajaxFilterFragment">
                    <i class="fas fa-rotate-left"></i> Reset
                </a>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list" style="color:#065F46; margin-right:8px;"></i>Data Absensi</h3>
                <div class="card-header-actions">
                    <span class="total-data">Total: {{ $absensiList->total() }} data</span>
                    <a href="{{ route('admin.absensi.export', ['tanggal' => $tanggalFilter, 'status' => $statusFilter, 'q' => $search ?? '', 'type' => 'excel']) }}" class="btn-export excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="{{ route('admin.absensi.export', ['tanggal' => $tanggalFilter, 'status' => $statusFilter, 'q' => $search ?? '', 'type' => 'pdf']) }}" class="btn-export pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table-bordered">
                    <thead>
                        <tr class="table-header">
                            <th width="40">No</th>
                            <th>Tanggal</th>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Jam Masuk</th>
                            <th>Jam Keluar</th>
                            <th>Shift</th>
                            <th>Sumber Jadwal</th>
                            <th>Mesin / Lokasi</th>
                            <th>Status</th>
                            <th>Koreksi</th>
                            <th>Terlambat</th>
                            <th>Pulang Cepat</th>
                            <th>Lembur</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($absensiList as $index => $absen)
                            @php
                                $badgeClass = match ($absen->status) {
                                    'hadir' => 'badge-success',
                                    'terlambat' => 'badge-warning',
                                    'izin' => 'badge-info',
                                    default => 'badge-danger',
                                };
                                $badgeLabel = match ($absen->status) {
                                    'hadir' => 'Hadir',
                                    'terlambat' => 'Terlambat',
                                    'izin' => 'Izin',
                                    default => 'Cuti',
                                };
                            @endphp
                            <tr>
                                <td class="text-center">{{ ($absensiList->firstItem() ?? 1) + $index }}</td>
                                <td>{{ \Carbon\Carbon::parse($absen->tanggal)->format('d/m/Y') }}</td>
                                <td>{{ $absen->nik }}</td>
                                <td><strong>{{ $absen->nama_lengkap }}</strong></td>
                                <td>{{ $absen->jabatan ?: '-' }}</td>
                                <td class="text-center">{{ $absen->jam_masuk ? \Carbon\Carbon::parse($absen->jam_masuk)->format('H:i:s') : '-' }}</td>
                                <td class="text-center">{{ $absen->jam_keluar ? \Carbon\Carbon::parse($absen->jam_keluar)->format('H:i:s') : '-' }}</td>
                                <td class="text-center">{{ $absen->shift_nama ?: '-' }}</td>
                                <td class="text-center">{{ $absen->schedule_source_label }}</td>
                                <td>
                                    {{ $absen->device_name ?: '-' }}
                                    <span class="table-meta">{{ $absen->lokasi_masuk ?: '-' }}</span>
                                </td>
                                <td class="text-center"><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
                                <td class="text-center">
                                    @php
                                        $correctionBadgeClass = match ($absen->correction_status) {
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'pending' => 'badge-warning',
                                            default => 'badge-info',
                                        };
                                        $correctionBadgeLabel = match ($absen->correction_status) {
                                            'approved' => 'Disetujui',
                                            'rejected' => 'Ditolak',
                                            'pending' => 'Menunggu',
                                            default => 'Belum Ada',
                                        };
                                    @endphp
                                    <span class="badge {{ $correctionBadgeClass }}">{{ $correctionBadgeLabel }}</span>
                                    @if ($absen->correction_note)
                                        <span class="correction-note">{{ $absen->correction_note }}</span>
                                    @endif
                                </td>
                                <td class="text-center" style="color:{{ $absen->menit_terlambat_display > 0 ? '#DC2626' : '#1E293B' }};">
                                    {{ $absen->menit_terlambat_display_label }}
                                </td>
                                <td class="text-center" style="color:{{ $absen->menit_pulang_cepat_display > 0 ? '#D97706' : '#1E293B' }};">
                                    {{ $absen->menit_pulang_cepat_display_label }}
                                </td>
                                <td class="text-center" style="color:{{ $absen->menit_lembur_display > 0 ? '#7C3AED' : '#1E293B' }};">
                                    {{ $absen->menit_lembur_display_label }}
                                </td>
                                <td>
                                    <div class="action-buttons-inline">
                                        @if ($absen->correction_status !== 'pending')
                                            <button
                                                type="button"
                                                class="btn btn-warning"
                                                data-action="{{ route('admin.absensi.corrections.store', $absen->id) }}"
                                                data-tanggal="{{ \Carbon\Carbon::parse($absen->tanggal)->format('Y-m-d') }}"
                                                data-shift-id="{{ $absen->shift_id }}"
                                                data-status="{{ $absen->status }}"
                                                data-jam-masuk="{{ $absen->jam_masuk ? \Carbon\Carbon::parse($absen->jam_masuk)->format('H:i') : '' }}"
                                                data-jam-keluar="{{ $absen->jam_keluar ? \Carbon\Carbon::parse($absen->jam_keluar)->format('H:i') : '' }}"
                                                data-lokasi-masuk="{{ $absen->lokasi_masuk }}"
                                                data-lokasi-keluar="{{ $absen->lokasi_keluar }}"
                                                onclick="openCorrectionModal(this)">
                                                <i class="fas fa-pen-to-square"></i> Koreksi
                                            </button>
                                        @endif

                                        @if ($absen->correction_status === 'pending' && $absen->correction_id)
                                            <form method="POST" action="{{ route('admin.absensi.corrections.approve', $absen->correction_id) }}" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-confirm="Setujui koreksi absensi ini?" data-confirm-title="Setujui Koreksi" data-confirm-button="Ya, setujui" data-confirm-variant="success">
                                                @csrf
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-check"></i> Setujui
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.absensi.corrections.reject', $absen->correction_id) }}" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-confirm="Tolak koreksi absensi ini?" data-confirm-title="Tolak Koreksi" data-confirm-button="Ya, tolak" data-confirm-variant="danger">
                                                @csrf
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-times"></i> Tolak
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="16" class="empty-state">Belum ada data absensi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $absensiList->links('partials.pagination-ajax', ['target' => '#ajaxFilterFragment']) }}
        </div>
    </div>

    <div id="correctionModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Koreksi Absensi</h3>
                    <button type="button" class="modal-close" onclick="closeCorrectionModal()">&times;</button>
                </div>
                <form method="POST" id="correctionForm" action="#" data-ajax="true" data-refresh-target="#ajaxFilterFragment" data-close-modal="#correctionModal" data-reset-on-success="true">
                    @csrf
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal" id="correction_tanggal" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" id="correction_status" class="form-select" required>
                                    <option value="hadir">Hadir</option>
                                    <option value="terlambat">Terlambat</option>
                                    <option value="izin">Izin</option>
                                    <option value="cuti">Cuti</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Jam Masuk</label>
                                <input type="time" name="jam_masuk" id="correction_jam_masuk" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Jam Keluar</label>
                                <input type="time" name="jam_keluar" id="correction_jam_keluar" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Shift</label>
                            <select name="shift_id" id="correction_shift_id" class="form-select">
                                <option value="">Tanpa Shift</option>
                                @foreach ($shiftOptions as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->nama_shift }} ({{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->jam_keluar)->format('H:i') }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Lokasi Masuk</label>
                                <input type="text" name="lokasi_masuk" id="correction_lokasi_masuk" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Lokasi Keluar</label>
                                <input type="text" name="lokasi_keluar" id="correction_lokasi_keluar" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Alasan Koreksi</label>
                            <textarea name="note" id="correction_note" class="form-control" rows="3" placeholder="Jelaskan alasan perubahan data absensi..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeCorrectionModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">Kirim Koreksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const correctionModal = document.getElementById('correctionModal');
        const correctionForm = document.getElementById('correctionForm');

        function openCorrectionModal(button) {
            const data = button.dataset;
            correctionForm.action = data.action || '#';
            document.getElementById('correction_tanggal').value = data.tanggal || '';
            document.getElementById('correction_shift_id').value = data.shiftId || '';
            document.getElementById('correction_status').value = data.status || 'hadir';
            document.getElementById('correction_jam_masuk').value = data.jamMasuk || '';
            document.getElementById('correction_jam_keluar').value = data.jamKeluar || '';
            document.getElementById('correction_lokasi_masuk').value = data.lokasiMasuk || '';
            document.getElementById('correction_lokasi_keluar').value = data.lokasiKeluar || '';
            document.getElementById('correction_note').value = '';
            correctionModal.classList.add('show');
        }

        function closeCorrectionModal() {
            correctionModal.classList.remove('show');
        }

        window.addEventListener('click', function (event) {
            if (event.target === correctionModal) {
                closeCorrectionModal();
            }
        });
    </script>
@endsection
