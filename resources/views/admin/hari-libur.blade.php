@extends('layouts.panel', ['panel' => 'admin', 'pageTitle' => 'Kalender'])

@php
    $openTambahModal = $errors->any() && old('form_type') === 'create';
    $openEditModal = (bool) $editHoliday || ($errors->any() && old('form_type') === 'edit');
    $weekdayHeaders = ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU', 'MINGGU'];
    $sourceOptions = [
        'all' => 'Semua',
        'manual' => 'Libur Manual',
        'api' => 'Libur Nasional',
    ];
@endphp

@section('styles')
    .holiday-page {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .holiday-shell {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }
    .holiday-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding: 18px 20px 10px;
    }
    .holiday-title {
        margin: 0;
        font-size: 18px;
        line-height: 1.2;
        font-weight: 700;
        color: #0f172a;
    }
    .holiday-subtitle {
        margin-top: 4px;
        font-size: 12px;
        color: #64748b;
    }
    .holiday-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .holiday-tabs {
        display: inline-flex;
        gap: 4px;
        padding: 0 20px 12px;
    }
    .holiday-tab {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        background: #ffffff;
        color: #334155;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        transition: .2s ease;
    }
    .holiday-tab:hover {
        border-color: #7c3aed;
        color: #5b4dff;
    }
    .holiday-tab.active {
        background: #5b4dff;
        border-color: #5b4dff;
        color: #ffffff;
    }
    .holiday-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        padding: 0 20px 16px;
        border-bottom: 1px solid #eef2f7;
    }
    .month-nav {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #dbe2ea;
        border-radius: 12px;
        background: #ffffff;
        overflow: hidden;
    }
    .month-nav-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 36px;
        color: #334155;
        text-decoration: none;
        background: #ffffff;
        transition: .2s ease;
    }
    .month-nav-btn:hover {
        background: #f8fafc;
    }
    .month-label {
        min-width: 128px;
        text-align: center;
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        padding: 0 10px;
    }
    .holiday-filter {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .holiday-filter label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
    }
    .holiday-filter .filter-input {
        min-width: 160px;
    }
    .holiday-layout {
        display: grid;
        grid-template-columns: minmax(0, 2.1fr) minmax(280px, 0.95fr);
        gap: 16px;
        padding: 16px 20px 20px;
    }
    .calendar-panel {
        min-width: 0;
    }
    .weekday-row {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 8px;
        padding: 0 2px;
    }
    .weekday-item {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-align: center;
        letter-spacing: .04em;
    }
    .weekday-item.is-sunday {
        color: #dc2626;
    }
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
    }
    .calendar-spacer {
        min-height: 72px;
        border: 1px solid transparent;
        border-radius: 12px;
    }
    .calendar-day {
        min-height: 72px;
        border: 1px solid #dbe2ea;
        border-radius: 12px;
        background: #ffffff;
        padding: 8px 8px 10px;
        text-align: left;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        gap: 8px;
        transition: .18s ease;
    }
    .calendar-day:hover {
        border-color: #5b4dff;
        box-shadow: 0 6px 16px rgba(91, 77, 255, .08);
        transform: translateY(-1px);
    }
    .calendar-day.is-sunday {
        background: #fff7f7;
    }
    .calendar-day.is-api {
        background: #fff1f2;
        border-color: #fecdd3;
    }
    .calendar-day.is-manual {
        background: #fffbeb;
        border-color: #fde68a;
    }
    .calendar-day.is-today {
        border-color: #38bdf8;
        box-shadow: 0 0 0 1px rgba(56, 189, 248, .25);
    }
    .calendar-day.is-selected {
        border-color: #5b4dff;
        box-shadow: 0 0 0 1px rgba(91, 77, 255, .3), 0 6px 18px rgba(91, 77, 255, .12);
    }
    .day-number {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1;
    }
    .calendar-day.is-sunday .day-number,
    .calendar-day.is-api .day-number {
        color: #dc2626;
    }
    .day-label {
        font-size: 11px;
        color: #64748b;
        line-height: 1.35;
        min-height: 30px;
    }
    .day-label strong {
        color: #991b1b;
        font-weight: 700;
    }
    .day-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: auto;
    }
    .day-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 7px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        line-height: 1;
    }
    .day-badge.today {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .day-badge.sunday {
        background: #fee2e2;
        color: #b91c1c;
    }
    .day-badge.api {
        background: #fecdd3;
        color: #9f1239;
    }
    .day-badge.manual {
        background: #fef3c7;
        color: #92400e;
    }
    .holiday-legend {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        padding: 10px 2px 0;
        font-size: 11px;
        color: #475569;
    }
    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 3px;
    }
    .legend-dot.api {
        background: #fecdd3;
    }
    .legend-dot.manual {
        background: #fef08a;
    }
    .legend-dot.today {
        background: #38bdf8;
    }
    .detail-panel {
        border: 1px solid #dbe2ea;
        border-radius: 14px;
        background: #ffffff;
        padding: 16px;
        position: sticky;
        top: 16px;
        min-height: 100%;
    }
    .detail-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }
    .detail-title {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
    }
    .detail-subtitle {
        margin-top: 4px;
        font-size: 12px;
        color: #64748b;
    }
    .detail-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        line-height: 1;
        background: #e2e8f0;
        color: #334155;
        white-space: nowrap;
    }
    .detail-pill.api {
        background: #fecdd3;
        color: #9f1239;
    }
    .detail-pill.manual {
        background: #fef3c7;
        color: #92400e;
    }
    .detail-pill.today {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .detail-pill.sunday {
        background: #fee2e2;
        color: #b91c1c;
    }
    .detail-empty {
        padding: 26px 10px;
        border: 1px dashed #dbe2ea;
        border-radius: 12px;
        color: #64748b;
        text-align: center;
        font-size: 12px;
        line-height: 1.6;
    }
    .detail-body {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .detail-date {
        font-size: 22px;
        line-height: 1.1;
        font-weight: 800;
        color: #0f172a;
    }
    .detail-day {
        font-size: 12px;
        color: #64748b;
    }
    .detail-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .detail-note {
        border-top: 1px solid #eef2f7;
        padding-top: 12px;
        font-size: 12px;
        line-height: 1.7;
        color: #334155;
    }
    .detail-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 4px;
    }
    .detail-actions .btn,
    .detail-actions .btn-outline,
    .detail-actions .btn-danger {
        font-size: 12px;
        padding: 8px 12px;
    }
    .list-block {
        padding: 18px 20px 20px;
    }
    .list-toolbar {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        flex-wrap: wrap;
        padding: 16px 20px 18px;
        border-bottom: 1px solid #eef2f7;
        margin-bottom: 18px;
    }
    .list-toolbar .filter-group {
        min-width: 180px;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-badge.active { background:#D1FAE5; color:#065F46; }
    .status-badge.inactive { background:#FEE2E2; color:#B91C1C; }
    .holiday-name { font-weight: 600; color: #0f172a; }
    .holiday-date { font-weight: 600; white-space: nowrap; }
    .holiday-note { color: #64748b; font-size: 12px; line-height: 1.45; }
    .holiday-source-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }
    .holiday-source-badge.api { background:#fecdd3; color:#9f1239; }
    .holiday-source-badge.manual { background:#fef3c7; color:#92400e; }
    .modal-footer .btn + .btn {
        margin-left: 8px;
    }
    @media (max-width: 1200px) {
        .holiday-layout {
            grid-template-columns: 1fr;
        }
        .detail-panel {
            position: static;
        }
    }
    @media (max-width: 992px) {
        .holiday-header,
        .holiday-toolbar {
            flex-direction: column;
            align-items: stretch;
        }
        .holiday-actions,
        .holiday-filter {
            justify-content: flex-start;
        }
        .calendar-grid {
            gap: 6px;
        }
        .calendar-day {
            min-height: 68px;
            padding: 7px;
        }
    }
    @media (max-width: 768px) {
        .holiday-tabs {
            overflow-x: auto;
            padding-bottom: 14px;
        }
        .weekday-row,
        .calendar-grid {
            min-width: 700px;
        }
        .holiday-layout {
            overflow-x: auto;
        }
        .holiday-filter .filter-input {
            min-width: 140px;
        }
    }
@endsection

@section('content')
    <div id="ajaxCrudFragment">
        <div class="holiday-page">
            <div class="holiday-shell">
                <div class="holiday-header">
                    <div>
                        <h3 class="holiday-title">Kalender</h3>
                        <div class="holiday-subtitle">Kelola daftar hari libur untuk kebutuhan absensi karyawan.</div>
                    </div>
                    <div class="holiday-actions">
                        <form
                            method="POST"
                            action="{{ route('admin.kalender.sync') }}"
                            data-ajax="true"
                            data-refresh-target="#ajaxCrudFragment"
                            data-confirm="Sinkron data hari libur nasional tahun {{ $calendarMonth->format('Y') }}?"
                            data-confirm-title="Sinkron Hari Libur Nasional"
                            data-confirm-button="Ya, sinkron"
                            data-confirm-variant="success">
                            @csrf
                            <input type="hidden" name="year" value="{{ $calendarMonth->format('Y') }}">
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-cloud-arrow-down"></i> Sinkron Libur
                            </button>
                        </form>
                        <button type="button" class="btn btn-primary" onclick="openTambahModal()">
                            <i class="fas fa-plus"></i> Tambah Hari Libur
                        </button>
                    </div>
                </div>

                <div class="holiday-tabs">
                    <a href="{{ $calendarListUrl }}" class="holiday-tab {{ $viewMode === 'list' ? 'active' : '' }}" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment">
                        <i class="fas fa-list"></i> Daftar
                    </a>
                    <a href="{{ $calendarViewUrl }}" class="holiday-tab {{ $viewMode === 'calendar' ? 'active' : '' }}" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment">
                        <i class="fas fa-calendar-days"></i> Kalender
                    </a>
                </div>

                @if ($viewMode === 'calendar')
                    <div class="holiday-toolbar">
                        <div class="month-nav" aria-label="Navigasi bulan kalender">
                            <a href="{{ $calendarPrevUrl }}" class="month-nav-btn" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment" data-loading-mode="icon" data-loading-silent="true" title="Bulan sebelumnya">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <div class="month-label">{{ $calendarMonth->translatedFormat('F Y') }}</div>
                            <a href="{{ $calendarNextUrl }}" class="month-nav-btn" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment" data-loading-mode="icon" data-loading-silent="true" title="Bulan berikutnya">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>

                        <form method="GET" class="holiday-filter" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxCrudFragment">
                            <input type="hidden" name="view" value="calendar">
                            <input type="hidden" name="bulan" value="{{ $month }}">
                            <label for="sourceFilter">Sumber</label>
                            <select id="sourceFilter" name="source" class="filter-input">
                                @foreach ($sourceOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(($sourceFilter ?? 'all') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <div class="holiday-layout">
                        <div class="calendar-panel">
                            <div class="weekday-row">
                                @foreach ($weekdayHeaders as $index => $weekdayHeader)
                                    <div class="weekday-item {{ $index === 6 ? 'is-sunday' : '' }}">{{ $weekdayHeader }}</div>
                                @endforeach
                            </div>

                            <div class="calendar-grid">
                                @foreach ($calendarWeeks as $week)
                                    @foreach ($week as $day)
                                        @if ($day === null)
                                            <div class="calendar-spacer"></div>
                                        @else
                                            @php
                                                $holiday = $day['holiday'] ?? null;
                                                $dateLabel = \Carbon\Carbon::parse($day['date'])->translatedFormat('d F Y');
                                                $isToday = $day['date'] === now()->toDateString();
                                                $source = $holiday?->source ?? 'none';
                                                $sourceLabel = $source === \App\Models\HariLibur::SOURCE_API_CO_ID ? 'Libur Nasional' : 'Manual';
                                                $dayLabel = $holiday
                                                    ? $holiday->nama_libur
                                                    : ($day['is_sunday'] ? 'Hari Minggu' : 'Hari Kerja');
                                                $dayNote = $holiday
                                                    ? ($holiday->keterangan ?: ($source === \App\Models\HariLibur::SOURCE_API_CO_ID ? 'Libur nasional hasil sinkron.' : 'Libur yang dibuat manual.'))
                                                    : ($day['is_sunday']
                                                        ? 'Libur mingguan default setiap hari Minggu.'
                                                        : 'Tanggal ini hari kerja dan belum memiliki libur tersimpan.');
                                            @endphp
                                            <button
                                                type="button"
                                                class="calendar-day {{ $day['is_sunday'] ? 'is-sunday' : '' }} {{ $day['is_global_holiday'] ? ($source === \App\Models\HariLibur::SOURCE_API_CO_ID ? 'is-api' : 'is-manual') : '' }} {{ $isToday ? 'is-today' : '' }}"
                                                data-calendar-day-card
                                                data-date="{{ $day['date'] }}"
                                                data-date-label="{{ $dateLabel }}"
                                                data-day-number="{{ $day['day_number'] }}"
                                                data-day-label="{{ $dayLabel }}"
                                                data-day-note="{{ $dayNote }}"
                                                data-is-today="{{ $isToday ? '1' : '0' }}"
                                                data-is-sunday="{{ $day['is_sunday'] ? '1' : '0' }}"
                                                data-is-holiday="{{ $holiday ? '1' : '0' }}"
                                                data-holiday-id="{{ $holiday->id ?? '' }}"
                                                data-holiday-name="{{ $holiday->nama_libur ?? '' }}"
                                                data-holiday-note="{{ $holiday->keterangan ?? '' }}"
                                                data-holiday-source="{{ $source }}"
                                                data-holiday-source-label="{{ $sourceLabel }}"
                                                data-holiday-active="{{ $holiday?->aktif ? '1' : '0' }}"
                                                data-update-url="{{ $holiday ? route('admin.kalender.update', $holiday) : '' }}"
                                                data-delete-url="{{ $holiday ? route('admin.kalender.destroy', $holiday) : '' }}"
                                                data-add-date="{{ $day['date'] }}"
                                                onclick="selectHolidayDay(this)">
                                                <div class="day-number">{{ $day['day_number'] }}</div>
                                                <div class="day-label">
                                                    {{ $dayLabel }}
                                                </div>
                                                <div class="day-badges">
                                                    @if ($isToday)
                                                        <span class="day-badge today">Hari Ini</span>
                                                    @endif
                                                    @if ($day['is_sunday'])
                                                        <span class="day-badge sunday">Minggu</span>
                                                    @endif
                                                    @if ($source === \App\Models\HariLibur::SOURCE_API_CO_ID)
                                                        <span class="day-badge api">Nasional</span>
                                                    @elseif ($source === \App\Models\HariLibur::SOURCE_MANUAL)
                                                        <span class="day-badge manual">Manual</span>
                                                    @endif
                                                </div>
                                            </button>
                                        @endif
                                    @endforeach
                                @endforeach
                            </div>

                            <div class="holiday-legend">
                                <div class="legend-item"><span class="legend-dot manual"></span>Libur Manual</div>
                                <div class="legend-item"><span class="legend-dot api"></span>Libur Nasional</div>
                                <div class="legend-item"><span class="legend-dot today"></span>Hari Ini</div>
                            </div>
                        </div>

                        <aside class="detail-panel" id="holidayDetailPanel">
                            <div class="detail-header">
                                <div>
                                    <h4 class="detail-title">Detail Tanggal</h4>
                                    <div class="detail-subtitle" id="holidayDetailSubtitle">Pilih tanggal pada kalender.</div>
                                </div>
                                <span class="detail-pill" id="holidayDetailPill" style="display:none;"></span>
                            </div>

                            <div class="detail-empty" id="holidayDetailEmpty">
                                Pilih tanggal di kalender untuk melihat detail libur, status, dan aksi cepat.
                            </div>

                            <div class="detail-body" id="holidayDetailBody" style="display:none;">
                                <div>
                                    <div class="detail-date" id="holidayDetailDate"></div>
                                    <div class="detail-day" id="holidayDetailDay"></div>
                                </div>

                                <div class="detail-meta" id="holidayDetailMeta"></div>
                                <div class="detail-note" id="holidayDetailNote"></div>
                                <div class="detail-actions" id="holidayDetailActions"></div>
                            </div>
                        </aside>
                    </div>
                @else
                    <div class="list-toolbar">
                        <form method="GET" class="filter-form" data-ajax="true" data-auto-submit="true" data-refresh-target="#ajaxCrudFragment">
                            <input type="hidden" name="view" value="list">
                            <input type="hidden" name="bulan" value="{{ $month }}">
                            <div class="filter-group">
                                <label>Cari Data</label>
                                <input type="search" name="q" class="filter-input" value="{{ $search ?? '' }}" placeholder="Cari nama libur atau keterangan...">
                            </div>
                            <div class="filter-group">
                                <label>Sumber</label>
                                <select name="source" class="filter-input">
                                    @foreach ($sourceOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($sourceFilter ?? 'all') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
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
                                <a href="{{ $calendarListUrl }}" class="btn-reset" data-ajax-link="true" data-refresh-target="#ajaxCrudFragment">Reset</a>
                            </div>
                        </form>
                    </div>

                    <div class="list-block">
                        <div class="table-responsive">
                            <table class="table-bordered">
                                <thead>
                                    <tr class="table-header">
                                        <th width="50">No</th>
                                        <th width="150">Tanggal</th>
                                        <th width="260">Nama Libur</th>
                                        <th>Keterangan</th>
                                        <th width="120">Sumber</th>
                                        <th width="120">Status</th>
                                        <th width="180">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($holidayList as $index => $holiday)
                                        <tr>
                                            <td class="text-center">{{ ($holidayList->firstItem() ?? 1) + $index }}</td>
                                            <td class="holiday-date">{{ $holiday->tanggal?->translatedFormat('d F Y') }}</td>
                                            <td><span class="holiday-name">{{ $holiday->nama_libur }}</span></td>
                                            <td>
                                                @if ($holiday->keterangan)
                                                    <div class="holiday-note">{{ $holiday->keterangan }}</div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="holiday-source-badge {{ $holiday->source === \App\Models\HariLibur::SOURCE_API_CO_ID ? 'api' : 'manual' }}">
                                                    {{ $holiday->source === \App\Models\HariLibur::SOURCE_API_CO_ID ? 'Libur Nasional' : 'Manual' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="status-badge {{ $holiday->aktif ? 'active' : 'inactive' }}">
                                                    {{ $holiday->aktif ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button
                                                        type="button"
                                                        class="btn btn-warning"
                                                        data-id="{{ $holiday->id }}"
                                                        data-update-url="{{ route('admin.kalender.update', $holiday) }}"
                                                        data-tanggal="{{ $holiday->tanggal?->format('Y-m-d') }}"
                                                        data-nama-libur="{{ $holiday->nama_libur }}"
                                                        data-keterangan="{{ $holiday->keterangan }}"
                                                        data-aktif="{{ $holiday->aktif ? '1' : '0' }}"
                                                        onclick="openEditModal(this)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.kalender.destroy', $holiday) }}"
                                                        data-ajax="true"
                                                        data-refresh-target="#ajaxCrudFragment"
                                                        data-confirm="Hapus hari libur ini?"
                                                        data-confirm-title="Hapus Hari Libur"
                                                        data-confirm-button="Ya, hapus"
                                                        data-confirm-variant="danger">
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
                                            <td colspan="7" class="empty-state">Belum ada data hari libur.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{ $holidayList->links('partials.pagination-ajax', ['target' => '#ajaxCrudFragment']) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="tambahModal" class="modal{{ $openTambahModal ? ' show' : '' }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Tambah Hari Libur</h3>
                    <button type="button" class="modal-close" onclick="closeTambahModal()">&times;</button>
                </div>
                <form method="POST" id="tambahForm" action="{{ route('admin.kalender.store') }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#tambahModal" data-reset-on-success="true">
                    @csrf
                    <input type="hidden" name="form_type" value="create">

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tanggal <span class="required">*</span></label>
                            <input type="date" name="tanggal" id="create_tanggal" class="form-control" value="{{ $openTambahModal ? old('tanggal') : '' }}" required>
                        </div>
                        <div class="form-group">
                            <label>Nama Hari Libur <span class="required">*</span></label>
                            <input type="text" name="nama_libur" class="form-control" value="{{ $openTambahModal ? old('nama_libur') : '' }}" maxlength="150" required>
                        </div>
                        <div class="form-group">
                            <label>Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3" placeholder="Opsional">{{ $openTambahModal ? old('keterangan') : '' }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Status <span class="required">*</span></label>
                            <select name="aktif" class="form-control" required>
                                <option value="1" @selected(($openTambahModal ? old('aktif', '1') : '1') === '1')>Aktif</option>
                                <option value="0" @selected(($openTambahModal ? old('aktif') : '') === '0')>Nonaktif</option>
                            </select>
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
                    <h3>Edit Hari Libur</h3>
                    <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
                </div>
                <form method="POST" id="editForm" action="{{ $editHoliday ? route('admin.kalender.update', $editHoliday) : '#' }}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-close-modal="#editModal">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="form_type" value="edit">
                    <input type="hidden" name="edit_id" id="edit_id" value="{{ old('edit_id', $editHoliday->id ?? '') }}">

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tanggal <span class="required">*</span></label>
                            <input type="date" name="tanggal" id="edit_tanggal" class="form-control" value="{{ old('tanggal', $editHoliday?->tanggal?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="form-group">
                            <label>Nama Hari Libur <span class="required">*</span></label>
                            <input type="text" name="nama_libur" id="edit_nama_libur" class="form-control" value="{{ old('nama_libur', $editHoliday->nama_libur ?? '') }}" maxlength="150" required>
                        </div>
                        <div class="form-group">
                            <label>Keterangan</label>
                            <textarea name="keterangan" id="edit_keterangan" class="form-control" rows="3" placeholder="Opsional">{{ old('keterangan', $editHoliday->keterangan ?? '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Status <span class="required">*</span></label>
                            <select name="aktif" id="edit_aktif" class="form-control" required>
                                <option value="1" @selected(old('aktif', $editHoliday ? ($editHoliday->aktif ? '1' : '0') : '1') === '1')>Aktif</option>
                                <option value="0" @selected(old('aktif', $editHoliday ? ($editHoliday->aktif ? '1' : '0') : '1') === '0')>Nonaktif</option>
                            </select>
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
        const tambahHolidayModal = document.getElementById('tambahModal');
        const editHolidayModal = document.getElementById('editModal');
        const tambahHolidayForm = document.getElementById('tambahForm');
        const editHolidayForm = document.getElementById('editForm');
        const holidayDetailSubtitle = document.getElementById('holidayDetailSubtitle');
        const holidayDetailPill = document.getElementById('holidayDetailPill');
        const holidayDetailEmpty = document.getElementById('holidayDetailEmpty');
        const holidayDetailBody = document.getElementById('holidayDetailBody');
        const holidayDetailDate = document.getElementById('holidayDetailDate');
        const holidayDetailDay = document.getElementById('holidayDetailDay');
        const holidayDetailMeta = document.getElementById('holidayDetailMeta');
        const holidayDetailNote = document.getElementById('holidayDetailNote');
        const holidayDetailActions = document.getElementById('holidayDetailActions');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        let selectedCalendarCard = null;
        let selectedCalendarRecord = null;

        function resetHolidayForm(form) {
            if (!form) {
                return;
            }

            form.reset();
            window.panelAjax?.clearErrors?.(form);
        }

        function openTambahModal(dateValue = '') {
            if (dateValue) {
                const createDateInput = document.getElementById('create_tanggal');
                if (createDateInput) {
                    createDateInput.value = dateValue;
                }
            }

            tambahHolidayModal.classList.add('show');
        }

        function closeTambahModal() {
            resetHolidayForm(tambahHolidayForm);
            tambahHolidayModal.classList.remove('show');
        }

        function openEditModal(button) {
            const data = button?.dataset || {};
            document.getElementById('edit_id').value = data.id || '';
            document.getElementById('edit_tanggal').value = data.tanggal || '';
            document.getElementById('edit_nama_libur').value = data.namaLibur || '';
            document.getElementById('edit_keterangan').value = data.keterangan || '';
            document.getElementById('edit_aktif').value = data.aktif || '1';
            editHolidayForm.action = data.updateUrl || '#';
            editHolidayModal.classList.add('show');
        }

        function closeEditModal() {
            resetHolidayForm(editHolidayForm);
            editHolidayModal.classList.remove('show');
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function buildDetailActionButtons(record) {
            if (record.isHoliday) {
                return `
                    <button type="button" class="btn btn-warning" onclick="openEditModalFromSelection()">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="POST" action="${escapeHtml(record.deleteUrl)}" data-ajax="true" data-refresh-target="#ajaxCrudFragment" data-confirm="Hapus hari libur ini?" data-confirm-title="Hapus Hari Libur" data-confirm-button="Ya, hapus" data-confirm-variant="danger">
                        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </form>
                `;
            }

            return `
                <button type="button" class="btn btn-primary" onclick="openTambahModal('${escapeHtml(record.addDate)}')">
                    <i class="fas fa-plus"></i> Tambah Hari Libur
                </button>
            `;
        }

        function renderHolidayDetail(record) {
            if (!record) {
                holidayDetailSubtitle.textContent = 'Pilih tanggal pada kalender.';
                holidayDetailPill.style.display = 'none';
                holidayDetailEmpty.style.display = 'block';
                holidayDetailBody.style.display = 'none';
                holidayDetailActions.innerHTML = '';
                return;
            }

            holidayDetailSubtitle.textContent = record.isHoliday
                ? 'Tanggal ini memiliki data libur tersimpan.'
                : (record.isSunday
                    ? 'Tanggal ini termasuk libur mingguan.'
                    : 'Tanggal ini belum memiliki libur tersimpan.');
            holidayDetailPill.style.display = 'inline-flex';
            holidayDetailPill.textContent = record.isToday ? 'Hari Ini' : (record.isSunday ? 'Minggu' : 'Tanggal');
            holidayDetailPill.className = 'detail-pill ' + (record.isHoliday
                ? (record.holidaySource === 'api_co_id' ? 'api' : 'manual')
                : (record.isSunday ? 'sunday' : (record.isToday ? 'today' : '')));

            holidayDetailEmpty.style.display = 'none';
            holidayDetailBody.style.display = 'flex';
            holidayDetailDate.textContent = record.dateLabel || '-';
            holidayDetailDay.textContent = record.isHoliday
                ? record.holidayName
                : (record.isSunday ? 'Hari Minggu' : 'Hari kerja');

            const metaItems = [];
            if (record.isToday) {
                metaItems.push('<span class="detail-pill today">Hari Ini</span>');
            }
            if (record.isSunday) {
                metaItems.push('<span class="detail-pill manual">Minggu</span>');
            }
            if (record.isHoliday) {
                metaItems.push('<span class="detail-pill ' + (record.holidaySource === 'api_co_id' ? 'api' : 'manual') + '">' + escapeHtml(record.holidaySourceLabel) + '</span>');
                metaItems.push('<span class="detail-pill ' + (record.holidayActive === '1' ? 'today' : 'manual') + '">' + (record.holidayActive === '1' ? 'Aktif' : 'Nonaktif') + '</span>');
            } else if (record.isSunday) {
                metaItems.push('<span class="detail-pill sunday">Libur Mingguan</span>');
            } else {
                metaItems.push('<span class="detail-pill">Belum diatur</span>');
            }
            holidayDetailMeta.innerHTML = metaItems.join('');

            holidayDetailNote.textContent = record.dayNote || '';
            holidayDetailActions.innerHTML = buildDetailActionButtons(record);
        }

        function selectHolidayDay(button) {
            const data = button?.dataset || {};
            const record = {
                date: data.date || '',
                dateLabel: data.dateLabel || '',
                dayNumber: data.dayNumber || '',
                dayLabel: data.dayLabel || '',
                dayNote: data.dayNote || '',
                isToday: data.isToday === '1',
                isSunday: data.isSunday === '1',
                isHoliday: data.isHoliday === '1',
                holidayId: data.holidayId || '',
                holidayName: data.holidayName || '',
                holidayNote: data.holidayNote || '',
                holidaySource: data.holidaySource || '',
                holidaySourceLabel: data.holidaySourceLabel || 'Manual',
                holidayActive: data.holidayActive || '0',
                updateUrl: data.updateUrl || '',
                deleteUrl: data.deleteUrl || '',
                addDate: data.addDate || data.date || '',
            };

            if (selectedCalendarCard) {
                selectedCalendarCard.classList.remove('is-selected');
            }

            selectedCalendarCard = button;
            selectedCalendarCard.classList.add('is-selected');
            selectedCalendarRecord = record;

            renderHolidayDetail(record);
        }

        function openEditModalFromSelection() {
            if (!selectedCalendarRecord || !selectedCalendarRecord.isHoliday) {
                return;
            }

            document.getElementById('edit_id').value = selectedCalendarRecord.holidayId || '';
            document.getElementById('edit_tanggal').value = selectedCalendarRecord.date || '';
            document.getElementById('edit_nama_libur').value = selectedCalendarRecord.holidayName || '';
            document.getElementById('edit_keterangan').value = selectedCalendarRecord.holidayNote || '';
            document.getElementById('edit_aktif').value = selectedCalendarRecord.holidayActive === '1' ? '1' : '0';
            editHolidayForm.action = selectedCalendarRecord.updateUrl || '#';
            editHolidayModal.classList.add('show');
        }

        document.querySelectorAll('[data-calendar-day-card]').forEach(function (card) {
            card.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectHolidayDay(card);
                }
            });
        });

        window.addEventListener('click', function (event) {
            if (event.target === tambahHolidayModal) {
                closeTambahModal();
            }

            if (event.target === editHolidayModal) {
                closeEditModal();
            }
        });

        renderHolidayDetail(null);
    </script>
@endsection
