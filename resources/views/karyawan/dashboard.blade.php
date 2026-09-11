@extends('layouts.panel', ['panel' => 'karyawan', 'pageTitle' => 'Dashboard Karyawan'])

@section('styles')
    .portal-page { display:grid; gap:18px; }
    .portal-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }
    .portal-card-header {
        padding: 14px 18px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .portal-card-title {
        margin: 0;
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .portal-card-title i { color: #065F46; }
    .portal-card-body { padding: 18px; }
    .schedule-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }
    .schedule-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 82px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #166534;
        font-size: 11px;
        font-weight: 700;
    }
    .schedule-info { display: grid; gap: 10px; }
    .schedule-row {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eef2f7;
    }
    .schedule-row:last-child { padding-bottom: 0; border-bottom: none; }
    .schedule-label { font-size: 12px; color: #64748b; }
    .schedule-value { font-size: 13px; font-weight: 600; color: #0f172a; text-align: right; }
    .schedule-empty {
        display: grid;
        place-items: center;
        text-align: center;
        gap: 8px;
        min-height: 154px;
        color: #64748b;
        font-size: 13px;
    }
    .schedule-empty i { font-size: 24px; color: #94a3b8; }
    .notice-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
    .notice-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background: #fff;
    }
    .notice-item.warning { border-color: #fde68a; background: #fffbeb; }
    .notice-item.info { border-color: #bfdbfe; background: #eff6ff; }
    .notice-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: #fff;
        flex-shrink: 0;
    }
    .notice-item.warning .notice-icon { background: #f59e0b; }
    .notice-item.info .notice-icon { background: #3b82f6; }
    .notice-title { font-size: 13px; font-weight: 700; color: #0f172a; }
    .notice-subtitle { margin-top: 4px; font-size: 12px; color: #475569; }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }
    .stat-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
    }
    .stat-label { font-size: 12px; color: #64748b; margin-bottom: 8px; }
    .stat-value { font-size: 24px; font-weight: 800; color: #0f172a; line-height: 1.15; }
    .stat-note { margin-top: 8px; font-size: 12px; color: #475569; }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        background: #ecfdf5;
        color: #065F46;
        flex-shrink: 0;
    }
    .bottom-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }
    .quick-menu {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }
    .quick-item {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px 12px;
        text-decoration: none;
        color: #0f172a;
        background: #fff;
        display: grid;
        justify-items: center;
        gap: 10px;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
    }
    .quick-item i {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #ecfdf5;
        color: #065F46;
        font-size: 18px;
    }
    .quick-item:hover { border-color: #86efac; background: #f8fafc; }
    .summary-list { display: grid; gap: 10px; }
    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eef2f7;
    }
    .summary-row:last-child { padding-bottom: 0; border-bottom: none; }
    .summary-label { font-size: 12px; color: #64748b; }
    .summary-value { font-size: 13px; font-weight: 600; color: #0f172a; text-align: right; }
    .table-responsive { overflow-x: auto; }
    .portal-table { width: 100%; border-collapse: collapse; }
    .portal-table th, .portal-table td {
        padding: 10px 0;
        border-bottom: 1px solid #eef2f7;
        text-align: left;
        font-size: 13px;
        color: #334155;
    }
    .portal-table th {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
    }
    .portal-table td:last-child, .portal-table th:last-child { text-align: right; }
    .portal-empty { color: #64748b; font-size: 13px; }
    @media (max-width: 1100px) {
        .stats-grid, .schedule-grid, .notice-grid, .bottom-grid { grid-template-columns: 1fr 1fr; }
        .bottom-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .stats-grid, .schedule-grid, .notice-grid, .quick-menu { grid-template-columns: 1fr; }
        .schedule-row, .summary-row { flex-direction: column; gap: 6px; }
        .schedule-value, .summary-value { text-align: left; }
    }
@endsection

@section('content')
    @php
        $scheduleSourceLabel = static function (array $schedule): string {
            return match ($schedule['source'] ?? 'none') {
                'tetap' => 'Shift Tetap',
                'rolling' => 'Shift Rolling',
                'fleksibel' => 'Shift Fleksibel',
                'libur_global' => 'Libur Nasional',
                'libur_mingguan' => 'Libur Mingguan',
                default => 'Belum Ada Jadwal',
            };
        };
    @endphp

    <div class="portal-page">
        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-label">Total Hadir</div>
                    <div class="stat-value">{{ $attendanceCount }}</div>
                    <div class="stat-note">{{ $periodLabel }}</div>
                </div>
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-label">Terlambat</div>
                    <div class="stat-value">{{ format_duration_minutes_label($lateMinutes) }}</div>
                    <div class="stat-note">Akumulasi bulan ini</div>
                </div>
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-label">Izin Disetujui</div>
                    <div class="stat-value">{{ $approvedLeaves }}</div>
                    <div class="stat-note">Riwayat izin aktif</div>
                </div>
                <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-label">Saldo Kasbon</div>
                    <div class="stat-value">Rp {{ number_format((float) $employee->saldo_kasbon, 0, ',', '.') }}</div>
                    <div class="stat-note">Saldo aktif</div>
                </div>
                <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            </div>
        </div>

        <div class="schedule-grid">
            <div class="portal-card">
                <div class="portal-card-header">
                    <h3 class="portal-card-title"><i class="fas fa-calendar-day"></i> Jadwal Hari Ini</h3>
                    <span class="schedule-badge">{{ $today->translatedFormat('l') }}</span>
                </div>
                <div class="portal-card-body">
                    @if ($todaySchedule['is_workday'] ?? false)
                        <div class="schedule-info">
                            <div class="schedule-row">
                                <span class="schedule-label">Tipe Jadwal</span>
                                <span class="schedule-value">{{ $scheduleSourceLabel($todaySchedule) }}</span>
                            </div>
                            <div class="schedule-row">
                                <span class="schedule-label">Shift</span>
                                <span class="schedule-value">{{ $todaySchedule['shift_name'] ?? '-' }}</span>
                            </div>
                            <div class="schedule-row">
                                <span class="schedule-label">Jam Kerja</span>
                                <span class="schedule-value">{{ $todaySchedule['shift_time'] ?? '-' }}</span>
                            </div>
                            <div class="schedule-row">
                                <span class="schedule-label">Lokasi</span>
                                <span class="schedule-value">{{ $employee->lokasiGps?->nama_lokasi ?? '-' }}</span>
                            </div>
                        </div>
                    @else
                        <div class="schedule-empty">
                            <i class="fas fa-calendar-times"></i>
                            <div>
                                <div><strong>Libur / Tidak ada jadwal</strong></div>
                                <div>{{ $todaySchedule['holiday_name'] ?? 'Tidak ada jadwal kerja' }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="portal-card">
                <div class="portal-card-header">
                    <h3 class="portal-card-title"><i class="fas fa-calendar-alt"></i> Jadwal Besok</h3>
                    <span class="schedule-badge">{{ $tomorrow->translatedFormat('l') }}</span>
                </div>
                <div class="portal-card-body">
                    @if ($tomorrowSchedule['is_workday'] ?? false)
                        <div class="schedule-info">
                            <div class="schedule-row">
                                <span class="schedule-label">Tipe Jadwal</span>
                                <span class="schedule-value">{{ $scheduleSourceLabel($tomorrowSchedule) }}</span>
                            </div>
                            <div class="schedule-row">
                                <span class="schedule-label">Shift</span>
                                <span class="schedule-value">{{ $tomorrowSchedule['shift_name'] ?? '-' }}</span>
                            </div>
                            <div class="schedule-row">
                                <span class="schedule-label">Jam Kerja</span>
                                <span class="schedule-value">{{ $tomorrowSchedule['shift_time'] ?? '-' }}</span>
                            </div>
                            <div class="schedule-row">
                                <span class="schedule-label">Lokasi</span>
                                <span class="schedule-value">{{ $employee->lokasiGps?->nama_lokasi ?? '-' }}</span>
                            </div>
                        </div>
                    @else
                        <div class="schedule-empty">
                            <i class="fas fa-calendar-times"></i>
                            <div>
                                <div><strong>Libur / Tidak ada jadwal</strong></div>
                                <div>{{ $tomorrowSchedule['holiday_name'] ?? 'Tidak ada jadwal kerja' }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($pendingLeaves > 0)
            <div class="notice-grid">
                @if ($pendingLeaves > 0)
                    <div class="notice-item warning">
                        <div class="notice-icon"><i class="fas fa-envelope-open-text"></i></div>
                        <div>
                            <div class="notice-title">Pengajuan Izin</div>
                            <div class="notice-subtitle">{{ $pendingLeaves }} pengajuan menunggu persetujuan.</div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="bottom-grid">
            <div class="portal-card">
                <div class="portal-card-header">
                    <h3 class="portal-card-title"><i class="fas fa-bolt"></i> Menu Cepat</h3>
                </div>
                <div class="portal-card-body">
                    <div class="quick-menu">
                        <a href="{{ route('karyawan.absensi') }}" class="quick-item">
                            <i class="fas fa-fingerprint"></i>
                            <span>Absensi</span>
                        </a>
                        <a href="{{ route('karyawan.jadwal') }}" class="quick-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Jadwal Kerja</span>
                        </a>
                        <a href="{{ route('karyawan.izin') }}" class="quick-item">
                            <i class="fas fa-envelope-open-text"></i>
                            <span>Izin / Cuti</span>
                        </a>
                        <a href="{{ route('karyawan.payroll') }}" class="quick-item">
                            <i class="fas fa-wallet"></i>
                            <span>Payroll</span>
                        </a>
                        <a href="{{ route('karyawan.pajak') }}" class="quick-item">
                            <i class="fas fa-receipt"></i>
                            <span>Pajak</span>
                        </a>
                        <a href="{{ route('karyawan.profil') }}" class="quick-item">
                            <i class="fas fa-user-circle"></i>
                            <span>Profil</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="portal-card">
                <div class="portal-card-header">
                    <h3 class="portal-card-title"><i class="fas fa-chart-line"></i> Ringkasan Bulan Ini</h3>
                </div>
                <div class="portal-card-body">
                    <div class="summary-list">
                        <div class="summary-row"><span class="summary-label">Nama</span><span class="summary-value">{{ $employee->nama_lengkap }}</span></div>
                        <div class="summary-row"><span class="summary-label">NIK</span><span class="summary-value">{{ $employee->nik }}</span></div>
                        <div class="summary-row"><span class="summary-label">Jabatan</span><span class="summary-value">{{ $employee->jabatanData?->nama_jabatan ?? $employee->jabatan ?? '-' }}</span></div>
                        <div class="summary-row"><span class="summary-label">Departemen</span><span class="summary-value">{{ $employee->departemenData?->nama_departemen ?? $employee->departemen ?? '-' }}</span></div>
                        <div class="summary-row"><span class="summary-label">Tanggal Join</span><span class="summary-value">{{ optional($employee->tgl_join)->translatedFormat('d M Y') ?? '-' }}</span></div>
                        <div class="summary-row"><span class="summary-label">Pulang Cepat</span><span class="summary-value">{{ format_duration_minutes_label($earlyLeaveMinutes) }}</span></div>
                        <div class="summary-row"><span class="summary-label">Lembur</span><span class="summary-value">{{ format_duration_minutes_label($overtimeMinutes) }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="portal-card">
            <div class="portal-card-header">
                <h3 class="portal-card-title"><i class="fas fa-history"></i> Absensi Terakhir</h3>
            </div>
            <div class="portal-card-body">
                @if ($latestAttendances->isEmpty())
                    <div class="portal-empty">Belum ada data absensi.</div>
                @else
                    <div class="table-responsive">
                        <table class="portal-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Masuk</th>
                                    <th>Pulang</th>
                                    <th>Telat</th>
                                    <th>Pulang Cepat</th>
                                    <th>Lembur</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($latestAttendances as $attendance)
                                    <tr>
                                        <td>{{ optional($attendance->tanggal)->translatedFormat('d M Y') }}</td>
                                        <td>{{ $attendance->jam_masuk ?: '-' }}</td>
                                        <td>{{ $attendance->jam_keluar ?: '-' }}</td>
                                        <td>{{ format_duration_minutes_label((int) ($attendance->menit_terlambat ?? 0)) }}</td>
                                        <td>{{ format_duration_minutes_label((int) ($attendance->menit_pulang_cepat ?? 0)) }}</td>
                                        <td>{{ format_duration_minutes_label((int) ($attendance->menit_lembur ?? 0)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
