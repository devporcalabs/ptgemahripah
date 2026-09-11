<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_API_CO_ID = 'api_co_id';

    protected $table = 'hari_libur';

    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
        'aktif' => 'boolean',
        'sync_meta' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
