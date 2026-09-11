<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\AttendanceCorrection;
use App\Models\Shift;
use App\Services\AttendanceCorrectionService;
use App\Services\AttendanceOvertimeService;
use App\Services\PayrollPeriodService;
use App\Services\SalaryService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\Request;

class AttendanceCorrectionController extends Controller
{
    public function __construct(
        private readonly AttendanceCorrectionService $attendanceCorrectionService,
        private readonly AttendanceOvertimeService $attendanceOvertimeService,
        private readonly SalaryService $salaryService,
        private readonly PayrollPeriodService $payrollPeriodService,
    ) {
    }

    public function store(Request $request, Absensi $absensi)
    {
        try {
            $this->payrollPeriodService->assertEditable(
                Carbon::parse($absensi->tanggal ?: now())->startOfMonth(),
                'Periode absensi ini sudah masuk payroll final.'
            );
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.absensi'), $exception->getMessage(), status: 422);
        }

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'shift_id' => ['nullable', 'integer', 'exists:shift,id'],
            'status' => ['required', 'in:hadir,terlambat,izin,cuti'],
            'jam_masuk' => ['nullable', 'date_format:H:i'],
            'jam_keluar' => ['nullable', 'date_format:H:i'],
            'lokasi_masuk' => ['nullable', 'string', 'max:255'],
            'lokasi_keluar' => ['nullable', 'string', 'max:255'],
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $latestCorrection = $absensi->corrections()->latest('id')->first();

        if ($latestCorrection?->status === 'pending') {
            return $this->respondError(
                $request,
                route('admin.absensi'),
                'Masih ada koreksi absensi yang menunggu persetujuan.',
                status: 422
            );
        }

        if (in_array($validated['status'], ['hadir', 'terlambat'], true) && empty($validated['jam_masuk'])) {
            return $this->respondError(
                $request,
                route('admin.absensi'),
                'Jam masuk wajib diisi untuk status hadir atau terlambat.',
                status: 422
            );
        }

        AttendanceCorrection::query()->create([
            'absensi_id' => $absensi->id,
            'previous_data' => [
                'tanggal' => $absensi->tanggal?->toDateString(),
                'shift_id' => $absensi->shift_id,
                'status' => $absensi->status,
                'jam_masuk' => $absensi->jam_masuk,
                'jam_keluar' => $absensi->jam_keluar,
                'lokasi_masuk' => $absensi->lokasi_masuk,
                'lokasi_keluar' => $absensi->lokasi_keluar,
            ],
            'requested_data' => $this->attendanceCorrectionService->normalizeRequestData($validated),
            'note' => $validated['note'],
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        return $this->respondSuccess($request, route('admin.absensi'), 'Permintaan koreksi absensi berhasil dibuat.');
    }

    public function approve(Request $request, AttendanceCorrection $attendanceCorrection)
    {
        if ($attendanceCorrection->status !== 'pending') {
            return $this->respondError(
                $request,
                route('admin.absensi'),
                'Koreksi absensi ini sudah diproses sebelumnya.',
                status: 422
            );
        }

        $request->validate([
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $absensi = $attendanceCorrection->absensi()->with('karyawan')->firstOrFail();
        $oldPeriod = Carbon::parse($absensi->tanggal ?: now())->startOfMonth();
        $appliedData = $this->attendanceCorrectionService->buildAppliedData($attendanceCorrection->requested_data ?? []);
        $newPeriod = Carbon::parse($appliedData['tanggal'])->startOfMonth();

        try {
            $this->payrollPeriodService->assertEditable($oldPeriod, 'Periode absensi lama sudah masuk payroll final.');
            $this->payrollPeriodService->assertEditable($newPeriod, 'Periode absensi tujuan sudah masuk payroll final.');
        } catch (DomainException $exception) {
            return $this->respondError($request, route('admin.absensi'), $exception->getMessage(), status: 422);
        }

        if (! empty($appliedData['shift_id']) && ! Shift::query()->whereKey($appliedData['shift_id'])->exists()) {
            return $this->respondError(
                $request,
                route('admin.absensi'),
                'Shift pada permintaan koreksi tidak ditemukan.',
                status: 422
            );
        }

        $absensi->update($appliedData);
        $overtime = $this->attendanceOvertimeService->recalculate($absensi, $absensi->karyawan);
        $absensi->update([
            'menit_lembur' => (int) ($overtime['menit_lembur'] ?? 0),
            'jam_lembur' => (float) ($overtime['jam_lembur'] ?? 0),
            'tarif_lembur' => (float) ($overtime['tarif_lembur'] ?? 0),
        ]);

        $attendanceCorrection->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'review_note' => $request->input('review_note'),
        ]);

        $this->salaryService->syncHistory($this->salaryService->forEmployee($absensi->karyawan, $oldPeriod));

        if (! $newPeriod->isSameMonth($oldPeriod)) {
            $this->salaryService->syncHistory($this->salaryService->forEmployee($absensi->karyawan, $newPeriod));
        }

        return $this->respondSuccess($request, route('admin.absensi'), 'Koreksi absensi disetujui dan data payroll terkait diperbarui.');
    }

    public function reject(Request $request, AttendanceCorrection $attendanceCorrection)
    {
        if ($attendanceCorrection->status !== 'pending') {
            return $this->respondError(
                $request,
                route('admin.absensi'),
                'Koreksi absensi ini sudah diproses sebelumnya.',
                status: 422
            );
        }

        $request->validate([
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $attendanceCorrection->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'review_note' => $request->input('review_note'),
        ]);

        return $this->respondSuccess($request, route('admin.absensi'), 'Koreksi absensi ditolak.');
    }
}
