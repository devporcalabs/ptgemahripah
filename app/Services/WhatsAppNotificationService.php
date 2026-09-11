<?php

namespace App\Services;

use App\Jobs\SendSystemWhatsAppNotificationJob;
use App\Models\Absensi;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\Setting;
use Carbon\Carbon;

class WhatsAppNotificationService
{
    private ?Setting $setting = null;

    public function __construct(
        private readonly WhatsAppService $whatsAppService,
    ) {
    }

    public function queueAttendanceNotification(Karyawan $employee, Absensi $attendance, array $context = []): bool
    {
        if (! $this->isEnabled('notif_absensi')) {
            return false;
        }

        $phone = $this->resolvePhone($employee);
        if ($phone === null) {
            return false;
        }

        SendSystemWhatsAppNotificationJob::dispatch('attendance', $attendance->id, $context);

        return true;
    }

    public function queueLeaveSubmittedNotification(Izin $leave): bool
    {
        if (! $this->isEnabled('notif_izin')) {
            return false;
        }

        $employee = $leave->karyawan;
        if (! $employee) {
            return false;
        }

        $phone = $this->resolvePhone($employee);
        if ($phone === null) {
            return false;
        }

        SendSystemWhatsAppNotificationJob::dispatch('leave_submitted', $leave->id);

        return true;
    }

    public function queueLeaveStatusNotification(Izin $leave): bool
    {
        if (! $this->isEnabled('notif_izin')) {
            return false;
        }

        $employee = $leave->karyawan;
        if (! $employee) {
            return false;
        }

        $phone = $this->resolvePhone($employee);
        if ($phone === null) {
            return false;
        }

        SendSystemWhatsAppNotificationJob::dispatch('leave_status', $leave->id);

        return true;
    }

    public function deliverAttendanceNotification(int|Absensi $attendance, array $context = []): bool
    {
        $attendance = $attendance instanceof Absensi
            ? $attendance->loadMissing('karyawan')
            : Absensi::query()->with('karyawan')->find($attendance);

        if (! $attendance || ! $attendance->karyawan) {
            return false;
        }

        if (! $this->isEnabled('notif_absensi')) {
            return false;
        }

        $phone = $this->resolvePhone($attendance->karyawan);
        if ($phone === null) {
            return false;
        }

        return $this->whatsAppService->send(
            $phone,
            $this->buildAttendanceMessage($attendance->karyawan, $attendance, $context)
        );
    }

    public function deliverLeaveSubmittedNotification(int|Izin $leave): bool
    {
        $leave = $leave instanceof Izin
            ? $leave->loadMissing('karyawan', 'jenisIzin')
            : Izin::query()->with(['karyawan', 'jenisIzin'])->find($leave);

        if (! $leave || ! $leave->karyawan) {
            return false;
        }

        if (! $this->isEnabled('notif_izin')) {
            return false;
        }

        $phone = $this->resolvePhone($leave->karyawan);
        if ($phone === null) {
            return false;
        }

        return $this->whatsAppService->send($phone, $this->buildLeaveSubmittedMessage($leave));
    }

    public function deliverLeaveStatusNotification(int|Izin $leave): bool
    {
        $leave = $leave instanceof Izin
            ? $leave->loadMissing('karyawan', 'jenisIzin')
            : Izin::query()->with(['karyawan', 'jenisIzin'])->find($leave);

        if (! $leave || ! $leave->karyawan) {
            return false;
        }

        if (! $this->isEnabled('notif_izin')) {
            return false;
        }

        $phone = $this->resolvePhone($leave->karyawan);
        if ($phone === null) {
            return false;
        }

        return $this->whatsAppService->send($phone, $this->buildLeaveStatusMessage($leave));
    }

