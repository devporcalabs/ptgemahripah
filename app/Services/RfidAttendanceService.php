<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use Carbon\Carbon;

class RfidAttendanceService
{
    public function __construct(
        private readonly ShiftScheduleResolver $shiftScheduleResolver,
        private readonly AttendanceOvertimeService $attendanceOvertimeService,
        private readonly WhatsAppNotificationService $whatsAppNotificationService,
    ) {
    }

    public function process(Karyawan $karyawan, Carbon $scannedAt): array
    {
        $scanTime = $scannedAt->copy()->timezone(config('app.timezone'));

        if (($karyawan->status ?? 'aktif') !== 'aktif') {
            return [
                'success' => false,
                'reason' => 'employee_inactive',
                'message' => 'Karyawan sedang nonaktif.',
                'nama' => $karyawan->nama_lengkap,
                'nik' => $karyawan->nik,
            ];
        }

        $schedule = $this->shiftScheduleResolver->resolveForAttendance($karyawan, $scanTime);

        $attendance = Absensi::query()
            ->where('karyawan_id', $karyawan->id)
            ->whereDate('tanggal', $scanTime->toDateString())
            ->first();

        if ($attendance && $attendance->jam_masuk && $attendance->jam_keluar) {
            return [
                'success' => false,
                'reason' => 'already_checked_out',
                'message' => 'Absensi masuk dan pulang untuk hari ini sudah lengkap.',
                'nama' => $karyawan->nama_lengkap,
                'nik' => $karyawan->nik,
                'checkin_time' => $attendance->jam_masuk,
                'checkout_time' => $attendance->jam_keluar,
            ];
        }

        if ($attendance && ! $attendance->jam_masuk) {
            return [
                'success' => false,
                'reason' => 'attendance_locked',
                'message' => 'Data absensi hari ini tidak dapat diproses dari RFID.',
                'nama' => $karyawan->nama_lengkap,
                'nik' => $karyawan->nik,
            ];
        }

        if ($attendance) {
            return $this->processCheckout($attendance, $karyawan, $scanTime, $schedule);
        }

        return $this->processCheckin($karyawan, $scanTime, $schedule);
    }

