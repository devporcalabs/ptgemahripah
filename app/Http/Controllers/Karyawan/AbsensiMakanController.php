<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\AbsensiMakan;
use App\Models\Setting;
use App\Services\AbsensiMakanService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsensiMakanController extends Controller
{
    public function __construct(
        private readonly AbsensiMakanService $absensiMakanService
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->hasEmployeePanelAccess(), 403);

        $employee = $user->karyawan;
        abort_unless($employee, 404);

        $today = now()->toDateString();
        $settings = Setting::query()->find(1);

        $todayMeals = AbsensiMakan::query()
            ->where('karyawan_id', $employee->id)
            ->whereDate('tanggal', $today)
            ->where('status', 'valid')
            ->get()
            ->keyBy('jenis_makan');

        $periodInput = trim($request->string('bulan')->toString());
        $period = preg_match('/^\d{4}-\d{2}$/', $periodInput) === 1
            ? Carbon::createFromFormat('Y-m', $periodInput)->startOfMonth()
            : now()->startOfMonth();

        $history = AbsensiMakan::query()
            ->with(['lokasiGps'])
            ->where('karyawan_id', $employee->id)
            ->whereYear('tanggal', $period->year)
            ->whereMonth('tanggal', $period->month)
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_makan')
            ->paginate(15)
            ->withQueryString();

        $currentSession = $this->absensiMakanService->determineCurrentMealSession();
        $selfClaimEnabled = (bool) ($settings?->makan_mandiri_enabled ?? true);

        return view('karyawan.absensi-makan', [
            'panel' => 'karyawan',
            'pageTitle' => 'Kupon & Absensi Makan',
            'employee' => $employee,
            'todayMeals' => $todayMeals,
            'history' => $history,
            'period' => $period,
            'currentSession' => $currentSession,
            'selfClaimEnabled' => $selfClaimEnabled,
            'settings' => $settings,
        ]);
    }

    public function claim(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->hasEmployeePanelAccess(), 403);

        $employee = $user->karyawan;
        abort_unless($employee, 404);

        $settings = Setting::query()->find(1);
        if (! ($settings?->makan_mandiri_enabled ?? true)) {
            return back()->with('error', 'Klaim kupon makan mandiri sedang dinonaktifkan oleh administrator.');
        }

        $session = $request->string('jenis_makan')->toString() ?: $this->absensiMakanService->determineCurrentMealSession();

        $result = $this->absensiMakanService->recordMeal(
            $employee,
            $session,
            'web',
            null,
            $employee->lokasi_gps_id,
            $user->id,
            'Klaim mandiri melalui portal karyawan'
        );

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }
}
