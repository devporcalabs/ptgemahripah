<?php

namespace App\Services;

use App\Models\Shift;
use Carbon\Carbon;

class AttendanceCorrectionService
{
    public function normalizeRequestData(array $data): array
    {
        $status = (string) ($data['status'] ?? 'hadir');
        $jamMasuk = $this->normalizeTime($data['jam_masuk'] ?? null);
        $jamKeluar = $this->normalizeTime($data['jam_keluar'] ?? null);

        if (in_array($status, ['izin', 'cuti'], true)) {
            $jamMasuk = null;
            $jamKeluar = null;
        }

        return [
            'tanggal' => (string) ($data['tanggal'] ?? now()->toDateString()),
            'shift_id' => $this->nullableInt($data['shift_id'] ?? null),
            'status' => $status,
            'jam_masuk' => $jamMasuk,
            'jam_keluar' => $jamKeluar,
            'lokasi_masuk' => $this->nullableString($data['lokasi_masuk'] ?? null),
            'lokasi_keluar' => $this->nullableString($data['lokasi_keluar'] ?? null),
        ];
    }

    public function buildAppliedData(array $requested): array
    {
        $requested = $this->normalizeRequestData($requested);
        $shift = ! empty($requested['shift_id'])
            ? Shift::query()->find($requested['shift_id'])
            : null;

        $status = $requested['status'];
        $lateMinutes = 0;
        $earlyMinutes = 0;

        if (in_array($status, ['hadir', 'terlambat'], true)) {
            [$lateMinutes, $earlyMinutes] = $this->calculateVariance(
                $requested['tanggal'],
                $requested['jam_masuk'],
                $requested['jam_keluar'],
                $shift,
            );

            if ($shift) {
                $status = $lateMinutes > 0 ? 'terlambat' : 'hadir';
            }
        }

        return array_merge($requested, [
            'status' => $status,
            'menit_terlambat' => $lateMinutes,
            'menit_pulang_cepat' => $earlyMinutes,
        ]);
    }

    private function calculateVariance(string $date, ?string $jamMasuk, ?string $jamKeluar, ?Shift $shift): array
    {
        if (! $shift || ! $jamMasuk) {
            return [0, 0];
        }

        $shiftStart = $this->combine($date, (string) $shift->jam_masuk);
        $shiftEnd = $this->combine($date, (string) $shift->jam_keluar);
        $actualStart = $this->combine($date, $jamMasuk);
        $actualEnd = $jamKeluar ? $this->combine($date, $jamKeluar) : null;

        if ($shiftEnd && $shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        if ($actualEnd && $actualStart && $actualEnd->lessThanOrEqualTo($actualStart)) {
            $actualEnd->addDay();
        }

        $lateRaw = max(0, $shiftStart->diffInMinutes($actualStart, false));
        $lateMinutes = $lateRaw > 0
            ? max(0, $lateRaw - (int) ($shift->toleransi ?? 0))
            : 0;

        $earlyMinutes = 0;

        if ($shiftEnd && $actualEnd && $actualEnd->lt($shiftEnd)) {
            $earlyMinutes = $actualEnd->diffInMinutes($shiftEnd);
        }

        return [$lateMinutes, $earlyMinutes];
    }

    private function combine(string $date, string $time): Carbon
    {
        $normalizedTime = strlen($time) === 5 ? $time.':00' : $time;

        return Carbon::createFromFormat('Y-m-d H:i:s', $date.' '.$normalizedTime, config('app.timezone'));
    }

    private function normalizeTime(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return strlen($value) === 5 ? $value.':00' : $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value !== null && $value !== '' ? (int) $value : null;
    }
}