    protected function processCheckin(Karyawan $karyawan, Carbon $scanTime, array $schedule): array
    {
        $expectedStart = $schedule['expected_start'];
        $expectedCheckout = $schedule['expected_checkout'];
        $isHoliday = ! ($schedule['is_workday'] ?? true);
        $isFlexible = ($schedule['jenis_jam_kerja'] ?? null) === 'fleksibel';
        $checkinWindowBeforeMinutes = max(0, (int) ($schedule['checkin_window_before'] ?? 30));
        $toleranceMinutes = $schedule['tolerance_minutes'];
        $flexibleDurationHours = max(0.0, (float) ($schedule['durasi_kerja_fleksibel'] ?? 8));
        $flexibleExpectedCheckout = $isFlexible && $flexibleDurationHours > 0
            ? $scanTime->copy()->addMinutes((int) round($flexibleDurationHours * 60))
            : null;
        $allowedFrom = $expectedStart instanceof Carbon
            ? $expectedStart->copy()->subMinutes($checkinWindowBeforeMinutes)
            : null;
        $lateMinutes = 0;
        $reason = 'checkin_on_time';
        $status = 'hadir';
        $statusLabel = 'Masuk';
        $message = $isHoliday
            ? 'Absensi masuk hari libur berhasil diterima.'
            : 'Absensi masuk berhasil diterima.';

        if (! $isFlexible && $expectedStart instanceof Carbon) {
            if ($allowedFrom instanceof Carbon && $scanTime->lt($allowedFrom)) {
                return [
                    'success' => false,
                    'reason' => 'outside_checkin_window',
                    'message' => 'Absensi masuk terlalu awal. Silakan scan mendekati jam shift.',
                    'nama' => $karyawan->nama_lengkap,
                    'nik' => $karyawan->nik,
                    'jabatan' => $karyawan->jabatan,
                    'departemen' => $karyawan->departemen,
                    'checkin_window_before' => $checkinWindowBeforeMinutes,
                    'checkin_start' => $allowedFrom->format('H:i:s'),
                    'checkin_end' => $expectedCheckout?->format('H:i:s'),
                ];
            }

            if ($expectedCheckout instanceof Carbon && $scanTime->gt($expectedCheckout)) {
                return [
                    'success' => false,
                    'reason' => 'outside_checkin_window',
                    'message' => 'Absensi masuk melewati jam pulang shift.',
                    'nama' => $karyawan->nama_lengkap,
                    'nik' => $karyawan->nik,
                    'jabatan' => $karyawan->jabatan,
                    'departemen' => $karyawan->departemen,
                    'checkin_window_before' => $checkinWindowBeforeMinutes,
                    'checkin_start' => $allowedFrom?->format('H:i:s'),
                    'checkin_end' => $expectedCheckout->format('H:i:s'),
                ];
            }

            $lateMinutes = max(0, $expectedStart->diffInMinutes($scanTime, false));

            if ($lateMinutes > $toleranceMinutes) {
                $lateMinutes -= $toleranceMinutes;
                $reason = 'checkin_late';
                $status = 'terlambat';
                $statusLabel = 'Terlambat';
                $message = 'Absensi masuk tercatat sebagai terlambat.';
            } else {
                $lateMinutes = 0;
            }
        }

        $attendance = Absensi::query()->create([
            'karyawan_id' => $karyawan->id,
            'tanggal' => $scanTime->toDateString(),
            'jam_masuk' => $scanTime->format('H:i:s'),
            'status' => $status,
            'lokasi_masuk' => 'RFID',
            'shift_id' => $schedule['shift_id'],
            'shift_assignment_id' => $schedule['assignment_id'],
            'schedule_source' => $schedule['source'],
            'jenis_jam_kerja' => $this->resolveAttendanceWorkType($karyawan, $schedule),
            'menit_terlambat' => $lateMinutes,
            'menit_pulang_cepat' => 0,
        ]);

        $this->whatsAppNotificationService->queueAttendanceNotification($karyawan, $attendance, [
            'type' => 'checkin',
            'status_label' => $statusLabel,
            'message' => $message,
        ]);

        return [
            'success' => true,
            'reason' => $reason,
            'message' => $message,
            'type' => 'checkin',
            'status_label' => $statusLabel,
            'nama' => $karyawan->nama_lengkap,
            'nik' => $karyawan->nik,
            'jabatan' => $karyawan->jabatan,
            'departemen' => $karyawan->departemen,
            'checkin_window_before' => $checkinWindowBeforeMinutes,
            'durasi_kerja_fleksibel' => $schedule['durasi_kerja_fleksibel'] ?? null,
            'jamDatang' => $attendance->jam_masuk,
            'late_minutes' => $lateMinutes,
            'checkin_start' => $allowedFrom?->format('H:i:s'),
            'checkin_end' => $flexibleExpectedCheckout?->format('H:i:s') ?? $expectedCheckout?->format('H:i:s'),
            'expected_checkout' => $flexibleExpectedCheckout?->format('H:i:s') ?? $expectedCheckout?->format('H:i:s'),
        ];
    }

    protected function processCheckout(Absensi $attendance, Karyawan $karyawan, Carbon $scanTime, array $schedule): array
    {
        $expectedStart = $schedule['expected_start'];
        $expectedCheckout = $this->attendanceOvertimeService->expectedCheckout($attendance, $karyawan);
        if ($expectedStart instanceof Carbon && $scanTime->lt($expectedStart)) {
            return [
                'success' => false,
                'reason' => 'outside_checkin_window',
                'message' => 'Absensi pulang belum boleh diproses sebelum jam masuk shift.',
                'type' => 'checkout',
                'status_label' => 'Belum Waktunya Pulang',
                'nama' => $karyawan->nama_lengkap,
                'nik' => $karyawan->nik,
                'jabatan' => $karyawan->jabatan,
                'departemen' => $karyawan->departemen,
                'checkin_window_before' => $schedule['checkin_window_before'] ?? null,
                'jamDatang' => $attendance->jam_masuk,
                'expected_start' => $expectedStart->format('H:i:s'),
                'expected_checkout' => $expectedCheckout?->format('H:i:s'),
                'checkin_start' => $expectedStart->format('H:i:s'),
                'checkin_end' => $expectedCheckout?->format('H:i:s'),
                'checkout_time' => null,
            ];
        }

        $reason = 'checkout_on_time';
        $statusLabel = 'Pulang';
        $message = 'Absensi pulang berhasil diterima.';
        $earlyMinutes = 0;
        $attendanceDate = $attendance->tanggal instanceof Carbon
            ? $attendance->tanggal->copy()
            : Carbon::parse($attendance->tanggal);
        $actualCheckin = $attendance->jam_masuk
            ? Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $attendanceDate->toDateString().' '.(strlen($attendance->jam_masuk) === 5 ? $attendance->jam_masuk.':00' : $attendance->jam_masuk),
                config('app.timezone')
            )
            : null;

