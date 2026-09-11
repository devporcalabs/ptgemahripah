<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE `payroll_periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `periode` date NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `cutoff_absensi` date DEFAULT NULL,
  `status` enum('draft','processing','finalized') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `generated_by` bigint unsigned DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `submitted_by` bigint unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_stage_one_by` bigint unsigned DEFAULT NULL,
  `approved_stage_one_at` timestamp NULL DEFAULT NULL,
  `approved_stage_two_by` bigint unsigned DEFAULT NULL,
  `approved_stage_two_at` timestamp NULL DEFAULT NULL,
  `finalized_by` bigint unsigned DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `salary_whatsapp_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary_whatsapp_queued_at` timestamp NULL DEFAULT NULL,
  `salary_whatsapp_sent_at` timestamp NULL DEFAULT NULL,
  `salary_whatsapp_total` int unsigned NOT NULL DEFAULT '0',
  `salary_whatsapp_success` int unsigned NOT NULL DEFAULT '0',
  `salary_whatsapp_failed` int unsigned NOT NULL DEFAULT '0',
  `salary_whatsapp_skipped` int unsigned NOT NULL DEFAULT '0',
  `salary_whatsapp_last_error` text COLLATE utf8mb4_unicode_ci,
  `reopened_by` bigint unsigned DEFAULT NULL,
  `reopened_at` timestamp NULL DEFAULT NULL,
  `total_karyawan` int unsigned NOT NULL DEFAULT '0',
  `total_gaji` decimal(14,2) NOT NULL DEFAULT '0.00',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_periods_periode_unique` (`periode`),
  KEY `payroll_periods_generated_by_foreign` (`generated_by`),
  KEY `payroll_periods_finalized_by_foreign` (`finalized_by`),
  KEY `payroll_periods_reopened_by_foreign` (`reopened_by`),
  KEY `payroll_periods_submitted_by_foreign` (`submitted_by`),
  KEY `payroll_periods_approved_stage_one_by_foreign` (`approved_stage_one_by`),
  KEY `payroll_periods_approved_stage_two_by_foreign` (`approved_stage_two_by`),
  CONSTRAINT `payroll_periods_approved_stage_one_by_foreign` FOREIGN KEY (`approved_stage_one_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_periods_approved_stage_two_by_foreign` FOREIGN KEY (`approved_stage_two_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_periods_finalized_by_foreign` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_periods_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_periods_reopened_by_foreign` FOREIGN KEY (`reopened_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_periods_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `gaji_karyawan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `payroll_period_id` bigint unsigned DEFAULT NULL,
  `karyawan_id` int unsigned NOT NULL,
  `bulan` date NOT NULL,
  `gaji_pokok` decimal(12,2) NOT NULL,
  `gaji_per_hari` decimal(12,2) DEFAULT NULL,
  `total_hadir` int NOT NULL DEFAULT '0',
  `total_terlambat` int NOT NULL DEFAULT '0',
  `total_izin` int NOT NULL DEFAULT '0',
  `total_lembur` decimal(12,2) NOT NULL DEFAULT '0.00',
  `thr` decimal(12,2) NOT NULL DEFAULT '0.00',
  `gaji_kehadiran` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tunjangan_makan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tunjangan_transport` decimal(12,2) NOT NULL DEFAULT '0.00',
  `potongan_terlambat` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pph21` decimal(12,2) NOT NULL DEFAULT '0.00',
  `prorate_ratio` decimal(8,4) NOT NULL DEFAULT '1.0000',
  `total_gaji` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('proses','selesai') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proses',
  `is_finalized` tinyint(1) NOT NULL DEFAULT '0',
  `finalized_by` bigint unsigned DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `slip_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detail_payload` json DEFAULT NULL,
  `applied_kasbon_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `manual_kasbon_amount` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `gaji_karyawan_karyawan_id_bulan_index` (`karyawan_id`,`bulan`),
  KEY `gaji_karyawan_finalized_by_foreign` (`finalized_by`),
  KEY `gaji_karyawan_period_employee_idx` (`payroll_period_id`,`karyawan_id`),
  CONSTRAINT `gaji_karyawan_finalized_by_foreign` FOREIGN KEY (`finalized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gaji_karyawan_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gaji_karyawan_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `gaji_tambahan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int unsigned NOT NULL,
  `bulan` date NOT NULL,
  `jenis` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jumlah` decimal(12,2) NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gaji_tambahan_karyawan_id_bulan_index` (`karyawan_id`,`bulan`),
  KEY `gaji_tambahan_created_by_foreign` (`created_by`),
  CONSTRAINT `gaji_tambahan_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gaji_tambahan_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `kasbon_mutations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `arah` enum('plus','minus') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `kasbon_awal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `kasbon_akhir` decimal(14,2) NOT NULL DEFAULT '0.00',
  `referensi_tipe` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referensi_id` int unsigned DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `meta` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kasbon_mutations_employee_date_idx` (`karyawan_id`,`tanggal`),
  KEY `kasbon_mutations_reference_idx` (`referensi_tipe`,`referensi_id`),
  KEY `kasbon_mutations_created_by_foreign` (`created_by`),
  CONSTRAINT `kasbon_mutations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kasbon_mutations_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `whatsapp_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `response` text COLLATE utf8mb4_unicode_ci,
  `status` enum('success','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'failed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
        Schema::dropIfExists('kasbon_mutations');
        Schema::dropIfExists('gaji_tambahan');
        Schema::dropIfExists('gaji_karyawan');
        Schema::dropIfExists('payroll_periods');
    }
};
