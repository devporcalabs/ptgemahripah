<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE `absensi` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int unsigned DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `status` enum('hadir','terlambat','izin','cuti') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lokasi_masuk` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lokasi_keluar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `latitude_keluar` decimal(10,8) DEFAULT NULL,
  `longitude_keluar` decimal(11,8) DEFAULT NULL,
  `menit_terlambat` int NOT NULL DEFAULT '0',
  `menit_pulang_cepat` int NOT NULL DEFAULT '0',
  `menit_lembur` int NOT NULL DEFAULT '0',
  `jam_lembur` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tarif_lembur` decimal(12,2) NOT NULL DEFAULT '0.00',
  `shift_id` int unsigned DEFAULT NULL,
  `shift_assignment_id` int unsigned DEFAULT NULL,
  `schedule_source` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_jam_kerja` enum('shift','tetap','rolling','fleksibel') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tetap',
  `jam_fleksibel_mulai` time DEFAULT NULL,
  `jam_fleksibel_selesai` time DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `absensi_karyawan_id_tanggal_index` (`karyawan_id`,`tanggal`),
  KEY `absensi_shift_id_foreign` (`shift_id`),
  KEY `absensi_shift_assignment_id_foreign` (`shift_assignment_id`),
  CONSTRAINT `absensi_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE SET NULL,
  CONSTRAINT `absensi_shift_assignment_id_foreign` FOREIGN KEY (`shift_assignment_id`) REFERENCES `shift_assignments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `absensi_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shift` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `absensi_selfie` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `absensi_id` int unsigned NOT NULL,
  `karyawan_id` int unsigned NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `absensi_selfie_absensi_id_foreign` (`absensi_id`),
  KEY `absensi_selfie_karyawan_id_foreign` (`karyawan_id`),
  CONSTRAINT `absensi_selfie_absensi_id_foreign` FOREIGN KEY (`absensi_id`) REFERENCES `absensi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `absensi_selfie_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `attendance_corrections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `absensi_id` int unsigned NOT NULL,
  `previous_data` json DEFAULT NULL,
  `requested_data` json NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `review_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_corrections_absensi_id_status_index` (`absensi_id`,`status`),
  KEY `attendance_corrections_created_by_foreign` (`created_by`),
  KEY `attendance_corrections_approved_by_foreign` (`approved_by`),
  CONSTRAINT `attendance_corrections_absensi_id_foreign` FOREIGN KEY (`absensi_id`) REFERENCES `absensi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_corrections_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_corrections_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `gps_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int unsigned DEFAULT NULL,
  `activity` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `accuracy` decimal(8,2) DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `gps_logs_karyawan_id_foreign` (`karyawan_id`),
  CONSTRAINT `gps_logs_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    }

    public function down(): void
    {
        Schema::dropIfExists('gps_logs');
        Schema::dropIfExists('attendance_corrections');
        Schema::dropIfExists('absensi_selfie');
        Schema::dropIfExists('absensi');
    }
};
