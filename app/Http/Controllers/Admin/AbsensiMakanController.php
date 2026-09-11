<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbsensiMakan;
use App\Models\Departemen;
use App\Models\Karyawan;
use App\Services\AbsensiMakanService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbsensiMakanController extends Controller
{
    public function __construct(
        private readonly AbsensiMakanService $absensiMakanService
    ) {
    }

    public function index(Request $request)
    {
        $selectedDateInput = $request->string('tanggal')->toString();
        $selectedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDateInput) === 1
            ? Carbon::parse($selectedDateInput)
            : now();

        $jenisMakanFilter = $request->string('jenis_makan')->toString();
        $departemenFilter = $request->string('departemen_id')->toString();
        $search = trim($request->string('q')->toString());
        $perPage = max(10, min(100, (int) $request->input('per_page', 20)));

        $query = AbsensiMakan::query()
            ->with(['karyawan.departemenData', 'karyawan.jabatanData', 'lokasiGps', 'creator'])
            ->whereDate('tanggal', $selectedDate->toDateString())
            ->when($jenisMakanFilter !== '', fn (Builder $q) => $q->where('jenis_makan', $jenisMakanFilter))
            ->when($departemenFilter !== '', function (Builder $q) use ($departemenFilter) {
                $q->whereHas('karyawan', fn (Builder $kq) => $kq->where('departemen_id', $departemenFilter));
            })
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->whereHas('karyawan', function (Builder $kq) use ($search) {
                    $kq->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('rfid_uid', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('jam_makan');

        $records = $query->paginate($perPage)->withQueryString();
        $stats = $this->absensiMakanService->getTodayStats($selectedDate);
        $departemenList = Departemen::query()->orderBy('nama_departemen')->get();
        $activeEmployees = Karyawan::query()
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nik', 'jabatan', 'departemen']);

        $currentSession = $this->absensiMakanService->determineCurrentMealSession();

        return view('admin.absensi-makan.index', [
            'pageTitle' => 'Absensi Makan & Kantin',
            'records' => $records,
            'stats' => $stats,
            'selectedDate' => $selectedDate,
            'departemenList' => $departemenList,
            'activeEmployees' => $activeEmployees,
            'currentSession' => $currentSession,
            'perPage' => $perPage,
        ]);
    }

    public function scanner()
    {
        $currentSession = $this->absensiMakanService->determineCurrentMealSession();
        $stats = $this->absensiMakanService->getTodayStats();

        return view('admin.absensi-makan.scanner', [
            'pageTitle' => 'Kios Scanner Kantin',
            'currentSession' => $currentSession,
            'stats' => $stats,
        ]);
    }

    public function verifyScan(Request $request): JsonResponse
    {
        $identifier = $request->string('identifier')->toString();
        $jenisMakan = $request->string('jenis_makan')->toString() ?: null;

        $result = $this->absensiMakanService->verifyScan(
            $identifier,
            $jenisMakan,
            null,
            auth()->id()
        );

        $stats = $this->absensiMakanService->getTodayStats();
        $result['stats'] = $stats;

        if (isset($result['karyawan']) && $result['karyawan'] instanceof Karyawan) {
            $emp = $result['karyawan'];
            $result['karyawan_info'] = [
                'id' => $emp->id,
                'nama' => $emp->nama_lengkap,
                'nik' => $emp->nik,
                'jabatan' => $emp->jabatanData?->nama_jabatan ?? $emp->jabatan ?? '-',
                'departemen' => $emp->departemenData?->nama_departemen ?? $emp->departemen ?? '-',
                'rfid' => $emp->rfid_uid ?? '-',
                'foto' => $emp->foto ? asset('storage/'.$emp->foto) : null,
            ];
            unset($result['karyawan']);
        }

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawan,id',
            'tanggal' => 'required|date',
            'jam_makan' => 'nullable|string',
            'jenis_makan' => 'required|in:siang,malam,lembur,sahur',
            'catatan' => 'nullable|string|max:255',
        ]);

        $karyawan = Karyawan::query()->findOrFail($validated['karyawan_id']);
        $time = $validated['jam_makan'] ? Carbon::parse($validated['tanggal'].' '.$validated['jam_makan']) : now();

        $result = $this->absensiMakanService->recordMeal(
            $karyawan,
            $validated['jenis_makan'],
            'manual',
            null,
            null,
            auth()->id(),
            $validated['catatan'] ?? 'Input manual oleh admin',
            $time
        );

        if (! $result['success']) {
            return $this->respondError($request, route('admin.absensi-makan'), $result['message']);
        }

        return $this->respondSuccess($request, route('admin.absensi-makan'), $result['message']);
    }

    public function destroy(Request $request, AbsensiMakan $absensiMakan)
    {
        $nama = $absensiMakan->karyawan?->nama_lengkap ?? 'Karyawan';
        $jenis = $absensiMakan->jenis_makan_label;
        $absensiMakan->delete();

        return $this->respondSuccess($request, route('admin.absensi-makan'), "Kupon {$jenis} untuk {$nama} berhasil dihapus.");
    }

    public function export(Request $request): StreamedResponse
    {
        $startDate = $request->string('start_date')->toString() ?: now()->startOfMonth()->toDateString();
        $endDate = $request->string('end_date')->toString() ?: now()->toDateString();

        $records = AbsensiMakan::query()
            ->with(['karyawan', 'lokasiGps'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->orderBy('jam_makan')
            ->get();

        $filename = "rekap_absensi_makan_{$startDate}_sampai_{$endDate}.csv";

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                'Tanggal',
                'Jam Makan',
                'NIK',
                'Nama Karyawan',
                'Departemen',
                'Jabatan',
                'Jenis Makan',
                'Metode',
                'Nominal (Rp)',
                'Status',
                'Lokasi Kantin',
                'Catatan',
            ]);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->id,
                    $r->tanggal->format('Y-m-d'),
                    $r->jam_makan,
                    $r->karyawan?->nik ?? '-',
                    $r->karyawan?->nama_lengkap ?? '-',
                    $r->karyawan?->departemen ?? '-',
                    $r->karyawan?->jabatan ?? '-',
                    $r->jenis_makan_label,
                    $r->metode_label,
                    $r->nominal,
                    $r->status,
                    $r->lokasiGps?->nama_lokasi ?? 'Kantor Pusat',
                    $r->catatan ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
