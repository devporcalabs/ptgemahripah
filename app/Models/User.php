<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'karyawan_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getNamaLengkapAttribute(): string
    {
        return $this->name;
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function getDisplayRoleAttribute(): string
    {
        $roleName = $this->getRoleNames()->first() ?? $this->role ?? 'administrator';

        return (string) Str::of($roleName)->replace('-', ' ')->title();
    }

    public function hasAdminPanelAccess(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin']);
    }

    public function hasCanteenAccess(): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin', 'petugas-kantin']);
    }

    public function hasEmployeePanelAccess(): bool
    {
        if (! $this->hasRole('karyawan')) {
            return false;
        }

        $employee = $this->relationLoaded('karyawan')
            ? $this->karyawan
            : $this->karyawan()->first();

        if (! $employee) {
            return false;
        }

        if ($employee->isResigned()) {
            return false;
        }

        return ($employee->status ?? 'aktif') === 'aktif';
    }
}
