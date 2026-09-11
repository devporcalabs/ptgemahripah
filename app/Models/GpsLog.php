<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class GpsLog extends Model
{
    protected $table = 'gps_logs';

    protected $guarded = [];

    public const UPDATED_AT = null;

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }
}
