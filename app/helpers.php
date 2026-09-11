<?php

if (! function_exists('format_duration_minutes_label')) {
    function format_duration_minutes_label(int|float|null $minutes, string $zeroLabel = '0 menit'): string
    {
        $minutes = max(0, (int) round((float) ($minutes ?? 0)));

        if ($minutes === 0) {
            return $zeroLabel;
        }

        if ($minutes < 60) {
            return number_format($minutes, 0, ',', '.').' menit';
        }

        $hours = $minutes / 60;
        $formattedHours = rtrim(rtrim(number_format($hours, 2, ',', '.'), '0'), ',');

        return $formattedHours.' jam';
    }
}
