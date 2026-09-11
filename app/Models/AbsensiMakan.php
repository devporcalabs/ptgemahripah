<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiMakan extends Model
{
    protected $table = 'absensi_makan';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function lokasiGps(): BelongsTo
    {
        return $this->belongsTo(LokasiGps::class, 'lokasi_gps_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', 'valid');
    }

    public function scopeForDate(Builder $query, Carbon|string $date): Builder
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : $date;
        return $query->whereDate('tanggal', $dateString);
    }

    public function scopeForMonth(Builder $query, Carbon|string $period): Builder
    {
        $carbon = $period instanceof Carbon ? $period : Carbon::parse($period);
        return $query->whereYear('tanggal', $carbon->year)->whereMonth('tanggal', $carbon->month);
    }

    public function getJenisMakanLabelAttribute(): string
    {
        return match ($this->jenis_makan) {
            'siang' => 'Makan Siang',
            'malam' => 'Makan Malam',
            'sahur' => 'Sahur',
            'lembur' => 'Makan Lembur',
            default => ucfirst((string) $this->jenis_makan),
        };
    }

    public function getMetodeLabelAttribute(): string
    {
        return match ($this->metode) {
            'rfid' => 'Tap Kartu RFID',
            'web' => 'Portal Karyawan (Web)',
            'qr_scan' => 'Scan QR Code',
            'manual' => 'Input Manual Petugas',
            default => strtoupper((string) $this->metode),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'valid' => '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Valid</span>',
            'dibatalkan' => '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Dibatalkan</span>',
            default => '<span class="badge bg-secondary">'.$this->status.'</span>',
        };
    }
}
