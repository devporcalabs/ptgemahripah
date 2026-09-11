<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\KasbonMutation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KasbonController extends Controller
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
        $direction = trim($request->string('arah')->toString());
        $perPage = $this->resolvePerPage($request, 10);

        $periodQuery = KasbonMutation::query()
            ->where('karyawan_id', $employee->id)
            ->whereYear('tanggal', $period->year)
            ->whereMonth('tanggal', $period->month);

        $rows = KasbonMutation::query()
            ->with(['createdBy:id,name'])
            ->where('karyawan_id', $employee->id)
            ->when(in_array($direction, ['plus', 'minus'], true), fn ($query) => $query->where('arah', $direction))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('karyawan.kasbon', [
            'panel' => 'karyawan',
            'pageTitle' => 'Kasbon Saya',
            'employee' => $employee->loadMissing('latestKasbonMutation'),
            'period' => $period,
            'direction' => $direction,
            'perPage' => $perPage,
            'mutationRows' => $rows,
            'stats' => [
                'saldo' => (float) $employee->saldo_kasbon,
                'plus' => (float) (clone $periodQuery)->where('arah', 'plus')->sum('nominal'),
                'minus' => (float) (clone $periodQuery)->where('arah', 'minus')->sum('nominal'),
                'count' => (int) (clone $periodQuery)->count(),
            ],
        ]);
    }
}
