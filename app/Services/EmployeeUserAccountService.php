<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class EmployeeUserAccountService
{
    public function sync(Karyawan $employee, ?string $plainPassword = null): User
    {
        $username = trim((string) $employee->nik);

        if ($username === '') {
            throw ValidationException::withMessages([
                'nik' => 'NIK wajib diisi untuk membuat akun login karyawan.',
            ]);
        }

        $currentUser = User::query()
            ->where('karyawan_id', $employee->id)
            ->first();

        $usernameConflict = User::query()
            ->where('username', $username)
            ->when($currentUser, fn ($query) => $query->whereKeyNot($currentUser->id))
            ->exists();

        if ($usernameConflict) {
            throw ValidationException::withMessages([
                'nik' => 'NIK ini sudah dipakai sebagai username login oleh akun lain.',
            ]);
        }

        $payload = [
            'name' => trim((string) ($employee->nama_lengkap ?: $username)),
            'username' => $username,
            'email' => $this->resolveEmail($employee, $currentUser),
            'role' => 'karyawan',
            'karyawan_id' => $employee->id,
        ];

        if (! $currentUser) {
            $currentUser = new User($payload);
            $currentUser->password = $plainPassword ?: '123456';
            $currentUser->save();
        } else {
            $currentUser->fill($payload);

            if ($plainPassword !== null) {
                $currentUser->password = $plainPassword;
            }

            $currentUser->save();
        }

        Role::findOrCreate('karyawan', 'web');
        $currentUser->syncRoles(['karyawan']);

        return $currentUser->fresh();
    }

    public function resetPassword(Karyawan $employee, string $plainPassword = '123456'): User
    {
        return $this->sync($employee, $plainPassword);
    }

    public function syncAll(?Collection $employees = null): int
    {
        $synced = 0;

        ($employees ?? Karyawan::query()->get())->each(function (Karyawan $employee) use (&$synced): void {
            $this->sync($employee);
            $synced++;
        });

        return $synced;
    }

    private function resolveEmail(Karyawan $employee, ?User $currentUser = null): ?string
    {
        $email = trim((string) ($employee->email ?? ''));

        if ($email === '') {
            return null;
        }

        $conflict = User::query()
            ->where('email', $email)
            ->when($currentUser, fn ($query) => $query->whereKeyNot($currentUser->id))
            ->exists();

        return $conflict ? null : $email;
    }
}
