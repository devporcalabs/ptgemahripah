@php
    $portalTimezone = config('app.timezone', 'Asia/Jakarta');
    $portalTimezoneLabel = match ($portalTimezone) {
        'Asia/Makassar' => 'WITA',
        'Asia/Jayapura' => 'WIT',
        default => 'WIB',
    };
    $portalNow = now($portalTimezone);
@endphp

<div class="portal-info-zone" data-portal-clock data-timezone="{{ $portalTimezone }}">
    <i class="fas fa-globe"></i>
    <span>Zona Waktu: <strong class="portal-zone-label">{{ $portalTimezoneLabel }}</strong></span>
    <span class="separator">|</span>
    <i class="fas fa-clock"></i>
    <span>Waktu Server: <strong class="portal-live-clock">{{ $portalNow->format('H:i:s') }}</strong></span>
    <span class="separator">|</span>
    <i class="fas fa-calendar-alt"></i>
    <span>Tanggal: <strong class="portal-live-date">{{ $portalNow->translatedFormat('d/m/Y') }}</strong></span>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const clockGroups = document.querySelectorAll('[data-portal-clock]');
                if (!clockGroups.length) {
                    return;
                }

                const createFormatters = (timezone) => ({
                    time: new Intl.DateTimeFormat('id-ID', {
                        timeZone: timezone,
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                    }),
                    date: new Intl.DateTimeFormat('id-ID', {
                        timeZone: timezone,
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                    }),
                });

                const formatterCache = new Map();

                const updateClocks = () => {
                    const now = new Date();

                    clockGroups.forEach((group) => {
                        const timezone = group.dataset.timezone || 'Asia/Jakarta';
                        if (!formatterCache.has(timezone)) {
                            formatterCache.set(timezone, createFormatters(timezone));
                        }

                        const formatters = formatterCache.get(timezone);
                        const timeNode = group.querySelector('.portal-live-clock');
                        const dateNode = group.querySelector('.portal-live-date');

                        if (timeNode) {
                            timeNode.textContent = formatters.time.format(now).replace(/\./g, ':');
                        }

                        if (dateNode) {
                            dateNode.textContent = formatters.date.format(now).replace(/\./g, '/');
                        }
                    });
                };

                updateClocks();
                window.setInterval(updateClocks, 1000);
            });
        </script>
    @endpush
@endonce
