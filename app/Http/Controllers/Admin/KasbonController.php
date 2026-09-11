<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KasbonMutation;
use App\Models\Karyawan;
use App\Services\KasbonService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KasbonController extends Controller
{
    public function __construct(
        private readonly KasbonService $kasbonService,
    ) {
    }

    public function index(Request $request)
    {
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);
        $employeeId = $request->integer('karyawan_id');
        $period = $this->resolvePeriod($request);

        $periodMutationQuery = KasbonMutation::query()
            ->when($employeeId > 0, fn ($query) => $query->where('karyawan_id', $employeeId))
            ->whereMonth('tanggal', $period->month)
            ->whereYear('tanggal', $period->year);

        $latestBalanceSubquery = KasbonMutation::query()
            ->selectRaw('MAX(id) as latest_mutation_id, karyawan_id')
            ->groupBy('karyawan_id');

        $employeeBalanceQuery = Karyawan::query()
            ->joinSub($latestBalanceSubquery, 'latest_kasbon', function ($join): void {
                $join->on('latest_kasbon.karyawan_id', '=', 'karyawan.id');
            })
            ->join('kasbon_mutations as latest_mutation', 'latest_mutation.id', '=', 'latest_kasbon.latest_mutation_id')
            ->select([
                'karyawan.id',
                'karyawan.nik',
                'karyawan.nama_lengkap',
                DB::raw('latest_mutation.kasbon_akhir as kasbon_balance'),
                DB::raw('latest_mutation.tanggal as last_transaction_date'),
                DB::raw('latest_mutation.created_at as last_transaction_at'),
                DB::raw('latest_mutation.arah as last_transaction_direction'),
                DB::raw('latest_mutation.nominal as last_transaction_amount'),
            ])
            ->when($employeeId > 0, fn ($query) => $query->where('karyawan.id', $employeeId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('nik', 'like', "%{$search}%")
                        ->orWhere('nama_lengkap', 'like', "%{$search}%");
                });
            })
            ->where('latest_mutation.kasbon_akhir', '>', 0)
            ->orderByDesc('latest_mutation.created_at')
            ->orderByDesc('latest_mutation.id')
            ->orderBy('karyawan.nama_lengkap');

        return view('admin.kasbon', [
            'period' => $period,
            'search' => $search,
            'perPage' => $perPage,
            'employeeId' => $employeeId,
            'employeeOptions' => Karyawan::query()
                ->orderBy('nama_lengkap')
                ->get(['id', 'nik', 'nama_lengkap']),
            'employeeBalances' => (clone $employeeBalanceQuery)->limit(15)->get(),
            'stats' => [
                'total_saldo' => (float) (clone $employeeBalanceQuery)->sum('latest_mutation.kasbon_akhir'),
                'employee_with_saldo' => (int) (clone $employeeBalanceQuery)->count(),
                'total_plus' => (float) (clone $periodMutationQuery)->where('arah', 'plus')->sum('nominal'),
                'total_minus' => (float) (clone $periodMutationQuery)->where('arah', 'minus')->sum('nominal'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'karyawan_id' => ['required', 'integer', Rule::exists('karyawan', 'id')],
            'tanggal' => ['required', 'date'],
            'arah' => ['required', Rule::in(['plus', 'minus'])],
            'nominal' => ['required', 'string', 'max:30'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = Karyawan::query()->findOrFail((int) $data['karyawan_id']);
        $payload = [
            'tanggal' => $data['tanggal'],
            'arah' => $data['arah'],
            'nominal' => $this->parseAmount($data['nominal']),
            'catatan' => $data['catatan'] ?? null,
        ];

        try {
            $this->kasbonService->createManualMutation($employee, $payload, auth()->id());
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.kasbon'), $exception->getMessage(), status: 422);
        }

        return $this->respondSuccess($request, route('admin.kasbon'), 'Mutasi kasbon berhasil disimpan.');
    }

    public function history(Request $request, Karyawan $karyawan)
    {
        $perPage = $this->resolvePerPage($request, default: 10);
        $search = $this->resolveSearch($request);
        $direction = $request->string('arah')->toString();

        $mutations = KasbonMutation::query()
            ->with(['createdBy:id,name'])
            ->where('karyawan_id', $karyawan->id)
            ->when(in_array($direction, ['plus', 'minus'], true), function ($query) use ($direction): void {
                $query->where('arah', $direction);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('catatan', 'like', "%{$search}%")
                        ->orWhere('jenis', 'like', "%{$search}%")
                        ->orWhere('referensi_tipe', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.partials.kasbon-history-modal-body', [
            'historyEmployee' => $karyawan,
            'historyMutations' => $mutations,
            'historyPerPage' => $perPage,
            'historySearch' => $search,
            'historyDirection' => $direction,
        ]);
    }

    private function resolvePeriod(Request $request): Carbon
    {
        $periodInput = trim($request->string('bulan')->toString());

        if ($periodInput !== '' && preg_match('/^\d{4}\-\d{2}$/', $periodInput) === 1) {
            try {
                return Carbon::createFromFormat('Y-m', $periodInput)->startOfMonth();
            } catch (\Throwable) {
            }
        }

        return now()->startOfMonth();
    }

    private function parseAmount(string $value): float
    {
        $normalized = preg_replace('/[^\d,-]/', '', trim($value)) ?? '0';
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return round(max(0, (float) $normalized), 2);
    }
}
