<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AbsensiSelfie extends Model
{
    protected $table = 'absensi_selfie';

    protected $guarded = [];

    public const UPDATED_AT = null;

    public function absensi(): BelongsTo
    {
        return $this->belongsTo(Absensi::class);
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }
}
