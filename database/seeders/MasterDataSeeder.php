<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\HariLibur;
use App\Models\KomponenGaji;
use App\Models\LokasiGps;
use App\Models\Shift;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Lokasi GPS
        $locations = [
            [
                'nama_lokasi' => 'Kantor Pusat PT Gemah Ripah',
                'latitude' => -6.20880000,
                'longitude' => 106.84560000,
                'radius' => 150,
                'alamat' => 'Gedung Gemah Ripah Tower Lt. 12-15, Jl. Jenderal Sudirman Kav. 88, Jakarta Pusat',
                'status' => 1,
                'is_default' => 1,
            ],
            [
                'nama_lokasi' => 'Pabrik & Gudang Logistik Cikarang',
                'latitude' => -6.30120000,
                'longitude' => 107.15240000,
                'radius' => 200,
                'alamat' => 'Kawasan Industri MM2100, Blok C-3 No. 12, Cikarang Barat, Bekasi',
                'status' => 1,
                'is_default' => 0,
            ],
            [
                'nama_lokasi' => 'Kantor Cabang Bandung',
                'latitude' => -6.91750000,
                'longitude' => 107.61910000,
                'radius' => 120,
                'alamat' => 'Jl. Asia Afrika No. 45, Sumur Bandung, Kota Bandung, Jawa Barat',
                'status' => 1,
                'is_default' => 0,
            ],
            [
                'nama_lokasi' => 'Kantor Cabang Surabaya',
                'latitude' => -7.25750000,
                'longitude' => 112.75210000,
                'radius' => 120,
                'alamat' => 'Jl. Pemuda No. 12, Genteng, Kota Surabaya, Jawa Timur',
                'status' => 1,
                'is_default' => 0,
            ],
        ];

        foreach ($locations as $loc) {
            LokasiGps::query()->updateOrCreate(
                ['nama_lokasi' => $loc['nama_lokasi']],
                $loc
            );
        }

        // 2. Shifts
        $shifts = [
            [
                'nama_shift' => 'Shift Pagi',
                'jam_masuk' => '07:00:00',
                'jam_keluar' => '15:00:00',
                'toleransi' => 15,
                'checkin_window_before' => 60,
                'kode_warna' => '#3B82F6',
                'aktif' => 1,
            ],
            [
                'nama_shift' => 'Shift Siang',
                'jam_masuk' => '15:00:00',
                'jam_keluar' => '23:00:00',
                'toleransi' => 15,
                'checkin_window_before' => 60,
                'kode_warna' => '#10B981',
                'aktif' => 1,
            ],
            [
                'nama_shift' => 'Shift Malam',
                'jam_masuk' => '23:00:00',
                'jam_keluar' => '07:00:00',
                'toleransi' => 15,
                'checkin_window_before' => 60,
                'kode_warna' => '#8B5CF6',
                'aktif' => 1,
            ],
            [
                'nama_shift' => 'Reguler Kantor',
                'jam_masuk' => '08:00:00',
                'jam_keluar' => '17:00:00',
                'toleransi' => 15,
                'checkin_window_before' => 60,
                'kode_warna' => '#F59E0B',
                'aktif' => 1,
            ],
            [
                'nama_shift' => 'Fleksibel 8 Jam',
                'jam_masuk' => '08:00:00',
                'jam_keluar' => '16:00:00',
                'toleransi' => 30,
                'checkin_window_before' => 60,
                'kode_warna' => '#EC4899',
                'aktif' => 1,
            ],
        ];

        foreach ($shifts as $s) {
            Shift::query()->updateOrCreate(
                ['nama_shift' => $s['nama_shift']],
                $s
            );
        }

        // 3. Hari Libur Nasional 2026
        $holidays = [
            ['tanggal' => '2026-01-01', 'nama_libur' => 'Tahun Baru 2026 Masehi', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-01-16', 'nama_libur' => 'Isra Mi\'raj Nabi Muhammad SAW', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-02-17', 'nama_libur' => 'Tahun Baru Imlek 2577 Kongzili', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-03-20', 'nama_libur' => 'Hari Suci Nyepi Tahun Baru Saka 1948', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-03-21', 'nama_libur' => 'Hari Raya Idul Fitri 1447 H (Hari Pertama)', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-03-22', 'nama_libur' => 'Hari Raya Idul Fitri 1447 H (Hari Kedua)', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-04-03', 'nama_libur' => 'Wafat Yesus Kristus', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-05-01', 'nama_libur' => 'Hari Buruh Internasional', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-05-14', 'nama_libur' => 'Kenaikan Yesus Kristus', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-05-27', 'nama_libur' => 'Hari Raya Waisak 2570 BE', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-05-28', 'nama_libur' => 'Hari Raya Idul Adha 1447 H', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-06-01', 'nama_libur' => 'Hari Lahir Pancasila', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-06-16', 'nama_libur' => 'Tahun Baru Islam 1448 H', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-08-17', 'nama_libur' => 'Hari Kemerdekaan Republik Indonesia ke-81', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-08-25', 'nama_libur' => 'Maulid Nabi Muhammad SAW', 'keterangan' => 'Libur Nasional'],
            ['tanggal' => '2026-12-25', 'nama_libur' => 'Hari Raya Natal', 'keterangan' => 'Libur Nasional'],
        ];

        foreach ($holidays as $h) {
            HariLibur::query()->updateOrCreate(
                ['tanggal' => $h['tanggal']],
                [
                    'nama_libur' => $h['nama_libur'],
                    'keterangan' => $h['keterangan'],
                    'aktif' => true,
                    'source' => HariLibur::SOURCE_MANUAL,
                ]
            );
        }

        // 4. Devices
        $devices = [
            [
                'name' => 'Mesin RFID Lobby Kantor Pusat',
                'serial_number' => 'RFID-HO-01',
                'mac_address' => '00:1A:2B:3C:4D:01',
                'device_token' => 'tok_dev_ho_rfid_01_secure',
                'firmware_version' => 'v2.5.0',
                'status' => Device::STATUS_ACTIVE,
                'last_seen' => now(),
                'activated_at' => now()->subMonths(6),
            ],
            [
                'name' => 'Mesin RFID Gate Gudang Cikarang',
                'serial_number' => 'RFID-WH-01',
                'mac_address' => '00:1A:2B:3C:4D:02',
                'device_token' => 'tok_dev_wh_rfid_02_secure',
                'firmware_version' => 'v2.5.0',
                'status' => Device::STATUS_ACTIVE,
                'last_seen' => now(),
                'activated_at' => now()->subMonths(5),
            ],
            [
                'name' => 'Mesin Biometrik Cabang Bandung',
                'serial_number' => 'BIO-BDG-01',
                'mac_address' => '00:1A:2B:3C:4D:03',
                'device_token' => 'tok_dev_bdg_bio_03_secure',
                'firmware_version' => 'v3.1.2',
                'status' => Device::STATUS_ACTIVE,
                'last_seen' => now(),
                'activated_at' => now()->subMonths(4),
            ],
            [
                'name' => 'Mesin Biometrik Cabang Surabaya',
                'serial_number' => 'BIO-SBY-01',
                'mac_address' => '00:1A:2B:3C:4D:04',
                'device_token' => 'tok_dev_sby_bio_04_secure',
                'firmware_version' => 'v3.1.2',
                'status' => Device::STATUS_ACTIVE,
                'last_seen' => now(),
                'activated_at' => now()->subMonths(3),
            ],
        ];

        foreach ($devices as $dev) {
            Device::query()->updateOrCreate(
                ['serial_number' => $dev['serial_number']],
                $dev
            );
        }

        // 5. Komponen Gaji Master
        $komponenList = [
            ['nama_komponen' => 'Tunjangan Jabatan', 'jenis' => 'tunjangan', 'nominal' => 1000000],
            ['nama_komponen' => 'Tunjangan Uang Makan', 'jenis' => 'tunjangan', 'nominal' => 15000],
            ['nama_komponen' => 'Tunjangan Transportasi', 'jenis' => 'tunjangan', 'nominal' => 10000],
            ['nama_komponen' => 'Premi Kehadiran Penuh', 'jenis' => 'tunjangan', 'nominal' => 200000],
            ['nama_komponen' => 'Bonus Prestasi Bulanan', 'jenis' => 'tunjangan', 'nominal' => 500000],
            ['nama_komponen' => 'Potongan Keterlambatan', 'jenis' => 'potongan', 'nominal' => 1000],
            ['nama_komponen' => 'Potongan Ketidakhadiran / Mangkir', 'jenis' => 'potongan', 'nominal' => 120000],
        ];

        foreach ($komponenList as $k) {
            KomponenGaji::query()->updateOrCreate(
                ['nama_komponen' => $k['nama_komponen']],
                $k + ['status' => 1]
            );
        }
    }
}
