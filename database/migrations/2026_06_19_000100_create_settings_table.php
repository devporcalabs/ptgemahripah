<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_instansi` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_instansi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deskripsi_instansi` text COLLATE utf8mb4_unicode_ci,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favicon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `radius_valid` int NOT NULL DEFAULT '100',
  `jam_masuk` time NOT NULL DEFAULT '08:00:00',
  `jam_keluar` time NOT NULL DEFAULT '17:00:00',
  `potongan_terlambat` decimal(5,2) NOT NULL DEFAULT '0.00',
  `gaji_per_hari` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payroll_divisor_bulanan` tinyint unsigned NOT NULL DEFAULT '26',
  `payroll_prorate_method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'work_days',
  `overtime_calculation_mode` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'flat_hourly',
  `bpjs_auto_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `bpjs_kesehatan_company_percent` decimal(5,2) NOT NULL DEFAULT '4.00',
  `bpjs_kesehatan_employee_percent` decimal(5,2) NOT NULL DEFAULT '1.00',
  `bpjs_kesehatan_salary_cap` decimal(12,2) NOT NULL DEFAULT '12000000.00',
  `bpjs_jht_company_percent` decimal(5,2) NOT NULL DEFAULT '3.70',
  `bpjs_jht_employee_percent` decimal(5,2) NOT NULL DEFAULT '2.00',
  `bpjs_jp_company_percent` decimal(5,2) NOT NULL DEFAULT '2.00',
  `bpjs_jp_employee_percent` decimal(5,2) NOT NULL DEFAULT '1.00',
  `bpjs_jp_salary_cap` decimal(12,2) NOT NULL DEFAULT '0.00',
  `bpjs_jkk_company_percent` decimal(5,2) NOT NULL DEFAULT '0.24',
  `bpjs_jkk_risk_level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'very_low',
  `bpjs_jkm_company_percent` decimal(5,2) NOT NULL DEFAULT '0.30',
  `pph21_auto_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `pph21_job_expense_percent` decimal(5,2) NOT NULL DEFAULT '5.00',
  `pph21_job_expense_monthly_cap` decimal(12,2) NOT NULL DEFAULT '500000.00',
  `tax_company_npwp` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_company_nitku` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thr_auto_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `thr_payment_month` tinyint unsigned DEFAULT NULL,
  `thr_payment_date` date DEFAULT NULL,
  `thr_include_tunjangan_jabatan` tinyint(1) NOT NULL DEFAULT '1',
  `thr_include_tunjangan_makan` tinyint(1) NOT NULL DEFAULT '0',
  `thr_include_tunjangan_transport` tinyint(1) NOT NULL DEFAULT '0',
  `leave_quota_policy` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'annual_reset',
  `leave_carryover_max_days` int unsigned NOT NULL DEFAULT '6',
  `leave_quota_policy_effective_year` int unsigned DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `telepon` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `toleransi_terlambat` int NOT NULL DEFAULT '15',
  `potongan_per_menit` decimal(10,2) NOT NULL DEFAULT '1000.00',
  `max_potongan_harian` decimal(10,2) NOT NULL DEFAULT '50000.00',
  `uang_makan` decimal(10,2) NOT NULL DEFAULT '15000.00',
  `tunjangan_transport` decimal(10,2) NOT NULL DEFAULT '10000.00',
  `admin_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_sender` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `notif_absensi` tinyint(1) NOT NULL DEFAULT '1',
  `notif_izin` tinyint(1) NOT NULL DEFAULT '1',
  `notif_gaji` tinyint(1) NOT NULL DEFAULT '1',
  `jam_fleksibel_mulai` time NOT NULL DEFAULT '07:00:00',
  `jam_fleksibel_selesai` time NOT NULL DEFAULT '22:00:00',
  `durasi_kerja_fleksibel` int NOT NULL DEFAULT '8',
  `zona_waktu` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Jakarta',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
