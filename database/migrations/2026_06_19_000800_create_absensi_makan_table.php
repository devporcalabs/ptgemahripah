<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE `absensi_makan` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `jam_makan` time NOT NULL,
  `jenis_makan` enum('siang','malam','sahur','lembur') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'siang',
  `metode` enum('rfid','web','manual','qr_scan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `lokasi_gps_id` int unsigned DEFAULT NULL,
  `device_id` bigint unsigned DEFAULT NULL,
  `nominal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('valid','dibatalkan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'valid',
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `absensi_makan_karyawan_tanggal_jenis_unique` (`karyawan_id`,`tanggal`,`jenis_makan`),
  KEY `absensi_makan_tanggal_jenis_index` (`tanggal`,`jenis_makan`),
  KEY `absensi_makan_lokasi_gps_id_foreign` (`lokasi_gps_id`),
  KEY `absensi_makan_device_id_foreign` (`device_id`),
  KEY `absensi_makan_created_by_foreign` (`created_by`),
  CONSTRAINT `absensi_makan_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `absensi_makan_lokasi_gps_id_foreign` FOREIGN KEY (`lokasi_gps_id`) REFERENCES `lokasi_gps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `absensi_makan_device_id_foreign` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `absensi_makan_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

        // Add meal time window columns to settings table if not present
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'makan_siang_mulai')) {
                $table->time('makan_siang_mulai')->default('11:30:00')->after('uang_makan');
                $table->time('makan_siang_selesai')->default('13:30:00')->after('makan_siang_mulai');
                $table->time('makan_malam_mulai')->default('18:30:00')->after('makan_siang_selesai');
                $table->time('makan_malam_selesai')->default('20:30:00')->after('makan_malam_mulai');
                $table->time('makan_lembur_mulai')->default('21:00:00')->after('makan_malam_selesai');
                $table->time('makan_lembur_selesai')->default('23:00:00')->after('makan_lembur_mulai');
                $table->boolean('makan_mandiri_enabled')->default(true)->after('makan_lembur_selesai');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_makan');

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'makan_siang_mulai',
                'makan_siang_selesai',
                'makan_malam_mulai',
                'makan_malam_selesai',
                'makan_lembur_mulai',
                'makan_lembur_selesai',
                'makan_mandiri_enabled',
            ]);
        });
    }
};
