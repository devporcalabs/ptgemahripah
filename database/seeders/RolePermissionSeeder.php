<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'dashboard.view',
        'karyawan.manage',
        'absensi.view',
        'gaji.view',
        'kasbon.manage',
        'izin.manage',
        'laporan.view',
        'jadwal.manage',
        'shift.manage',
        'lokasi.manage',
        'whatsapp.manage',
        'profil.manage',
        'sistem.manage',
        'device.manage',
        'absensi_makan.view',
        'absensi_makan.manage',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $permissions = Permission::query()->where('guard_name', 'web')->pluck('name')->all();

        $superAdminRole = Role::findOrCreate('super-admin', 'web');
        $adminRole = Role::findOrCreate('admin', 'web');
        $employeeRole = Role::findOrCreate('karyawan', 'web');
        $canteenRole = Role::findOrCreate('petugas-kantin', 'web');

        $superAdminRole->syncPermissions($permissions);
        $adminRole->syncPermissions($permissions);
        $employeeRole->syncPermissions([]);
        $canteenRole->syncPermissions(['absensi_makan.view', 'absensi_makan.manage']);

        $deprecatedPermission = Permission::query()
            ->where('name', 'lembur.manage')
            ->where('guard_name', 'web')
            ->first();

        if ($deprecatedPermission) {
            $superAdminRole->revokePermissionTo($deprecatedPermission);
            $adminRole->revokePermissionTo($deprecatedPermission);
            $employeeRole->revokePermissionTo($deprecatedPermission);
            $deprecatedPermission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
