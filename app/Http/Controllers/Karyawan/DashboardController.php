<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Izin;
use App\Services\MonthlyScheduleService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, MonthlyScheduleService $monthlyScheduleService)
    {
        $user = $request->user();
        abort_unless($user?->hasEmployeePanelAccess(), 403);

        $employee = $user->karyawan;

        abort_unless($employee, 404);

        $employee->loadMissing(['jabatanData', 'departemenData', 'lokasiGps', 'latestKasbonMutation']);

        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();
        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();

        $attendanceQuery = Absensi::query()
            ->where('karyawan_id', $employee->id)
            ->whereBetween('tanggal', [$periodStart->toDateString(), $periodEnd->toDateString()]);

        $attendanceCount = (clone $attendanceQuery)->count();
        $lateMinutes = (int) (clone $attendanceQuery)->sum('menit_terlambat');
        $earlyLeaveMinutes = (int) (clone $attendanceQuery)->sum('menit_pulang_cepat');
        $overtimeMinutes = (int) (clone $attendanceQuery)->sum('menit_lembur');

        $pendingLeaves = Izin::query()
            ->where('karyawan_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        $approvedLeaves = Izin::query()
            ->where('karyawan_id', $employee->id)
            ->where('status', 'disetujui')
            ->count();

        $latestAttendances = Absensi::query()
            ->where('karyawan_id', $employee->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_masuk')
            ->limit(5)
            ->get();

        $todaySchedule = $monthlyScheduleService->resolveForDate($employee, $today->copy());
        $tomorrowSchedule = $monthlyScheduleService->resolveForDate($employee, $tomorrow->copy());

        return view('karyawan.dashboard', [
            'panel' => 'karyawan',
            'pageTitle' => 'Dashboard Karyawan',
            'employee' => $employee,
            'periodLabel' => $periodStart->translatedFormat('F Y'),
            'today' => $today,
            'tomorrow' => $tomorrow,
            'todaySchedule' => $todaySchedule,
            'tomorrowSchedule' => $tomorrowSchedule,
            'attendanceCount' => $attendanceCount,
            'lateMinutes' => $lateMinutes,
            'earlyLeaveMinutes' => $earlyLeaveMinutes,
            'overtimeMinutes' => $overtimeMinutes,
            'pendingLeaves' => $pendingLeaves,
            'approvedLeaves' => $approvedLeaves,
            'latestAttendances' => $latestAttendances,
        ]);
    }
}
