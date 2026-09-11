<?php

namespace Database\Seeders;

use App\Models\JenisIzin;
use Illuminate\Database\Seeder;

class JenisIzinSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'kode' => 'sakit',
                'nama' => 'Sakit',
                'description' => 'Izin sakit dibayar. Bukti pendukung wajib dilampirkan bila diperlukan.',
                'legacy_code' => 'sakit',
                'badge_class' => 'badge-info',
                'is_paid' => true,
                'deduct_quota' => false,
                'quota_field' => null,
                'annual_quota_days' => null,
                'max_days_per_request' => null,
                'require_attachment' => true,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'kode' => 'cuti_tahunan',
                'nama' => 'Cuti Tahunan',
                'description' => 'Cuti reguler yang memotong kuota cuti tahunan karyawan.',
                'legacy_code' => 'cuti',
                'badge_class' => 'badge-primary',
                'is_paid' => true,
                'deduct_quota' => true,
                'quota_field' => 'izin_cuti',
                'annual_quota_days' => null,
                'max_days_per_request' => null,
                'require_attachment' => false,
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'kode' => 'izin_pribadi',
                'nama' => 'Izin Pribadi',
                'description' => 'Izin tidak dibayar yang memotong kuota izin pribadi karyawan.',
                'legacy_code' => 'lainnya',
                'badge_class' => 'badge-secondary',
                'is_paid' => false,
                'deduct_quota' => true,
                'quota_field' => 'izin_lainnya',
                'annual_quota_days' => null,
                'max_days_per_request' => null,
                'require_attachment' => false,
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'kode' => 'cuti_menikah',
                'nama' => 'Cuti Menikah',
                'description' => 'Cuti dibayar untuk kebutuhan menikah dengan kuota default 3 hari kerja per tahun.',
                'legacy_code' => 'cuti',
                'badge_class' => 'badge-primary',
                'is_paid' => true,
                'deduct_quota' => false,
                'quota_field' => null,
                'annual_quota_days' => 3,
                'max_days_per_request' => 3,
                'require_attachment' => true,
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'kode' => 'cuti_duka',
                'nama' => 'Cuti Duka',
                'description' => 'Cuti dibayar untuk kedukaan dengan kuota default 2 hari kerja per tahun.',
                'legacy_code' => 'cuti',
                'badge_class' => 'badge-primary',
                'is_paid' => true,
                'deduct_quota' => false,
                'quota_field' => null,
                'annual_quota_days' => 2,
                'max_days_per_request' => 2,
                'require_attachment' => false,
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'kode' => 'cuti_melahirkan',
                'nama' => 'Cuti Melahirkan',
                'description' => 'Cuti dibayar dengan kuota default 78 hari kerja per tahun. Sesuaikan lagi bila kebijakan perusahaan berbeda.',
                'legacy_code' => 'cuti',
                'badge_class' => 'badge-primary',
                'is_paid' => true,
                'deduct_quota' => false,
                'quota_field' => null,
                'annual_quota_days' => 78,
                'max_days_per_request' => 78,
                'require_attachment' => true,
                'is_active' => true,
                'sort_order' => 60,
            ],
            [
                'kode' => 'dinas_luar',
                'nama' => 'Dinas Luar',
                'description' => 'Penugasan luar kantor yang tetap dibayar dan tidak memotong kuota.',
                'legacy_code' => 'cuti',
                'badge_class' => 'badge-info',
                'is_paid' => true,
                'deduct_quota' => false,
                'quota_field' => null,
                'annual_quota_days' => null,
                'max_days_per_request' => null,
                'require_attachment' => false,
                'is_active' => true,
                'sort_order' => 70,
            ],
            [
                'kode' => 'cuti_khusus',
                'nama' => 'Cuti Khusus (Legacy)',
                'description' => 'Tipe lama. Dipertahankan hanya untuk histori data lama.',
                'legacy_code' => 'cuti',
                'badge_class' => 'badge-secondary',
                'is_paid' => true,
                'deduct_quota' => false,
                'quota_field' => null,
                'annual_quota_days' => null,
                'max_days_per_request' => null,
                'require_attachment' => false,
                'is_active' => false,
                'sort_order' => 999,
            ],
        ];

        foreach ($rows as $row) {
            JenisIzin::query()->updateOrCreate(
                ['kode' => $row['kode']],
                $row
            );
        }
    }
}
