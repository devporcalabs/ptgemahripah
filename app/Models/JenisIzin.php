<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisIzin extends Model
{
    protected $table = 'jenis_izin';

    protected $guarded = [];

    protected $casts = [
        'is_paid' => 'boolean',
        'deduct_quota' => 'boolean',
        'annual_quota_days' => 'integer',
        'max_days_per_request' => 'integer',
        'require_attachment' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const UPDATED_AT = null;

    public function izin(): HasMany
    {
        return $this->hasMany(Izin::class, 'jenis_izin_id');
    }

    public function getQuotaLabelAttribute(): ?string
    {
        $label = match ($this->quota_field) {
            'izin_cuti' => 'Kuota Cuti',
            'izin_lainnya' => 'Kuota Izin',
            default => null,
        };

        if ($label !== null) {
            return $label;
        }

        return (int) ($this->annual_quota_days ?? 0) > 0
            ? 'Kuota '.$this->nama
            : null;
    }

    public function getUsesQuotaAttribute(): bool
    {
        return $this->deduct_quota
            || (int) ($this->annual_quota_days ?? 0) > 0;
    }

    public function getQuotaSummaryAttribute(): ?string
    {
        if ($this->quota_field === 'izin_cuti') {
            return 'Mengikuti jatah cuti tahunan karyawan.';
        }

        if ($this->quota_field === 'izin_lainnya') {
            return 'Mengikuti jatah izin pribadi karyawan.';
        }

        if ((int) ($this->annual_quota_days ?? 0) > 0) {
            return "{$this->annual_quota_days} hari kerja per tahun.";
        }

        return null;
    }

    public function getMaxRequestSummaryAttribute(): ?string
    {
        $maxDays = (int) ($this->max_days_per_request ?? 0);

        return $maxDays > 0
            ? "Maksimal {$maxDays} hari kerja per pengajuan."
            : null;
    }
}
