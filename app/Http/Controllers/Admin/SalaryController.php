<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GajiTambahan;
use App\Models\GajiKaryawan;
use App\Models\PayrollPeriod;
use App\Models\Setting;
use App\Services\PayrollPeriodService;
use App\Services\PayrollSlipWhatsAppService;
use App\Services\SalaryService;
use Carbon\Carbon;
use DomainException;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class SalaryController extends Controller
{
    private const ADJUSTMENT_TYPE_OPTIONS = [
        'bonus_manual' => 'Bonus Manual',
        'reimbursement' => 'Reimbursement',
        'koreksi_plus' => 'Koreksi Plus',
        'koreksi_minus' => 'Koreksi Minus',
        'potongan_lain' => 'Potongan Lain',
        'denda' => 'Denda',
    ];

    public function __construct(
        private readonly SalaryService $salaryService,
        private readonly PayrollPeriodService $payrollPeriodService,
        private readonly PayrollSlipWhatsAppService $payrollSlipWhatsAppService,
    ) {
    }

    public function index(Request $request)
    {
        $period = $this->resolvePeriod($request);
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);
        $payrollPeriod = $this->payrollPeriodService->syncDraft($period, auth()->id());
        $allSalaryRows = $this->attachAdjustments($this->resolveRowsForPeriod($period, $payrollPeriod), $period);
        $filteredRows = $this->filterSalaryRows($allSalaryRows, $search);
        $salaryRows = $this->paginateCollection($filteredRows, $request, $perPage);

        return view('admin.gaji', [
            'period' => $period,
            'payrollPeriod' => $payrollPeriod,
            'periodSummary' => $this->buildPeriodSummary($filteredRows, $payrollPeriod),
            'salaryRows' => $salaryRows,
            'grandTotal' => $filteredRows->sum('total_gaji'),
            'salarySummary' => [
                'employees' => $filteredRows->count(),
                'scheduled_days' => $filteredRows->sum('scheduled_days'),
                'hadir_days' => $filteredRows->sum('total_hadir'),
                'alpha_days' => $filteredRows->sum('total_alpha'),
                'late_minutes' => $filteredRows->sum('total_menit_terlambat'),
                'early_leave_minutes' => $filteredRows->sum('total_menit_pulang_cepat'),
            ],
            'search' => $search,
            'perPage' => $perPage,
            'settings' => Setting::query()->find(1),
        ]);
    }

    public function finalize(Request $request, GajiKaryawan $gajiKaryawan)
    {
        if ($gajiKaryawan->is_finalized) {
            return $this->respondSuccess($request, route('admin.riwayat-gaji'), 'Payroll sudah berstatus final.');
        }

        $period = Carbon::parse($gajiKaryawan->bulan)->startOfMonth();
        $payrollPeriod = $this->payrollPeriodService->ensure($period);

        if ($this->payrollPeriodService->usesFrozenHistory($payrollPeriod)) {
            return $this->respondError(
                $request,
                route('admin.riwayat-gaji'),
                'Periode payroll ini sudah diajukan ke approval atau final. Gunakan workflow periode payroll.',
                status: 422
            );
        }

        DB::transaction(function () use ($gajiKaryawan, $period, $payrollPeriod): void {
            $salary = $this->salaryService->forEmployee($gajiKaryawan->karyawan, $period);
            $this->salaryService->syncHistory($salary, $payrollPeriod);
            $gajiKaryawan->refresh();

            $gajiKaryawan->update([
                'payroll_period_id' => $payrollPeriod->id,
                'is_finalized' => true,
                'finalized_by' => auth()->id(),
                'finalized_at' => now(),
                'slip_number' => $gajiKaryawan->slip_number ?: $this->generateSlipNumber($gajiKaryawan),
                'detail_payload' => $gajiKaryawan->detail_payload ?: $this->salaryService->snapshot($salary),
            ]);

            $this->salaryService->applyKasbonDeduction($gajiKaryawan->fresh(['karyawan']));
            $this->payrollPeriodService->refreshSummary($payrollPeriod);
        });

        return $this->respondSuccess($request, route('admin.riwayat-gaji'), 'Payroll berhasil difinalisasi dan angka gaji dikunci.');
    }

    public function unfinalize(Request $request, GajiKaryawan $gajiKaryawan)
    {
        if (! $gajiKaryawan->is_finalized) {
            return $this->respondSuccess($request, route('admin.riwayat-gaji'), 'Payroll belum difinalisasi.');
        }

        $period = Carbon::parse($gajiKaryawan->bulan)->startOfMonth();
        $payrollPeriod = $this->payrollPeriodService->ensure($period);

        if ($this->payrollPeriodService->usesFrozenHistory($payrollPeriod)) {
            return $this->respondError(
                $request,
                route('admin.riwayat-gaji'),
                'Periode payroll ini sudah diajukan ke approval atau final. Gunakan buka periode terlebih dahulu.',
                status: 422
            );
        }

        DB::transaction(function () use ($gajiKaryawan, $period, $payrollPeriod): void {
            $this->salaryService->revertKasbonDeduction($gajiKaryawan->fresh(['karyawan']));

            $gajiKaryawan->update([
                'payroll_period_id' => $payrollPeriod->id,
                'is_finalized' => false,
                'finalized_by' => null,
                'finalized_at' => null,
                'applied_kasbon_amount' => 0,
            ]);

            $salary = $this->salaryService->forEmployee($gajiKaryawan->karyawan->fresh(), $period);
            $this->salaryService->syncHistory($salary, $payrollPeriod);
            $this->payrollPeriodService->refreshSummary($payrollPeriod);
        });

        return $this->respondSuccess($request, route('admin.riwayat-gaji'), 'Finalisasi payroll dibuka kembali.');
    }

    public function submitPeriod(Request $request)
    {
        $period = $this->resolvePeriod($request);

        try {
            $this->payrollPeriodService->submitForApproval($period, auth()->id());
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.riwayat-gaji'), $exception->getMessage(), status: 422);
        }

        return $this->respondSuccess($request, route('admin.riwayat-gaji'), 'Periode payroll berhasil diajukan ke approval tahap 1.');
    }

    public function approvePeriodStageOne(Request $request)
    {
        $period = $this->resolvePeriod($request);

        try {
            $this->payrollPeriodService->approveStageOne($period, auth()->id());
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.riwayat-gaji'), $exception->getMessage(), status: 422);
        }

        return $this->respondSuccess($request, route('admin.riwayat-gaji'), 'Approval payroll tahap 1 berhasil disimpan.');
    }

    public function approvePeriodStageTwo(Request $request)
    {
        $period = $this->resolvePeriod($request);

        try {
            $payrollPeriod = $this->payrollPeriodService->approveStageTwoAndFinalize($period, auth()->id());
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.riwayat-gaji'), $exception->getMessage(), status: 422);
        }

        $queued = $this->payrollSlipWhatsAppService->queueForFinalizedPeriod($payrollPeriod);
        $message = 'Approval payroll tahap 2 selesai dan periode berhasil difinalisasi.';

        if ($queued) {
            $message .= ' Notifikasi WhatsApp slip gaji dimasukkan ke antrean.';
        }

        return $this->respondSuccess($request, route('admin.riwayat-gaji'), $message);
    }

    public function finalizePeriod(Request $request)
    {
        return $this->approvePeriodStageTwo($request);
    }

    public function reopenPeriod(Request $request)
    {
        $period = $this->resolvePeriod($request);
        $this->payrollPeriodService->reopen($period, auth()->id());

        return $this->respondSuccess($request, route('admin.riwayat-gaji'), 'Periode payroll berhasil dibuka kembali ke mode draft.');
    }

    public function refreshPeriod(Request $request)
    {
        $period = $this->resolvePeriod($request);

        try {
            $payrollPeriod = $this->payrollPeriodService->syncDraft($period, auth()->id());
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.riwayat-gaji'), $exception->getMessage(), status: 422);
        }

        $message = $this->payrollPeriodService->usesFrozenHistory($payrollPeriod)
            ? 'Periode payroll sedang dalam approval atau sudah final. Draft tidak diubah.'
            : 'Draft payroll periode berhasil diperbarui.';

        return $this->respondSuccess($request, route('admin.riwayat-gaji'), $message);
    }

    public function updateKasbon(Request $request, GajiKaryawan $gajiKaryawan)
    {
        $period = Carbon::parse($gajiKaryawan->bulan)->startOfMonth();

        try {
            $this->payrollPeriodService->assertEditable($period, 'Kasbon payroll tidak bisa diubah.');
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.riwayat-gaji'), $exception->getMessage(), status: 422);
        }

        $validated = $request->validate([
            'kasbon_mode' => ['nullable', 'in:auto,manual'],
            'potongan_kasbon' => ['nullable', 'string', 'max:30'],
        ]);

        $manualAmount = ($validated['kasbon_mode'] ?? 'manual') === 'auto'
            ? null
            : $this->parseAmount((string) ($validated['potongan_kasbon'] ?? '0'));

        try {
            $this->salaryService->updateDraftKasbon($gajiKaryawan, $manualAmount);
            $this->payrollPeriodService->refreshSummary($this->payrollPeriodService->ensure($period));
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.riwayat-gaji'), $exception->getMessage(), status: 422);
        }

        return $this->respondSuccess($request, route('admin.riwayat-gaji'), 'Potongan kasbon payroll berhasil diperbarui.');
    }

    public function adjustments(Request $request, GajiKaryawan $gajiKaryawan): Response
    {
        $gajiKaryawan->loadMissing('karyawan');
        $period = Carbon::parse($gajiKaryawan->bulan)->startOfMonth();
        $payrollPeriod = $this->payrollPeriodService->ensure($period);
        $editable = ! $gajiKaryawan->is_finalized && ! $this->payrollPeriodService->usesFrozenHistory($payrollPeriod);

        return response()->json([
            'ok' => true,
            'editable' => $editable,
            'employee' => [
                'id' => $gajiKaryawan->karyawan_id,
                'nik' => $gajiKaryawan->karyawan?->nik,
                'nama_lengkap' => $gajiKaryawan->karyawan?->nama_lengkap,
            ],
            'period' => [
                'value' => $period->format('Y-m'),
                'label' => $period->translatedFormat('F Y'),
            ],
            'store_url' => route('admin.riwayat-gaji.adjustments.store', $gajiKaryawan),
            'type_options' => collect(self::ADJUSTMENT_TYPE_OPTIONS)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'adjustments' => GajiTambahan::query()
                ->where('karyawan_id', $gajiKaryawan->karyawan_id)
                ->whereMonth('bulan', $period->month)
                ->whereYear('bulan', $period->year)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->map(fn (GajiTambahan $adjustment) => $this->transformAdjustment($adjustment))
                ->values()
                ->all(),
        ]);
    }

    public function storeAdjustment(Request $request, GajiKaryawan $gajiKaryawan)
    {
        $gajiKaryawan->loadMissing('karyawan');
        $period = Carbon::parse($gajiKaryawan->bulan)->startOfMonth();

        try {
            $this->assertAdjustmentEditable($gajiKaryawan->karyawan_id, $period, $gajiKaryawan);
            $payload = $this->validateAdjustmentPayload($request);
            $signedAmount = $this->resolveSignedAdjustmentAmount($payload['direction'], (string) $payload['nominal']);
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.riwayat-gaji'), $exception->getMessage(), status: 422);
        }

        DB::transaction(function () use ($gajiKaryawan, $period, $payload, $signedAmount): void {
            GajiTambahan::query()->create([
                'karyawan_id' => $gajiKaryawan->karyawan_id,
                'bulan' => $period->toDateString(),
                'jenis' => $payload['jenis'],
                'jumlah' => $signedAmount,
                'keterangan' => $this->normalizeAdjustmentNote($payload['keterangan'] ?? null),
                'created_by' => auth()->id(),
            ]);

            $this->refreshDraftAfterAdjustmentChange($period);
        });

        return $this->respondSuccess($request, route('admin.riwayat-gaji'), 'Koreksi payroll berhasil ditambahkan.');
    }

    public function updateAdjustment(Request $request, GajiTambahan $gajiTambahan)
    {
        $period = Carbon::parse($gajiTambahan->bulan)->startOfMonth();

        try {
            $this->assertAdjustmentEditable($gajiTambahan->karyawan_id, $period);
            $payload = $this->validateAdjustmentPayload($request);
            $signedAmount = $this->resolveSignedAdjustmentAmount($payload['direction'], (string) $payload['nominal']);
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.riwayat-gaji'), $exception->getMessage(), status: 422);
        }

        DB::transaction(function () use ($gajiTambahan, $payload, $signedAmount, $period): void {
            $gajiTambahan->update([
                'jenis' => $payload['jenis'],
                'jumlah' => $signedAmount,
                'keterangan' => $this->normalizeAdjustmentNote($payload['keterangan'] ?? null),
            ]);

            $this->refreshDraftAfterAdjustmentChange($period);
        });

        return $this->respondSuccess($request, route('admin.riwayat-gaji'), 'Koreksi payroll berhasil diperbarui.');
    }

    public function destroyAdjustment(Request $request, GajiTambahan $gajiTambahan)
    {
        $period = Carbon::parse($gajiTambahan->bulan)->startOfMonth();

        try {
            $this->assertAdjustmentEditable($gajiTambahan->karyawan_id, $period);
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.riwayat-gaji'), $exception->getMessage(), status: 422);
        }

        DB::transaction(function () use ($gajiTambahan, $period): void {
            $gajiTambahan->delete();
            $this->refreshDraftAfterAdjustmentChange($period);
        });

        return $this->respondSuccess($request, route('admin.riwayat-gaji'), 'Koreksi payroll berhasil dihapus.');
    }

    public function slip(Request $request, GajiKaryawan $gajiKaryawan): Response
    {
        $period = Carbon::parse($gajiKaryawan->bulan)->startOfMonth();
        $salary = $this->salaryService->forEmployee($gajiKaryawan->karyawan, $period);
        $snapshot = $gajiKaryawan->detail_payload ?: $this->salaryService->snapshot($salary);
        $fileName = 'Slip_Gaji_'.$gajiKaryawan->karyawan->nik.'_'.$period->format('Y-m').'.pdf';
        $settings = Setting::query()->find(1);

        return response($this->createSlipPdfOutput($gajiKaryawan->loadMissing(['karyawan', 'finalizedBy']), $snapshot, $settings), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    public function downloadPeriodSlips(Request $request): Response|StreamedResponse
    {
        $period = $this->resolvePeriod($request);
        $payrollPeriod = $this->payrollPeriodService->ensure($period);

        if ($payrollPeriod->status !== 'finalized') {
            return $this->respondError($request, route('admin.riwayat-gaji'), 'Slip massal hanya bisa diunduh setelah periode payroll difinalisasi.', status: 422);
        }

        if (! class_exists(ZipArchive::class)) {
            return $this->respondError($request, route('admin.riwayat-gaji'), 'Ekstensi ZipArchive tidak tersedia di server.', status: 500);
        }

        $histories = $this->payrollPeriodService->historyQuery($period, $payrollPeriod)
            ->with(['karyawan', 'finalizedBy'])
            ->where('is_finalized', true)
            ->get()
            ->filter(fn (GajiKaryawan $history) => $history->karyawan !== null)
            ->values();
        $settings = Setting::query()->find(1);

        if ($histories->isEmpty()) {
            return $this->respondError($request, route('admin.riwayat-gaji'), 'Tidak ada slip final yang bisa diunduh pada periode ini.', status: 422);
        }

        $tempBase = tempnam(sys_get_temp_dir(), 'payroll_slips_');
        $zipPath = $tempBase.'.zip';
        @unlink($tempBase);

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return $this->respondError($request, route('admin.riwayat-gaji'), 'File ZIP slip tidak bisa dibuat.', status: 500);
        }

        foreach ($histories as $history) {
            $snapshot = $history->detail_payload ?: $this->salaryService->historySnapshot($history);
            $pdfOutput = $this->createSlipPdfOutput($history, $snapshot, $settings);
            $zip->addFromString($this->buildSlipFileName($history), $pdfOutput);
        }

        $zip->close();

        return response()->download(
            $zipPath,
            'Slip_Gaji_'.$period->format('Y-m').'.zip',
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }

    public function exportExcel(Request $request): Response|StreamedResponse
    {
        $period = $this->resolvePeriod($request);
        $search = $this->resolveSearch($request);
        $payrollPeriod = $this->payrollPeriodService->syncDraft($period, auth()->id());
        $salaryRows = $this->filterSalaryRows($this->resolveRowsForPeriod($period, $payrollPeriod), $search);
        $type = $request->string('type')->toString() ?: 'excel';

        if ($type === 'pdf') {
            return $this->renderPdf([
                'period' => $period,
                'salaryRows' => $salaryRows,
                'grandTotal' => $salaryRows->sum('total_gaji'),
                'settings' => Setting::query()->find(1),
            ], 'Laporan_Gaji_'.$period->format('Y-m').'.pdf');
        }

        return response()->streamDownload(function () use ($salaryRows, $period): void {
            echo view('admin.exports.gaji', [
                'period' => $period,
                'salaryRows' => $salaryRows,
                'grandTotal' => $salaryRows->sum('total_gaji'),
                'settings' => Setting::query()->find(1),
                'exportMode' => 'excel',
            ])->render();
        }, 'Laporan_Gaji_'.$period->format('Y-m').'.xls', [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    private function resolvePeriod(Request $request): Carbon
    {
        $value = (string) ($request->input('bulan') ?? $request->query('bulan', ''));

        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        }

        return now()->startOfMonth();
    }

    private function renderPdf(array $data, string $fileName): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('admin.exports.gaji', $data + ['exportMode' => 'pdf'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    private function filterSalaryRows(iterable $rows, string $search)
    {
        $keyword = strtolower($search);

        return collect($rows)->filter(function (array $salary) use ($keyword) {
            if ($keyword === '') {
                return true;
            }

            $employee = $salary['karyawan'];

            return str_contains(strtolower($employee->nik), $keyword)
                || str_contains(strtolower($employee->nama_lengkap), $keyword)
                || str_contains(strtolower((string) $employee->jabatan), $keyword);
        })->values();
    }

    private function attachHistory(iterable $rows, Carbon $period)
    {
        $rows = collect($rows)->values();
        $employeeIds = $rows->pluck('karyawan.id')->filter()->all();

        $historyMap = GajiKaryawan::query()
            ->with('finalizedBy:id,name')
            ->whereDate('bulan', $period->toDateString())
            ->whereIn('karyawan_id', $employeeIds)
            ->get()
            ->keyBy('karyawan_id');

        return $rows->map(function (array $salary) use ($historyMap) {
            $history = $historyMap->get($salary['karyawan']->id);
            $salary['history'] = $history;
            $salary['is_finalized'] = (bool) $history?->is_finalized;

            return $salary;
        });
    }

    private function resolveRowsForPeriod(Carbon $period, PayrollPeriod $payrollPeriod)
    {
        return $this->salaryService->historyRows($period, $payrollPeriod);
    }

    private function attachAdjustments(iterable $rows, Carbon $period)
    {
        $rows = collect($rows)->values();
        $employeeIds = $rows->pluck('karyawan.id')->filter()->unique()->values()->all();

        if ($employeeIds === []) {
            return $rows;
        }

        $adjustmentMap = GajiTambahan::query()
            ->whereIn('karyawan_id', $employeeIds)
            ->whereMonth('bulan', $period->month)
            ->whereYear('bulan', $period->year)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('karyawan_id');

        return $rows->map(function (array $salary) use ($adjustmentMap) {
            $employeeId = $salary['karyawan']->id ?? null;
            $salary['adjustments'] = collect($adjustmentMap->get($employeeId, collect()))
                ->map(fn (GajiTambahan $adjustment) => $this->transformAdjustment($adjustment))
                ->values()
                ->all();

            return $salary;
        });
    }

    private function buildPeriodSummary(iterable $rows, PayrollPeriod $payrollPeriod): array
    {
        $rows = collect($rows);
        $finalizedCount = $rows->filter(fn (array $salary) => (bool) ($salary['is_finalized'] ?? false))->count();
        $draftCount = max(0, $rows->count() - $finalizedCount);
        $workflow = $this->resolveWorkflowState($payrollPeriod);

        return [
            'status' => $payrollPeriod->status,
            'total_rows' => $rows->count(),
            'finalized_rows' => $finalizedCount,
            'draft_rows' => $draftCount,
            'total_gaji' => (float) ($payrollPeriod->total_gaji ?? $rows->sum('total_gaji')),
            'workflow_key' => $workflow['key'],
            'workflow_label' => $workflow['label'],
            'workflow_description' => $workflow['description'],
            'wa_status_label' => $this->resolveWhatsappStatusLabel($payrollPeriod),
        ];
    }

    private function resolveWorkflowState(PayrollPeriod $payrollPeriod): array
    {
        if ($payrollPeriod->finalized_at) {
            return [
                'key' => 'finalized',
                'label' => 'Final',
                'description' => 'Periode sudah final. Slip massal dan histori gaji memakai snapshot terkunci.',
            ];
        }

        if ($payrollPeriod->approved_stage_one_at) {
            return [
                'key' => 'waiting_stage_two',
                'label' => 'Menunggu Approval 2',
                'description' => 'Approval tahap 1 selesai. Tahap berikutnya akan memfinalisasi seluruh payroll periode ini.',
            ];
        }

        if ($payrollPeriod->submitted_at) {
            return [
                'key' => 'waiting_stage_one',
                'label' => 'Menunggu Approval 1',
                'description' => 'Periode sudah diajukan. Draft payroll dibekukan sampai approval diproses atau periode dibuka ulang.',
            ];
        }

        return [
            'key' => 'draft',
            'label' => 'Draft',
            'description' => 'Draft payroll masih bisa dihitung ulang sebelum diajukan ke approval.',
        ];
    }

    private function createSlipPdfOutput(GajiKaryawan $history, array $snapshot, $settings = null): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('admin.exports.slip-gaji', [
            'history' => $history,
            'snapshot' => $snapshot,
            'settings' => $settings ?: Setting::query()->find(1),
        ])->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function buildSlipFileName(GajiKaryawan $gajiKaryawan): string
    {
        $period = Carbon::parse($gajiKaryawan->bulan)->format('Y-m');
        $employee = $gajiKaryawan->karyawan;
        $nik = $employee?->nik ?: 'UNKNOWN';

        return 'Slip_Gaji_'.$nik.'_'.$period.'.pdf';
    }

    private function generateSlipNumber(GajiKaryawan $gajiKaryawan): string
    {
        $period = Carbon::parse($gajiKaryawan->bulan)->format('Ym');

        return 'SLIP/'.$period.'/'.$gajiKaryawan->karyawan_id.'/'.$gajiKaryawan->id;
    }

    private function resolveWhatsappStatusLabel(PayrollPeriod $payrollPeriod): string
    {
        return match ($payrollPeriod->salary_whatsapp_status) {
            'queued' => 'WA slip: dalam antrean',
            'sending' => 'WA slip: sedang dikirim',
            'sent' => 'WA slip: terkirim semua',
            'partial' => 'WA slip: terkirim sebagian',
            'failed' => 'WA slip: gagal',
            'disabled' => 'WA slip: nonaktif dari pengaturan',
            'cancelled' => 'WA slip: dibatalkan',
            default => 'WA slip: belum diproses',
        };
    }

    private function parseAmount(string $value): float
    {
        $normalized = preg_replace('/[^\d,-]/', '', trim($value)) ?? '0';
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return round(max(0, (float) $normalized), 2);
    }

    private function validateAdjustmentPayload(Request $request): array
    {
        return $request->validate([
            'jenis' => ['required', 'string', 'max:50', Rule::in(array_keys(self::ADJUSTMENT_TYPE_OPTIONS))],
            'direction' => ['required', Rule::in(['plus', 'minus'])],
            'nominal' => ['required', 'string', 'max:30'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'jenis.required' => 'Jenis koreksi wajib dipilih.',
            'direction.required' => 'Arah koreksi wajib dipilih.',
            'nominal.required' => 'Nominal koreksi wajib diisi.',
            'keterangan.max' => 'Catatan koreksi maksimal 255 karakter.',
        ]);
    }

    private function resolveSignedAdjustmentAmount(string $direction, string $nominal): float
    {
        $amount = $this->parseAmount($nominal);

        if ($amount <= 0) {
            throw new DomainException('Nominal koreksi payroll harus lebih dari 0.');
        }

        return $direction === 'minus'
            ? -1 * $amount
            : $amount;
    }

    private function normalizeAdjustmentNote(?string $value): ?string
    {
        $note = trim((string) ($value ?? ''));

        return $note !== '' ? $note : null;
    }

    private function assertAdjustmentEditable(int $employeeId, Carbon $period, ?GajiKaryawan $history = null): void
    {
        $payrollPeriod = $this->payrollPeriodService->ensure($period);

        if ($this->payrollPeriodService->usesFrozenHistory($payrollPeriod)) {
            throw new DomainException('Koreksi payroll hanya bisa diubah saat periode masih draft.');
        }

        $history ??= GajiKaryawan::query()
            ->where('karyawan_id', $employeeId)
            ->whereDate('bulan', $period->toDateString())
            ->first();

        if ($history?->is_finalized) {
            throw new DomainException('Koreksi payroll tidak bisa diubah karena payroll karyawan ini sudah final.');
        }
    }

    private function refreshDraftAfterAdjustmentChange(Carbon $period): void
    {
        $payrollPeriod = $this->payrollPeriodService->syncDraft($period, auth()->id());
        $this->payrollPeriodService->refreshSummary($payrollPeriod);
    }

    private function transformAdjustment(GajiTambahan $adjustment): array
    {
        $signedAmount = (float) ($adjustment->jumlah ?? 0);
        $direction = $signedAmount < 0 ? 'minus' : 'plus';

        return [
            'id' => $adjustment->id,
            'jenis' => (string) $adjustment->jenis,
            'jenis_label' => $this->resolveAdjustmentTypeLabel($adjustment->jenis),
            'direction' => $direction,
            'amount' => $signedAmount,
            'amount_abs' => abs($signedAmount),
            'keterangan' => $adjustment->keterangan,
            'created_at' => optional($adjustment->created_at)->format('d/m/Y H:i'),
            'update_url' => route('admin.riwayat-gaji.adjustments.update', $adjustment),
            'delete_url' => route('admin.riwayat-gaji.adjustments.destroy', $adjustment),
        ];
    }

    private function resolveAdjustmentTypeLabel(?string $type): string
    {
        $key = (string) $type;

        return self::ADJUSTMENT_TYPE_OPTIONS[$key]
            ?? ucwords(str_replace('_', ' ', $key));
    }
}
