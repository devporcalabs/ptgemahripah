<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class GajiKaryawan extends Model
{
    protected $table = 'gaji_karyawan';

    protected $guarded = [];

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'bulan' => 'date',
            'is_finalized' => 'boolean',
            'finalized_at' => 'datetime',
            'detail_payload' => 'array',
            'applied_kasbon_amount' => 'decimal:2',
            'manual_kasbon_amount' => 'decimal:2',
            'thr' => 'decimal:2',
            'pph21' => 'decimal:2',
            'prorate_ratio' => 'float',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }
}