        $overtime = $this->attendanceOvertimeService->calculateFromCheckout(
            $expectedCheckout,
            $scanTime,
            $karyawan,
            $attendanceDate,
            $schedule,
            $actualCheckin,
        );

        if ($expectedCheckout instanceof Carbon && $scanTime->lt($expectedCheckout)) {
            $reason = 'checkout_early';
            $statusLabel = 'Pulang Cepat';
            $message = 'Absensi pulang tercatat lebih awal.';
            $earlyMinutes = $scanTime->diffInMinutes($expectedCheckout);
        } elseif ((int) ($overtime['menit_lembur'] ?? 0) > 0) {
            $reason = 'checkout_overtime';
            $statusLabel = 'Lembur';
            $message = $expectedCheckout instanceof Carbon
                ? 'Absensi pulang tercatat lembur otomatis.'
                : 'Absensi hari libur tercatat lembur otomatis.';
        }

        $attendance->update([
            'jam_keluar' => $scanTime->format('H:i:s'),
            'lokasi_keluar' => 'RFID',
            'shift_id' => $attendance->shift_id ?: $schedule['shift_id'],
            'shift_assignment_id' => $attendance->shift_assignment_id ?: $schedule['assignment_id'],
            'schedule_source' => $attendance->schedule_source ?: $schedule['source'],
            'menit_pulang_cepat' => $earlyMinutes,
            'menit_lembur' => (int) ($overtime['menit_lembur'] ?? 0),
            'jam_lembur' => (float) ($overtime['jam_lembur'] ?? 0),
            'tarif_lembur' => (float) ($overtime['tarif_lembur'] ?? 0),
        ]);

        $attendance->refresh();

        $this->whatsAppNotificationService->queueAttendanceNotification($karyawan, $attendance, [
            'type' => 'checkout',
            'status_label' => $statusLabel,
            'message' => $message,
        ]);

        return [
            'success' => true,
            'reason' => $reason,
            'message' => $message,
            'type' => 'checkout',
            'status_label' => $statusLabel,
            'nama' => $karyawan->nama_lengkap,
            'nik' => $karyawan->nik,
            'jabatan' => $karyawan->jabatan,
            'departemen' => $karyawan->departemen,
            'checkin_window_before' => $schedule['checkin_window_before'] ?? null,
            'durasi_kerja_fleksibel' => $schedule['durasi_kerja_fleksibel'] ?? null,
            'jamDatang' => $attendance->jam_masuk,
            'jamPulang' => $attendance->jam_keluar,
            'checkin_time' => $attendance->jam_masuk,
            'checkout_time' => $attendance->jam_keluar,
            'expected_checkout' => $expectedCheckout?->format('H:i:s'),
            'early_minutes' => $earlyMinutes,
            'menit_lembur' => (int) ($overtime['menit_lembur'] ?? 0),
            'jam_lembur' => (float) ($overtime['jam_lembur'] ?? 0),
            'tarif_lembur' => (float) ($overtime['tarif_lembur'] ?? 0),
            'checkin_start' => null,
            'checkin_end' => null,
        ];
    }

    protected function resolveAttendanceWorkType(Karyawan $karyawan, array $schedule): string
    {
        $workType = (string) ($schedule['jenis_jam_kerja'] ?? '');

        return match ($workType) {
            'rolling', 'fleksibel', 'tetap' => $workType,
            'libur' => $this->normalizeWorkType($karyawan->jenis_jam_kerja),
            default => $this->normalizeWorkType($karyawan->jenis_jam_kerja),
        };
    }

    protected function normalizeWorkType(?string $value): string
    {
        return match (trim(strtolower((string) $value))) {
            'rolling' => 'rolling',
            'fleksibel' => 'fleksibel',
            'shift', 'tetap', '' => 'tetap',
            default => 'tetap',
        };
    }
}
