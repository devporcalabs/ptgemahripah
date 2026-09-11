<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisIzin;
use App\Models\Izin;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);
        $editId = $request->integer('edit');

        if (! $editId && old('form_type') === 'edit') {
            $editId = (int) old('edit_id');
        }

        $editJenisIzin = $editId ? JenisIzin::query()->find($editId) : null;

        return view('admin.jenis-izin', [
            'jenisIzinList' => JenisIzin::query()
                ->withCount('izin')
                ->when($search !== '', function ($query) use ($search): void {
                    $query
                        ->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
                ->orderBy('sort_order')
                ->orderBy('nama')
                ->paginate($perPage)
                ->withQueryString(),
            'editJenisIzin' => $editJenisIzin,
            'search' => $search,
            'perPage' => $perPage,
            'legacyCodeOptions' => $this->legacyCodeOptions(),
            'quotaModeOptions' => $this->quotaModeOptions(),
            'leaveSettings' => Setting::query()->find(1),
            'leaveQuotaPolicyOptions' => $this->leaveQuotaPolicyOptions(),
        ]);
    }

    public function updatePolicy(Request $request)
    {
        $validated = $request->validate([
            'leave_quota_policy' => ['required', Rule::in(array_keys($this->leaveQuotaPolicyOptions()))],
            'leave_carryover_max_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'leave_quota_policy_effective_year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $policy = (string) $validated['leave_quota_policy'];

        Setting::query()->updateOrCreate(
            ['id' => 1],
            [
                'leave_quota_policy' => $policy,
                'leave_carryover_max_days' => $policy === Setting::LEAVE_QUOTA_POLICY_CARRY_LIMITED
                    ? max(0, (int) ($validated['leave_carryover_max_days'] ?? 0))
                    : 0,
                'leave_quota_policy_effective_year' => (int) $validated['leave_quota_policy_effective_year'],
            ],
        );

        return $this->respondSuccess($request, route('admin.jenis-izin'), 'Aturan kuota cuti tahunan berhasil diperbarui.');
    }

    public function store(Request $request)
    {
        JenisIzin::query()->create($this->validatedData($request));

        return $this->respondSuccess($request, route('admin.jenis-izin'), 'Jenis cuti berhasil ditambahkan.');
    }

    public function update(Request $request, JenisIzin $jenisIzin)
    {
        $jenisIzin->update($this->validatedData($request, $jenisIzin));

        return $this->respondSuccess($request, route('admin.jenis-izin'), 'Jenis cuti berhasil diperbarui.');
    }

    public function destroy(Request $request, JenisIzin $jenisIzin)
    {
        if (Izin::query()->where('jenis_izin_id', $jenisIzin->id)->exists()) {
            return $this->respondError($request, route('admin.jenis-izin'), 'Jenis cuti ini sudah dipakai pada data izin/cuti dan tidak bisa dihapus.', status: 409);
        }

        $jenisIzin->delete();

        return $this->respondSuccess($request, route('admin.jenis-izin'), 'Jenis cuti berhasil dihapus.');
    }

    private function validatedData(Request $request, ?JenisIzin $jenisIzin = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('jenis_izin', 'nama')->ignore($jenisIzin?->id)],
            'kode' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('jenis_izin', 'kode')->ignore($jenisIzin?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'legacy_code' => ['required', Rule::in(array_keys($this->legacyCodeOptions()))],
            'is_paid' => ['required', 'boolean'],
            'quota_mode' => ['required', Rule::in(array_keys($this->quotaModeOptions()))],
            'annual_quota_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'max_days_per_request' => ['nullable', 'integer', 'min:0', 'max:365'],
            'require_attachment' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        [$deductQuota, $quotaField, $annualQuotaDays] = $this->resolveQuotaPayload(
            $data['quota_mode'],
            isset($data['annual_quota_days']) ? (int) $data['annual_quota_days'] : null,
        );

        return [
            'nama' => Str::of($data['nama'])->trim()->title()->toString(),
            'kode' => Str::of($data['kode'])->trim()->lower()->replace(' ', '_')->toString(),
            'description' => Str::of((string) ($data['description'] ?? ''))->trim()->toString() ?: null,
            'legacy_code' => $data['legacy_code'],
            'badge_class' => $this->resolveBadgeClass($data['legacy_code'], (bool) $data['is_paid']),
            'is_paid' => (bool) $data['is_paid'],
            'deduct_quota' => $deductQuota,
            'quota_field' => $quotaField,
            'annual_quota_days' => $annualQuotaDays,
            'max_days_per_request' => max(0, (int) ($data['max_days_per_request'] ?? 0)) ?: null,
            'require_attachment' => (bool) $data['require_attachment'],
            'is_active' => (bool) $data['is_active'],
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
        ];
    }

    private function resolveQuotaPayload(string $quotaMode, ?int $annualQuotaDays): array
    {
        return match ($quotaMode) {
            'employee_cuti' => [true, 'izin_cuti', null],
            'employee_izin' => [true, 'izin_lainnya', null],
            'annual_manual' => [false, null, max(0, (int) $annualQuotaDays) ?: null],
            default => [false, null, null],
        };
    }

    private function resolveBadgeClass(string $legacyCode, bool $isPaid): string
    {
        if ($legacyCode === 'sakit') {
            return 'badge-info';
        }

        if (! $isPaid || $legacyCode === 'lainnya') {
            return 'badge-secondary';
        }

        return 'badge-primary';
    }

    private function legacyCodeOptions(): array
    {
        return [
            'sakit' => 'Sakit',
            'cuti' => 'Cuti',
            'lainnya' => 'Izin Lainnya',
        ];
    }

    private function quotaModeOptions(): array
    {
        return [
            'none' => 'Tanpa Kuota',
            'employee_cuti' => 'Pakai Kuota Cuti Karyawan',
            'employee_izin' => 'Pakai Kuota Izin Karyawan',
            'annual_manual' => 'Kuota Tetap per Tahun',
        ];
    }

    private function leaveQuotaPolicyOptions(): array
    {
        return [
            Setting::LEAVE_QUOTA_POLICY_ANNUAL_RESET => 'Reset Tahunan',
            Setting::LEAVE_QUOTA_POLICY_CARRY_LIMITED => 'Carry Over Terbatas',
            Setting::LEAVE_QUOTA_POLICY_CARRY_FULL => 'Akumulasi Penuh',
        ];
    }
}
