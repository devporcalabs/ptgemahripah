<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::query()->firstOrCreate(
            ['id' => 1],
            [
                'nama_instansi' => env('SEED_NAMA_INSTANSI', config('app.name')),
                'deskripsi_instansi' => env('SEED_DESKRIPSI_INSTANSI', config('app.name')),
                'latitude' => env('SEED_LATITUDE', -6.20000000),
                'longitude' => env('SEED_LONGITUDE', 106.81666667),
                'favicon' => env('SEED_FAVICON'),
                'radius_valid' => env('SEED_RADIUS_VALID', 100),
                'jam_masuk' => env('SEED_JAM_MASUK', '08:00:00'),
                'jam_keluar' => env('SEED_JAM_KELUAR', '17:00:00'),
                'potongan_terlambat' => env('SEED_POTONGAN_TERLAMBAT', 0),
                'gaji_per_hari' => env('SEED_GAJI_PER_HARI', 100000),
                'payroll_divisor_bulanan' => env('SEED_PAYROLL_DIVISOR_BULANAN', 26),
                'payroll_prorate_method' => env('SEED_PAYROLL_PRORATE_METHOD', 'work_days'),
                'overtime_calculation_mode' => env('SEED_OVERTIME_CALCULATION_MODE', \App\Models\Setting::OVERTIME_MODE_FLAT_HOURLY),
                'bpjs_auto_enabled' => filter_var(env('SEED_BPJS_AUTO_ENABLED', true), FILTER_VALIDATE_BOOL),
                'bpjs_kesehatan_company_percent' => env('SEED_BPJS_KESEHATAN_COMPANY_PERCENT', 4),
                'bpjs_kesehatan_employee_percent' => env('SEED_BPJS_KESEHATAN_EMPLOYEE_PERCENT', 1),
                'bpjs_kesehatan_salary_cap' => env('SEED_BPJS_KESEHATAN_SALARY_CAP', 12000000),
                'bpjs_jht_company_percent' => env('SEED_BPJS_JHT_COMPANY_PERCENT', 3.7),
                'bpjs_jht_employee_percent' => env('SEED_BPJS_JHT_EMPLOYEE_PERCENT', 2),
                'bpjs_jp_company_percent' => env('SEED_BPJS_JP_COMPANY_PERCENT', 2),
                'bpjs_jp_employee_percent' => env('SEED_BPJS_JP_EMPLOYEE_PERCENT', 1),
                'bpjs_jp_salary_cap' => env('SEED_BPJS_JP_SALARY_CAP', config('payroll_compliance.bpjs.jp_salary_cap', 10547400)),
                'bpjs_jkk_risk_level' => env('SEED_BPJS_JKK_RISK_LEVEL', 'very_low'),
                'bpjs_jkk_company_percent' => env('SEED_BPJS_JKK_COMPANY_PERCENT', 0.24),
                'bpjs_jkm_company_percent' => env('SEED_BPJS_JKM_COMPANY_PERCENT', 0.3),
                'pph21_auto_enabled' => filter_var(env('SEED_PPH21_AUTO_ENABLED', true), FILTER_VALIDATE_BOOL),
                'pph21_job_expense_percent' => env('SEED_PPH21_JOB_EXPENSE_PERCENT', 5),
                'pph21_job_expense_monthly_cap' => env('SEED_PPH21_JOB_EXPENSE_MONTHLY_CAP', 500000),
                'tax_company_npwp' => env('SEED_TAX_COMPANY_NPWP'),
                'tax_company_nitku' => env('SEED_TAX_COMPANY_NITKU'),
                'thr_auto_enabled' => filter_var(env('SEED_THR_AUTO_ENABLED', false), FILTER_VALIDATE_BOOL),
                'thr_payment_month' => env('SEED_THR_PAYMENT_MONTH'),
                'thr_payment_date' => env('SEED_THR_PAYMENT_DATE'),
                'thr_include_tunjangan_jabatan' => filter_var(env('SEED_THR_INCLUDE_TUNJANGAN_JABATAN', true), FILTER_VALIDATE_BOOL),
                'thr_include_tunjangan_makan' => filter_var(env('SEED_THR_INCLUDE_TUNJANGAN_MAKAN', false), FILTER_VALIDATE_BOOL),
                'thr_include_tunjangan_transport' => filter_var(env('SEED_THR_INCLUDE_TUNJANGAN_TRANSPORT', false), FILTER_VALIDATE_BOOL),
                'alamat' => env('SEED_ALAMAT_INSTANSI', ''),
                'telepon' => env('SEED_TELP_INSTANSI', ''),
                'email' => env('SEED_EMAIL_INSTANSI'),
                'website' => env('SEED_WEBSITE_INSTANSI'),
                'toleransi_terlambat' => env('SEED_TOLERANSI_TERLAMBAT', 15),
                'potongan_per_menit' => env('SEED_POTONGAN_PER_MENIT', 1000),
                'max_potongan_harian' => env('SEED_MAX_POTONGAN_HARIAN', 50000),
                'uang_makan' => env('SEED_UANG_MAKAN', 15000),
                'tunjangan_transport' => env('SEED_TUNJANGAN_TRANSPORT', 10000),
                'whatsapp_api_key' => env('SEED_WHATSAPP_API_KEY'),
                'whatsapp_sender' => env('SEED_WHATSAPP_SENDER'),
                'whatsapp_enabled' => filter_var(env('SEED_WHATSAPP_ENABLED', false), FILTER_VALIDATE_BOOL),
                'notif_absensi' => true,
                'notif_izin' => true,
                'notif_gaji' => true,
                'jam_fleksibel_mulai' => env('SEED_JAM_FLEKSIBEL_MULAI', '07:00:00'),
                'jam_fleksibel_selesai' => env('SEED_JAM_FLEKSIBEL_SELESAI', '22:00:00'),
                'durasi_kerja_fleksibel' => env('SEED_DURASI_FLEKSIBEL', 8),
                'zona_waktu' => env('APP_TIMEZONE', 'Asia/Jakarta'),
            ],
        );
    }
}
