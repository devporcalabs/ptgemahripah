<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    protected $table = 'payroll_periods';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'periode' => 'date',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'cutoff_absensi' => 'date',
            'generated_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_stage_one_at' => 'datetime',
            'approved_stage_two_at' => 'datetime',
            'finalized_at' => 'datetime',
            'salary_whatsapp_queued_at' => 'datetime',
            'salary_whatsapp_sent_at' => 'datetime',
            'reopened_at' => 'datetime',
            'total_gaji' => 'decimal:2',
        ];
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(GajiKaryawan::class, 'payroll_period_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedStageOneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_stage_one_by');
    }

    public function approvedStageTwoBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_stage_two_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }
}
