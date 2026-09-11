<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Karyawan extends Authenticatable
{
    use Notifiable;

    protected $table = 'karyawan';

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'tgl_lahir' => 'date',
        'tgl_join' => 'date',
        'tgl_resign' => 'date',
        'clearance_payroll_final' => 'boolean',
        'clearance_kasbon_resolved' => 'boolean',
        'clearance_asset_returned' => 'boolean',
        'clearance_access_revoked' => 'boolean',
        'clearance_document_completed' => 'boolean',
        'clearance_completed_at' => 'datetime',
        'masa_berlaku' => 'date',
        'tanggal_mulai_pkwt' => 'date',
        'tanggal_berakhir_pkwt' => 'date',
        'shift_rotation_ids' => 'array',
        'shift_rotation_start' => 'date',
        'tax_has_second_employer' => 'boolean',
        'tax_prev_gross_income' => 'decimal:2',
        'tax_prev_pph21_paid' => 'decimal:2',
        'tax_prev_retirement_contribution' => 'decimal:2',
        'gaji_pokok' => 'decimal:2',
        'gaji_per_hari' => 'decimal:2',
        'bonus_pribadi' => 'decimal:2',
        'bonus_team' => 'decimal:2',
        'premi_kehadiran' => 'decimal:2',
        'thr_manual_amount' => 'decimal:2',
        'premi_kehadiran_toleransi_telat' => 'integer',
        'premi_kehadiran_toleransi_pulang_cepat' => 'integer',
    ];

    public const UPDATED_AT = null;

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function getJenisJamKerjaAttribute($value): string
    {
        return match ($value) {
            'shift' => 'tetap',
            'rolling', 'fleksibel' => $value,
            default => 'tetap',
        };
    }

    public function getJenisKaryawanAttribute($value): string
    {
        return match ((string) $value) {
            'kontrak' => 'kontrak',
            'magang' => 'magang',
            default => 'tetap',
        };
    }

    public function getJenisKaryawanLabelAttribute(): string
    {
        return match ($this->jenis_karyawan) {
            'kontrak' => 'Kontrak',
            'magang' => 'Magang',
            default => 'Tetap',
        };
    }

    public function getJenisKaryawanBadgeClassAttribute(): string
    {
        return match ($this->jenis_karyawan) {
            'kontrak' => 'badge-warning',
            'magang' => 'badge-info',
            default => 'badge-success',
        };
    }

    public function getShiftTypeLabelAttribute(): string
    {
        return match ($this->jenis_jam_kerja) {
            'rolling' => 'Rolling',
            'fleksibel' => 'Fleksibel',
            default => 'Tetap',
        };
    }

    public function getShiftRotationModeAttribute($value): string
    {
        return match ((string) $value) {
            'weekly', 'biweekly', 'monthly' => (string) $value,
            default => 'daily',
        };
    }

    public function getShiftRotationModeLabelAttribute(): string
    {
        return match ($this->shift_rotation_mode) {
            'weekly' => 'Mingguan',
            'biweekly' => '2 Mingguan',
            'monthly' => 'Bulanan',
            default => 'Harian',
        };
    }

    public function scopeWithoutResigned($query)
    {
        return $query->whereNull('tgl_resign');
    }

    public function scopeOnlyResigned($query)
    {
        return $query->whereNotNull('tgl_resign');
    }

    public function isResigned(): bool
    {
        return $this->tgl_resign !== null;
    }

    public function getEmploymentStatusLabelAttribute(): string
    {
        if ($this->isResigned()) {
            return 'Resign';
        }

        return ($this->status ?? 'aktif') === 'aktif'
            ? 'Aktif'
            : 'Nonaktif';
    }

    public function getEmploymentStatusBadgeClassAttribute(): string
    {
        if ($this->isResigned()) {
            return 'badge-danger';
        }

        return ($this->status ?? 'aktif') === 'aktif'
            ? 'badge-success'
            : 'badge-warning';
    }

    public function gajiKaryawan(): HasMany
    {
        return $this->hasMany(GajiKaryawan::class);
    }

    public function getClearanceStatusLabelAttribute(): string
    {
        return match ((string) ($this->clearance_status ?? 'draft')) {
            'selesai' => 'Selesai',
            'proses' => 'Proses',
            default => 'Draft',
        };
    }

    public function getClearanceStatusBadgeClassAttribute(): string
    {
        return match ((string) ($this->clearance_status ?? 'draft')) {
            'selesai' => 'badge-success',
            'proses' => 'badge-warning',
            default => 'badge-secondary',
        };
    }

    public function getClearanceCompletedItemsCountAttribute(): int
    {
        return collect([
            (bool) ($this->clearance_payroll_final ?? false),
            (bool) ($this->clearance_kasbon_resolved ?? false),
            (bool) ($this->clearance_asset_returned ?? false),
            (bool) ($this->clearance_access_revoked ?? false),
            (bool) ($this->clearance_document_completed ?? false),
        ])->filter()->count();
    }

    public function getClearanceTotalItemsCountAttribute(): int
    {
        return 5;
    }

    public function getClearanceProgressLabelAttribute(): string
    {
        return $this->clearance_completed_items_count.'/'.$this->clearance_total_items_count;
    }

    public function getTipePenggajianAttribute($value): string
    {
        if (in_array($value, ['bulanan', 'harian'], true)) {
            return $value;
        }

        return (float) ($this->attributes['gaji_pokok'] ?? 0) > 0
            ? 'bulanan'
            : 'harian';
    }

    public function getTipePenggajianLabelAttribute(): string
    {
        return $this->tipe_penggajian === 'harian'
            ? 'Harian'
            : 'Bulanan';
    }

    public function getStatusPtkpAttribute(): string
    {
        $status = strtoupper(trim((string) ($this->attributes['status_nikah'] ?? '')));
        $ptkpMap = config('payroll_tax.ptkp', []);

        return array_key_exists($status, $ptkpMap)
            ? $status
            : 'TK/0';
    }

    public function getTerCategoryAttribute(): ?string
    {
        $categories = config('payroll_tax.ter_categories', []);
        $status = $this->status_ptkp;

        if ($status === 'TK/0') {
            return $categories[$status] ?? 'A';
        }

        return $categories[$status] ?? null;
    }

    public function jabatanData(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    public function departemenData(): BelongsTo
    {
        return $this->belongsTo(Departemen::class, 'departemen_id');
    }

    public function lokasiGps(): BelongsTo
    {
        return $this->belongsTo(LokasiGps::class, 'lokasi_gps_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function izin(): HasMany
    {
        return $this->hasMany(Izin::class);
    }

    public function lembur(): HasMany
    {
        return $this->hasMany(Lembur::class);
    }

    public function komponenGaji(): HasOne
    {
        return $this->hasOne(KomponenGajiKaryawan::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'karyawan_id');
    }

    public function latestKasbonMutation(): HasOne
    {
        return $this->hasOne(KasbonMutation::class)
            ->latestOfMany('id')
            ->select([
                'kasbon_mutations.id',
                'kasbon_mutations.karyawan_id',
                'kasbon_mutations.tanggal',
                'kasbon_mutations.kasbon_akhir',
            ]);
    }

    public function kasbonMutations(): HasMany
    {
        return $this->hasMany(KasbonMutation::class);
    }

    public function getSaldoKasbonAttribute(): float
    {
        if ($this->relationLoaded('latestKasbonMutation')) {
            return max(0, (float) ($this->latestKasbonMutation?->kasbon_akhir ?? 0));
        }

        return max(0, (float) $this->latestKasbonMutation()->value('kasbon_akhir'));
    }

    public function getKasbonBalanceAttribute(): float
    {
        return $this->saldo_kasbon;
    }

    public function getCicilanKasbonAttribute(): float
    {
        return 0.0;
    }

    public function getMasaKerjaLabelAttribute(): string
    {
        if (! $this->tgl_join) {
            return '-';
        }

        $joinDate = $this->tgl_join instanceof Carbon
            ? $this->tgl_join->copy()->startOfDay()
            : Carbon::parse($this->tgl_join)->startOfDay();
        $today = ($this->tgl_resign instanceof Carbon && $this->tgl_resign->lessThan(now()))
            ? $this->tgl_resign->copy()->startOfDay()
            : now()->startOfDay();

        if ($joinDate->greaterThan($today)) {
            return '0 Tahun, 0 Bulan, 0 Hari';
        }

        $duration = $today->diff($joinDate);

        return "{$duration->y} Tahun, {$duration->m} Bulan, {$duration->d} Hari";
    }

    public function absensiMakan(): HasMany
    {
        return $this->hasMany(AbsensiMakan::class, 'karyawan_id');
    }

    public function hasEatenToday(string $jenisMakan = 'siang'): bool
    {
        return $this->absensiMakan()
            ->whereDate('tanggal', now()->toDateString())
            ->where('jenis_makan', $jenisMakan)
            ->where('status', 'valid')
            ->exists();
    }
}
