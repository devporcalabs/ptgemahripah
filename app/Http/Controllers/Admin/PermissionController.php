<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Izin;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use App\Services\LeaveService;
use App\Services\PayrollPeriodService;
use App\Services\WhatsAppNotificationService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PayrollPeriodService $payrollPeriodService,
        private readonly LeaveService $leaveService,
        private readonly WhatsAppNotificationService $whatsAppNotificationService,
    ) {
    }

    public function index(Request $request)
    {
        $statusFilter = $request->string('status')->toString();
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);
        $leaveTypeFilter = max(0, (int) $request->query('jenis_izin_id', 0));

        $izinList = Izin::query()
            ->with(['karyawan', 'approvedBy', 'jenisIzin'])
            ->when($statusFilter !== '', fn ($query) => $query->where('status', $statusFilter))
            ->when($leaveTypeFilter > 0, fn ($query) => $query->where('jenis_izin_id', $leaveTypeFilter))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('alasan', 'like', "%{$search}%")
                        ->orWhere('jenis_izin', 'like', "%{$search}%")
                        ->orWhereHas('jenisIzin', fn ($typeQuery) => $typeQuery->where('nama', 'like', "%{$search}%"))
                        ->orWhereHas('karyawan', function ($employeeQuery) use ($search): void {
                            $employeeQuery
                                ->where('nik', 'like', "%{$search}%")
                                ->orWhere('nama_lengkap', 'like', "%{$search}%");
                        });

                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search) === 1) {
                        $innerQuery->orWhere(function ($dateQuery) use ($search): void {
                            $dateQuery
                                ->whereRaw('COALESCE(tanggal_mulai, tanggal_izin, tanggal) <= ?', [$search])
                                ->whereRaw('COALESCE(tanggal_selesai, tanggal_mulai, tanggal_izin, tanggal) >= ?', [$search]);
                        });
                    }
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.izin', [
            'statusFilter' => $statusFilter,
            'leaveTypeFilter' => $leaveTypeFilter,
            'izinList' => $izinList,
            'search' => $search,
            'perPage' => $perPage,
            'karyawanList' => Karyawan::query()->where('status', 'aktif')->orderBy('nama_lengkap')->get(['id', 'nik', 'nama_lengkap']),
            'jenisIzinList' => JenisIzin::query()->where('is_active', true)->orderBy('sort_order')->orderBy('nama')->get(),
            'totalPending' => Izin::query()->where('status', 'pending')->count(),
            'totalDisetujui' => Izin::query()->where('status', 'disetujui')->count(),
            'totalDitolak' => Izin::query()->where('status', 'ditolak')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $karyawan = Karyawan::query()->findOrFail((int) $data['karyawan_id']);
        $jenisIzin = JenisIzin::query()
            ->where('is_active', true)
            ->find((int) $data['jenis_izin_id']);

        if (! $jenisIzin) {
            return $this->respondError($request, route('admin.izin'), 'Jenis izin tidak ditemukan atau sudah nonaktif.', status: 422);
        }

        $tanggalMulai = Carbon::parse($data['tanggal_mulai'])->startOfDay();
        $tanggalSelesai = Carbon::parse($data['tanggal_selesai'])->startOfDay();

        try {
            $this->assertEditableRange($tanggalMulai, $tanggalSelesai, 'Periode izin ini sudah masuk payroll final.');
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.izin'), $exception->getMessage(), status: 422);
        }

        if ($jenisIzin->require_attachment && ! $request->hasFile('bukti')) {
            return $this->respondError($request, route('admin.izin'), 'Jenis izin ini wajib menyertakan bukti pendukung.', status: 422);
        }

        $overlap = $this->leaveService->findOverlap($karyawan, $tanggalMulai, $tanggalSelesai);

        if ($overlap) {
            $conflictDate = optional($overlap->tanggal_mulai_efektif)->format('d/m/Y') ?: '-';

            return $this->respondError(
                $request,
                route('admin.izin'),
                "Rentang izin bentrok dengan pengajuan lain pada {$conflictDate}. Selesaikan atau hapus pengajuan lama terlebih dahulu.",
                status: 422
            );
        }

        $workdayDates = $this->leaveService->workdayDatesForRange($karyawan, $tanggalMulai, $tanggalSelesai);
        $jumlahHari = $workdayDates->count();

        if ($jumlahHari < 1) {
            return $this->respondError(
                $request,
                route('admin.izin'),
                'Rentang izin tidak mengenai hari kerja. Gunakan halaman Hari Libur untuk libur global/perusahaan.',
                status: 422
            );
        }

        $quotaError = $this->validateQuotaAvailability($karyawan, $jenisIzin, $tanggalMulai, $tanggalSelesai);

        if ($quotaError !== null) {
            return $this->respondError($request, route('admin.izin'), $quotaError, status: 422);
        }

        $leave = Izin::query()->create([
            'karyawan_id' => $karyawan->id,
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

        return $this->respondSuccess($request, route('admin.izin'), 'Pengajuan izin berhasil ditambahkan.');
    }

    public function previewQuota(Request $request)
    {
        $karyawanId = (int) $request->query('karyawan_id', 0);
        $jenisIzinId = (int) $request->query('jenis_izin_id', 0);
        $tanggalMulaiValue = trim((string) $request->query('tanggal_mulai', ''));
        $tanggalSelesaiValue = trim((string) $request->query('tanggal_selesai', ''));

        if ($karyawanId < 1 || $jenisIzinId < 1 || $tanggalMulaiValue === '' || $tanggalSelesaiValue === '') {
            return response()->json([
                'ok' => true,
                'ready' => false,
                'message' => 'Pilih karyawan, jenis izin, dan rentang tanggal untuk melihat preview kuota.',
            ]);
        }

        $validated = validator([
            'karyawan_id' => $karyawanId,
            'jenis_izin_id' => $jenisIzinId,
            'tanggal_mulai' => $tanggalMulaiValue,
            'tanggal_selesai' => $tanggalSelesaiValue,
        ], [
            'karyawan_id' => ['required', 'integer', 'exists:karyawan,id'],
            'jenis_izin_id' => ['required', 'integer', 'exists:jenis_izin,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ])->validate();

        $karyawan = Karyawan::query()->findOrFail((int) $validated['karyawan_id']);
        $jenisIzin = JenisIzin::query()
            ->where('is_active', true)
            ->find((int) $validated['jenis_izin_id']);

        if (! $jenisIzin) {
            return response()->json([
                'ok' => false,
                'message' => 'Jenis izin tidak ditemukan atau sudah nonaktif.',
            ], 422);
        }

        $tanggalMulai = Carbon::parse($validated['tanggal_mulai'])->startOfDay();
        $tanggalSelesai = Carbon::parse($validated['tanggal_selesai'])->startOfDay();
        $workdayDates = $this->leaveService->workdayDatesForRange($karyawan, $tanggalMulai, $tanggalSelesai);
        $jumlahHari = $workdayDates->count();
        $assessment = $this->evaluateQuotaAvailability($karyawan, $jenisIzin, $tanggalMulai, $tanggalSelesai);

        if ($jumlahHari < 1) {
            return response()->json([
                'ok' => true,
                'ready' => true,
                'enough_quota' => false,
                'workday_count' => 0,
                'uses_quota' => (bool) ($assessment['uses_quota'] ?? false),
                'quota_label' => $assessment['quota_label'] ?? ($jenisIzin->quota_label ?: 'Kuota'),
                'years' => $assessment['years'] ?? [],
                'message' => 'Rentang ini tidak mengenai hari kerja. Hari Minggu dan libur global tidak dihitung sebagai durasi izin.',
                'note' => 'Preview ini hanya memeriksa kuota. Validasi akhir tetap mengecek bentrok data, lampiran wajib, dan lock payroll.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'ready' => true,
            'enough_quota' => (bool) ($assessment['enough'] ?? true),
            'uses_quota' => (bool) ($assessment['uses_quota'] ?? false),
            'quota_label' => $assessment['quota_label'] ?? ($jenisIzin->quota_label ?: 'Kuota'),
            'workday_count' => $jumlahHari,
            'requested_days_total' => (int) ($assessment['requested_days_total'] ?? $jumlahHari),
            'max_per_request' => (int) ($assessment['max_per_request'] ?? 0),
            'years' => $assessment['years'] ?? [],
            'message' => $assessment['message'] ?: 'Kuota tersedia untuk rentang tanggal yang dipilih.',
            'note' => 'Preview ini hanya memeriksa kuota. Validasi akhir tetap mengecek bentrok data, lampiran wajib, dan lock payroll.',
        ]);
    }

    public function approve(Request $request, Izin $izin)
    {
        try {
            $this->assertEditableForLeave($izin, 'Periode izin ini sudah masuk payroll final.');
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.izin'), $exception->getMessage(), status: 422);
        }

        $izin->update([
            'status' => 'disetujui',
            'disetujui_oleh' => auth()->id(),
            'tanggal_disetujui' => now(),
        ]);

        $izin->loadMissing('karyawan', 'jenisIzin');
        $this->whatsAppNotificationService->queueLeaveStatusNotification($izin);

        return $this->respondSuccess($request, route('admin.izin'), 'Pengajuan izin disetujui.');
    }

    public function reject(Request $request, Izin $izin)
    {
        try {
            $this->assertEditableForLeave($izin, 'Periode izin ini sudah masuk payroll final.');
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.izin'), $exception->getMessage(), status: 422);
        }

        $izin->update([
            'status' => 'ditolak',
            'disetujui_oleh' => auth()->id(),
            'tanggal_disetujui' => now(),
        ]);

        $izin->loadMissing('karyawan', 'jenisIzin');
        $this->whatsAppNotificationService->queueLeaveStatusNotification($izin);

        return $this->respondSuccess($request, route('admin.izin'), 'Pengajuan izin ditolak.');
    }

    public function updateNote(Request $request, Izin $izin)
    {
        $request->validate([
            'catatan_admin' => ['nullable', 'string'],
        ]);

        $izin->update([
            'catatan_admin' => $request->input('catatan_admin'),
        ]);

        return $this->respondSuccess($request, route('admin.izin'), 'Catatan admin berhasil diperbarui.');
    }

    public function destroy(Request $request, Izin $izin)
    {
        try {
            $this->assertEditableForLeave($izin, 'Periode izin ini sudah masuk payroll final.');
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.izin'), $exception->getMessage(), status: 422);
        }

        if ($izin->bukti) {
            Storage::disk('public')->delete('bukti_izin/'.$izin->bukti);
        }

        $izin->delete();

        return $this->respondSuccess($request, route('admin.izin'), 'Data izin berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'karyawan_id' => ['required', 'integer', 'exists:karyawan,id'],
            'jenis_izin_id' => ['required', 'integer', 'exists:jenis_izin,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'alasan' => ['required', 'string'],
            'bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'form_type' => ['nullable', Rule::in(['create'])],
        ]);
    }

    private function validateQuotaAvailability(
        Karyawan $karyawan,
        JenisIzin $jenisIzin,
        Carbon $tanggalMulai,
        Carbon $tanggalSelesai,
    ): ?string {
        $assessment = $this->evaluateQuotaAvailability($karyawan, $jenisIzin, $tanggalMulai, $tanggalSelesai);

        return $assessment['message'] ?? null;
    }

    private function evaluateQuotaAvailability(
        Karyawan $karyawan,
        JenisIzin $jenisIzin,
        Carbon $tanggalMulai,
        Carbon $tanggalSelesai,
    ): array {
        $requestByYear = $this->leaveService->requestedQuotaByYear($karyawan, $tanggalMulai, $tanggalSelesai);
        $requestedDaysTotal = array_sum($requestByYear);
        $maxPerRequest = max(0, (int) ($jenisIzin->max_days_per_request ?? 0));
        $quotaLabel = $jenisIzin->quota_label ?: ('Kuota '.$jenisIzin->nama);
        $result = [
            'uses_quota' => false,
            'quota_label' => $quotaLabel,
            'requested_days_total' => $requestedDaysTotal,
            'max_per_request' => $maxPerRequest,
            'years' => [],
            'enough' => true,
            'message' => null,
        ];

        if ($maxPerRequest > 0 && $requestedDaysTotal > $maxPerRequest) {
            $result['enough'] = false;
            $result['message'] = "{$jenisIzin->nama} maksimal {$maxPerRequest} hari kerja per pengajuan. Rentang yang dipilih menghasilkan {$requestedDaysTotal} hari kerja.";

            return $result;
        }

        if ($jenisIzin->deduct_quota && $jenisIzin->quota_field) {
            $result['uses_quota'] = true;

            foreach ($requestByYear as $year => $requestedDays) {
                $balance = $this->leaveService->quotaBalanceForYear($karyawan, $jenisIzin, (int) $year);
                $availableDays = max(0, (int) ($balance['remaining'] ?? 0));
                $result['years'][] = [
                    'year' => (int) $year,
                    'requested_days' => (int) $requestedDays,
                    'available_days' => $availableDays,
                    'base_quota' => max(0, (int) ($balance['base_quota'] ?? 0)),
                    'carried_over' => max(0, (int) ($balance['carried_over'] ?? 0)),
                    'used_days' => max(0, (int) ($balance['used'] ?? 0)),
                    'entitlement_days' => max(0, (int) ($balance['entitlement'] ?? 0)),
                    'remaining_after_request' => max(0, $availableDays - (int) $requestedDays),
                ];

                if ((int) $requestedDays > $availableDays && $result['message'] === null) {
                    $carriedDays = max(0, (int) ($balance['carried_over'] ?? 0));
                    $baseDays = max(0, (int) ($balance['base_quota'] ?? 0));
                    $carryInfo = $carriedDays > 0
                        ? " (jatah {$baseDays} hari + carry over {$carriedDays} hari)"
                        : " (jatah {$baseDays} hari)";

                    $result['enough'] = false;
                    $result['message'] = "{$quotaLabel} tahun {$year} tidak cukup. Tersedia {$availableDays} hari kerja{$carryInfo}, diminta {$requestedDays} hari kerja.";
                }
            }

            return $result;
        }

        $annualQuotaDays = max(0, (int) ($jenisIzin->annual_quota_days ?? 0));

        if ($annualQuotaDays < 1) {
            $result['message'] = 'Jenis izin ini tidak memakai kuota karyawan.';

            return $result;
        }

        $result['uses_quota'] = true;

        foreach ($requestByYear as $year => $requestedDays) {
            $usedDays = $this->leaveService->usedQuotaForYear($karyawan, $jenisIzin, (int) $year);
            $availableDays = max(0, $annualQuotaDays - $usedDays);
            $result['years'][] = [
                'year' => (int) $year,
                'requested_days' => (int) $requestedDays,
                'available_days' => $availableDays,
                'base_quota' => $annualQuotaDays,
                'carried_over' => 0,
                'used_days' => $usedDays,
                'entitlement_days' => $annualQuotaDays,
                'remaining_after_request' => max(0, $availableDays - (int) $requestedDays),
            ];

            if ((int) $requestedDays > $availableDays && $result['message'] === null) {
                $result['enough'] = false;
                $result['message'] = "{$quotaLabel} tahun {$year} tidak cukup. Tersedia {$availableDays} hari kerja, diminta {$requestedDays} hari kerja.";
            }
        }

        return $result;
    }

    private function assertEditableForLeave(Izin $izin, string $message): void
    {
        $tanggalMulai = $izin->tanggal_mulai_efektif ?? now()->startOfMonth();
        $tanggalSelesai = $izin->tanggal_selesai_efektif ?? $tanggalMulai;

        $this->assertEditableRange($tanggalMulai, $tanggalSelesai, $message);
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
