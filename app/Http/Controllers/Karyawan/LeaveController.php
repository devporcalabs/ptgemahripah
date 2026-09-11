<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Izin;
use App\Models\JenisIzin;
use App\Services\LeaveService;
use App\Services\PayrollPeriodService;
use App\Services\WhatsAppNotificationService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function __construct(
        private readonly PayrollPeriodService $payrollPeriodService,
        private readonly LeaveService $leaveService,
        private readonly WhatsAppNotificationService $whatsAppNotificationService,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->hasEmployeePanelAccess(), 403);

        $employee = $user->karyawan;
        $statusFilter = trim($request->string('status')->toString());
        $leaveTypeFilter = max(0, (int) $request->query('jenis_izin_id', 0));
        $perPage = $this->resolvePerPage($request, 10);

        $query = Izin::query()
            ->with(['jenisIzin', 'approvedBy'])
            ->where('karyawan_id', $employee->id)
            ->when($statusFilter !== '', fn ($builder) => $builder->where('status', $statusFilter))
            ->when($leaveTypeFilter > 0, fn ($builder) => $builder->where('jenis_izin_id', $leaveTypeFilter));

        $rows = (clone $query)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $leaveTypes = JenisIzin::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('nama')
            ->get();

        $quotaSummary = $leaveTypes
            ->filter(fn (JenisIzin $type) => $type->uses_quota)
            ->map(function (JenisIzin $type) use ($employee) {
                $balance = $this->leaveService->quotaBalanceForYear($employee, $type, now()->year);

                return [
                    'name' => $type->nama,
                    'label' => $type->quota_label ?: 'Kuota',
                    'remaining' => max(0, (int) ($balance['remaining'] ?? 0)),
                    'used' => max(0, (int) ($balance['used'] ?? 0)),
                    'entitlement' => max(0, (int) ($balance['entitlement'] ?? 0)),
                ];
            })
            ->values();

        return view('karyawan.izin', [
            'panel' => 'karyawan',
            'pageTitle' => 'Izin / Cuti Saya',
            'employee' => $employee,
            'statusFilter' => $statusFilter,
            'leaveTypeFilter' => $leaveTypeFilter,
            'perPage' => $perPage,
            'leaveRows' => $rows,
            'leaveTypes' => $leaveTypes,
            'quotaSummary' => $quotaSummary,
            'stats' => [
                'pending' => Izin::query()->where('karyawan_id', $employee->id)->where('status', 'pending')->count(),
                'approved' => Izin::query()->where('karyawan_id', $employee->id)->where('status', 'disetujui')->count(),
                'rejected' => Izin::query()->where('karyawan_id', $employee->id)->where('status', 'ditolak')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->hasEmployeePanelAccess(), 403);

        $employee = $user->karyawan;

        $data = $request->validate([
            'jenis_izin_id' => ['required', 'integer', 'exists:jenis_izin,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'alasan' => ['required', 'string'],
            'bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $jenisIzin = JenisIzin::query()
            ->where('is_active', true)
            ->find((int) $data['jenis_izin_id']);

        if (! $jenisIzin) {
            return $this->respondError($request, route('karyawan.izin'), 'Jenis izin tidak ditemukan atau sudah nonaktif.', status: 422);
        }

        $tanggalMulai = Carbon::parse($data['tanggal_mulai'])->startOfDay();
        $tanggalSelesai = Carbon::parse($data['tanggal_selesai'])->startOfDay();

        try {
            $this->assertEditableRange($tanggalMulai, $tanggalSelesai, 'Periode izin ini sudah masuk payroll final.');
        } catch (DomainException $exception) {
            return $this->respondError($request, route('karyawan.izin'), $exception->getMessage(), status: 422);
        }

        if ($jenisIzin->require_attachment && ! $request->hasFile('bukti')) {
            return $this->respondError($request, route('karyawan.izin'), 'Jenis izin ini wajib menyertakan bukti pendukung.', status: 422);
        }

        $overlap = $this->leaveService->findOverlap($employee, $tanggalMulai, $tanggalSelesai);
        if ($overlap) {
            return $this->respondError($request, route('karyawan.izin'), 'Rentang izin bentrok dengan pengajuan lain yang masih aktif.', status: 422);
        }

        $workdayDates = $this->leaveService->workdayDatesForRange($employee, $tanggalMulai, $tanggalSelesai);
        $jumlahHari = $workdayDates->count();

        if ($jumlahHari < 1) {
            return $this->respondError($request, route('karyawan.izin'), 'Rentang izin tidak mengenai hari kerja.', status: 422);
        }

        $quotaMessage = $this->validateQuotaAvailability($employee, $jenisIzin, $tanggalMulai, $tanggalSelesai);
        if ($quotaMessage !== null) {
            return $this->respondError($request, route('karyawan.izin'), $quotaMessage, status: 422);
        }

        $leave = Izin::query()->create([
            'karyawan_id' => $employee->id,
            'jenis_izin_id' => $jenisIzin->id,
            'tanggal' => $tanggalMulai->toDateString(),
            'tanggal_izin' => $tanggalMulai->toDateString(),
            'tanggal_mulai' => $tanggalMulai->toDateString(),
            'tanggal_selesai' => $tanggalSelesai->toDateString(),
            'jumlah_hari' => $jumlahHari,
            'tanggal_pengajuan' => now(),
            'jenis_izin' => $jenisIzin->legacy_code,
            'alasan' => $data['alasan'],
            'bukti' => $this->storeProof($request),
            'status' => 'pending',
        ]);

        $leave->loadMissing('karyawan', 'jenisIzin');
        $this->whatsAppNotificationService->queueLeaveSubmittedNotification($leave);

        return $this->respondSuccess($request, route('karyawan.izin'), 'Pengajuan izin berhasil dikirim.');
    }

    public function destroy(Request $request, Izin $izin)
    {
        $user = $request->user();
        abort_unless($user?->hasEmployeePanelAccess(), 403);
        abort_unless((int) $izin->karyawan_id === (int) $user->karyawan_id, 404);

        if ($izin->status !== 'pending') {
            return $this->respondError($request, route('karyawan.izin'), 'Hanya pengajuan pending yang bisa dibatalkan.', status: 422);
        }

        try {
            $this->assertEditableRange(
                $izin->tanggal_mulai_efektif ?? now()->startOfDay(),
                $izin->tanggal_selesai_efektif ?? now()->startOfDay(),
                'Periode izin ini sudah masuk payroll final.'
            );
        } catch (DomainException $exception) {
            return $this->respondError($request, route('karyawan.izin'), $exception->getMessage(), status: 422);
        }

        if ($izin->bukti) {
            Storage::disk('public')->delete('bukti_izin/'.$izin->bukti);
        }

        $izin->delete();

        return $this->respondSuccess($request, route('karyawan.izin'), 'Pengajuan izin berhasil dibatalkan.');
    }

    private function validateQuotaAvailability($employee, JenisIzin $jenisIzin, Carbon $tanggalMulai, Carbon $tanggalSelesai): ?string
    {
        $requestByYear = $this->leaveService->requestedQuotaByYear($employee, $tanggalMulai, $tanggalSelesai);
        $requestedDaysTotal = array_sum($requestByYear);
        $maxPerRequest = max(0, (int) ($jenisIzin->max_days_per_request ?? 0));
        $quotaLabel = $jenisIzin->quota_label ?: ('Kuota '.$jenisIzin->nama);

        if ($maxPerRequest > 0 && $requestedDaysTotal > $maxPerRequest) {
            return "{$jenisIzin->nama} maksimal {$maxPerRequest} hari kerja per pengajuan. Rentang yang dipilih menghasilkan {$requestedDaysTotal} hari kerja.";
        }

        if ($jenisIzin->deduct_quota && $jenisIzin->quota_field) {
            foreach ($requestByYear as $year => $requestedDays) {
                $balance = $this->leaveService->quotaBalanceForYear($employee, $jenisIzin, (int) $year);
                $availableDays = max(0, (int) ($balance['remaining'] ?? 0));

                if ((int) $requestedDays > $availableDays) {
                    return "{$quotaLabel} tahun {$year} tidak cukup. Tersedia {$availableDays} hari kerja, diminta {$requestedDays} hari kerja.";
                }
            }

            return null;
        }

        $annualQuotaDays = max(0, (int) ($jenisIzin->annual_quota_days ?? 0));

        if ($annualQuotaDays < 1) {
            return null;
        }

        foreach ($requestByYear as $year => $requestedDays) {
            $usedDays = $this->leaveService->usedQuotaForYear($employee, $jenisIzin, (int) $year);
            $availableDays = max(0, $annualQuotaDays - $usedDays);

            if ((int) $requestedDays > $availableDays) {
                return "{$quotaLabel} tahun {$year} tidak cukup. Tersedia {$availableDays} hari kerja, diminta {$requestedDays} hari kerja.";
            }
        }

        return null;
    }

    private function assertEditableRange(Carbon $tanggalMulai, Carbon $tanggalSelesai, string $message): void
    {
        $cursor = $tanggalMulai->copy()->startOfMonth();
        $endMonth = $tanggalSelesai->copy()->startOfMonth();

        while ($cursor->lte($endMonth)) {
            $this->payrollPeriodService->assertEditable($cursor, $message);
            $cursor->addMonthNoOverflow()->startOfMonth();
        }
    }

    private function storeProof(Request $request): string
    {
        if (! $request->hasFile('bukti')) {
            return '';
        }

        $file = $request->file('bukti');
        $fileName = time().'_'.$file->getClientOriginalName();
        $file->storeAs('bukti_izin', $fileName, 'public');

        return $fileName;
    }
}
