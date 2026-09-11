<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $table = 'absensi';

    protected $guarded = [];

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'menit_terlambat' => 'integer',
            'menit_pulang_cepat' => 'integer',
            'menit_lembur' => 'integer',
            'jam_lembur' => 'decimal:2',
            'tarif_lembur' => 'decimal:2',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class, 'absensi_id');
    }

    public function latestCorrection(): HasOne
    {
        return $this->hasOne(AttendanceCorrection::class, 'absensi_id')->latestOfMany();
    }

    public function selfies(): HasMany
    {
        return $this->hasMany(AbsensiSelfie::class);
    }
}
