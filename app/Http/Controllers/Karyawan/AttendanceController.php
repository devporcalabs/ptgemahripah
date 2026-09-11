<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->hasEmployeePanelAccess(), 403);

        $employee = $user->karyawan;
        $periodInput = trim($request->string('bulan')->toString());
        $period = preg_match('/^\d{4}-\d{2}$/', $periodInput) === 1
            ? Carbon::createFromFormat('Y-m', $periodInput)->startOfMonth()
            : now()->startOfMonth();
        $statusFilter = trim($request->string('status')->toString());
        $perPage = $this->resolvePerPage($request, 10);

        $baseQuery = Absensi::query()
            ->with('shift:id,nama_shift')
            ->where('karyawan_id', $employee->id)
            ->whereYear('tanggal', $period->year)
            ->whereMonth('tanggal', $period->month);

        $rows = (clone $baseQuery)
            ->when($statusFilter !== '', fn ($query) => $query->where('status', $statusFilter))
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_masuk')
            ->paginate($perPage)
            ->withQueryString();

        $rows->getCollection()->transform(function (Absensi $attendance) {
            $attendance->schedule_source_label = match ((string) $attendance->schedule_source) {
                'tetap' => 'Shift Tetap',
                'rolling' => 'Shift Rolling',
                'fleksibel' => 'Shift Fleksibel',
                'libur_global' => 'Lembur Hari Libur',
                'libur_mingguan' => 'Libur Mingguan',
                'libur' => 'Hari Libur',
                default => 'Jadwal Sistem',
            };

            $attendance->late_label = format_duration_minutes_label((int) ($attendance->menit_terlambat ?? 0));
            $attendance->early_leave_label = format_duration_minutes_label((int) ($attendance->menit_pulang_cepat ?? 0));
            $attendance->overtime_label = format_duration_minutes_label((int) ($attendance->menit_lembur ?? 0));

            return $attendance;
        });

        return view('karyawan.absensi', [
            'panel' => 'karyawan',
            'pageTitle' => 'Absensi Saya',
            'employee' => $employee,
            'period' => $period,
            'statusFilter' => $statusFilter,
            'perPage' => $perPage,
            'attendanceRows' => $rows,
            'stats' => [
                'hadir' => (clone $baseQuery)->count(),
                'terlambat' => (int) (clone $baseQuery)->sum('menit_terlambat'),
                'pulang_cepat' => (int) (clone $baseQuery)->sum('menit_pulang_cepat'),
                'lembur' => (int) (clone $baseQuery)->sum('menit_lembur'),
            ],
        ]);
    }
}
