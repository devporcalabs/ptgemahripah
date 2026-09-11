<?php

use App\Models\Device;
use App\Models\GajiKaryawan;
use App\Models\PayrollPeriod;
use App\Services\ApiCoIdHolidaySyncService;
use App\Services\PayrollPeriodService;
use App\Services\SalaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('device:register {serial_number} {--name=} {--status=pending}', function (): int {
    $serialNumber = trim((string) $this->argument('serial_number'));
    $name = trim((string) $this->option('name'));
    $status = trim((string) $this->option('status'));

    if ($serialNumber === '') {
        $this->error('Serial number wajib diisi.');

        return 1;
    }

    if (! in_array($status, [Device::STATUS_PENDING, Device::STATUS_ACTIVE, Device::STATUS_INACTIVE, Device::STATUS_REVOKED], true)) {
        $this->error('Status device tidak valid. Gunakan: pending, active, inactive, atau revoked.');

        return 1;
    }

    $device = Device::query()->updateOrCreate(
        ['serial_number' => $serialNumber],
        [
            'name' => $name !== '' ? $name : null,
            'status' => $status,
        ],
    );

    $this->info('Device RFID berhasil disimpan.');
    $this->line('ID: '.$device->id);
    $this->line('Serial: '.$device->serial_number);
    $this->line('Nama: '.($device->name ?: '-'));
    $this->line('Status: '.$device->status);

    return 0;
})->purpose('Daftarkan serial number mesin RFID agar bisa aktivasi dari perangkat');

