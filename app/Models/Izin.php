<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Izin extends Model
{
    protected $table = 'izin';

    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_izin' => 'date',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_pengajuan' => 'datetime',
        'tanggal_disetujui' => 'datetime',
        'jumlah_hari' => 'integer',
    ];

    public const UPDATED_AT = null;

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function jenisIzin(): BelongsTo
    {
        return $this->belongsTo(JenisIzin::class, 'jenis_izin_id');
    }

    public function getTanggalMulaiEfektifAttribute(): ?Carbon
    {
        return $this->tanggal_mulai
            ?? $this->tanggal_izin
            ?? $this->tanggal;
    }

    public function getTanggalSelesaiEfektifAttribute(): ?Carbon
    {
        return $this->tanggal_selesai
            ?? $this->tanggal_mulai
            ?? $this->tanggal_izin
            ?? $this->tanggal;
    }

    public function getEffectiveJumlahHariAttribute(): int
    {
        return max(1, (int) ($this->jumlah_hari ?? 1));
    }

    public function getLegacyJenisIzinCodeAttribute(): string
    {
        $legacyCode = trim((string) ($this->jenisIzin?->legacy_code ?? $this->jenis_izin ?? 'lainnya'));

        return in_array($legacyCode, ['sakit', 'cuti', 'lainnya'], true)
            ? $legacyCode
            : 'lainnya';
    }

    public function getLeaveTypeNameAttribute(): string
    {
        return trim((string) ($this->jenisIzin?->nama ?: match ($this->legacy_jenis_izin_code) {
            'sakit' => 'Sakit',
            'cuti' => 'Cuti',
            default => 'Izin',
        }));
    }

    public function getLeaveIsPaidAttribute(): bool
    {
        if ($this->jenisIzin) {
            return (bool) $this->jenisIzin->is_paid;
        }

        return in_array($this->legacy_jenis_izin_code, ['sakit', 'cuti'], true);
    }

    public function getLeaveDeductQuotaAttribute(): bool
    {
        return (bool) ($this->jenisIzin?->uses_quota ?? false);
    }
}
