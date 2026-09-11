<?php

namespace App\Services;

use App\Models\Izin;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeaveService
{
    private ?Setting $settings = null;

    public function __construct(
        private readonly MonthlyScheduleService $monthlyScheduleService,
    ) {
    }

    public function workdayDatesForRange(Karyawan $employee, Carbon $start, Carbon $end): Collection
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();

        if ($end->lt($start)) {
            return collect();
        }

        $dates = collect();
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $calendar = $this->monthlyScheduleService->calendarForEmployee($employee, $cursor);
            $segmentStart = $cursor->greaterThan($start) ? $cursor->copy() : $start->copy();
            $segmentEnd = $cursor->copy()->endOfMonth();

            if ($segmentEnd->gt($end)) {
                $segmentEnd = $end->copy();
            }

            $segmentStartString = $segmentStart->toDateString();
            $segmentEndString = $segmentEnd->toDateString();

            $dates = $dates->merge(
                $calendar
                    ->filter(fn (array $day, string $date): bool => ($day['is_workday'] ?? false) && $date >= $segmentStartString && $date <= $segmentEndString)
                    ->keys()
                    ->values()
            );

            $cursor->addMonthNoOverflow()->startOfMonth();
        }

        return $dates->unique()->values();
    }

    public function expandLeavesAgainstCalendar(Collection $leaves, Collection $scheduleCalendar, Carbon $period): Collection
    {
        $periodStart = $period->copy()->startOfMonth();
        $periodEnd = $period->copy()->endOfMonth();
        $entries = collect();

        foreach ($leaves as $leave) {
            if (! $leave instanceof Izin) {
                continue;
            }

            $start = $leave->tanggal_mulai_efektif;
            $end = $leave->tanggal_selesai_efektif;

            if (! $start || ! $end) {
                continue;
            }

            if ($start->lt($periodStart)) {
                $start = $periodStart->copy();
            }

            if ($end->gt($periodEnd)) {
                $end = $periodEnd->copy();
            }

            if ($end->lt($start)) {
                continue;
            }

            $cursor = $start->copy();

            while ($cursor->lte($end)) {
                $dateString = $cursor->toDateString();
                $schedule = $scheduleCalendar->get($dateString);

                if (($schedule['is_workday'] ?? false) === true) {
                    $entries->push([
                        'date' => $dateString,
                        'izin' => $leave,
                        'legacy_code' => $leave->legacy_jenis_izin_code,
                        'type_name' => $leave->leave_type_name,
                        'is_paid' => $leave->leave_is_paid,
                    ]);
                }

                $cursor->addDay();
            }
        }

        return $entries->unique('date')->values();
    }

    public function requestedQuotaByYear(Karyawan $employee, Carbon $start, Carbon $end): array
    {
        $workdayDates = $this->workdayDatesForRange($employee, $start, $end);

        return $workdayDates
            ->map(fn (string $date): int => (int) Carbon::parse($date)->year)
            ->countBy()
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    public function usedQuotaForYear(Karyawan $employee, JenisIzin $leaveType, int $year): int
    {
        return (int) ($this->usedQuotaMap($employee, $leaveType, $year, $year)[$year] ?? 0);
    }

    public function quotaBalanceForYear(Karyawan $employee, JenisIzin $leaveType, int $year): array
    {
        $baseQuota = $this->baseQuotaForYear($employee, $leaveType);

        if ($baseQuota < 1) {
            return [
                'policy' => $this->resolveQuotaPolicy($leaveType)['policy'],
                'base_quota' => 0,
                'carried_over' => 0,
                'entitlement' => 0,
                'used' => 0,
                'remaining' => 0,
                'effective_year' => null,
            ];
        }

        $policyConfig = $this->resolveQuotaPolicy($leaveType);
        $policy = $policyConfig['policy'];
        $carryLimit = $policyConfig['carry_limit'];
        $effectiveYear = $policyConfig['effective_year'];
        $startYear = $effectiveYear !== null
            ? min($year, $effectiveYear)
            : $year;
        $usedMap = $this->usedQuotaMap($employee, $leaveType, $startYear, $year);
        $carryFromPrevious = 0;
        $current = [
            'policy' => $policy,
            'base_quota' => $baseQuota,
            'carried_over' => 0,
            'entitlement' => $baseQuota,
            'used' => 0,
            'remaining' => $baseQuota,
            'effective_year' => $effectiveYear,
        ];

        for ($cursorYear = $startYear; $cursorYear <= $year; $cursorYear++) {
            $carriedOver = $cursorYear === $startYear ? 0 : $carryFromPrevious;
            $entitlement = $baseQuota + $carriedOver;
            $used = max(0, (int) ($usedMap[$cursorYear] ?? 0));
            $remaining = max(0, $entitlement - $used);

            $current = [
                'policy' => $policy,
                'base_quota' => $baseQuota,
                'carried_over' => $carriedOver,
                'entitlement' => $entitlement,
                'used' => $used,
                'remaining' => $remaining,
                'effective_year' => $effectiveYear,
            ];

            if ($cursorYear < $year) {
                $carryFromPrevious = match ($policy) {
                    Setting::LEAVE_QUOTA_POLICY_CARRY_LIMITED => min($remaining, $carryLimit),
                    Setting::LEAVE_QUOTA_POLICY_CARRY_FULL => $remaining,
                    default => 0,
                };
            }
        }

        return $current;
    }

    public function findOverlap(Karyawan $employee, Carbon $start, Carbon $end): ?Izin
    {
        return Izin::query()
            ->where('karyawan_id', $employee->id)
            ->whereIn('status', ['pending', 'disetujui'])
            ->whereRaw('COALESCE(tanggal_mulai, tanggal_izin, tanggal) <= ?', [$end->toDateString()])
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_mulai, tanggal_izin, tanggal) >= ?', [$start->toDateString()])
            ->latest('id')
            ->first();
    }

    private function usedQuotaMap(Karyawan $employee, JenisIzin $leaveType, int $startYear, int $endYear): array
    {
        if (! $leaveType->deduct_quota && (int) ($leaveType->annual_quota_days ?? 0) < 1) {
            return [];
        }

        $yearStart = Carbon::create($startYear, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($endYear, 12, 31)->startOfDay();
        $leaves = Izin::query()
            ->with('jenisIzin:id,kode,quota_field,deduct_quota')
            ->where('karyawan_id', $employee->id)
            ->whereIn('status', ['pending', 'disetujui'])
            ->where('jenis_izin_id', $leaveType->id)
            ->whereRaw('COALESCE(tanggal_mulai, tanggal_izin, tanggal) <= ?', [$yearEnd->toDateString()])
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_mulai, tanggal_izin, tanggal) >= ?', [$yearStart->toDateString()])
            ->get();

        $usage = [];

        foreach ($leaves as $leave) {
            $start = $leave->tanggal_mulai_efektif;
            $end = $leave->tanggal_selesai_efektif;

            if (! $start || ! $end) {
                continue;
            }

            if ($start->lt($yearStart)) {
                $start = $yearStart->copy();
            }

            if ($end->gt($yearEnd)) {
                $end = $yearEnd->copy();
            }

            foreach ($this->workdayDatesForRange($employee, $start, $end) as $date) {
                $usage[(int) Carbon::parse($date)->year] = ((int) ($usage[(int) Carbon::parse($date)->year] ?? 0)) + 1;
            }
        }

        return $usage;
    }

    private function baseQuotaForYear(Karyawan $employee, JenisIzin $leaveType): int
    {
        if ($leaveType->quota_field === 'izin_cuti') {
            return max(0, (int) ($employee->izin_cuti ?? 0));
        }

        if ($leaveType->quota_field === 'izin_lainnya') {
            return max(0, (int) ($employee->izin_lainnya ?? 0));
        }

        return max(0, (int) ($leaveType->annual_quota_days ?? 0));
    }

    private function resolveQuotaPolicy(JenisIzin $leaveType): array
    {
        if ($leaveType->quota_field !== 'izin_cuti') {
            return [
                'policy' => Setting::LEAVE_QUOTA_POLICY_ANNUAL_RESET,
                'carry_limit' => 0,
                'effective_year' => null,
            ];
        }

        $settings = $this->settings();
        $policy = (string) ($settings?->leave_quota_policy ?: Setting::LEAVE_QUOTA_POLICY_ANNUAL_RESET);

        if (! in_array($policy, [
            Setting::LEAVE_QUOTA_POLICY_ANNUAL_RESET,
            Setting::LEAVE_QUOTA_POLICY_CARRY_LIMITED,
            Setting::LEAVE_QUOTA_POLICY_CARRY_FULL,
        ], true)) {
            $policy = Setting::LEAVE_QUOTA_POLICY_ANNUAL_RESET;
        }

        return [
            'policy' => $policy,
            'carry_limit' => max(0, (int) ($settings?->leave_carryover_max_days ?? 0)),
            'effective_year' => $settings?->leave_quota_policy_effective_year
                ? max(2000, (int) $settings->leave_quota_policy_effective_year)
                : null,
        ];
    }

    private function settings(): ?Setting
    {
        if ($this->settings === null) {
            $this->settings = Setting::query()->find(1);
        }

        return $this->settings;
    }
}