Artisan::command('payroll:refresh-history {--period=*}', function (SalaryService $salaryService, PayrollPeriodService $payrollPeriodService): int {
    $selectedPeriods = collect((array) $this->option('period'))
        ->map(fn ($period) => trim((string) $period))
        ->filter()
        ->values();

    if ($selectedPeriods->isEmpty()) {
        $selectedPeriods = PayrollPeriod::query()
            ->pluck('periode')
            ->map(fn ($period) => Carbon::parse($period)->startOfMonth()->toDateString())
            ->merge(
                GajiKaryawan::query()
                    ->select('bulan')
                    ->distinct()
                    ->pluck('bulan')
                    ->map(fn ($period) => Carbon::parse($period)->startOfMonth()->toDateString())
            )
            ->unique()
            ->sort()
            ->values();
    } else {
        $selectedPeriods = $selectedPeriods
            ->map(function (string $period) {
                if (preg_match('/^\d{4}-\d{2}$/', $period) === 1) {
                    return Carbon::createFromFormat('Y-m', $period)->startOfMonth()->toDateString();
                }

                return Carbon::parse($period)->startOfMonth()->toDateString();
            })
            ->unique()
            ->sort()
            ->values();
    }

    if ($selectedPeriods->isEmpty()) {
        $this->warn('Tidak ada periode payroll yang bisa direfresh.');

        return 0;
    }

    $this->info('Refresh histori payroll dimulai...');
    $totalPeriods = 0;
    $totalRows = 0;

    foreach ($selectedPeriods as $periodValue) {
        $period = Carbon::parse($periodValue)->startOfMonth();
        $payrollPeriod = $payrollPeriodService->ensure($period);
        $isFrozen = $payrollPeriodService->usesFrozenHistory($payrollPeriod);

        $this->line('');
        $this->line('Periode '.$period->format('Y-m').' ['.($isFrozen ? 'frozen/final' : 'draft').']');

        if (! $isFrozen) {
            $payrollPeriodService->syncDraft($period, null);
            $refreshedRows = (int) $payrollPeriodService->historyQuery($period, $payrollPeriod)->count();
            $this->line(' - Draft direbuild ulang: '.$refreshedRows.' baris');
            $totalPeriods++;
            $totalRows += $refreshedRows;
            continue;
        }

        $histories = $payrollPeriodService->historyQuery($period, $payrollPeriod)
            ->with('karyawan')
            ->get()
            ->filter(fn (GajiKaryawan $history) => $history->karyawan !== null)
            ->values();

        DB::transaction(function () use ($histories, $period, $payrollPeriod, $salaryService): void {
            foreach ($histories as $history) {
                $salary = $salaryService->forEmployee($history->karyawan->fresh(), $period);
                $snapshot = $salaryService->snapshot($salary);
                $existingSnapshot = $history->detail_payload ?: [];
                $potonganKasbon = max(0, (float) ($history->applied_kasbon_amount ?? ($existingSnapshot['potongan_kasbon'] ?? 0)));

                if (array_key_exists('saldo_kasbon_awal', $existingSnapshot)) {
                    $snapshot['saldo_kasbon_awal'] = (float) $existingSnapshot['saldo_kasbon_awal'];
                }

                $snapshot['potongan_kasbon'] = $potonganKasbon;
                $snapshot['total_potongan'] = round(
                    (float) ($snapshot['potongan_mangkir'] ?? 0)
                    + (float) ($snapshot['potongan_izin'] ?? 0)
                    + (float) ($snapshot['potongan_terlambat'] ?? 0)
                    + (float) ($snapshot['potongan_bpjs_kesehatan_total'] ?? 0)
                    + (float) ($snapshot['potongan_bpjs_ketenagakerjaan_total'] ?? 0)
                    + $potonganKasbon,
                    2
                );
                $snapshot['total_gaji'] = round(
                    (float) ($snapshot['gaji_kehadiran'] ?? 0)
                    + (float) ($snapshot['tunjangan_jabatan_tampil'] ?? 0)
                    + (float) ($snapshot['tunjangan_makan_total'] ?? 0)
                    + (float) ($snapshot['tunjangan_transport_total'] ?? 0)
                    + (float) ($snapshot['total_bonus'] ?? 0)
                    + (float) ($snapshot['tunjangan_bpjs_kesehatan_total'] ?? 0)
                    + (float) ($snapshot['tunjangan_bpjs_ketenagakerjaan_total'] ?? 0)
                    + (float) ($snapshot['total_lembur_tarif'] ?? 0)
                    + (float) ($snapshot['total_penyesuaian'] ?? 0)
                    - (float) ($snapshot['total_potongan'] ?? 0),
                    2
                );

                $history->update([
                    'payroll_period_id' => $payrollPeriod->id,
                    'gaji_pokok' => $salary['base_salary_total'],
                    'gaji_per_hari' => $salary['daily_deduction_rate'],
                    'total_hadir' => $salary['total_hadir'],
                    'total_terlambat' => $salary['total_menit_terlambat'],
                    'total_izin' => $salary['total_izin'],
                    'total_lembur' => $salary['total_lembur_tarif'],
                    'gaji_kehadiran' => $salary['gaji_kehadiran'],
                    'tunjangan_makan' => $salary['tunjangan_makan_total'],
                    'tunjangan_transport' => $salary['tunjangan_transport_total'],
                    'potongan_terlambat' => $salary['potongan_terlambat'],
                    'total_gaji' => $snapshot['total_gaji'],
                    'detail_payload' => $snapshot,
                ]);
            }
        });

        $payrollPeriodService->refreshSummary($payrollPeriod);

        $this->line(' - Frozen/final direfresh tanpa mengubah status: '.$histories->count().' baris');
        $totalPeriods++;
        $totalRows += $histories->count();
    }

    $this->line('');
    $this->info("Selesai. {$totalPeriods} periode direfresh, {$totalRows} baris payroll diperbarui.");

    return 0;
})->purpose('Refresh histori payroll lama ke formula payroll terbaru tanpa mengubah status periode');

