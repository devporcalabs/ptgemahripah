<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LokasiGps;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $settings = Setting::query()->find(1);
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);
        $locations = LokasiGps::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query
                    ->where('nama_lokasi', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%");
            })
            ->orderByDesc('is_default')
            ->orderBy('nama_lokasi')
            ->paginate($perPage)
            ->withQueryString();
        $editId = $request->integer('edit');

        if (! $editId && old('form_type') === 'edit') {
            $editId = (int) old('edit_id');
        }

        $editLocation = $editId
            ? LokasiGps::query()->find($editId)
            : null;

        $defaultLocation = LokasiGps::query()
            ->orderByDesc('is_default')
            ->orderBy('nama_lokasi')
            ->first();

        return view('admin.location', [
            'locations' => $locations,
            'editLocation' => $editLocation,
            'defaultLocation' => $defaultLocation,
            'search' => $search,
            'perPage' => $perPage,
            'centerLat' => (float) old('latitude', $editLocation->latitude ?? $defaultLocation->latitude ?? $settings?->latitude ?? -6.2),
            'centerLng' => (float) old('longitude', $editLocation->longitude ?? $defaultLocation->longitude ?? $settings?->longitude ?? 106.81666667),
            'centerRadius' => (int) old('radius', $editLocation->radius ?? $defaultLocation->radius ?? $settings?->radius_valid ?? 100),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data): void {
            $location = LokasiGps::query()->create($data);

            if (($data['is_default'] ?? 0) || ! LokasiGps::query()->where('is_default', 1)->exists()) {
                $this->setDefaultLocation($location);
            }

            $this->syncSettingLocation();
        });

        return $this->respondSuccess($request, route('admin.radius-gps'), 'Lokasi GPS berhasil ditambahkan.');
    }

    public function update(Request $request, LokasiGps $lokasiGps)
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($lokasiGps, $data): void {
            $lokasiGps->update($data);

            if ($data['is_default'] ?? false) {
                $this->setDefaultLocation($lokasiGps->fresh());
            } elseif ($lokasiGps->is_default && ($data['status'] ?? 1) == 0) {
                $lokasiGps->update(['is_default' => 0]);
            }

            $this->syncSettingLocation();
        });

        return $this->respondSuccess($request, route('admin.radius-gps'), 'Lokasi GPS berhasil diperbarui.');
    }

    public function destroy(Request $request, LokasiGps $lokasiGps)
    {
        $wasDefault = (bool) $lokasiGps->is_default;
        $lokasiGps->delete();

        if ($wasDefault) {
            $this->syncSettingLocation();
        }

        return $this->respondSuccess($request, route('admin.radius-gps'), 'Lokasi GPS berhasil dihapus.');
    }

    public function updateStatus(Request $request, LokasiGps $lokasiGps)
    {
        $lokasiGps->update([
            'status' => $lokasiGps->status ? 0 : 1,
        ]);

        if (! $lokasiGps->fresh()->status && $lokasiGps->is_default) {
            $lokasiGps->update(['is_default' => 0]);
        }

        $this->syncSettingLocation();

        return $this->respondSuccess($request, route('admin.radius-gps'), 'Status lokasi berhasil diperbarui.');
    }

    public function makeDefault(Request $request, LokasiGps $lokasiGps)
    {
        if (! $lokasiGps->status) {
            return $this->respondError($request, route('admin.radius-gps'), 'Lokasi nonaktif tidak bisa dijadikan default.', status: 422);
        }

        $this->setDefaultLocation($lokasiGps);
        $this->syncSettingLocation();

        return $this->respondSuccess($request, route('admin.radius-gps'), 'Lokasi default berhasil diperbarui.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:10', 'max:10000'],
            'alamat' => ['nullable', 'string'],
            'status' => ['required', 'in:0,1'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    private function setDefaultLocation(LokasiGps $location): void
    {
        LokasiGps::query()->update(['is_default' => 0]);
        $location->update(['is_default' => 1]);
    }

    private function syncSettingLocation(): void
    {
        $defaultLocation = LokasiGps::query()
            ->where('status', 1)
            ->where('is_default', 1)
            ->first();

        if (! $defaultLocation) {
            $defaultLocation = LokasiGps::query()
                ->where('status', 1)
                ->orderBy('id')
                ->first();

            if ($defaultLocation) {
                $defaultLocation->update(['is_default' => 1]);
            }
        }

        if (! $defaultLocation) {
            return;
        }

        Setting::query()->updateOrCreate(
            ['id' => 1],
            [
                'latitude' => $defaultLocation->latitude,
                'longitude' => $defaultLocation->longitude,
                'radius_valid' => $defaultLocation->radius,
            ],
        );
    }
}
