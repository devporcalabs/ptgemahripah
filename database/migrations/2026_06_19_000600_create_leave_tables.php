<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE `jenis_izin` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `legacy_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lainnya',
  `badge_class` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'badge-secondary',
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `deduct_quota` tinyint(1) NOT NULL DEFAULT '0',
  `quota_field` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `annual_quota_days` int unsigned DEFAULT NULL,
  `max_days_per_request` int unsigned DEFAULT NULL,
  `require_attachment` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jenis_izin_kode_unique` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `izin` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int unsigned DEFAULT NULL,
  `jenis_izin_id` bigint unsigned DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `tanggal_izin` date DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `jumlah_hari` int unsigned NOT NULL DEFAULT '1',
  `tanggal_pengajuan` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_disetujui` timestamp NULL DEFAULT NULL,
  `disetujui_oleh` bigint unsigned DEFAULT NULL,
  `catatan_admin` text COLLATE utf8mb4_unicode_ci,
  `bukti` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_izin` enum('sakit','cuti','lainnya') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alasan` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','disetujui','ditolak') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `izin_karyawan_id_tanggal_izin_index` (`karyawan_id`,`tanggal_izin`),
  KEY `izin_disetujui_oleh_foreign` (`disetujui_oleh`),
  KEY `izin_jenis_izin_id_index` (`jenis_izin_id`),
  KEY `izin_tanggal_mulai_tanggal_selesai_index` (`tanggal_mulai`,`tanggal_selesai`),
  CONSTRAINT `izin_disetujui_oleh_foreign` FOREIGN KEY (`disetujui_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `izin_jenis_izin_id_foreign` FOREIGN KEY (`jenis_izin_id`) REFERENCES `jenis_izin` (`id`) ON DELETE SET NULL,
  CONSTRAINT `izin_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `hari_libur` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `nama_libur` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `source` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `external_id` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_type` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sync_meta` json DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hari_libur_tanggal_unique` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `lembur` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int unsigned NOT NULL,
  `tanggal_lembur` date NOT NULL,
  `waktu_mulai` time NOT NULL,
  `waktu_selesai` time NOT NULL,
  `durasi_jam` decimal(5,2) NOT NULL,
  `alasan` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','disetujui','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `catatan_admin` text COLLATE utf8mb4_unicode_ci,
  `jam_lembur` decimal(5,2) DEFAULT NULL,
  `tarif_lembur` decimal(12,2) DEFAULT NULL,
  `disetujui_oleh` bigint unsigned DEFAULT NULL,
  `tanggal_pengajuan` datetime DEFAULT CURRENT_TIMESTAMP,
  `tanggal_disetujui` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lembur_karyawan_id_tanggal_lembur_index` (`karyawan_id`,`tanggal_lembur`),
  KEY `lembur_disetujui_oleh_foreign` (`disetujui_oleh`),
  CONSTRAINT `lembur_disetujui_oleh_foreign` FOREIGN KEY (`disetujui_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lembur_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

    }

    public function down(): void
    {
        Schema::dropIfExists('lembur');
        Schema::dropIfExists('hari_libur');
        Schema::dropIfExists('izin');
        Schema::dropIfExists('jenis_izin');
    }
};
