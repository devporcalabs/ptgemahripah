<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Services\MonthlyScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request, MonthlyScheduleService $monthlyScheduleService)
    {
        $user = $request->user();
        abort_unless($user?->hasEmployeePanelAccess(), 403);

        $employee = $user->karyawan?->loadMissing(['lokasiGps']);
        abort_unless($employee, 404);

        $today = now()->startOfDay();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);

        $weeklySchedules = collect(range(0, 6))
            ->map(function (int $offset) use ($weekStart, $monthlyScheduleService, $employee, $today): array {
                $date = $weekStart->copy()->addDays($offset);
                $schedule = $monthlyScheduleService->resolveForDate($employee, $date->copy());

                return [
                    'date' => $date,
                    'schedule' => $schedule,
                    'is_today' => $date->isSameDay($today),
                ];
            });

        return view('karyawan.jadwal', [
            'panel' => 'karyawan',
            'pageTitle' => 'Jadwal Kerja',
            'employee' => $employee,
            'weeklySchedules' => $weeklySchedules,
            'today' => $today,
        ]);
    }
}
