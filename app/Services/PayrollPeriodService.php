<?php

namespace App\Services;

use App\Models\AttendanceCorrection;
use App\Models\GajiKaryawan;
use App\Models\Izin;
use App\Models\PayrollPeriod;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PayrollPeriodService
{
    public function __construct(
        private readonly SalaryService $salaryService
    ) {
    }

    public function ensure(Carbon $period): PayrollPeriod
    {
        $period = $period->copy()->startOfMonth();
        $payrollPeriod = PayrollPeriod::query()
            ->whereDate('periode', $period->toDateString())
            ->first();

        if (! $payrollPeriod) {
            $payrollPeriod = PayrollPeriod::query()->create([
                'periode' => $period->toDateString(),
                'tanggal_mulai' => $period->toDateString(),
                'tanggal_selesai' => $period->copy()->endOfMonth()->toDateString(),
                'cutoff_absensi' => $period->copy()->endOfMonth()->toDateString(),
                'status' => 'draft',
            ]);
        }

        $updates = [];

        if (! $payrollPeriod->tanggal_mulai) {
            $updates['tanggal_mulai'] = $period->toDateString();
        }

        if (! $payrollPeriod->tanggal_selesai) {
            $updates['tanggal_selesai'] = $period->copy()->endOfMonth()->toDateString();
        }

        if (! $payrollPeriod->cutoff_absensi) {
            $updates['cutoff_absensi'] = $period->copy()->endOfMonth()->toDateString();
        }

        if ($updates !== []) {
            $payrollPeriod->update($updates);
        }

        return $this->loadRelations($payrollPeriod);
    }

    public function syncDraft(Carbon $period, ?int $userId = null): PayrollPeriod
    {
        $period = $period->copy()->startOfMonth();
        $payrollPeriod = $this->ensure($period);

        if ($this->usesFrozenHistory($payrollPeriod)) {
            return $this->refreshSummary($payrollPeriod);
        }

        $rows = $this->salaryService->forActiveEmployees($period);
        $this->salaryService->syncAll($rows, $payrollPeriod);

        $updates = [];

        if ($rows->isNotEmpty()) {
            $updates['generated_at'] = now();

            if ($userId) {
                $updates['generated_by'] = $userId;
            }
        }

        if ($updates !== []) {
            $payrollPeriod->update($updates);
        }

        return $this->refreshSummary($payrollPeriod);
    }

    public function submitForApproval(Carbon $period, ?int $userId = null): PayrollPeriod
    {
        $period = $period->copy()->startOfMonth();

        return DB::transaction(function () use ($period, $userId) {
            $payrollPeriod = $this->syncDraft($period, $userId);

            if ($payrollPeriod->status === 'finalized') {
                throw new DomainException('Periode payroll ini sudah final.');
            }

            if ($payrollPeriod->submitted_at) {
                throw new DomainException('Periode payroll ini sudah diajukan ke approval.');
            }

            if ((int) $payrollPeriod->total_karyawan < 1) {
                throw new DomainException('Belum ada draft payroll yang bisa diajukan untuk periode ini.');
            }

            $this->guardPeriodCanBeFinalized($period);

            $payrollPeriod->update([
                'status' => 'processing',
                'submitted_by' => $userId,
                'submitted_at' => now(),
                'approved_stage_one_by' => null,
                'approved_stage_one_at' => null,
                'approved_stage_two_by' => null,
                'approved_stage_two_at' => null,
            ]);

            return $this->refreshSummary($payrollPeriod);
        });
    }

    public function approveStageOne(Carbon $period, ?int $userId = null): PayrollPeriod
    {
        $period = $period->copy()->startOfMonth();

        return DB::transaction(function () use ($period, $userId) {
            $payrollPeriod = $this->ensure($period);

            if ($payrollPeriod->status === 'finalized') {
                throw new DomainException('Periode payroll ini sudah final.');
            }

            if (! $payrollPeriod->submitted_at) {
                throw new DomainException('Periode payroll ini belum diajukan ke approval.');
            }

            if ($payrollPeriod->approved_stage_one_at) {
                throw new DomainException('Approval tahap 1 untuk periode ini sudah selesai.');
            }

            $payrollPeriod->update([
                'status' => 'processing',
                'approved_stage_one_by' => $userId,
                'approved_stage_one_at' => now(),
            ]);

            return $this->refreshSummary($payrollPeriod);
        });
    }

    public function approveStageTwoAndFinalize(Carbon $period, ?int $userId = null): PayrollPeriod
    {
        $period = $period->copy()->startOfMonth();

        return DB::transaction(function () use ($period, $userId) {
            $payrollPeriod = $this->ensure($period);

            if ($payrollPeriod->status === 'finalized') {
                throw new DomainException('Periode payroll ini sudah final.');
            }

            if (! $payrollPeriod->submitted_at) {
                throw new DomainException('Periode payroll ini belum diajukan ke approval.');
            }

            if (! $payrollPeriod->approved_stage_one_at) {
                throw new DomainException('Approval tahap 1 harus selesai lebih dulu.');
            }

            if ($payrollPeriod->approved_stage_two_at) {
                throw new DomainException('Approval tahap 2 untuk periode ini sudah selesai.');
            }

            $payrollPeriod->update([
                'approved_stage_two_by' => $userId,
                'approved_stage_two_at' => now(),
            ]);

            return $this->finalizeResolvedPeriod($period, $this->ensure($period), $userId);
        });
    }

    public function finalize(Carbon $period, ?int $userId = null): PayrollPeriod
    {
        $period = $period->copy()->startOfMonth();

        return DB::transaction(function () use ($period, $userId) {
            $payrollPeriod = $this->ensure($period);

            return $this->finalizeResolvedPeriod($period, $payrollPeriod, $userId);
        });
    }

    public function reopen(Carbon $period, ?int $userId = null): PayrollPeriod
    {
        $period = $period->copy()->startOfMonth();

        return DB::transaction(function () use ($period, $userId) {
            $payrollPeriod = $this->ensure($period);

            $finalizedHistories = $this->historyQuery($period, $payrollPeriod)
                ->with('karyawan')
                ->where('is_finalized', true)
                ->get();

            foreach ($finalizedHistories as $history) {
                $this->salaryService->revertKasbonDeduction($history);
            }

            $this->historyQuery($period, $payrollPeriod)->update([
                'payroll_period_id' => $payrollPeriod->id,
                'is_finalized' => false,
                'finalized_by' => null,
                'finalized_at' => null,
                'applied_kasbon_amount' => 0,
            ]);

            $payrollPeriod->update([
                'status' => 'draft',
                'submitted_by' => null,
                'submitted_at' => null,
                'approved_stage_one_by' => null,
                'approved_stage_one_at' => null,
                'approved_stage_two_by' => null,
                'approved_stage_two_at' => null,
                'finalized_by' => null,
                'finalized_at' => null,
                'salary_whatsapp_status' => null,
                'salary_whatsapp_queued_at' => null,
                'salary_whatsapp_sent_at' => null,
                'salary_whatsapp_total' => 0,
                'salary_whatsapp_success' => 0,
                'salary_whatsapp_failed' => 0,
                'salary_whatsapp_skipped' => 0,
                'salary_whatsapp_last_error' => null,
                'reopened_by' => $userId,
                'reopened_at' => now(),
            ]);

            $this->syncDraft($period, $userId);

            return $this->refreshSummary($this->ensure($period));
        });
    }

    public function refreshSummary(PayrollPeriod $payrollPeriod): PayrollPeriod
    {
        $period = Carbon::parse($payrollPeriod->periode)->startOfMonth();
        $summary = $this->historyQuery($period, $payrollPeriod)
            ->selectRaw('COUNT(*) as total_karyawan')
            ->selectRaw('COALESCE(SUM(total_gaji), 0) as total_gaji')
            ->selectRaw('SUM(CASE WHEN is_finalized = 1 THEN 1 ELSE 0 END) as total_finalized')
            ->first();

        $totalKaryawan = (int) ($summary->total_karyawan ?? 0);
        $totalFinalized = (int) ($summary->total_finalized ?? 0);
        $status = $payrollPeriod->finalized_at
            ? 'finalized'
            : (($payrollPeriod->submitted_at || $payrollPeriod->approved_stage_one_at || $payrollPeriod->approved_stage_two_at || $totalFinalized > 0) ? 'processing' : 'draft');

        $payrollPeriod->update([
            'status' => $status,
            'total_karyawan' => $totalKaryawan,
            'total_gaji' => (float) ($summary->total_gaji ?? 0),
        ]);

        return $this->loadRelations($payrollPeriod->fresh());
    }

    public function usesFrozenHistory(PayrollPeriod $payrollPeriod): bool
    {
        return $payrollPeriod->status === 'finalized'
            || $payrollPeriod->submitted_at !== null
            || $payrollPeriod->approved_stage_one_at !== null
            || $payrollPeriod->approved_stage_two_at !== null;
    }

    public function isFinalized(Carbon $period): bool
    {
        return $this->ensure($period)->status === 'finalized';
    }

    public function assertEditable(Carbon $period, string $message = 'Periode payroll ini tidak bisa diubah.'): void
    {
        $payrollPeriod = $this->ensure($period);

        if ($payrollPeriod->status === 'finalized') {
            throw new DomainException($message.' Periode payroll ini sudah final. Buka periode terlebih dahulu.');
        }

        if ($this->usesFrozenHistory($payrollPeriod)) {
            throw new DomainException($message.' Periode payroll ini sedang dalam proses approval. Buka periode terlebih dahulu.');
        }
    }

    public function historyQuery(Carbon $period, ?PayrollPeriod $payrollPeriod = null): Builder
    {
        $period = $period->copy()->startOfMonth()->toDateString();
        $payrollPeriodId = $payrollPeriod?->id;

        return GajiKaryawan::query()->where(function (Builder $query) use ($period, $payrollPeriodId): void {
            if ($payrollPeriodId) {
                $query->where('payroll_period_id', $payrollPeriodId)
                    ->orWhere(function (Builder $fallbackQuery) use ($period): void {
                        $fallbackQuery->whereNull('payroll_period_id')
                            ->whereDate('bulan', $period);
                    });

                return;
            }

            $query->whereDate('bulan', $period);
        });
    }

    private function finalizeResolvedPeriod(Carbon $period, PayrollPeriod $payrollPeriod, ?int $userId = null): PayrollPeriod
    {
        $this->guardPeriodCanBeFinalized($period);

        if (! $payrollPeriod->submitted_at) {
            throw new DomainException('Periode payroll ini belum diajukan ke approval.');
        }

        if (! $payrollPeriod->approved_stage_one_at) {
            throw new DomainException('Approval tahap 1 harus selesai lebih dulu.');
        }

        $rows = $this->salaryService->forActiveEmployees($period);

        if ($rows->isEmpty()) {
            throw new DomainException('Belum ada draft payroll yang bisa difinalisasi pada periode ini.');
        }

        if (! $this->usesFrozenHistory($payrollPeriod)) {
            $this->salaryService->syncAll($rows, $payrollPeriod);
        }

        $salaryMap = $rows->keyBy(fn (array $salary) => (int) $salary['karyawan']->id);
        $histories = $this->historyQuery($period, $payrollPeriod)
            ->with('karyawan')
            ->get();

        if ($histories->isEmpty()) {
            throw new DomainException('Belum ada data payroll yang tersimpan untuk periode ini.');
        }

        $timestamp = now();

        foreach ($histories as $history) {
            $salary = $salaryMap->get((int) $history->karyawan_id);
            $snapshot = $history->detail_payload
                ?: ($salary ? $this->salaryService->snapshot($salary) : $this->salaryService->historySnapshot($history));

            $history->update([
                'payroll_period_id' => $payrollPeriod->id,
                'is_finalized' => true,
                'finalized_by' => $userId,
                'finalized_at' => $timestamp,
                'slip_number' => $history->slip_number ?: $this->generateSlipNumber($history),
                'detail_payload' => $snapshot,
                'status' => 'selesai',
            ]);

            $this->salaryService->applyKasbonDeduction($history->fresh(['karyawan']));
        }

        $payrollPeriod->update([
            'status' => 'finalized',
            'generated_by' => $payrollPeriod->generated_by ?: $userId,
            'generated_at' => $payrollPeriod->generated_at ?: $timestamp,
            'approved_stage_two_by' => $payrollPeriod->approved_stage_two_by ?: $userId,
            'approved_stage_two_at' => $payrollPeriod->approved_stage_two_at ?: $timestamp,
            'finalized_by' => $userId,
            'finalized_at' => $timestamp,
            'reopened_by' => null,
            'reopened_at' => null,
        ]);

        return $this->refreshSummary($payrollPeriod);
    }

    private function loadRelations(PayrollPeriod $payrollPeriod): PayrollPeriod
    {
        return $payrollPeriod->loadMissing([
            'generatedBy:id,name',
            'submittedBy:id,name',
            'approvedStageOneBy:id,name',
            'approvedStageTwoBy:id,name',
            'finalizedBy:id,name',
            'reopenedBy:id,name',
        ]);
    }

    private function guardPeriodCanBeFinalized(Carbon $period): void
    {
        $pendingCorrections = AttendanceCorrection::query()
            ->where('status', 'pending')
            ->whereHas('absensi', function (Builder $query) use ($period): void {
                $query
                    ->whereMonth('tanggal', $period->month)
                    ->whereYear('tanggal', $period->year);
            })
            ->count();

        if ($pendingCorrections > 0) {
            throw new DomainException("Masih ada {$pendingCorrections} koreksi absensi pending pada periode ini.");
        }

        $pendingPermissions = Izin::query()
            ->where('status', 'pending')
            ->whereRaw('COALESCE(tanggal_mulai, tanggal_izin, tanggal) <= ?', [$period->copy()->endOfMonth()->toDateString()])
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_mulai, tanggal_izin, tanggal) >= ?', [$period->copy()->startOfMonth()->toDateString()])
            ->count();

        if ($pendingPermissions > 0) {
            throw new DomainException("Masih ada {$pendingPermissions} pengajuan izin pending pada periode ini.");
        }
    }

    private function generateSlipNumber(GajiKaryawan $gajiKaryawan): string
    {
        $period = Carbon::parse($gajiKaryawan->bulan)->format('Ym');

        return 'SLIP/'.$period.'/'.$gajiKaryawan->karyawan_id.'/'.$gajiKaryawan->id;
    }
}
