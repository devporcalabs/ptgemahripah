<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $table = 'shift';

    protected $guarded = [];

    public const UPDATED_AT = null;

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }
}