Artisan::command('holidays:sync {year?}', function (ApiCoIdHolidaySyncService $syncService): int {
    $year = (int) ($this->argument('year') ?: now()->year);

    try {
        $result = $syncService->syncYear($year);
    } catch (Throwable $exception) {
        $this->error($exception->getMessage());

        return 1;
    }

    $this->info('Sinkron hari libur nasional selesai.');
    $this->line('Tahun: '.$result['year']);
    $this->line('Data API: '.$result['total_api_items']);
    $this->line('Hari tersimpan: '.$result['total_synced_days']);
    $this->line('Baru: '.$result['created']);
    $this->line('Diperbarui: '.$result['updated']);
    $this->line('Manual dipertahankan: '.$result['manual_skipped']);
    $this->line('Dihapus: '.$result['deleted']);

    return 0;
})->purpose('Sinkron hari libur nasional Indonesia dari API.co.id ke tabel lokal');

Artisan::command('queue:notifications-cron', function (): int {
    if (config('queue.default') === 'sync') {
        $this->warn('QUEUE_CONNECTION masih sync. Ubah ke database agar mode cron-only bisa memproses antrean.');

        return 0;
    }

    $queue = (string) config('queue.notifications_queue', 'notifications');
    $tries = max(1, (int) config('queue.cron_tries', 3));
    $timeout = max(30, (int) config('queue.cron_timeout', 120));
    $sleep = max(1, (int) config('queue.cron_sleep', 1));

    $this->line("Memproses queue '{$queue}'...");

    $exitCode = Artisan::call('queue:work', [
        '--queue' => $queue,
        '--stop-when-empty' => true,
        '--tries' => $tries,
        '--timeout' => $timeout,
        '--sleep' => $sleep,
    ]);

    $output = trim(Artisan::output());
    if ($output !== '') {
        $this->newLine();
        $this->output->write($output);
        $this->newLine();
    }

    return (int) $exitCode;
})->purpose('Proses antrean notifikasi WhatsApp sekali jalan untuk cron shared hosting');

Schedule::command('queue:notifications-cron')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn (): bool => (bool) config('queue.cron_only', false));

Artisan::command('storage:cleanup-temp {--tmp-only : Hanya bersihkan storage/app/tmp} {--debug-only : Hanya bersihkan file debug payroll_monthly_summary*.json}', function (): int {
    $cleanTmp = ! (bool) $this->option('debug-only');
    $cleanDebug = ! (bool) $this->option('tmp-only');

    $deletedFiles = 0;
    $deletedDirectories = 0;

    if ($cleanTmp) {
        $tmpPath = storage_path('app/tmp');

        if (File::isDirectory($tmpPath)) {
            foreach (File::allFiles($tmpPath) as $file) {
                if ($file->getFilename() === '.gitignore') {
                    continue;
                }

                File::delete($file->getPathname());
                $deletedFiles++;
            }

            $directories = collect(File::allDirectories($tmpPath))
                ->sortByDesc(fn (string $path) => strlen($path))
                ->values();

            foreach ($directories as $directory) {
                if (blank(File::files($directory)) && blank(File::directories($directory))) {
                    File::deleteDirectory($directory);
                    $deletedDirectories++;
                }
            }
        }
    }

    if ($cleanDebug) {
        $debugFiles = File::glob(storage_path('app/payroll_monthly_summary*.json')) ?: [];

        foreach ($debugFiles as $debugFile) {
            if (! File::exists($debugFile)) {
                continue;
            }

            File::delete($debugFile);
            $deletedFiles++;
        }
    }

    $this->info('Cleanup file temporary selesai.');
    $this->line('File dihapus: '.$deletedFiles);
    $this->line('Folder dihapus: '.$deletedDirectories);
    $this->line('Tmp dibersihkan: '.($cleanTmp ? 'ya' : 'tidak'));
    $this->line('Debug payroll dibersihkan: '.($cleanDebug ? 'ya' : 'tidak'));

    return 0;
})->purpose('Bersihkan file temporary storage/app/tmp dan file debug payroll yang tidak dipakai runtime');
