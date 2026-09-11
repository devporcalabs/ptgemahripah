@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Detail Karyawan'])

@section('styles')
    .employee-detail-layout {
        display: grid;
        grid-template-columns: 320px minmax(0, 1fr);
        gap: 20px;
        align-items: start;
    }
    .employee-profile-card {
        position: sticky;
        top: 96px;
    }
    .employee-avatar-box {
        width: 132px;
        height: 132px;
        border-radius: 26px;
        margin: 0 auto 16px;
        background: linear-gradient(135deg, #ECFDF5 0%, #D1FAE5 100%);
        border: 1px solid #A7F3D0;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .employee-avatar-box i {
        font-size: 46px;
        color: #047857;
    }
    .employee-profile-name {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        color: #0F172A;
        text-align: center;
    }
    .employee-profile-meta {
        margin-top: 6px;
        color: #64748B;
        font-size: 13px;
        text-align: center;
    }
    .employee-summary-list {
        margin-top: 18px;
        display: grid;
        gap: 10px;
    }
    .employee-summary-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 12px;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        background: #F8FAFC;
        font-size: 13px;
    }
    .employee-summary-item span:first-child {
        color: #64748B;
        font-weight: 600;
    }
    .employee-summary-item span:last-child {
        color: #0F172A;
        font-weight: 700;
        text-align: right;
    }
    .employee-main-stack {
        display: grid;
        gap: 20px;
    }
    .employee-detail-form {
        display: grid;
        gap: 16px;
    }
    .employee-form-section {
        display: grid;
        gap: 16px;
    }
    .detail-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
    .detail-form-grid.full-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .detail-form-grid .full-width {
        grid-column: 1 / -1;
    }
    .readonly-field {
        background: #F1F5F9 !important;
        color: #475569 !important;
        cursor: not-allowed !important;
    }
    .shift-work-help {
        padding: 12px 14px;
        border-radius: 14px;
        background: #ECFDF5;
        border: 1px solid #A7F3D0;
        color: #065F46;
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 16px;
    }
    .shift-rotation-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .shift-rotation-grid .form-group {
        margin-bottom: 0;
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
        margin-bottom: 16px;
    }
    .bank-info-card {
        display: grid;
        gap: 14px;
        padding: 14px 16px;
        border: 1px solid #BFDBFE;
        border-radius: 16px;
        background: #F8FBFF;
        margin-bottom: 16px;
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
    .employee-action-bar {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding-top: 4px;
    }
    .mini-help {
        margin-top: 6px;
        color: #64748B;
        font-size: 11px;
    }
    .shift-reset-row {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    .rfid-input-group {
        display: flex;
        align-items: stretch;
        gap: 8px;
    }
    .rfid-input-group .form-control {
        flex: 1;
        min-width: 0;
    }
    .rfid-pair-button {
        flex-shrink: 0;
        min-width: 126px;
        justify-content: center;
    }
    .rfid-feedback {
        display: block;
        margin-top: 6px;
        min-height: 18px;
        font-size: 11px;
        color: #64748B;
    }
    .rfid-feedback.is-waiting {
        color: #1D4ED8;
    }
    .rfid-feedback.is-success {
        color: #065F46;
    }
    .rfid-feedback.is-error {
        color: #B91C1C;
    }
    .detail-history-stack {
        display: grid;
        gap: 18px;
        margin-top: 18px;
    }
    .stats-grid {
        margin-top: 18px;
    }
    @media (max-width: 1100px) {
        .employee-detail-layout {
            grid-template-columns: 1fr;
        }
        .employee-profile-card {
            position: static;
        }
    }
    @media (max-width: 768px) {
        .rfid-input-group {
            flex-direction: column;
        }
        .rfid-pair-button {
            width: 100%;
            min-width: 0;
        }
        .detail-form-grid,
        .detail-form-grid.full-3 {
            grid-template-columns: 1fr;
        }
        .shift-rotation-grid,
        .weekday-grid {
            grid-template-columns: 1fr;
        }
    }
@endsection

@section('content')
    @php
        $formatDurationLabel = static fn (int $minutes): string => format_duration_minutes_label($minutes);

        $totalLemburMinutes = (int) round((float) ($totalLembur ?? 0) * 60);
        $backUrl = request('from') === 'resign'
            ? route('admin.karyawan.resign')
            : route('admin.karyawan');
    @endphp
    <div id="employeeDetailFragment">
        <div class="page-header" style="margin-bottom:18px;">
            <div>
                <h3 class="page-subtitle">Detail Pegawai</h3>
                <p class="page-description">Lihat informasi lengkap tentang karyawan ini.</p>
            </div>
            <div class="button-group">
                <a href="{{ $backUrl }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Karyawan
                </a>
            </div>
        </div>

        <div class="employee-detail-layout">
            <div class="employee-profile-card">
                <div class="card">
                    <div class="card-body">
                        <div class="employee-avatar-box">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2 class="employee-profile-name">{{ $karyawan->nama_lengkap }}</h2>
                        <div class="employee-profile-meta">
                            {{ $karyawan->nik }} &middot; {{ $karyawan->jabatan ?: 'Belum ada jabatan' }}
                        </div>

                        <div style="display:flex; justify-content:center; margin-top:14px;">
                            <span class="badge {{ $karyawan->employment_status_badge_class }}">
                                {{ $karyawan->employment_status_label }}
                            </span>
                        </div>

                        <div class="employee-summary-list">
                            <div class="employee-summary-item">
                                <span>Email</span>
                                <span>{{ $karyawan->email ?: '-' }}</span>
                            </div>
                            <div class="employee-summary-item">
                                <span>Telepon</span>
                                <span>{{ $karyawan->telepon ?: $karyawan->no_telp ?: '-' }}</span>
                            </div>
                            <div class="employee-summary-item">
                                <span>UID RFID</span>
                                <span>{{ $karyawan->rfid_uid ?: '-' }}</span>
                            </div>
                            <div class="employee-summary-item">
                                <span>Jenis Karyawan</span>
                                <span>{{ $karyawan->jenis_karyawan_label }}</span>
                            </div>
                            <div class="employee-summary-item">
                                <span>Departemen</span>
                                <span>{{ $karyawan->departemen ?: '-' }}</span>
                            </div>
                            <div class="employee-summary-item">
                                <span>Lokasi Default</span>
                                <span>{{ $karyawan->lokasiGps?->nama_lokasi ?: '-' }}</span>
                            </div>
                            <div class="employee-summary-item">
                                <span>Masa Kerja</span>
                                <span>{{ $karyawan->masa_kerja_label }}</span>
                            </div>
                            @if ($karyawan->tgl_resign)
                                <div class="employee-summary-item">
                                    <span>Clearance</span>
                                    <span>{{ $karyawan->clearance_status_label }} · {{ $karyawan->clearance_progress_label }}</span>
                                </div>
                            @endif
                            @if ($karyawan->tgl_resign)
                                <div class="employee-summary-item">
                                    <span>Tanggal Resign</span>
                                    <span>{{ $karyawan->tgl_resign->format('d/m/Y') }}</span>
                                </div>
                            @endif
                            @if ($karyawan->alasan_resign)
                                <div class="employee-summary-item">
                                    <span>Alasan Resign</span>
                                    <span>{{ $karyawan->alasan_resign }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="employee-main-stack">
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $totalHadir }}</div><div class="stat-label">Total Hadir</div></div><div class="stat-icon"><i class="fas fa-user-check"></i></div></div></div>
                    <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $totalTerlambat }}</div><div class="stat-label">Terlambat</div></div><div class="stat-icon"><i class="fas fa-clock"></i></div></div></div>
                    <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $totalIzin }}</div><div class="stat-label">Izin Disetujui</div></div><div class="stat-icon"><i class="fas fa-file-alt"></i></div></div></div>
                    <div class="stat-card"><div class="stat-card-body"><div><div class="stat-value">{{ $formatDurationLabel($totalLemburMinutes) }}</div><div class="stat-label">Total Lembur</div></div><div class="stat-icon"><i class="fas fa-business-time"></i></div></div></div>
                </div>

                <div class="detail-history-stack">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-history" style="color:#065F46; margin-right:8px;"></i>Riwayat Absensi</h3>
                            <span class="data-count">Total: {{ $absensiList->count() }} data</span>
                        </div>
                        <div class="table-responsive" data-fragment-loading-scope>
                            <table class="table-bordered">
                                <thead>
                                    <tr class="table-header">
                                        <th>Tanggal</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Keluar</th>
                                        <th>Lokasi Masuk</th>
                                        <th>Lokasi Keluar</th>
                                        <th>Status</th>
                                        <th>Terlambat</th>
                                        <th>Pulang Cepat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($absensiList as $absen)
                                        <tr>
                                            <td>{{ $absen->tanggal?->format('d/m/Y') }}</td>
                                            <td>{{ $absen->jam_masuk ?: '-' }}</td>
                                            <td>{{ $absen->jam_keluar ?: '-' }}</td>
                                            <td>{{ $absen->lokasi_masuk ?: '-' }}</td>
                                            <td>{{ $absen->lokasi_keluar ?: '-' }}</td>
                                            <td>
                                                <span class="badge {{ $absen->status === 'hadir' ? 'badge-success' : ($absen->status === 'terlambat' ? 'badge-warning' : 'badge-info') }}">
                                                    {{ ucfirst($absen->status) }}
                                                </span>
                                            </td>
                                            <td>{{ format_duration_minutes_label($absen->menit_terlambat ?? 0) }}</td>
                                            <td>{{ format_duration_minutes_label($absen->menit_pulang_cepat ?? 0) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="empty-state">Belum ada data absensi.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-file-alt" style="color:#065F46; margin-right:8px;"></i>Riwayat Izin</h3>
                            <span class="data-count">Total: {{ $izinList->count() }} data</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table-bordered">
                                <thead>
                                    <tr class="table-header">
                                        <th>Tanggal Pengajuan</th>
                                        <th>Periode Izin</th>
                                        <th>Durasi</th>
                                        <th>Jenis Izin</th>
                                        <th>Alasan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($izinList as $izin)
                                        <tr>
                                            <td>{{ $izin->created_at ? \Carbon\Carbon::parse($izin->created_at)->format('d/m/Y H:i') : '-' }}</td>
                                            <td>
                                                {{ $izin->tanggal_mulai_efektif ? $izin->tanggal_mulai_efektif->format('d/m/Y') : '-' }}
                                                @if ($izin->tanggal_selesai_efektif && $izin->tanggal_mulai_efektif && $izin->tanggal_selesai_efektif->ne($izin->tanggal_mulai_efektif))
                                                    <div class="text-muted">s/d {{ $izin->tanggal_selesai_efektif->format('d/m/Y') }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $izin->effective_jumlah_hari }} hari kerja</td>
                                            <td>{{ $izin->leave_type_name }}</td>
                                            <td>{{ $izin->alasan ?: '-' }}</td>
                                            <td>
                                                <span class="badge {{ $izin->status === 'disetujui' ? 'badge-success' : ($izin->status === 'pending' ? 'badge-warning' : 'badge-danger') }}">
                                                    {{ ucfirst($izin->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="empty-state">Belum ada data izin.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-clock" style="color:#065F46; margin-right:8px;"></i>Riwayat Lembur Otomatis</h3>
                            <span class="data-count">Total: {{ $overtimeList->count() }} data</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table-bordered">
                                <thead>
                                    <tr class="table-header">
                                        <th>Tanggal</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Keluar</th>
                                        <th>Durasi Lembur</th>
                                        <th>Tarif Lembur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($overtimeList as $overtime)
                                        <tr>
                                            <td>{{ $overtime->tanggal ? $overtime->tanggal->format('d/m/Y') : '-' }}</td>
                                            <td>{{ $overtime->jam_masuk ?: '-' }}</td>
                                            <td>{{ $overtime->jam_keluar ?: '-' }}</td>
                                            @php
                                                $overtimeMinutes = (int) ($overtime->menit_lembur ?? round(((float) ($overtime->jam_lembur ?? 0)) * 60));
                                            @endphp
                                            <td>{{ $formatDurationLabel($overtimeMinutes) }}</td>
                                            <td>Rp {{ number_format((float) ($overtime->tarif_lembur ?? 0), 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="empty-state">Belum ada data lembur otomatis.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