    private function buildAttendanceMessage(Karyawan $employee, Absensi $attendance, array $context): string
    {
        $institutionName = $this->institutionName();
        $date = $attendance->tanggal instanceof Carbon
            ? $attendance->tanggal->translatedFormat('d F Y')
            : Carbon::parse($attendance->tanggal)->translatedFormat('d F Y');
        $type = (string) ($context['type'] ?? ($attendance->jam_keluar ? 'checkout' : 'checkin'));
        $statusLabel = trim((string) ($context['status_label'] ?? 'Absensi'));
        $message = trim((string) ($context['message'] ?? 'Absensi berhasil diproses.'));

        $lines = [
            "Halo {$employee->nama_lengkap},",
            '',
            "{$institutionName}",
            $message,
            "Tanggal: {$date}",
            'Status: '.$statusLabel,
        ];

        if ($type === 'checkin' && $attendance->jam_masuk) {
            $lines[] = 'Jam masuk: '.$attendance->jam_masuk;
        }

        if ($type === 'checkout' && $attendance->jam_keluar) {
            $lines[] = 'Jam pulang: '.$attendance->jam_keluar;
        }

        $lateMinutes = (int) ($attendance->menit_terlambat ?? 0);
        if ($lateMinutes > 0) {
            $lines[] = 'Terlambat: '.$this->formatMinutes($lateMinutes);
        }

        $earlyMinutes = (int) ($attendance->menit_pulang_cepat ?? 0);
        if ($earlyMinutes > 0) {
            $lines[] = 'Pulang cepat: '.$this->formatMinutes($earlyMinutes);
        }

        $overtimeMinutes = (int) ($attendance->menit_lembur ?? 0);
        if ($overtimeMinutes > 0) {
            $lines[] = 'Lembur: '.$this->formatMinutes($overtimeMinutes);
        }

        $lines[] = '';
        $lines[] = 'Pesan ini dikirim otomatis oleh sistem.';

        return implode("\n", $lines);
    }

    private function buildLeaveSubmittedMessage(Izin $leave): string
    {
        $employee = $leave->karyawan;
        $institutionName = $this->institutionName();
        $periodLabel = $this->leavePeriodLabel($leave);

        return implode("\n", [
            "Halo {$employee->nama_lengkap},",
            '',
            "{$institutionName}",
            "Pengajuan {$leave->leave_type_name} berhasil diterima.",
            "Periode: {$periodLabel}",
            'Durasi: '.$leave->effective_jumlah_hari.' hari kerja',
            'Status: Menunggu persetujuan',
            '',
            'Pesan ini dikirim otomatis oleh sistem.',
        ]);
    }

    private function buildLeaveStatusMessage(Izin $leave): string
    {
        $employee = $leave->karyawan;
        $institutionName = $this->institutionName();
        $periodLabel = $this->leavePeriodLabel($leave);
        $statusLabel = match ((string) $leave->status) {
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            default => 'Pending',
        };

        $lines = [
            "Halo {$employee->nama_lengkap},",
            '',
            "{$institutionName}",
            "Pengajuan {$leave->leave_type_name} Anda {$statusLabel}.",
            "Periode: {$periodLabel}",
            'Durasi: '.$leave->effective_jumlah_hari.' hari kerja',
            "Status: {$statusLabel}",
        ];

        $adminNote = trim((string) ($leave->catatan_admin ?? ''));
        if ($adminNote !== '') {
            $lines[] = 'Catatan admin: '.$adminNote;
        }

        $lines[] = '';
        $lines[] = 'Pesan ini dikirim otomatis oleh sistem.';

        return implode("\n", $lines);
    }

    private function leavePeriodLabel(Izin $leave): string
    {
        $start = $leave->tanggal_mulai_efektif;
        $end = $leave->tanggal_selesai_efektif;

        if (! $start || ! $end) {
            return '-';
        }

        if ($start->toDateString() === $end->toDateString()) {
            return $start->translatedFormat('d F Y');
        }

        return $start->translatedFormat('d F Y').' s/d '.$end->translatedFormat('d F Y');
    }

    private function isEnabled(string $flag): bool
    {
        $setting = $this->setting();

        return (bool) ($setting?->whatsapp_enabled)
            && (bool) data_get($setting, $flag, false)
            && ! empty($setting?->whatsapp_api_key);
    }

    private function setting(): ?Setting
    {
        if ($this->setting === null) {
            $this->setting = Setting::query()->find(1);
        }

        return $this->setting;
    }

    private function institutionName(): string
    {
        return $this->setting()?->nama_instansi ?: config('app.name');
    }

    private function resolvePhone(Karyawan $employee): ?string
    {
        $phone = trim((string) ($employee->telepon ?: $employee->no_telp ?: ''));

        return $phone === '' ? null : $phone;
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' menit';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        if ($remaining === 0) {
            return $hours.' jam';
        }

        return $hours.' jam '.$remaining.' menit';
    }
}
