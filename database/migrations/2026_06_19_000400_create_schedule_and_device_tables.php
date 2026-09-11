<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE `jadwal_kerja` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int unsigned NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift_id` int unsigned DEFAULT NULL,
  `lokasi_gps_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `jadwal_kerja_karyawan_id_hari_index` (`karyawan_id`,`hari`),
  KEY `jadwal_kerja_shift_id_foreign` (`shift_id`),
  KEY `jadwal_kerja_lokasi_gps_id_foreign` (`lokasi_gps_id`),
  CONSTRAINT `jadwal_kerja_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `jadwal_kerja_lokasi_gps_id_foreign` FOREIGN KEY (`lokasi_gps_id`) REFERENCES `lokasi_gps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `jadwal_kerja_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shift` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `shift_assignments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int unsigned NOT NULL,
  `shift_id` int unsigned DEFAULT NULL,
  `lokasi_gps_id` int unsigned DEFAULT NULL,
  `tanggal` date NOT NULL,
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_assignments_karyawan_id_tanggal_unique` (`karyawan_id`,`tanggal`),
  KEY `shift_assignments_shift_id_tanggal_index` (`shift_id`,`tanggal`),
  KEY `shift_assignments_lokasi_gps_id_foreign` (`lokasi_gps_id`),
  KEY `shift_assignments_created_by_foreign` (`created_by`),
  CONSTRAINT `shift_assignments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shift_assignments_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_assignments_lokasi_gps_id_foreign` FOREIGN KEY (`lokasi_gps_id`) REFERENCES `lokasi_gps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shift_assignments_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shift` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mac_address` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firmware_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `last_seen` timestamp NULL DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `devices_serial_number_unique` (`serial_number`),
  UNIQUE KEY `devices_mac_address_unique` (`mac_address`),
  UNIQUE KEY `devices_device_token_unique` (`device_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `attendance_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `device_id` bigint unsigned NOT NULL,
  `uid` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scanned_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_logs_device_id_foreign` (`device_id`),
  KEY `attendance_logs_uid_scanned_at_index` (`uid`,`scanned_at`),
  CONSTRAINT `attendance_logs_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('shift_assignments');
        Schema::dropIfExists('jadwal_kerja');
    }
};
