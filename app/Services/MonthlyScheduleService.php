<?php

namespace App\Services;

use App\Models\HariLibur;
use App\Models\Karyawan;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MonthlyScheduleService
{
    private const DAY_NAMES = [
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu',
    ];

    public function resolveForDate(Karyawan $employee, Carbon $date): array
    {
        return $this->resolveDailySchedule(
            $employee,
            $date,
            $this->loadShiftMap($employee),
            $this->activeGlobalHolidayMapForMonth($date)
        );
    }

    public function calendarForEmployee(Karyawan $employee, Carbon $period): Collection
    {
        $monthStart = $period->copy()->startOfMonth();
        $shiftMap = $this->loadShiftMap($employee);
        $globalHolidayMap = $this->activeGlobalHolidayMapForMonth($monthStart);

        return collect(range(1, $monthStart->daysInMonth))
            ->mapWithKeys(function (int $day) use ($monthStart, $employee, $shiftMap, $globalHolidayMap): array {
                $date = $monthStart->copy()->day($day);
                $schedule = $this->resolveDailySchedule($employee, $date, $shiftMap, $globalHolidayMap);

                return [
                    $date->toDateString() => [
                        'date' => $date->toDateString(),
                        'day_name' => $schedule['day_name'],
                        'shift_id' => $schedule['shift_id'],
                        'shift_name' => $schedule['shift_name'],
                        'shift_time' => $schedule['shift_time'],
                        'source' => $schedule['source'],
                        'jenis_jam_kerja' => $schedule['jenis_jam_kerja'],
                        'assignment_id' => null,
                        'is_locked' => false,
                        'is_workday' => $schedule['is_workday'],
                        'holiday_name' => $schedule['holiday_name'],
                        'holiday_scope' => $schedule['holiday_scope'],
                    ],
                ];
            });
    }

    private function resolveDailySchedule(
        Karyawan $employee,
        Carbon $date,
        Collection $shiftMap,
        ?Collection $globalHolidayMap = null,
    ): array
    {
        $dayName = self::DAY_NAMES[$date->englishDayOfWeek] ?? $date->translatedFormat('l');
        $globalHoliday = $this->resolveActiveGlobalHolidayForDate($date, $globalHolidayMap);
        $workType = $this->normalizeWorkType($employee->jenis_jam_kerja);

        if ($globalHoliday) {
            return $this->buildSchedulePayload(
                employee: $employee,
                date: $date,
                dayName: $dayName,
                workType: 'libur',
                shift: null,
                source: 'libur_global',
                isWorkday: false,
                holidayName: $globalHoliday->nama_libur,
                holidayScope: 'global',
            );
        }

        if ($dayName === 'Minggu') {
            return $this->buildSchedulePayload(
                employee: $employee,
                date: $date,
                dayName: $dayName,
                workType: 'libur',
                shift: null,
                source: 'libur_mingguan',
                isWorkday: false,
                holidayName: 'Hari Minggu',
                holidayScope: 'weekly',
            );
        }

        return match ($workType) {
            'fleksibel' => $this->buildFlexibleSchedule($employee, $date, $dayName),
            'rolling' => $this->buildRollingSchedule($employee, $date, $dayName, $shiftMap),
            default => $this->buildFixedSchedule($employee, $date, $dayName, $shiftMap),
        };
    }

    private function buildFixedSchedule(Karyawan $employee, Carbon $date, string $dayName, Collection $shiftMap): array
    {
        $shift = $this->resolveFixedShift($employee, $shiftMap);

        return $this->buildSchedulePayload(
            employee: $employee,
            date: $date,
            dayName: $dayName,
            workType: 'tetap',
            shift: $shift,
            source: $shift ? 'tetap' : 'none',
            isWorkday: true,
        );
    }

    private function buildRollingSchedule(Karyawan $employee, Carbon $date, string $dayName, Collection $shiftMap): array
    {
        $shift = $this->resolveRollingShift($employee, $date, $shiftMap);

        return $this->buildSchedulePayload(
            employee: $employee,
            date: $date,
            dayName: $dayName,
            workType: 'rolling',
            shift: $shift,
            source: $shift ? 'rolling' : 'none',
            isWorkday: true,
        );
    }

    private function buildFlexibleSchedule(Karyawan $employee, Carbon $date, string $dayName): array
    {
        $shift = null;
        $durationHours = max(0.0, (float) ($employee->durasi_kerja_fleksibel ?? 8));

        return $this->buildSchedulePayload(
            employee: $employee,
            date: $date,
            dayName: $dayName,
            workType: 'fleksibel',
            shift: $shift,
            source: 'fleksibel',
            isWorkday: true,
            toleranceMinutes: 0,
            flexibleDurationHours: $durationHours,
        );
    }

    private function buildSchedulePayload(
        Karyawan $employee,
        Carbon $date,
        string $dayName,
        string $workType,
        ?Shift $shift,
        string $source,
        bool $isWorkday,
        ?Carbon $expectedStart = null,
        ?Carbon $expectedCheckout = null,
        int $toleranceMinutes = 0,
        float $flexibleDurationHours = 0.0,
        ?string $holidayName = null,
        ?string $holidayScope = null,
    ): array {
        if ($shift && ! $expectedStart) {
            $expectedStart = $this->combineDateAndTime($date, $shift->jam_masuk);
        }

        if ($shift && ! $expectedCheckout) {
            $expectedCheckout = $this->combineDateAndTime($date, $shift->jam_keluar);
            if ($expectedCheckout && $expectedStart && $expectedCheckout->lte($expectedStart)) {
                $expectedCheckout->addDay();
            }
        }

        if ($shift) {
            $toleranceMinutes = max(0, (int) ($shift->toleransi ?? 0));
        }

        if ($expectedCheckout && $expectedStart && $expectedCheckout->lte($expectedStart)) {
            $expectedCheckout->addDay();
        }

        return [
            'date' => $date->toDateString(),
            'day_name' => $dayName,
            'shift_id' => $shift?->id,
            'shift_name' => $shift?->nama_shift,
            'shift_time' => $shift
                ? trim((string) $shift->jam_masuk).' - '.trim((string) $shift->jam_keluar)
                : ($workType === 'fleksibel'
                    ? 'Fleksibel / '.rtrim(rtrim(number_format($flexibleDurationHours, 2, '.', ''), '0'), '.').' jam'
                    : null),
            'source' => $source,
            'jenis_jam_kerja' => $workType,
            'checkin_window_before' => $shift ? max(0, (int) ($shift->checkin_window_before ?? 30)) : 0,
            'assignment_id' => null,
            'is_locked' => false,
            'is_workday' => $isWorkday,
            'expected_start' => $expectedStart,
            'expected_checkout' => $expectedCheckout,
            'tolerance_minutes' => $toleranceMinutes,
            'durasi_kerja_fleksibel' => $workType === 'fleksibel' ? $flexibleDurationHours : null,
            'lokasi_gps_id' => $employee->lokasi_gps_id,
            'holiday_name' => $holidayName,
            'holiday_scope' => $holidayScope,
        ];
    }

    private function resolveFixedShift(Karyawan $employee, Collection $shiftMap): ?Shift
    {
        if ($employee->shift_id && $shiftMap->has($employee->shift_id)) {
            return $shiftMap->get($employee->shift_id);
        }

        $rotationIds = $this->rotationIds($employee);

        if ($rotationIds !== []) {
            foreach ($rotationIds as $shiftId) {
                if ($shiftMap->has($shiftId)) {
                    return $shiftMap->get($shiftId);
                }
            }
        }

        return null;
    }

    private function resolveRollingShift(Karyawan $employee, Carbon $date, Collection $shiftMap): ?Shift
    {
        $rotationIds = $this->rotationIds($employee);

        if ($rotationIds === []) {
            return $this->resolveFixedShift($employee, $shiftMap);
        }

        $rotationStart = $this->resolveRotationStart($employee);
        $firstShiftId = $rotationIds[0] ?? null;
        $rotationMode = $this->normalizeRotationMode($employee->shift_rotation_mode);

        if ($date->lt($rotationStart)) {
            return $firstShiftId && $shiftMap->has($firstShiftId)
                ? $shiftMap->get($firstShiftId)
                : $this->resolveFixedShift($employee, $shiftMap);
        }

        $index = $this->resolveRotationIndex($rotationStart, $date, $rotationMode) % count($rotationIds);
        $rotationShiftId = $rotationIds[$index] ?? null;

        if ($rotationShiftId && $shiftMap->has($rotationShiftId)) {
            return $shiftMap->get($rotationShiftId);
        }

        return $this->resolveFixedShift($employee, $shiftMap);
    }

    private function resolveRotationIndex(Carbon $start, Carbon $date, string $rotationMode): int
    {
        $start = $start->copy()->startOfDay();
        $date = $date->copy()->startOfDay();
        $dayDiff = $start->diffInDays($date);

        return match ($rotationMode) {
            'weekly' => intdiv($dayDiff, 7),
            'biweekly' => intdiv($dayDiff, 14),
            'monthly' => $this->diffInMonths($start, $date),
            default => $dayDiff,
        };
    }

    private function rotationIds(Karyawan $employee): array
    {
        return collect($employee->shift_rotation_ids ?? [])
            ->flatten()
            ->map(fn ($shiftId) => (int) $shiftId)
            ->filter(fn (int $shiftId) => $shiftId > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function activeGlobalHolidayMapForMonth(Carbon $date): Collection
    {
        $monthStart = $date->copy()->startOfMonth()->toDateString();
        $monthEnd = $date->copy()->endOfMonth()->toDateString();

        return HariLibur::query()
            ->where('aktif', true)
            ->whereBetween('tanggal', [$monthStart, $monthEnd])
            ->get(['id', 'tanggal', 'nama_libur'])
            ->keyBy(fn (HariLibur $holiday) => $holiday->tanggal->toDateString());
    }

    private function resolveActiveGlobalHolidayForDate(Carbon $date, ?Collection $globalHolidayMap = null): ?HariLibur
    {
        $globalHolidayMap ??= $this->activeGlobalHolidayMapForMonth($date);

        $holiday = $globalHolidayMap->get($date->toDateString());

        return $holiday instanceof HariLibur ? $holiday : null;
    }

    private function normalizeWorkType(?string $value): string
    {
        return match (trim(strtolower((string) $value))) {
            'rolling' => 'rolling',
            'fleksibel' => 'fleksibel',
            'shift', 'tetap', '' => 'tetap',
            default => 'tetap',
        };
    }

    private function normalizeRotationMode(?string $value): string
    {
        return match (trim(strtolower((string) $value))) {
            'weekly' => 'weekly',
            'biweekly' => 'biweekly',
            'monthly' => 'monthly',
            default => 'daily',
        };
    }

    private function loadShiftMap(Karyawan $employee): Collection
    {
        $shiftIds = collect([$employee->shift_id])
            ->merge($this->rotationIds($employee))
            ->filter(fn ($shiftId) => (int) $shiftId > 0)
            ->unique()
            ->values()
            ->all();

        if ($shiftIds === []) {
            return collect();
        }

        return Shift::query()
            ->whereIn('id', $shiftIds)
            ->get(['id', 'nama_shift', 'jam_masuk', 'jam_keluar', 'toleransi', 'checkin_window_before'])
            ->keyBy('id');
    }

    private function resolveRotationStart(Karyawan $employee): Carbon
    {
        if ($employee->shift_rotation_start) {
            return $employee->shift_rotation_start instanceof Carbon
                ? $employee->shift_rotation_start->copy()->startOfDay()
                : Carbon::parse($employee->shift_rotation_start)->startOfDay();
        }

        if ($employee->tgl_join) {
            return $employee->tgl_join instanceof Carbon
                ? $employee->tgl_join->copy()->startOfDay()
                : Carbon::parse($employee->tgl_join)->startOfDay();
        }

        return now()->startOfDay();
    }

    private function diffInMonths(Carbon $start, Carbon $end): int
    {
        $interval = $start->diff($end);

        return ($interval->y * 12) + $interval->m;
    }

    private function combineDateAndTime(Carbon $date, ?string $time): ?Carbon
    {
        $time = trim((string) $time);
        if ($time === '') {
            return null;
        }

        return Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $date->toDateString().' '.(strlen($time) === 5 ? $time.':00' : $time),
            config('app.timezone'),
        );
    }
}
