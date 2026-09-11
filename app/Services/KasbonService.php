<?php

namespace App\Services;

use App\Models\GajiKaryawan;
use App\Models\KasbonMutation;
use App\Models\Karyawan;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class KasbonService
{
    public function currentBalance(?Karyawan $karyawan): float
    {
        if (! $karyawan) {
            return 0.0;
        }

        if ($karyawan->relationLoaded('latestKasbonMutation')) {
            return max(0, (float) ($karyawan->latestKasbonMutation?->kasbon_akhir ?? 0));
        }

        return max(0, (float) $karyawan->latestKasbonMutation()->value('kasbon_akhir'));
    }

    public function createManualMutation(Karyawan $karyawan, array $payload, ?int $userId = null): KasbonMutation
    {
        $direction = ($payload['arah'] ?? 'plus') === 'minus' ? 'minus' : 'plus';
        $amount = round(max(0, (float) ($payload['nominal'] ?? 0)), 2);

        if ($amount <= 0) {
            throw new DomainException('Nominal kasbon harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($karyawan, $payload, $direction, $amount, $userId) {
            $employee = $karyawan->fresh(['latestKasbonMutation']);
            $latestMutation = $employee->latestKasbonMutation;
            $currentBalance = $this->currentBalance($employee);
            $mutationDate = Carbon::parse($payload['tanggal'] ?? now())->startOfDay();

            if ($direction === 'minus' && $amount > $currentBalance) {
                throw new DomainException('Nominal pembayaran kasbon melebihi saldo kasbon karyawan.');
            }

            if ($latestMutation && $mutationDate->lt($latestMutation->tanggal->copy()->startOfDay())) {
                throw new DomainException(
                    'Tanggal mutasi tidak boleh lebih awal dari mutasi terakhir pada '.$latestMutation->tanggal->format('d/m/Y').'.'
                );
            }

            $openingBalance = round($currentBalance, 2);
            $closingBalance = $direction === 'plus'
                ? $openingBalance + $amount
                : $openingBalance - $amount;

            $mutation = KasbonMutation::query()->create([
                'karyawan_id' => $employee->id,
                'tanggal' => $mutationDate->toDateString(),
                'arah' => $direction,
                'jenis' => $direction === 'plus' ? 'manual_plus' : 'manual_minus',
                'nominal' => $amount,
                'kasbon_awal' => $openingBalance,
                'kasbon_akhir' => round($closingBalance, 2),
                'referensi_tipe' => 'manual',
                'referensi_id' => null,
                'catatan' => $this->nullableTrim($payload['catatan'] ?? null),
                'meta' => [
                    'source' => 'admin_manual',
                ],
                'created_by' => $userId,
            ]);

            return $mutation->fresh(['karyawan', 'createdBy']);
        });
    }

    public function recordPayrollDeduction(GajiKaryawan $history, float $requestedAmount, ?int $userId = null): array
    {
        $history->loadMissing('karyawan');
        $requestedAmount = round(max(0, $requestedAmount), 2);
        $currentBalance = $this->currentBalance($history->karyawan);

        if (! $history->karyawan || $requestedAmount <= 0) {
            return [
                'requested' => $requestedAmount,
                'applied' => 0.0,
                'balance_before' => $currentBalance,
                'balance_after' => $currentBalance,
            ];
        }

        return DB::transaction(function () use ($history, $requestedAmount, $userId) {
            $employee = $history->karyawan->fresh(['latestKasbonMutation']);
            $latestMutation = $employee->latestKasbonMutation;
            $balanceBefore = $this->currentBalance($employee);
            $appliedAmount = min($requestedAmount, $balanceBefore);
            $mutationDate = $latestMutation && $latestMutation->tanggal->isFuture()
                ? $latestMutation->tanggal->toDateString()
                : now()->toDateString();

            if ($appliedAmount > 0) {
                KasbonMutation::query()->create([
                    'karyawan_id' => $employee->id,
                    'tanggal' => $mutationDate,
                    'arah' => 'minus',
                    'jenis' => 'payroll_deduction',
                    'nominal' => $appliedAmount,
                    'kasbon_awal' => round($balanceBefore, 2),
                    'kasbon_akhir' => round($balanceBefore - $appliedAmount, 2),
                    'referensi_tipe' => 'gaji_karyawan',
                    'referensi_id' => $history->id,
                    'catatan' => 'Potongan kasbon dari payroll periode '.optional($history->bulan)->translatedFormat('F Y'),
                    'meta' => [
                        'payroll_history_id' => $history->id,
                        'payroll_period' => optional($history->bulan)->format('Y-m'),
                    ],
                    'created_by' => $userId,
                ]);
            }

            return [
                'requested' => $requestedAmount,
                'applied' => $appliedAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => round($balanceBefore - $appliedAmount, 2),
            ];
        });
    }

    public function recordPayrollReversal(GajiKaryawan $history, float $appliedAmount, ?int $userId = null): array
    {
        $history->loadMissing('karyawan');
        $appliedAmount = round(max(0, $appliedAmount), 2);
        $currentBalance = $this->currentBalance($history->karyawan);

        if (! $history->karyawan || $appliedAmount <= 0) {
            return [
                'reverted' => 0.0,
                'balance_before' => $currentBalance,
                'balance_after' => $currentBalance,
            ];
        }

        return DB::transaction(function () use ($history, $appliedAmount, $userId) {
            $employee = $history->karyawan->fresh(['latestKasbonMutation']);
            $latestMutation = $employee->latestKasbonMutation;
            $balanceBefore = $this->currentBalance($employee);
            $mutationDate = $latestMutation && $latestMutation->tanggal->isFuture()
                ? $latestMutation->tanggal->toDateString()
                : now()->toDateString();

            KasbonMutation::query()->create([
                'karyawan_id' => $employee->id,
                'tanggal' => $mutationDate,
                'arah' => 'plus',
                'jenis' => 'payroll_reversal',
                'nominal' => $appliedAmount,
                'kasbon_awal' => round($balanceBefore, 2),
                'kasbon_akhir' => round($balanceBefore + $appliedAmount, 2),
                'referensi_tipe' => 'gaji_karyawan',
                'referensi_id' => $history->id,
                'catatan' => 'Pengembalian kasbon karena finalisasi payroll dibuka kembali.',
                'meta' => [
                    'payroll_history_id' => $history->id,
                    'payroll_period' => optional($history->bulan)->format('Y-m'),
                ],
                'created_by' => $userId,
            ]);

            return [
                'reverted' => $appliedAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => round($balanceBefore + $appliedAmount, 2),
            ];
        });
    }

    private function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
