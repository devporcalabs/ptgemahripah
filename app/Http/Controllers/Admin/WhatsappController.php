<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Setting;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WhatsappController extends Controller
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService
    ) {
    }

    public function index()
    {
        return view('admin.whatsapp', [
            'settings' => Setting::query()->find(1),
            'totalRecipients' => Karyawan::query()
                ->where('status', 'aktif')
                ->get()
                ->filter(fn (Karyawan $employee) => ! empty($employee->telepon ?: $employee->no_telp))
                ->count(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'whatsapp_api_key' => ['nullable', 'string', 'max:255'],
            'whatsapp_sender' => ['nullable', 'string', 'max:20'],
        ]);

        Setting::query()->updateOrCreate(
            ['id' => 1],
            [
                ...$validated,
                'whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
                'notif_absensi' => $request->boolean('notif_absensi'),
                'notif_izin' => $request->boolean('notif_izin'),
                'notif_gaji' => $request->boolean('notif_gaji'),
            ],
        );

        return $this->respondSuccess($request, route('admin.whatsapp-gateway'), 'Pengaturan WhatsApp berhasil disimpan.');
    }

    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'test_phone' => ['required', 'string', 'max:20'],
            'test_message' => ['nullable', 'string'],
        ]);

        $message = trim($validated['test_message'] ?? '') !== ''
            ? $validated['test_message']
            : 'Test WhatsApp dari '.$this->resolveInstitutionName().' - '.now()->format('Y-m-d H:i:s');

        if (! $this->whatsAppService->send($validated['test_phone'], $message)) {
            return $this->respondError($request, route('admin.whatsapp-gateway'), 'Pesan test gagal dikirim. Periksa API key dan status gateway.', status: 422);
        }

        return $this->respondSuccess($request, route('admin.whatsapp-gateway'), 'Pesan test berhasil dikirim.');
    }

    public function broadcast(Request $request)
    {
        $validated = $request->validate([
            'broadcast_message' => ['required', 'string'],
        ]);

        $employees = Karyawan::query()
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap')
            ->get(['nama_lengkap', 'telepon', 'no_telp']);

        $successCount = 0;
        $failedCount = 0;
        $failedNames = [];

        @set_time_limit(0);

        foreach ($employees as $employee) {
            $phone = $employee->telepon ?: $employee->no_telp;

            if (empty($phone)) {
                continue;
            }

            if ($this->whatsAppService->send($phone, $validated['broadcast_message'])) {
                $successCount++;
            } else {
                $failedCount++;
                $failedNames[] = $employee->nama_lengkap;
            }
        }

        if ($successCount === 0 && $failedCount === 0) {
            return $this->respondError($request, route('admin.whatsapp-gateway'), 'Tidak ada nomor WhatsApp karyawan yang bisa dipakai.', status: 422);
        }

        $message = "Broadcast selesai: {$successCount} berhasil, {$failedCount} gagal";

        if ($failedCount > 0) {
            $message .= ' ('.implode(', ', array_slice($failedNames, 0, 3)).')';
        }

        return $this->respondSuccess(
            $request,
            route('admin.whatsapp-gateway'),
            $message,
            ['level' => $failedCount > 0 ? 'warning' : 'success'],
        );
    }

    private function resolveInstitutionName(): string
    {
        return Setting::query()->value('nama_instansi') ?: config('app.name');
    }
}
