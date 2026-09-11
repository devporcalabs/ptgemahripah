<?php

namespace App\Services;

use App\Models\Karyawan;
use Carbon\Carbon;

class ShiftScheduleResolver
{
    public function __construct(
        private readonly MonthlyScheduleService $monthlyScheduleService,
    ) {
    }

    public function resolveForAttendance(Karyawan $karyawan, Carbon $scanTime): array
    {
        return $this->monthlyScheduleService->resolveForDate($karyawan, $scanTime);
    }
}
