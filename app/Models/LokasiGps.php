<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class LokasiGps extends Model
{
    protected $table = 'lokasi_gps';

    protected $guarded = [];

    public const UPDATED_AT = null;

    public function karyawanDefault(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'lokasi_gps_id');
    }
}
