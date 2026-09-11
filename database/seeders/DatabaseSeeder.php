<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            MasterDataSeeder::class,
            JenisIzinSeeder::class,
            JabatanSeeder::class,
            DepartemenSeeder::class,
        ];

        if (! config('installer.skip_admin_seed', false)) {
            array_splice($seeders, 1, 0, [AdminUserSeeder::class]);
        }

        if (filter_var(env('SEED_DUMMY_KARYAWAN', true), FILTER_VALIDATE_BOOL)) {
            $seeders[] = DummyKaryawanSeeder::class;
            $seeders[] = EmployeeUserSeeder::class;
            $seeders[] = OperationalDummySeeder::class;
        } else {
            $seeders[] = EmployeeUserSeeder::class;
        }

        $this->call($seeders);
    }
}
