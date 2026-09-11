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
CREATE TABLE `karyawan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nik` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_telp` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan_id` int unsigned DEFAULT NULL,
  `departemen` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departemen_id` int unsigned DEFAULT NULL,
  `gaji_pokok` decimal(12,2) DEFAULT NULL,
  `qr_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rfid_uid` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `gaji_per_hari` decimal(10,2) NOT NULL DEFAULT '100000.00',
  `tipe_penggajian` enum('bulanan','harian') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bulanan',
  `telepon` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_unicode_ci,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift_id` int unsigned DEFAULT NULL,
  `jenis_jam_kerja` enum('tetap','rolling','fleksibel') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tetap',
  `jam_fleksibel_mulai` time DEFAULT NULL,
  `jam_fleksibel_selesai` time DEFAULT NULL,
  `durasi_kerja_fleksibel` decimal(4,2) NOT NULL DEFAULT '8.00',
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `jenis_karyawan` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tetap',
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `gender` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_join` date DEFAULT NULL,
  `tgl_resign` date DEFAULT NULL,
  `alasan_resign` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clearance_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `clearance_payroll_final` tinyint(1) NOT NULL DEFAULT '0',
  `clearance_kasbon_resolved` tinyint(1) NOT NULL DEFAULT '0',
  `clearance_asset_returned` tinyint(1) NOT NULL DEFAULT '0',
  `clearance_access_revoked` tinyint(1) NOT NULL DEFAULT '0',
  `clearance_document_completed` tinyint(1) NOT NULL DEFAULT '0',
  `clearance_notes` text COLLATE utf8mb4_unicode_ci,
  `clearance_completed_at` timestamp NULL DEFAULT NULL,
  `status_nikah` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lokasi_gps_id` int unsigned DEFAULT NULL,
  `masa_berlaku` date DEFAULT NULL,
  `ktp` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kartu_keluarga` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bpjs_kesehatan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bpjs_ketenagakerjaan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bpjs_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `pph21_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'off',
  `thr_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto',
  `thr_manual_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `npwp` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_counterpart_opt` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Resident',
  `tax_passport_number` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_has_second_employer` tinyint(1) NOT NULL DEFAULT '0',
  `tax_prev_withholding_slip_number` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_prev_gross_income` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_prev_pph21_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_prev_retirement_contribution` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_certificate` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N/A',
  `sim` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_pkwt` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_kontrak` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_mulai_pkwt` date DEFAULT NULL,
  `tanggal_berakhir_pkwt` date DEFAULT NULL,
  `nama_bank` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rekening` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_rekening` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `izin_cuti` int unsigned NOT NULL DEFAULT '0',
  `izin_lainnya` int unsigned NOT NULL DEFAULT '0',
  `izin_telat` int unsigned NOT NULL DEFAULT '0',
  `izin_pulang_cepat` int unsigned NOT NULL DEFAULT '0',
  `bonus_pribadi` decimal(12,2) NOT NULL DEFAULT '0.00',
  `bonus_team` decimal(12,2) NOT NULL DEFAULT '0.00',
  `bonus_jackpot` decimal(12,2) NOT NULL DEFAULT '0.00',
  `premi_kehadiran` decimal(12,2) NOT NULL DEFAULT '0.00',
  `premi_kehadiran_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nonaktif',
  `premi_kehadiran_toleransi_telat` int unsigned NOT NULL DEFAULT '0',
  `premi_kehadiran_toleransi_pulang_cepat` int unsigned NOT NULL DEFAULT '0',
  `shift_rotation_ids` json DEFAULT NULL,
  `shift_rotation_start` date DEFAULT NULL,
  `shift_rotation_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  PRIMARY KEY (`id`),
  UNIQUE KEY `karyawan_rfid_uid_unique` (`rfid_uid`),
  KEY `karyawan_nik_index` (`nik`),
  KEY `karyawan_shift_id_foreign` (`shift_id`),
  KEY `karyawan_jabatan_id_foreign` (`jabatan_id`),
  KEY `karyawan_departemen_id_foreign` (`departemen_id`),
  KEY `karyawan_lokasi_gps_id_foreign` (`lokasi_gps_id`),
  CONSTRAINT `karyawan_departemen_id_foreign` FOREIGN KEY (`departemen_id`) REFERENCES `departemen` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `karyawan_jabatan_id_foreign` FOREIGN KEY (`jabatan_id`) REFERENCES `jabatan` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `karyawan_lokasi_gps_id_foreign` FOREIGN KEY (`lokasi_gps_id`) REFERENCES `lokasi_gps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `karyawan_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shift` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        DB::unprepared(<<<'SQL'
CREATE TABLE `komponen_gaji_karyawan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `karyawan_id` int unsigned NOT NULL,
  `gaji_per_hari` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tunjangan_jabatan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tunjangan_makan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tunjangan_transport` decimal(12,2) NOT NULL DEFAULT '0.00',
  `potongan_per_menit` decimal(12,2) NOT NULL DEFAULT '1000.00',
  `potongan_izin` decimal(12,2) NOT NULL DEFAULT '0.00',
  `potongan_mangkir` decimal(12,2) NOT NULL DEFAULT '0.00',
  `potongan_terlambat` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tarif_lembur_per_jam` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tunjangan_bpjs_kesehatan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tunjangan_bpjs_ketenagakerjaan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `potongan_bpjs_kesehatan` decimal(12,2) NOT NULL DEFAULT '0.00',
  `potongan_bpjs_ketenagakerjaan` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `komponen_gaji_karyawan_karyawan_id_unique` (`karyawan_id`),
  CONSTRAINT `komponen_gaji_karyawan_karyawan_id_foreign` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

        if (! Schema::hasColumn('users', 'karyawan_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('karyawan_id')->nullable()->unique()->after('id');
                $table->foreign('karyawan_id')->references('id')->on('karyawan')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'karyawan_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['karyawan_id']);
                $table->dropUnique('users_karyawan_id_unique');
                $table->dropColumn('karyawan_id');
            });
        }

        Schema::dropIfExists('komponen_gaji_karyawan');
        Schema::dropIfExists('karyawan');
    }
};
