<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    private const STATUS_LABELS = [
        Device::STATUS_PENDING => 'Pending',
        Device::STATUS_ACTIVE => 'Aktif',
        Device::STATUS_INACTIVE => 'Nonaktif',
        Device::STATUS_REVOKED => 'Dicabut',
    ];

    public function index(Request $request)
    {
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);
        $statusFilter = trim($request->string('status')->toString());
        $editId = $request->integer('edit');

        if (! $editId && old('form_type') === 'edit') {
            $editId = (int) old('edit_id');
        }

        $editDevice = $editId
            ? Device::query()->find($editId)
            : null;

        $devices = Device::query()
            ->withCount('attendanceLogs')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('mac_address', 'like', "%{$search}%")
                        ->orWhere('firmware_version', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter !== '', function ($query) use ($statusFilter): void {
                $query->where('status', $statusFilter);
            })
            ->orderByRaw("
                CASE status
                    WHEN '".Device::STATUS_ACTIVE."' THEN 1
                    WHEN '".Device::STATUS_INACTIVE."' THEN 2
                    WHEN '".Device::STATUS_PENDING."' THEN 3
                    WHEN '".Device::STATUS_REVOKED."' THEN 4
                    ELSE 5
                END
            ")
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.mesin-absensi', [
            'devices' => $devices,
            'deviceStats' => $this->deviceStats(),
            'statusOptions' => self::STATUS_LABELS,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'perPage' => $perPage,
            'editDevice' => $editDevice,
            'deviceApiBaseUrl' => url('/api/device'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'serial_number' => ['required', 'string', 'max:50', 'unique:devices,serial_number'],
        ]);

        Device::query()->create([
            'name' => trim($data['name']),
            'serial_number' => trim($data['serial_number']),
            'status' => Device::STATUS_PENDING,
        ]);

        return $this->respondSuccess($request, route('admin.mesin-absensi'), 'Mesin absensi berhasil ditambahkan.');
    }

    public function update(Request $request, Device $device)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'serial_number' => ['required', 'string', 'max:50'],
        ]);

        $incomingSerialNumber = trim($data['serial_number']);

        if ($incomingSerialNumber !== (string) $device->serial_number) {
            return $this->respondError(
                $request,
                route('admin.mesin-absensi'),
                'Serial number mesin tidak dapat diubah. Buat data mesin baru jika perangkat berbeda.',
                status: 422
            );
        }

        $device->update([
            'name' => trim($data['name']),
        ]);

        return $this->respondSuccess($request, route('admin.mesin-absensi'), 'Data mesin absensi berhasil diperbarui.');
    }

    public function logs(Request $request, Device $device)
    {
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request, 25);
        $tanggalFilter = trim($request->string('tanggal')->toString());

        $logs = AttendanceLog::query()
            ->where('attendance_logs.device_id', $device->id)
            ->leftJoin('karyawan', 'karyawan.rfid_uid', '=', 'attendance_logs.uid')
            ->select([
                'attendance_logs.*',
                'karyawan.id as karyawan_id',
                'karyawan.nik',
                'karyawan.nama_lengkap',
                'karyawan.jabatan',
                'karyawan.departemen',
            ])
            ->when($tanggalFilter !== '', function ($query) use ($tanggalFilter): void {
                $query->whereDate('attendance_logs.scanned_at', $tanggalFilter);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('attendance_logs.uid', 'like', "%{$search}%")
                        ->orWhere('karyawan.nik', 'like', "%{$search}%")
                        ->orWhere('karyawan.nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('karyawan.jabatan', 'like', "%{$search}%")
                        ->orWhere('karyawan.departemen', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('attendance_logs.scanned_at')
            ->paginate($perPage)
            ->withQueryString();

        $statsBaseQuery = AttendanceLog::query()
            ->where('device_id', $device->id);

        $unlinkedLogs = AttendanceLog::query()
            ->where('attendance_logs.device_id', $device->id)
            ->leftJoin('karyawan', 'karyawan.rfid_uid', '=', 'attendance_logs.uid')
            ->whereNull('karyawan.id')
            ->count();

        return view('admin.mesin-absensi-log', [
            'device' => $device,
            'logs' => $logs,
            'search' => $search,
            'perPage' => $perPage,
            'tanggalFilter' => $tanggalFilter,
            'logStats' => [
                'total' => (clone $statsBaseQuery)->count(),
                'today' => (clone $statsBaseQuery)->whereDate('scanned_at', now()->toDateString())->count(),
                'unique_cards' => (clone $statsBaseQuery)->distinct()->count('uid'),
                'unlinked' => $unlinkedLogs,
            ],
        ]);
    }

    public function activate(Request $request, Device $device)
    {
        if ($device->status === Device::STATUS_ACTIVE) {
            return $this->respondSuccess($request, route('admin.mesin-absensi'), 'Mesin sudah berstatus aktif.');
        }

        if ($device->status === Device::STATUS_REVOKED) {
            return $this->respondError($request, route('admin.mesin-absensi'), 'Mesin yang dicabut harus di-reset terlebih dahulu.', status: 422);
        }

        if (! $device->mac_address || ! $device->device_token) {
            return $this->respondError($request, route('admin.mesin-absensi'), 'Mesin belum pernah aktivasi dari perangkat. Silakan nyalakan mesin lalu lakukan aktivasi.', status: 422);
        }

        $device->update([
            'status' => Device::STATUS_ACTIVE,
        ]);

        return $this->respondSuccess($request, route('admin.mesin-absensi'), 'Mesin absensi berhasil diaktifkan.');
    }

    public function deactivate(Request $request, Device $device)
    {
        if ($device->status === Device::STATUS_INACTIVE) {
            return $this->respondSuccess($request, route('admin.mesin-absensi'), 'Mesin sudah berstatus nonaktif.');
        }

        if ($device->status === Device::STATUS_REVOKED) {
            return $this->respondError($request, route('admin.mesin-absensi'), 'Mesin yang sudah dicabut tidak bisa dinonaktifkan lagi.', status: 422);
        }

        if ($device->status === Device::STATUS_PENDING) {
            return $this->respondError($request, route('admin.mesin-absensi'), 'Mesin yang masih pending belum bisa dinonaktifkan.', status: 422);
        }

        $device->update([
            'status' => Device::STATUS_INACTIVE,
        ]);

        return $this->respondSuccess($request, route('admin.mesin-absensi'), 'Mesin absensi berhasil dinonaktifkan.');
    }

    public function revoke(Request $request, Device $device)
    {
        if ($device->status === Device::STATUS_REVOKED) {
            return $this->respondSuccess($request, route('admin.mesin-absensi'), 'Mesin sudah berstatus dicabut.');
        }

        $device->update([
            'status' => Device::STATUS_REVOKED,
        ]);

        return $this->respondSuccess($request, route('admin.mesin-absensi'), 'Mesin absensi berhasil dicabut.');
    }

    public function reset(Request $request, Device $device)
    {
        $device->update([
            'mac_address' => null,
            'device_token' => null,
            'firmware_version' => null,
            'status' => Device::STATUS_PENDING,
            'last_seen' => null,
            'activated_at' => null,
        ]);

        return $this->respondSuccess($request, route('admin.mesin-absensi'), 'Mesin absensi berhasil di-reset ke status pending.');
    }

    public function destroy(Request $request, Device $device)
    {
        $serialNumber = $device->serial_number;
        $device->delete();

        return $this->respondSuccess($request, route('admin.mesin-absensi'), "Mesin absensi {$serialNumber} berhasil dihapus.");
    }

    private function deviceStats(): array
    {
        return [
            'total' => Device::query()->count(),
            'active' => Device::query()->where('status', Device::STATUS_ACTIVE)->count(),
            'inactive' => Device::query()->where('status', Device::STATUS_INACTIVE)->count(),
            'pending' => Device::query()->where('status', Device::STATUS_PENDING)->count(),
            'revoked' => Device::query()->where('status', Device::STATUS_REVOKED)->count(),
        ];
    }
}
