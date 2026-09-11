<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $roleName = env('SEED_ADMIN_ROLE', 'super-admin');

        $user = User::query()->firstOrCreate(
            ['username' => env('SEED_ADMIN_USERNAME', 'admin')],
            [
                'name' => env('SEED_ADMIN_NAME', 'Administrator'),
                'email' => env('SEED_ADMIN_EMAIL'),
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', '123456')),
                'role' => $roleName,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        if ($user->role !== $roleName) {
            $user->forceFill(['role' => $roleName])->save();
        }

        $user->syncRoles([$roleName]);
    }
}
