<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\GajiKaryawan;
use App\Models\GpsLog;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\Setting;
use App\Models\WhatsappLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemController extends Controller
{
    private const PARTIAL_RESET_TABLES = [
        'absensi_selfie',
        'absensi',
        'izin',
        'lembur',
        'gaji_karyawan',
        'gaji_tambahan',
        'gps_logs',
        'whatsapp_logs',
    ];

    private const FULL_RESET_EXCLUDES = [
        'users',
        'settings',
        'shift',
        'lokasi_gps',
        'komponen_gaji',
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
        'password_reset_tokens',
    ];

    public function index()
    {
        $settings = Setting::query()->find(1);
        $mysqlVersion = optional(DB::selectOne('SELECT VERSION() as version'))->version;

        return view('admin.system', [
            'settings' => $settings,
            'jkkRiskOptions' => config('payroll_compliance.bpjs.jkk_risk_levels', []),
            'overtimeModeOptions' => [
                Setting::OVERTIME_MODE_FLAT_HOURLY => 'Jam Tetap',
                Setting::OVERTIME_MODE_UU_CIPTA_KERJA => 'Aturan UU Cipta Kerja',
            ],
            'timezoneOptions' => [
                'Asia/Jakarta' => 'WIB (UTC+7)',
                'Asia/Makassar' => 'WITA (UTC+8)',
                'Asia/Jayapura' => 'WIT (UTC+9)',
            ],
            'stats' => [
                'karyawan' => Karyawan::query()->count(),
                'absensi' => Absensi::query()->count(),
                'izin' => Izin::query()->count(),
                'gaji' => GajiKaryawan::query()->count(),
                'gps_logs' => GpsLog::query()->count(),
                'whatsapp_logs' => WhatsappLog::query()->count(),
            ],
            'mysqlVersion' => $mysqlVersion,
            'serverSoftware' => request()->server('SERVER_SOFTWARE'),
        ]);
    }

    public function payroll()
    {
        $settings = Setting::query()->find(1);

        return view('admin.payroll-settings', [
            'settings' => $settings,
            'jkkRiskOptions' => config('payroll_compliance.bpjs.jkk_risk_levels', []),
            'overtimeModeOptions' => [
                Setting::OVERTIME_MODE_FLAT_HOURLY => 'Jam Tetap',
                Setting::OVERTIME_MODE_UU_CIPTA_KERJA => 'Aturan UU Cipta Kerja',
            ],
        ]);
    }

    public function updateTimezone(Request $request)
    {
        $validated = $request->validate([
            'zona_waktu' => ['required', 'in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura'],
        ]);

        Setting::query()->updateOrCreate(
            ['id' => 1],
            ['zona_waktu' => $validated['zona_waktu']],
        );

        return $this->respondSuccess($request, route('admin.sistem'), 'Zona waktu berhasil diperbarui.');
    }

    public function updatePayrollSettings(Request $request)
    {
        $jkkRiskKeys = array_keys(config('payroll_compliance.bpjs.jkk_risk_levels', []));
        $validated = $request->validate([
            'payroll_divisor_bulanan' => ['required', 'integer', 'min:1', 'max:31'],
            'payroll_prorate_method' => ['required', 'in:work_days,calendar_days'],
            'overtime_calculation_mode' => ['required', Rule::in([Setting::OVERTIME_MODE_FLAT_HOURLY, Setting::OVERTIME_MODE_UU_CIPTA_KERJA])],
            'gaji_per_hari' => ['required', 'string', 'max:30'],
            'potongan_per_menit' => ['required', 'string', 'max:30'],
            'max_potongan_harian' => ['required', 'string', 'max:30'],
            'bpjs_auto_enabled' => ['required', 'boolean'],
            'bpjs_kesehatan_company_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'bpjs_kesehatan_employee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'bpjs_kesehatan_salary_cap' => ['required', 'string', 'max:30'],
            'bpjs_jht_company_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'bpjs_jht_employee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'bpjs_jp_company_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'bpjs_jp_employee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'bpjs_jp_salary_cap' => ['required', 'string', 'max:30'],
            'bpjs_jkk_risk_level' => ['required', Rule::in($jkkRiskKeys)],
            'bpjs_jkk_company_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'bpjs_jkm_company_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'pph21_auto_enabled' => ['required', 'boolean'],
            'pph21_job_expense_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'pph21_job_expense_monthly_cap' => ['required', 'string', 'max:30'],
            'tax_company_npwp' => ['nullable', 'string', 'max:32'],
            'tax_company_nitku' => ['nullable', 'string', 'max:32'],
            'thr_auto_enabled' => ['required', 'boolean'],
            'thr_payment_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'thr_payment_date' => ['nullable', 'date'],
            'thr_include_tunjangan_jabatan' => ['required', 'boolean'],
            'thr_include_tunjangan_makan' => ['required', 'boolean'],
            'thr_include_tunjangan_transport' => ['required', 'boolean'],
        ]);

        $gajiPerHari = $this->parseAmount($validated['gaji_per_hari']);
        $potonganPerMenit = $this->parseAmount($validated['potongan_per_menit']);
        $maxPotonganHarian = $this->parseAmount($validated['max_potongan_harian']);
        $bpjsKesehatanSalaryCap = $this->parseAmount($validated['bpjs_kesehatan_salary_cap']);
        $bpjsJpSalaryCap = $this->parseAmount($validated['bpjs_jp_salary_cap']);
        $pph21JobExpenseMonthlyCap = $this->parseAmount($validated['pph21_job_expense_monthly_cap']);
        $bpjsJkkCompanyPercent = $this->resolveJkkRate(
            (string) $validated['bpjs_jkk_risk_level'],
            (float) $validated['bpjs_jkk_company_percent'],
        );

        Setting::query()->updateOrCreate(
            ['id' => 1],
            [
                'payroll_divisor_bulanan' => (int) $validated['payroll_divisor_bulanan'],
                'payroll_prorate_method' => $validated['payroll_prorate_method'],
                'overtime_calculation_mode' => $validated['overtime_calculation_mode'],
                'gaji_per_hari' => $gajiPerHari,
                'potongan_per_menit' => $potonganPerMenit,
                'max_potongan_harian' => $maxPotonganHarian,
                'bpjs_auto_enabled' => (bool) $validated['bpjs_auto_enabled'],
                'bpjs_kesehatan_company_percent' => (float) $validated['bpjs_kesehatan_company_percent'],
                'bpjs_kesehatan_employee_percent' => (float) $validated['bpjs_kesehatan_employee_percent'],
                'bpjs_kesehatan_salary_cap' => $bpjsKesehatanSalaryCap,
                'bpjs_jht_company_percent' => (float) $validated['bpjs_jht_company_percent'],
                'bpjs_jht_employee_percent' => (float) $validated['bpjs_jht_employee_percent'],
                'bpjs_jp_company_percent' => (float) $validated['bpjs_jp_company_percent'],
                'bpjs_jp_employee_percent' => (float) $validated['bpjs_jp_employee_percent'],
                'bpjs_jp_salary_cap' => $bpjsJpSalaryCap,
                'bpjs_jkk_risk_level' => $validated['bpjs_jkk_risk_level'],
                'bpjs_jkk_company_percent' => $bpjsJkkCompanyPercent,
                'bpjs_jkm_company_percent' => (float) $validated['bpjs_jkm_company_percent'],
                'pph21_auto_enabled' => (bool) $validated['pph21_auto_enabled'],
                'pph21_job_expense_percent' => (float) $validated['pph21_job_expense_percent'],
                'pph21_job_expense_monthly_cap' => $pph21JobExpenseMonthlyCap,
                'tax_company_npwp' => $this->normalizeDigits($validated['tax_company_npwp'] ?? null),
                'tax_company_nitku' => $this->normalizeDigits($validated['tax_company_nitku'] ?? null),
                'thr_auto_enabled' => (bool) $validated['thr_auto_enabled'],
                'thr_payment_month' => ! empty($validated['thr_payment_month']) ? (int) $validated['thr_payment_month'] : null,
                'thr_payment_date' => ! empty($validated['thr_payment_date']) ? $validated['thr_payment_date'] : null,
                'thr_include_tunjangan_jabatan' => (bool) $validated['thr_include_tunjangan_jabatan'],
                'thr_include_tunjangan_makan' => (bool) $validated['thr_include_tunjangan_makan'],
                'thr_include_tunjangan_transport' => (bool) $validated['thr_include_tunjangan_transport'],
            ],
        );

        return $this->respondSuccess($request, route('admin.payroll-settings'), 'Pengaturan payroll berhasil diperbarui.');
    }

    private function parseAmount(mixed $value): float
    {
        $normalized = preg_replace('/[^0-9,.-]/', '', trim((string) $value)) ?? '';
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : 0;
    }

    private function resolveJkkRate(string $riskLevel, float $customRate): float
    {
        $config = config('payroll_compliance.bpjs.jkk_risk_levels.'.$riskLevel);

        if (is_array($config) && $riskLevel !== 'custom' && array_key_exists('percent', $config) && $config['percent'] !== null) {
            return (float) $config['percent'];
        }

        return max(0, $customRate);
    }

    private function normalizeDigits(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', trim((string) ($value ?? ''))) ?? '';

        return $digits !== '' ? $digits : null;
    }

    public function downloadBackup(): StreamedResponse
    {
        $tables = $this->baseTableNames();
        $pdo = DB::connection()->getPdo();
        $fileName = 'backup_absensi_'.Carbon::now()->format('Y-m-d_H-i-s').'.sql';

        return response()->streamDownload(function () use ($tables, $pdo): void {
            echo "-- Database Backup\n";
            echo '-- Generated: '.now()->format('Y-m-d H:i:s')."\n";
            echo '-- Timezone: '.config('app.timezone')."\n\n";

            foreach ($tables as $table) {
                $createRow = (array) DB::select("SHOW CREATE TABLE `{$table}`")[0];
                $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? '';

                echo "-- Table structure for `{$table}`\n";
                echo "DROP TABLE IF EXISTS `{$table}`;\n";
                echo $createSql.";\n\n";

                $rows = DB::select("SELECT * FROM `{$table}`");

                if ($rows === []) {
                    continue;
                }

                echo "-- Dumping data for `{$table}`\n";

                foreach ($rows as $row) {
                    $values = [];

                    foreach ((array) $row as $value) {
                        $values[] = $value === null ? 'NULL' : $pdo->quote((string) $value);
                    }

                    echo "INSERT INTO `{$table}` VALUES (".implode(', ', $values).");\n";
                }

                echo "\n";
            }
        }, $fileName, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function resetData(Request $request)
    {
        $request->validate([
            'confirm_reset' => ['required', 'in:RESET'],
        ], [
            'confirm_reset.in' => 'Ketik RESET untuk melanjutkan.',
        ]);

        DB::beginTransaction();

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach (self::PARTIAL_RESET_TABLES as $table) {
                DB::statement("TRUNCATE TABLE `{$table}`");
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();
        } catch (\Throwable $throwable) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::rollBack();

            return $this->respondError($request, route('admin.sistem'), 'Reset data gagal: '.$throwable->getMessage(), status: 500);
        }

        return $this->respondSuccess($request, route('admin.sistem'), 'Data operasional berhasil direset.');
    }

    public function resetAll(Request $request)
    {
        $request->validate([
            'confirm_reset_all' => ['required', 'in:RESET ALL'],
        ], [
            'confirm_reset_all.in' => 'Ketik RESET ALL untuk melanjutkan.',
        ]);

        DB::beginTransaction();

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($this->baseTableNames() as $table) {
                if (in_array($table, self::FULL_RESET_EXCLUDES, true)) {
                    continue;
                }

                DB::statement("TRUNCATE TABLE `{$table}`");
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();
        } catch (\Throwable $throwable) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::rollBack();

            return $this->respondError($request, route('admin.sistem'), 'Reset penuh gagal: '.$throwable->getMessage(), status: 500);
        }

        return $this->respondSuccess($request, route('admin.sistem'), 'Semua data non-master berhasil direset.');
    }

    private function baseTableNames(): array
    {
        return collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->all();
    }
}
