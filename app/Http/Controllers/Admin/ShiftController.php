<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);
        $editId = $request->integer('edit');

        if (! $editId && old('form_type') === 'edit') {
            $editId = (int) old('edit_id');
        }

        $editShift = $editId ? Shift::query()->find($editId) : null;

        return view('admin.shift', [
            'shiftList' => Shift::query()
                ->when($search !== '', function ($query) use ($search): void {
                    $query
                        ->where('nama_shift', 'like', "%{$search}%")
                        ->orWhere('jam_masuk', 'like', "%{$search}%")
                        ->orWhere('jam_keluar', 'like', "%{$search}%");
                })
                ->orderBy('jam_masuk')
                ->orderBy('nama_shift')
                ->paginate($perPage)
                ->withQueryString(),
            'editShift' => $editShift,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function show(Request $request, Shift $shift)
    {
        $period = $this->resolveMonth($request);
        $search = $this->resolveSearch($request);

        $defaultEmployees = Karyawan::query()
            ->where('shift_id', $shift->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('jabatan', 'like', "%{$search}%")
                        ->orWhere('departemen', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama_lengkap')
            ->limit(20)
            ->get(['id', 'nik', 'nama_lengkap', 'jabatan', 'departemen', 'status']);

        $recentAttendances = Absensi::query()
            ->with('karyawan:id,nik,nama_lengkap,jabatan,departemen')
            ->where('shift_id', $shift->id)
            ->whereBetween('tanggal', [$period->copy()->startOfMonth(), $period->copy()->endOfMonth()])
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('karyawan', function ($employeeQuery) use ($search): void {
                    $employeeQuery->where(function ($innerQuery) use ($search): void {
                        $innerQuery
                            ->where('nama_lengkap', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%")
                            ->orWhere('jabatan', 'like', "%{$search}%")
                            ->orWhere('departemen', 'like', "%{$search}%");
                    });
                });
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_masuk')
            ->limit(25)
            ->get();

        return view('admin.shift-show', [
            'shift' => $shift,
            'period' => $period,
            'search' => $search,
            'defaultEmployees' => $defaultEmployees,
            'recentAttendances' => $recentAttendances,
            'shiftStats' => [
                'default_employee_count' => Karyawan::query()->where('shift_id', $shift->id)->count(),
                'active_employee_count' => Karyawan::query()
                    ->where('shift_id', $shift->id)
                    ->where('status', 'aktif')
                    ->count(),
                'attendance_count' => Absensi::query()
                    ->where('shift_id', $shift->id)
                    ->whereBetween('tanggal', [$period->copy()->startOfMonth(), $period->copy()->endOfMonth()])
                    ->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Shift::query()->create($this->validatedData($request));

        return $this->respondSuccess($request, route('admin.jam-shift'), 'Shift berhasil ditambahkan.');
    }

    public function update(Request $request, Shift $shift)
    {
        $shift->update($this->validatedData($request));

        return $this->respondSuccess($request, route('admin.jam-shift'), 'Shift berhasil diperbarui.');
    }

    public function destroy(Request $request, Shift $shift)
    {
        $isStillUsed = Karyawan::query()->where('shift_id', $shift->id)->exists();

        if ($isStillUsed) {
            return $this->respondError(
                $request,
                route('admin.jam-shift'),
                'Shift masih dipakai di data karyawan.',
                status: 409
            );
        }

        $shift->delete();

        return $this->respondSuccess($request, route('admin.jam-shift'), 'Shift berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'nama_shift' => ['required', 'string', 'max:100'],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_keluar' => ['required', 'date_format:H:i'],
            'toleransi' => ['required', 'integer', 'min:0', 'max:180'],
            'checkin_window_before' => ['required', 'integer', 'min:0', 'max:240'],
            'kode_warna' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'aktif' => ['required', Rule::in(['0', '1'])],
        ], [
            'kode_warna.regex' => 'Format kode warna tidak valid.',
        ]) + [
            'kode_warna' => $request->input('kode_warna', '#065F46'),
        ];
    }

    private function resolveMonth(Request $request): Carbon
    {
        $value = $request->string('bulan')->toString();

        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        }

        return now()->startOfMonth();
    }
}
