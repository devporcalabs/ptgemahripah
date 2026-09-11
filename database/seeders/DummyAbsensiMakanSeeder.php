<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\AbsensiMakan;
use App\Models\Karyawan;
use App\Models\LokasiGps;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DummyAbsensiMakanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Roles & Permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('absensi_makan.view', 'web');
        Permission::findOrCreate('absensi_makan.manage', 'web');

        $superAdminRole = Role::findOrCreate('super-admin', 'web');
        $adminRole = Role::findOrCreate('admin', 'web');
        $canteenRole = Role::findOrCreate('petugas-kantin', 'web');

        $superAdminRole->givePermissionTo(['absensi_makan.view', 'absensi_makan.manage']);
        $adminRole->givePermissionTo(['absensi_makan.view', 'absensi_makan.manage']);
        $canteenRole->givePermissionTo(['absensi_makan.view', 'absensi_makan.manage']);

        // Ensure user kantin exists
        $kantinUser = User::query()->updateOrCreate(
            ['username' => 'kantin'],
            [
                'name' => 'Petugas Kantin & Katering',
                'email' => 'kantin@gemahripah.co.id',
                'password' => Hash::make('123456'),
                'role' => 'petugas-kantin',
            ]
        );
        $kantinUser->syncRoles(['petugas-kantin']);

        $adminUser = User::query()->where('username', 'admin')->first();

        // 2. Fetch Active Employees and Locations
        $employees = Karyawan::query()
            ->where('status', 'aktif')
            ->orderBy('id')
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        $defaultLocation = LokasiGps::query()->where('is_default', 1)->first()
            ?? LokasiGps::query()->first();

        // 3. Clear existing and seed from 2026-08-01 to 2026-09-11
        AbsensiMakan::query()->truncate();

        $startDate = Carbon::create(2026, 8, 1)->startOfDay();
        $today = Carbon::create(2026, 9, 11)->startOfDay();
        $datePeriod = CarbonPeriod::create($startDate, $today);

        // Fetch attendance map to check who was working
        $attendances = Absensi::query()
            ->whereBetween('tanggal', [$startDate->toDateString(), $today->toDateString()])
            ->whereIn('status', ['hadir', 'terlambat'])
            ->get()
            ->groupBy(fn ($a) => $a->karyawan_id . '_' . $a->tanggal->toDateString());

        $inserts = [];

        foreach ($datePeriod as $date) {
            $isToday = $date->isSameDay($today);
            $dateStr = $date->toDateString();

            foreach ($employees as $emp) {
                $uniqueKey = $emp->id . '_' . $dateStr;
                $attendance = $attendances->get($uniqueKey)?->first();

                // If employee was not attending work, they don't get canteen meal
                if (! $attendance) {
                    continue;
                }

                $hash = crc32($emp->id . '_' . $dateStr . '_meal');
                $rand = abs($hash) % 100;

                // A) Makan Siang (92% of attending employees take lunch)
                if ($rand < 92) {
                    $minute = 15 + ($rand % 40); // 11:45 - 12:55
                    $second = ($rand * 7) % 60;
                    $jamMakan = sprintf('%02d:%02d:%02d', 12, $minute % 60, $second);

                    $metodeRand = $rand % 10;
                    $metode = $metodeRand < 7 ? 'rfid' : ($metodeRand < 9 ? 'web' : 'qr_scan');

                    $inserts[] = [
                        'karyawan_id' => $emp->id,
                        'tanggal' => $dateStr,
                        'jam_makan' => $jamMakan,
                        'jenis_makan' => 'siang',
                        'metode' => $metode,
                        'lokasi_gps_id' => $emp->lokasi_gps_id ?: $defaultLocation?->id,
                        'device_id' => null,
                        'nominal' => 15000.00,
                        'status' => 'valid',
                        'catatan' => null,
                        'created_by' => $kantinUser->id,
                        'created_at' => Carbon::parse($dateStr . ' ' . $jamMakan),
                        'updated_at' => Carbon::parse($dateStr . ' ' . $jamMakan),
                    ];
                }

                // B) Makan Malam / Shift (Employees on shift / night or overtime)
                $isShiftOrNight = ($emp->jenis_jam_kerja === 'rolling') || ($emp->shift?->nama_shift === 'Shift Siang') || ($emp->shift?->nama_shift === 'Shift Malam');
                if ($isShiftOrNight && ($rand % 3 === 0)) {
                    $minute = 10 + ($rand % 45);
                    $jamMakan = sprintf('%02d:%02d:00', 19, $minute);

                    $inserts[] = [
                        'karyawan_id' => $emp->id,
                        'tanggal' => $dateStr,
                        'jam_makan' => $jamMakan,
                        'jenis_makan' => 'malam',
                        'metode' => 'rfid',
                        'lokasi_gps_id' => $emp->lokasi_gps_id ?: $defaultLocation?->id,
                        'device_id' => null,
                        'nominal' => 15000.00,
                        'status' => 'valid',
                        'catatan' => 'Makan shift malam',
                        'created_by' => $kantinUser->id,
                        'created_at' => Carbon::parse($dateStr . ' ' . $jamMakan),
                        'updated_at' => Carbon::parse($dateStr . ' ' . $jamMakan),
                    ];
                }

                // C) Makan Lembur (If attendance had overtime > 120 minutes)
                if (($attendance->menit_lembur ?? 0) >= 120 && ! $isToday) {
                    $jamMakan = sprintf('%02d:%02d:00', 21, 15 + ($rand % 30));

                    $inserts[] = [
                        'karyawan_id' => $emp->id,
                        'tanggal' => $dateStr,
                        'jam_makan' => $jamMakan,
                        'jenis_makan' => 'lembur',
                        'metode' => 'rfid',
                        'lokasi_gps_id' => $emp->lokasi_gps_id ?: $defaultLocation?->id,
                        'device_id' => null,
                        'nominal' => 15000.00,
                        'status' => 'valid',
                        'catatan' => 'Kupon lembur > 2 jam',
                        'created_by' => $kantinUser->id,
                        'created_at' => Carbon::parse($dateStr . ' ' . $jamMakan),
                        'updated_at' => Carbon::parse($dateStr . ' ' . $jamMakan),
                    ];
                }

                if (count($inserts) >= 500) {
                    AbsensiMakan::query()->insert($inserts);
                    $inserts = [];
                }
            }
        }

        if ($inserts !== []) {
            AbsensiMakan::query()->insert($inserts);
        }
    }
}
