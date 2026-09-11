<?php

namespace App\Services;

use App\Models\AbsensiMakan;
use App\Models\Karyawan;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AbsensiMakanService
{
    public function recordMeal(
        Karyawan $karyawan,
        string $jenisMakan = 'siang',
        string $metode = 'manual',
        ?int $deviceId = null,
        ?int $lokasiId = null,
        ?int $userId = null,
        ?string $catatan = null,
        ?Carbon $timestamp = null
    ): array {
        $now = $timestamp ? $timestamp->copy() : now();
        $dateStr = $now->toDateString();
        $timeStr = $now->toTimeString();

        if (($karyawan->status ?? 'aktif') !== 'aktif') {
            return [
                'success' => false,
                'reason' => 'inactive',
                'message' => "Karyawan {$karyawan->nama_lengkap} (NIK: {$karyawan->nik}) berstatus nonaktif.",
                'karyawan' => $karyawan,
            ];
        }

        // Check if already claimed for today's session
        $existing = AbsensiMakan::query()
            ->where('karyawan_id', $karyawan->id)
            ->whereDate('tanggal', $dateStr)
            ->where('jenis_makan', $jenisMakan)
            ->where('status', 'valid')
            ->first();

        $jenisLabel = match ($jenisMakan) {
            'siang' => 'Makan Siang',
            'malam' => 'Makan Malam',
            'lembur' => 'Makan Lembur',
            'sahur' => 'Sahur',
            default => ucfirst($jenisMakan),
        };

        if ($existing) {
            $jamAmbil = Carbon::parse($existing->jam_makan)->format('H:i');
            return [
                'success' => false,
                'reason' => 'already_claimed',
                'message' => "Karyawan {$karyawan->nama_lengkap} sudah mengambil {$jenisLabel} hari ini pukul {$jamAmbil} WIB.",
                'karyawan' => $karyawan,
                'existing' => $existing,
            ];
        }

        $settings = Setting::query()->find(1);
        $nominal = (float) ($settings?->uang_makan ?? 15000);

        $lokasiGpsId = $lokasiId ?: $karyawan->lokasi_gps_id ?: ($settings?->id ? 1 : null);

        $meal = AbsensiMakan::query()->create([
            'karyawan_id' => $karyawan->id,
            'tanggal' => $dateStr,
            'jam_makan' => $timeStr,
            'jenis_makan' => $jenisMakan,
            'metode' => $metode,
            'lokasi_gps_id' => $lokasiGpsId,
            'device_id' => $deviceId,
            'nominal' => $nominal,
            'status' => 'valid',
            'catatan' => $catatan,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'success' => true,
            'reason' => 'success',
            'message' => "Kupon {$jenisLabel} berhasil diverifikasi! Selamat makan, {$karyawan->nama_lengkap}.",
            'karyawan' => $karyawan,
            'meal' => $meal,
        ];
    }

    public function verifyScan(
        string $identifier,
        ?string $jenisMakan = null,
        ?int $deviceId = null,
        ?int $userId = null
    ): array {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return [
                'success' => false,
                'reason' => 'empty_input',
                'message' => 'Silakan tempelkan kartu RFID atau masukkan NIK karyawan.',
            ];
        }

        $karyawan = Karyawan::query()
            ->with(['departemenData', 'jabatanData', 'lokasiGps'])
            ->where(function (Builder $query) use ($identifier) {
                $query->where('rfid_uid', $identifier)
                    ->orWhere('nik', $identifier)
                    ->orWhere('qr_code', $identifier);
            })
            ->first();

        if (! $karyawan) {
            return [
                'success' => false,
                'reason' => 'not_found',
                'message' => "Kartu RFID / NIK '{$identifier}' tidak terdaftar dalam sistem.",
            ];
        }

        $session = $jenisMakan ?: $this->determineCurrentMealSession();
        $metode = (str_starts_with(strtoupper($identifier), 'A') && strlen($identifier) === 8) ? 'rfid' : 'qr_scan';

        return $this->recordMeal(
            $karyawan,
            $session,
            $metode,
            $deviceId,
            null,
            $userId
        );
    }

    public function determineCurrentMealSession(?Carbon $now = null): string
    {
        $now = $now ?: now();
        $currentTime = $now->format('H:i:s');
        $settings = Setting::query()->find(1);

        $siangStart = $settings?->makan_siang_mulai ?? '11:00:00';
        $siangEnd = $settings?->makan_siang_selesai ?? '14:30:00';

        $malamStart = $settings?->makan_malam_mulai ?? '18:00:00';
        $malamEnd = $settings?->makan_malam_selesai ?? '20:45:00';

        $lemburStart = $settings?->makan_lembur_mulai ?? '21:00:00';
        $lemburEnd = $settings?->makan_lembur_selesai ?? '23:59:59';

        if ($currentTime >= $siangStart && $currentTime <= $siangEnd) {
            return 'siang';
        }

        if ($currentTime >= $malamStart && $currentTime <= $malamEnd) {
            return 'malam';
        }

        if ($currentTime >= $lemburStart && $currentTime <= $lemburEnd) {
            return 'lembur';
        }

        return 'siang';
    }

    public function getTodayStats(?Carbon $date = null): array
    {
        $date = $date ? $date->toDateString() : now()->toDateString();

        $query = AbsensiMakan::query()
            ->whereDate('tanggal', $date)
            ->where('status', 'valid');

        $totalPorsi = (clone $query)->count();
        $porsiSiang = (clone $query)->where('jenis_makan', 'siang')->count();
        $porsiMalam = (clone $query)->where('jenis_makan', 'malam')->count();
        $porsiLembur = (clone $query)->where('jenis_makan', 'lembur')->count();
        $totalNominal = (float) (clone $query)->sum('nominal');

        return [
            'total_porsi' => $totalPorsi,
            'porsi_siang' => $porsiSiang,
            'porsi_malam' => $porsiMalam,
            'porsi_lembur' => $porsiLembur,
            'total_nominal' => $totalNominal,
            'formatted_nominal' => 'Rp ' . number_format($totalNominal, 0, ',', '.'),
        ];
    }
}
