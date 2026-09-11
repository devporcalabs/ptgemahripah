<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasbonMutation extends Model
{
    protected $table = 'kasbon_mutations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
            'kasbon_awal' => 'decimal:2',
            'kasbon_akhir' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getJenisLabelAttribute(): string
    {
        return match ((string) $this->jenis) {
            'opening_balance' => 'Saldo Awal',
            'manual_plus' => 'Kasbon Masuk',
            'manual_minus' => 'Pembayaran Manual',
            'payroll_deduction' => 'Potongan Payroll',
            'payroll_reversal' => 'Buka Payroll',
            default => ucwords(str_replace('_', ' ', (string) $this->jenis)),
        };
    }

    public function getArahLabelAttribute(): string
    {
        return $this->arah === 'minus' ? 'Pengurangan' : 'Penambahan';
    }

    public function getIsSystemGeneratedAttribute(): bool
    {
        return in_array((string) $this->jenis, ['opening_balance', 'payroll_deduction', 'payroll_reversal'], true);
    }
}
