<?php

namespace App\Services;

use App\Jobs\SendPayrollSlipWhatsAppJob;
use App\Models\GajiKaryawan;
use App\Models\PayrollPeriod;
use App\Models\Setting;
use Carbon\Carbon;
use Throwable;

class PayrollSlipWhatsAppService
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService
    ) {
    }

    public function queueForFinalizedPeriod(PayrollPeriod $payrollPeriod): bool
    {
        $payrollPeriod = PayrollPeriod::query()->findOrFail($payrollPeriod->id);

        if ($payrollPeriod->status !== 'finalized') {
            $this->updateTracking($payrollPeriod, [
                'salary_whatsapp_status' => 'cancelled',
                'salary_whatsapp_last_error' => 'Periode payroll belum final sehingga notifikasi slip tidak dimasukkan ke antrean.',
            ]);

            return false;
        }

        $setting = Setting::query()->find(1);

        if (! $this->isEnabled($setting)) {
            $this->updateTracking($payrollPeriod, [
                'salary_whatsapp_status' => 'disabled',
                'salary_whatsapp_queued_at' => null,
                'salary_whatsapp_sent_at' => null,
                'salary_whatsapp_total' => 0,
                'salary_whatsapp_success' => 0,
                'salary_whatsapp_failed' => 0,
                'salary_whatsapp_skipped' => 0,
                'salary_whatsapp_last_error' => 'Notifikasi slip gaji WhatsApp sedang nonaktif di pengaturan gateway.',
            ]);

            return false;
        }

        $totalRecipients = GajiKaryawan::query()
            ->where('payroll_period_id', $payrollPeriod->id)
            ->where('is_finalized', true)
            ->count();

        $queuedAt = now();

        $this->updateTracking($payrollPeriod, [
            'salary_whatsapp_status' => 'queued',
            'salary_whatsapp_queued_at' => $queuedAt,
            'salary_whatsapp_sent_at' => null,
            'salary_whatsapp_total' => $totalRecipients,
            'salary_whatsapp_success' => 0,
            'salary_whatsapp_failed' => 0,
            'salary_whatsapp_skipped' => 0,
            'salary_whatsapp_last_error' => null,
        ]);

        SendPayrollSlipWhatsAppJob::dispatch($payrollPeriod->id, $queuedAt->toIso8601String());

        return true;
    }

    public function sendForFinalizedPeriod(PayrollPeriod|int $payrollPeriod, ?string $queueMarker = null): array
    {
        $payrollPeriod = $payrollPeriod instanceof PayrollPeriod
            ? $payrollPeriod->fresh()
            : PayrollPeriod::query()->findOrFail($payrollPeriod);

        if ($queueMarker !== null) {
            $currentMarker = $payrollPeriod->salary_whatsapp_queued_at?->toIso8601String();

            if ($currentMarker === null || $currentMarker !== $queueMarker) {
                return [
                    'status' => 'cancelled',
                    'total' => 0,
                    'success' => 0,
                    'failed' => 0,
                    'skipped' => 0,
                ];
            }
        }

        if ($payrollPeriod->status !== 'finalized') {
            $this->updateTracking($payrollPeriod, [
                'salary_whatsapp_status' => 'cancelled',
                'salary_whatsapp_last_error' => 'Periode payroll tidak lagi final saat job diproses.',
            ]);

            return [
                'status' => 'cancelled',
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'skipped' => 0,
            ];
        }

        $setting = Setting::query()->find(1);

        if (! $this->isEnabled($setting)) {
            $this->updateTracking($payrollPeriod, [
                'salary_whatsapp_status' => 'disabled',
                'salary_whatsapp_last_error' => 'Notifikasi slip gaji WhatsApp sedang nonaktif saat job diproses.',
            ]);

            return [
                'status' => 'disabled',
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'skipped' => 0,
            ];
        }

        $histories = GajiKaryawan::query()
            ->with('karyawan')
            ->where('payroll_period_id', $payrollPeriod->id)
            ->where('is_finalized', true)
            ->get();

        if ($histories->isEmpty()) {
            $this->updateTracking($payrollPeriod, [
                'salary_whatsapp_status' => 'failed',
                'salary_whatsapp_total' => 0,
                'salary_whatsapp_last_error' => 'Tidak ada histori payroll final yang bisa dikirim ke WhatsApp.',
            ]);

            return [
                'status' => 'failed',
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'skipped' => 0,
            ];
        }

        $this->updateTracking($payrollPeriod, [
            'salary_whatsapp_status' => 'sending',
            'salary_whatsapp_total' => $histories->count(),
            'salary_whatsapp_last_error' => null,
        ]);

        $success = 0;
        $failed = 0;
        $skipped = 0;
        $failedNames = [];

        foreach ($histories as $history) {
            $employee = $history->karyawan;
            $phone = trim((string) ($employee?->telepon ?: $employee?->no_telp ?: ''));

            if (! $employee || $phone === '') {
                $skipped++;
                continue;
            }

            try {
                $message = $this->buildMessage($history, $setting);

                if ($this->whatsAppService->send($phone, $message)) {
                    $success++;
                } else {
                    $failed++;
                    $failedNames[] = $employee->nama_lengkap;
                }
            } catch (Throwable $throwable) {
                report($throwable);
                $failed++;
                $failedNames[] = $employee->nama_lengkap;
            }
        }

        $status = $failed > 0 || $skipped > 0
            ? ($success > 0 ? 'partial' : 'failed')
            : 'sent';

        $lastError = $failed > 0
            ? 'Gagal kirim ke: '.implode(', ', array_slice($failedNames, 0, 5))
            : ($skipped > 0 ? "{$skipped} karyawan dilewati karena nomor WhatsApp kosong." : null);

        $this->updateTracking($payrollPeriod, [
            'salary_whatsapp_status' => $status,
            'salary_whatsapp_sent_at' => now(),
            'salary_whatsapp_total' => $histories->count(),
            'salary_whatsapp_success' => $success,
            'salary_whatsapp_failed' => $failed,
            'salary_whatsapp_skipped' => $skipped,
            'salary_whatsapp_last_error' => $lastError,
        ]);

        return [
            'status' => $status,
            'total' => $histories->count(),
            'success' => $success,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    private function isEnabled(?Setting $setting): bool
    {
        return (bool) ($setting?->whatsapp_enabled)
            && (bool) ($setting?->notif_gaji)
            && ! empty($setting?->whatsapp_api_key);
    }

    private function buildMessage(GajiKaryawan $history, ?Setting $setting): string
    {
        $snapshot = $history->detail_payload ?: [];
        $employee = $history->karyawan;
        $institutionName = $setting?->nama_instansi ?: config('app.name');
        $periodLabel = Carbon::parse($history->bulan)->translatedFormat('F Y');
        $hadirDays = (int) ($snapshot['total_hadir'] ?? $history->total_hadir ?? 0);
        $izinDays = (int) ($snapshot['total_izin'] ?? $history->total_izin ?? 0);
        $alphaDays = (int) ($snapshot['total_alpha'] ?? 0);
        $totalBonus = (float) ($snapshot['total_bonus'] ?? 0);
        $bonusManual = (float) ($snapshot['bonus_manual_total'] ?? max(0, $totalBonus - (float) ($snapshot['premi_kehadiran_total'] ?? 0)));
        $premiKehadiran = (float) ($snapshot['premi_kehadiran_total'] ?? 0);
        $potonganKasbon = (float) ($snapshot['potongan_kasbon'] ?? 0);
        $totalGaji = (float) ($snapshot['total_gaji'] ?? $history->total_gaji ?? 0);

        $lines = [
            "Halo {$employee?->nama_lengkap},",
            '',
            "Slip gaji {$institutionName} periode {$periodLabel} sudah final.",
            "No. slip: ".($history->slip_number ?: '-'),
            "Hadir: {$hadirDays} hari | Izin: {$izinDays} hari | Alpha: {$alphaDays} hari",
        ];

        if ($bonusManual > 0 || $premiKehadiran > 0 || $potonganKasbon > 0) {
            $lines[] = 'Bonus Manual: Rp '.number_format($bonusManual, 0, ',', '.')
                .' | Premi Hadir: Rp '.number_format($premiKehadiran, 0, ',', '.')
                .' | Kasbon: Rp '.number_format($potonganKasbon, 0, ',', '.');
        }

        $lines[] = 'Total gaji: Rp '.number_format($totalGaji, 0, ',', '.');
        $lines[] = '';
        $lines[] = 'Jika memerlukan detail slip PDF, silakan hubungi admin.';
        $lines[] = 'Pesan ini dikirim otomatis oleh sistem.';

        return trim(implode("\n", $lines));
    }

    private function updateTracking(PayrollPeriod $payrollPeriod, array $payload): void
    {
        $payrollPeriod->forceFill($payload)->save();
    }
}
