@extends('layouts.panel', ['panel' => 'karyawan', 'pageTitle' => 'Jadwal Kerja'])

@section('styles')
    .portal-page { display:grid; gap:18px; }
    .info-card {
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        border-radius: 12px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13px;
        color: #1d4ed8;
    }
    .info-card i { font-size: 18px; }
    .portal-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        overflow: hidden;
    }
    .portal-card-header {
        padding: 14px 18px;
        border-bottom: 1px solid #e2e8f0;
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
    .table-responsive { overflow-x: auto; }
    .jadwal-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .jadwal-table th,
    .jadwal-table td {
        border: 1px solid #d1d5db;
        padding: 12px 10px;
        vertical-align: top;
    }
    .jadwal-table th {
        background: #f8fafc;
        color: #065F46;
        font-weight: 700;
        text-align: left;
        font-size: 12px;
    }
    .jadwal-table td { color: #334155; }
    .hari-cell {
        font-weight: 700;
        background: #f8fafc;
        width: 120px;
    }
    .hari-ini { background: #f0fdf4; }
    .hari-ini .hari-cell { background: #d1fae5; }
    .badge-today {
        display: inline-block;
        background: #10b981;
        color: #fff;
        font-size: 9px;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: 8px;
        font-weight: 600;
    }
    .jam-cell {
        font-family: monospace;
        font-weight: 600;
        white-space: nowrap;
    }
    .lokasi-name { font-weight: 700; color: #065F46; }
    .lokasi-alamat { margin-top: 4px; font-size: 11px; color: #64748b; }
    .lokasi-radius { margin-top: 2px; font-size: 10px; color: #f59e0b; }
    .no-jadwal-cell { text-align: center; background: #f8fafc; color: #64748b; }
    .legend-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        font-size: 11px;
    }
    .legend-title { font-weight: 700; color: #0f172a; }
    .legend-items { display: flex; align-items: center; flex-wrap: wrap; gap: 16px; }
    .legend-item { display: flex; align-items: center; gap: 6px; }
    .legend-badge { width: 16px; height: 16px; border-radius: 4px; display: inline-block; }
    @media (max-width: 640px) {
        .info-card { flex-direction: column; align-items: flex-start; }
        .jadwal-table thead { display: none; }
        .jadwal-table tbody tr {
            display: block;
            margin-bottom: 12px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            overflow: hidden;
        }
        .jadwal-table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            border: none;
            border-bottom: 1px solid #f1f5f9;
        }
        .jadwal-table tbody td:last-child { border-bottom: none; }
        .jadwal-table tbody td::before {
            content: attr(data-label);
            width: 96px;
            font-size: 11px;
            font-weight: 700;
            color: #065F46;
        }
        .no-jadwal-cell {
            display: block;
            text-align: center;
        }
        .no-jadwal-cell::before { display: none; }
        .jam-cell { white-space: normal; }
    }
@endsection

@section('content')
    @php
        $renderScheduleType = static function (array $schedule): string {
            return match ($schedule['source'] ?? 'none') {
                'tetap' => $schedule['shift_name'] ?? 'Shift Tetap',
                'rolling' => $schedule['shift_name'] ?? 'Shift Rolling',
                'fleksibel' => 'Fleksibel',
                'libur_global' => 'Libur Nasional',
                'libur_mingguan' => 'Libur Mingguan',
                default => 'Tidak ada jadwal',
            };
        };
    @endphp

    <div class="portal-page">
        <div class="info-card">
            <i class="fas fa-info-circle"></i>
            <div>
                Berikut adalah jadwal kerja Anda selama satu minggu.
                <strong>Hari ini: {{ $today->translatedFormat('l') }}</strong>
            </div>
        </div>

        <div class="portal-card">
            <div class="portal-card-header">
                <h3 class="portal-card-title"><i class="fas fa-calendar-alt"></i> Jadwal Kerja Mingguan</h3>
            </div>
            <div class="table-responsive">
                <table class="jadwal-table">
                    <thead>
                        <tr>
                            <th>Hari</th>
                            <th>Shift</th>
                            <th>Jam Kerja</th>
                            <th>Lokasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($weeklySchedules as $day)
                            @php
                                $schedule = $day['schedule'];
                                $isWorkday = (bool) ($schedule['is_workday'] ?? false);
                            @endphp
                            <tr class="{{ $day['is_today'] ? 'hari-ini' : '' }}">
                                <td class="hari-cell" data-label="Hari">
                                    {{ $day['date']->translatedFormat('l') }}
                                    @if ($day['is_today'])
                                        <span class="badge-today">Hari Ini</span>
                                    @endif
                                </td>
                                @if ($isWorkday)
                                    <td data-label="Shift">{{ $renderScheduleType($schedule) }}</td>
                                    <td class="jam-cell" data-label="Jam Kerja">{{ $schedule['shift_time'] ?? '-' }}</td>
                                    <td data-label="Lokasi">
                                        <div class="lokasi-name">{{ $employee->lokasiGps?->nama_lokasi ?? '-' }}</div>
                                        @if (!empty($employee->lokasiGps?->alamat))
                                            <div class="lokasi-alamat">{{ $employee->lokasiGps->alamat }}</div>
                                        @endif
                                        @if (!empty($employee->lokasiGps?->radius))
                                            <div class="lokasi-radius">Radius: {{ (int) $employee->lokasiGps->radius }} meter</div>
                                        @endif
                                    </td>
                                @else
                                    <td colspan="3" class="no-jadwal-cell">
                                        {{ $schedule['holiday_name'] ?? 'Tidak ada jadwal' }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="legend-card">
            <div class="legend-title">Keterangan:</div>
            <div class="legend-items">
                <div class="legend-item">
                    <span class="legend-badge" style="background:#D1FAE5;"></span>
                    <span>Hari Ini</span>
                </div>
                <div class="legend-item">
                    <span class="legend-badge" style="background:#FFFBEB;"></span>
                    <span>Lokasi & Radius</span>
                </div>
                <div class="legend-item">
                    <span class="legend-badge" style="background:#065F46;"></span>
                    <span>Zona Waktu Aktif</span>
                </div>
            </div>
        </div>
    </div>
@endsection
