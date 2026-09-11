<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class KomponenGajiKaryawan extends Model
{
    protected $table = 'komponen_gaji_karyawan';

    protected $guarded = [];

    protected $casts = [
        'gaji_per_hari' => 'decimal:2',
        'tunjangan_jabatan' => 'decimal:2',
        'tunjangan_makan' => 'decimal:2',
        'tunjangan_transport' => 'decimal:2',
        'tunjangan_bpjs_kesehatan' => 'decimal:2',
        'tunjangan_bpjs_ketenagakerjaan' => 'decimal:2',
        'potongan_per_menit' => 'decimal:2',
        'potongan_izin' => 'decimal:2',
        'potongan_mangkir' => 'decimal:2',
        'potongan_terlambat' => 'decimal:2',
        'potongan_bpjs_kesehatan' => 'decimal:2',
        'potongan_bpjs_ketenagakerjaan' => 'decimal:2',
        'tarif_lembur_per_jam' => 'decimal:2',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }
}
