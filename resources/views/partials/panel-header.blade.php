@php
    $headerTimezone = config('app.timezone', 'Asia/Jakarta');
    $headerTimezoneLabel = match ($headerTimezone) {
        'Asia/Makassar' => 'WITA',
        'Asia/Jayapura' => 'WIT',
        default => 'WIB',
    };
@endphp

<div class="top-bar">
    <div class="top-bar-left">
        <h2 class="page-title">{{ $pageTitle }}</h2>
    </div>
    <div class="top-bar-right">
        <div class="user-info">
            <div class="header-datetime" id="headerDateTime" data-timezone="{{ $headerTimezone }}" data-timezone-label="{{ $headerTimezoneLabel }}" aria-live="polite">
                <div class="header-time">
                    <i class="fas fa-clock"></i>
                    <span class="time-part" id="headerRunningHour">--</span>
                    <span class="time-separator">:</span>
                    <span class="time-part" id="headerRunningMinute">--</span>
                    <span class="time-separator">:</span>
                    <span class="time-part" id="headerRunningSecond">--</span>
                    <span class="time-zone" id="headerRunningTimezone">{{ $headerTimezoneLabel }}</span>
                </div>
                <div class="header-date"><i class="fas fa-calendar-days"></i><span id="headerRunningDate">---, -- --- ----</span></div>
            </div>
            <div class="user-avatar">{{ strtoupper(substr($currentUser->nama_lengkap ?? 'U', 0, 1)) }}</div>
            <div class="user-meta">
                <div class="user-name">{{ $currentUser->nama_lengkap ?? 'User' }}</div>
                <div class="user-role">{{ $currentUser->display_role ?? 'Administrator' }}</div>
            </div>
        </div>
    </div>
</div>
