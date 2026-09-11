<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Absensi;
use App\Models\AttendanceLog;
use App\Models\Departemen;
use App\Models\GajiKaryawan;
use App\Models\Izin;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\KomponenGajiKaryawan;
use App\Models\LokasiGps;
use App\Models\Setting;
use App\Models\Shift;
use App\Services\EmployeeUserAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    private const GENDER_OPTIONS = [
        'Laki-Laki',
        'Perempuan',
    ];

    private const MARITAL_STATUS_OPTIONS = [
        'TK/0',
        'TK/1',
        'TK/2',
        'TK/3',
        'K/0',
        'K/1',
        'K/2',
        'K/3',
        'K/I/0',
        'K/I/1',
        'K/I/2',
        'K/I/3',
    ];

    private const SHIFT_TYPE_OPTIONS = [
        'tetap',
        'rolling',
        'fleksibel',
    ];

    private const EMPLOYMENT_TYPE_OPTIONS = [
        'tetap',
        'kontrak',
        'magang',
    ];

    private const SHIFT_ROTATION_MODE_OPTIONS = [
        'daily',
        'weekly',
        'biweekly',
        'monthly',
    ];

    private const PREMI_KEHADIRAN_MODE_OPTIONS = [
        'nonaktif',
        'penuh',
        'toleran',
        'prorata',
    ];

    private const PAYROLL_TYPE_OPTIONS = [
        'bulanan',
        'harian',
    ];

    private const BPJS_MODE_OPTIONS = [
        'off',
        'auto',
    ];

    private const AUTO_MODE_OPTIONS = [
        'off',
        'auto',
    ];

    private const THR_MODE_OPTIONS = [
        'off',
        'auto',
        'manual',
    ];

    private const TAX_COUNTERPART_OPTIONS = [
        'Resident',
        'Foreign',
    ];

    private const TAX_CERTIFICATE_OPTIONS = [
        'N/A',
        'DTP',
    ];

    private const PAYROLL_COMPONENT_FIELDS = [
        'gaji_per_hari',
        'tunjangan_jabatan',
        'tunjangan_makan',
        'tunjangan_transport',
        'potongan_per_menit',
        'potongan_izin',
        'potongan_mangkir',
        'potongan_terlambat',
        'tarif_lembur_per_jam',
        'tax_prev_gross_income',
        'tax_prev_pph21_paid',
        'tax_prev_retirement_contribution',
    ];

    private const COMPONENT_ONLY_FIELDS = [
        'tunjangan_jabatan',
        'tunjangan_makan',
        'tunjangan_transport',
        'potongan_per_menit',
        'potongan_izin',
        'potongan_mangkir',
        'potongan_terlambat',
        'tarif_lembur_per_jam',
    ];

    private const INTEGER_PROFILE_FIELDS = [
        'izin_cuti',
        'izin_lainnya',
        'izin_telat',
        'izin_pulang_cepat',
        'premi_kehadiran_toleransi_telat',
        'premi_kehadiran_toleransi_pulang_cepat',
    ];

    private const MONEY_FIELDS = [
        'gaji_pokok',
        'gaji_per_hari',
        'bonus_pribadi',
        'bonus_team',
        'premi_kehadiran',
        'thr_manual_amount',
        'tunjangan_jabatan',
        'tunjangan_makan',
        'tunjangan_transport',
        'potongan_per_menit',
        'potongan_izin',
        'potongan_mangkir',
        'potongan_terlambat',
    ];

    public function index(Request $request)
    {
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);
        $employmentTypeFilterInput = trim(strtolower((string) $request->input('jenis_karyawan', '')));
        $employmentTypeFilter = in_array($employmentTypeFilterInput, self::EMPLOYMENT_TYPE_OPTIONS, true)
            ? $employmentTypeFilterInput
            : '';
        $settings = Setting::query()->find(1);
        $editKaryawan = null;
        $editId = $request->integer('edit');

        if (! $editId && old('form_type') === 'edit') {
            $editId = (int) old('edit_id');
        }

        if ($editId) {
            $editKaryawan = Karyawan::query()
                ->with(['komponenGaji', 'latestKasbonMutation'])
                ->find($editId);
        }

        $createPayrollDefaults = [
            'tipe_penggajian' => 'bulanan',
            'payroll_divisor' => $this->resolvePayrollDivisor($settings),
            'bpjs_mode' => 'auto',
            'pph21_mode' => 'auto',
            'thr_mode' => 'auto',
            'thr_manual_amount' => 0,
            'gaji_pokok' => 0,
            'gaji_per_hari' => (float) ($settings?->gaji_per_hari ?? 100000),
            'tunjangan_jabatan' => 0,
            'tunjangan_makan' => (float) ($settings?->uang_makan ?? 15000),
            'tunjangan_transport' => (float) ($settings?->tunjangan_transport ?? 10000),
            'potongan_per_menit' => (float) ($settings?->potongan_per_menit ?? 1000),
            'potongan_izin' => 0,
            'potongan_mangkir' => 0,
            'potongan_terlambat' => (float) ($settings?->potongan_terlambat ?? 0),
            'tarif_lembur_per_jam' => 0,
            'bonus_pribadi' => 0,
            'bonus_team' => 0,
            'premi_kehadiran' => 0,
            'premi_kehadiran_mode' => 'nonaktif',
            'premi_kehadiran_toleransi_telat' => 0,
            'premi_kehadiran_toleransi_pulang_cepat' => 0,
        ];

        $editPayrollProfile = $editKaryawan
            ? $this->resolvePayrollProfile($editKaryawan, $settings)
            : $createPayrollDefaults;

        $query = Karyawan::query()
            ->with(['komponenGaji', 'latestKasbonMutation'])
            ->withoutResigned()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('nik', 'like', "%{$search}%")
                        ->orWhere('rfid_uid', 'like', "%{$search}%")
                        ->orWhere('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('jenis_karyawan', 'like', "%{$search}%")
                        ->orWhere('jabatan', 'like', "%{$search}%")
                        ->orWhere('departemen', 'like', "%{$search}%")
                        ->orWhere('telepon', 'like', "%{$search}%")
                        ->orWhere('no_telp', 'like', "%{$search}%");
                });
            })
            ->when($employmentTypeFilter !== '', function ($query) use ($employmentTypeFilter) {
                $query->where('jenis_karyawan', $employmentTypeFilter);
            });

        return view('admin.karyawan.index', [
            'karyawanList' => (clone $query)
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString(),
            'employeeStats' => [
                'total' => (clone $query)->count(),
                'tetap' => (clone $query)->where('jenis_karyawan', 'tetap')->count(),
                'kontrak' => (clone $query)->where('jenis_karyawan', 'kontrak')->count(),
                'magang' => (clone $query)->where('jenis_karyawan', 'magang')->count(),
            ],
            'jabatanOptions' => Jabatan::query()
                ->orderBy('nama_jabatan')
                ->get(['id', 'nama_jabatan']),
            'departemenOptions' => Departemen::query()
                ->orderBy('nama_departemen')
                ->get(['id', 'nama_departemen']),
            'locationOptions' => LokasiGps::query()
                ->orderByDesc('is_default')
                ->orderByDesc('status')
                ->orderBy('nama_lokasi')
                ->get(['id', 'nama_lokasi', 'radius', 'status', 'is_default']),
            'shiftOptions' => Shift::query()
                ->orderBy('jam_masuk')
                ->orderBy('nama_shift')
                ->get(['id', 'nama_shift', 'jam_masuk', 'jam_keluar', 'aktif']),
            'shiftTypeOptions' => self::SHIFT_TYPE_OPTIONS,
            'employmentTypeOptions' => self::EMPLOYMENT_TYPE_OPTIONS,
            'shiftRotationModeOptions' => self::SHIFT_ROTATION_MODE_OPTIONS,
            'premiKehadiranModeOptions' => self::PREMI_KEHADIRAN_MODE_OPTIONS,
            'payrollTypeOptions' => self::PAYROLL_TYPE_OPTIONS,
            'bpjsModeOptions' => self::BPJS_MODE_OPTIONS,
            'autoModeOptions' => self::AUTO_MODE_OPTIONS,
            'thrModeOptions' => self::THR_MODE_OPTIONS,
            'taxCounterpartOptions' => self::TAX_COUNTERPART_OPTIONS,
            'taxCertificateOptions' => self::TAX_CERTIFICATE_OPTIONS,
            'genderOptions' => self::GENDER_OPTIONS,
            'maritalStatusOptions' => self::MARITAL_STATUS_OPTIONS,
            'settings' => $settings,
            'createPayrollDefaults' => $createPayrollDefaults,
            'editPayrollProfile' => $editPayrollProfile,
            'editKaryawan' => $editKaryawan,
            'search' => $search,
            'perPage' => $perPage,
            'employmentTypeFilter' => $employmentTypeFilter,
        ]);
    }

    public function resignIndex(Request $request)
    {
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);

        $query = Karyawan::query()
            ->onlyResigned()
            ->with(['latestKasbonMutation'])
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($innerQuery) use ($search): void {
                    $innerQuery
                        ->where('nik', 'like', "%{$search}%")
                        ->orWhere('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('jabatan', 'like', "%{$search}%")
                        ->orWhere('departemen', 'like', "%{$search}%")
                        ->orWhere('alasan_resign', 'like', "%{$search}%");
                });
            });

        $today = now();
        $karyawanList = (clone $query)
            ->orderByDesc('tgl_resign')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $payrollRows = GajiKaryawan::query()
            ->whereIn('karyawan_id', $karyawanList->getCollection()->pluck('id'))
            ->get(['id', 'karyawan_id', 'bulan', 'status', 'is_finalized', 'finalized_at', 'total_gaji']);

        $karyawanList->getCollection()->transform(function (Karyawan $karyawan) use ($payrollRows) {
            $snapshot = $this->syncResignClearanceState(
                $karyawan,
                $payrollRows->where('karyawan_id', $karyawan->id)->values(),
                true
            );

            $karyawan->setAttribute('clearance_snapshot', $snapshot);

            return $karyawan;
        });

        return view('admin.karyawan.resign', [
            'karyawanList' => $karyawanList,
            'search' => $search,
            'perPage' => $perPage,
            'stats' => [
                'total' => (clone $query)->count(),
                'bulan_ini' => (clone $query)
                    ->whereMonth('tgl_resign', $today->month)
                    ->whereYear('tgl_resign', $today->year)
                    ->count(),
                'tahun_ini' => (clone $query)
                    ->whereYear('tgl_resign', $today->year)
                    ->count(),
                'clearance_selesai' => (clone $query)
                    ->where('clearance_status', 'selesai')
                    ->count(),
            ],
        ]);
    }

    public function show(Karyawan $karyawan)
    {
        $karyawan->load([
            'shift:id,nama_shift,jam_masuk,jam_keluar',
            'jabatanData:id,nama_jabatan',
            'departemenData:id,nama_departemen',
            'lokasiGps:id,nama_lokasi,radius,status,is_default',
            'komponenGaji',
            'latestKasbonMutation',
        ]);

        if ($karyawan->tgl_resign) {
            $karyawan->setAttribute(
                'clearance_snapshot',
                $this->syncResignClearanceState($karyawan, persist: true)
            );
        }

        $settings = Setting::query()->find(1);
        $absensiList = Absensi::query()
            ->where('karyawan_id', $karyawan->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_masuk')
            ->get();

        $izinList = Izin::query()
            ->where('karyawan_id', $karyawan->id)
            ->orderByDesc('created_at')
            ->get();

        $overtimeList = Absensi::query()
            ->where('karyawan_id', $karyawan->id)
            ->where('menit_lembur', '>', 0)
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_keluar')
            ->get();

        return view('admin.karyawan.show', [
            'karyawan' => $karyawan,
            'absensiList' => $absensiList,
            'izinList' => $izinList,
            'overtimeList' => $overtimeList,
            'totalHadir' => $absensiList->where('status', 'hadir')->count(),
            'totalTerlambat' => $absensiList->where('status', 'terlambat')->count(),
            'totalIzin' => $izinList->where('status', 'disetujui')->count(),
            'totalLembur' => round((float) $overtimeList->sum('jam_lembur'), 2),
            'jabatanOptions' => Jabatan::query()->orderBy('nama_jabatan')->get(['id', 'nama_jabatan']),
            'departemenOptions' => Departemen::query()->orderBy('nama_departemen')->get(['id', 'nama_departemen']),
            'locationOptions' => LokasiGps::query()
                ->orderByDesc('is_default')
                ->orderByDesc('status')
                ->orderBy('nama_lokasi')
                ->get(['id', 'nama_lokasi', 'radius', 'status', 'is_default']),
            'shiftOptions' => Shift::query()
                ->orderBy('jam_masuk')
                ->orderBy('nama_shift')
                ->get(['id', 'nama_shift', 'jam_masuk', 'jam_keluar', 'aktif']),
            'shiftTypeOptions' => self::SHIFT_TYPE_OPTIONS,
            'employmentTypeOptions' => self::EMPLOYMENT_TYPE_OPTIONS,
            'shiftRotationModeOptions' => self::SHIFT_ROTATION_MODE_OPTIONS,
            'premiKehadiranModeOptions' => self::PREMI_KEHADIRAN_MODE_OPTIONS,
            'payrollTypeOptions' => self::PAYROLL_TYPE_OPTIONS,
            'bpjsModeOptions' => self::BPJS_MODE_OPTIONS,
            'autoModeOptions' => self::AUTO_MODE_OPTIONS,
            'genderOptions' => self::GENDER_OPTIONS,
            'maritalStatusOptions' => self::MARITAL_STATUS_OPTIONS,
            'payrollProfile' => $this->resolvePayrollProfile($karyawan, $settings),
        ]);
    }

    public function store(Request $request, EmployeeUserAccountService $accountService)
    {
        $data = $this->prepareEmployeePayload(
            $this->resolveEmployeeMasterFields($this->validateEmployeePayload($request))
        );
        $componentPayload = $this->extractPayrollComponentPayload($data);
        $employeeData = $this->stripComponentOnlyFields($data);

        if (! array_key_exists('gaji_per_hari', $employeeData) || $employeeData['gaji_per_hari'] === null) {
            $employeeData['gaji_per_hari'] = 0;
        }

        if (empty($employeeData['tgl_join'])) {
            $employeeData['tgl_join'] = now()->toDateString();
        }

        DB::transaction(function () use ($employeeData, $componentPayload, $accountService): void {
            $employee = Karyawan::query()->create([
                ...$employeeData,
                'no_telp' => $employeeData['telepon'] ?? null,
                'password' => Hash::make('123456'),
            ]);

            $this->syncPayrollComponent($employee, $componentPayload);
            $accountService->sync($employee, '123456');
        });

        return $this->respondSuccess($request, route('admin.karyawan'), 'Data karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, Karyawan $karyawan, EmployeeUserAccountService $accountService)
    {
        $data = $this->prepareEmployeePayload(
            $this->resolveEmployeeMasterFields($this->validateEmployeePayload($request, $karyawan))
        );
        $componentPayload = $this->extractPayrollComponentPayload($data);
        $employeeData = $this->stripComponentOnlyFields($data);

        DB::transaction(function () use ($karyawan, $employeeData, $componentPayload, $accountService): void {
            $karyawan->update([
                ...$employeeData,
                'no_telp' => $employeeData['telepon'] ?? null,
            ]);

            $freshEmployee = $karyawan->fresh();
            $this->syncPayrollComponent($freshEmployee, $componentPayload);
            $accountService->sync($freshEmployee);
        });

        return $this->respondSuccess($request, route('admin.karyawan.show', $karyawan), 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Request $request, Karyawan $karyawan)
    {
        if (Absensi::query()->where('karyawan_id', $karyawan->id)->exists()) {
            return $this->respondError($request, route('admin.karyawan'), 'Tidak dapat menghapus karyawan karena masih memiliki data absensi.', status: 409);
        }

        if ($karyawan->foto) {
            Storage::disk('public')->delete($karyawan->foto);
        }

        $karyawan->delete();

        return $this->respondSuccess($request, route('admin.karyawan'), 'Data karyawan berhasil dihapus.');
    }

    public function resetPassword(Request $request, Karyawan $karyawan, EmployeeUserAccountService $accountService)
    {
        DB::transaction(function () use ($karyawan, $accountService): void {
            $karyawan->update([
                'password' => Hash::make('123456'),
            ]);

            $accountService->resetPassword($karyawan->fresh(), '123456');
        });

        return $this->respondSuccess($request, route('admin.karyawan'), 'Password akun login karyawan direset ke 123456.');
    }

    public function resign(Request $request, Karyawan $karyawan)
    {
        $data = Validator::make($request->all(), [
            'tgl_resign' => ['required', 'date'],
            'alasan_resign' => ['nullable', 'string', 'max:255'],
        ], [
            'tgl_resign.required' => 'Tanggal resign wajib diisi.',
            'alasan_resign.max' => 'Alasan resign maksimal 255 karakter.',
        ])->after(function ($validator) use ($karyawan, $request): void {
            if ($karyawan->tgl_resign) {
                $validator->errors()->add('tgl_resign', 'Karyawan ini sudah masuk daftar resign.');
            }

            $tglResign = $request->input('tgl_resign');

            if (! $tglResign || ! $karyawan->tgl_join) {
                return;
            }

            try {
                if (Carbon::parse($tglResign)->lt($karyawan->tgl_join->copy()->startOfDay())) {
                    $validator->errors()->add('tgl_resign', 'Tanggal resign tidak boleh lebih awal dari tanggal join.');
                }
            } catch (\Throwable) {
                return;
            }
        })->validate();

        $resignDate = Carbon::parse($data['tgl_resign'])->toDateString();
        $snapshotEmployee = $karyawan->fresh()->loadMissing('latestKasbonMutation');
        $snapshotEmployee->tgl_resign = Carbon::parse($resignDate);
        $snapshot = $this->buildResignClearanceSnapshot($snapshotEmployee);

        $karyawan->update([
            'status' => 'nonaktif',
            'tgl_resign' => $resignDate,
            'alasan_resign' => $this->nullableTrim($data['alasan_resign'] ?? null),
            'clearance_status' => 'draft',
            'clearance_payroll_final' => (bool) ($snapshot['payroll_final'] ?? false),
            'clearance_kasbon_resolved' => (bool) ($snapshot['kasbon_resolved_auto'] ?? false),
            'clearance_asset_returned' => false,
            'clearance_access_revoked' => false,
            'clearance_document_completed' => false,
            'clearance_notes' => null,
            'clearance_completed_at' => null,
        ]);

        return $this->respondSuccess($request, route('admin.karyawan'), 'Karyawan berhasil dipindahkan ke daftar resign.');
    }

    public function updateClearance(Request $request, Karyawan $karyawan)
    {
        if (! $karyawan->tgl_resign) {
            return $this->respondError($request, route('admin.karyawan.resign'), 'Clearance hanya bisa diproses untuk karyawan resign.', status: 422);
        }

        $data = Validator::make($request->all(), [
            'clearance_payroll_final' => ['nullable', 'in:0,1'],
            'clearance_kasbon_resolved' => ['nullable', 'in:0,1'],
            'clearance_asset_returned' => ['nullable', 'in:0,1'],
            'clearance_access_revoked' => ['nullable', 'in:0,1'],
            'clearance_document_completed' => ['nullable', 'in:0,1'],
            'clearance_notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'clearance_notes.max' => 'Catatan clearance maksimal 2000 karakter.',
        ])->validate();

        $snapshot = $this->syncResignClearanceState($karyawan->fresh()->loadMissing('latestKasbonMutation'));

        $payload = [
            'clearance_payroll_final' => (bool) ($snapshot['payroll_final'] ?? false),
            'clearance_kasbon_resolved' => (bool) ($snapshot['kasbon_resolved_auto'] ?? false),
            'clearance_asset_returned' => (bool) ((int) ($data['clearance_asset_returned'] ?? 0)),
            'clearance_access_revoked' => (bool) ((int) ($data['clearance_access_revoked'] ?? 0)),
            'clearance_document_completed' => (bool) ((int) ($data['clearance_document_completed'] ?? 0)),
            'clearance_notes' => $this->nullableTrim($data['clearance_notes'] ?? null),
        ];

        $status = $this->deriveClearanceStatus($payload);
        $attemptedCompletion = $payload['clearance_asset_returned']
            && $payload['clearance_access_revoked']
            && $payload['clearance_document_completed'];

        if ($attemptedCompletion && $status !== 'selesai') {
            $blockers = [];

            if (! $payload['clearance_payroll_final']) {
                $blockers[] = 'payroll akhir bulan resign belum final';
            }

            if (! $payload['clearance_kasbon_resolved']) {
                $blockers[] = 'saldo kasbon masih aktif';
            }

            if ($blockers !== []) {
                return $this->respondError(
                    $request,
                    route('admin.karyawan.resign'),
                    'Clearance belum bisa diselesaikan karena '.implode(' dan ', $blockers).'.',
                    status: 422
                );
            }
        }

        $payload['clearance_status'] = $status;
        $payload['clearance_completed_at'] = $status === 'selesai' ? now() : null;

        $karyawan->update($payload);

        return $this->respondSuccess($request, route('admin.karyawan.resign'), 'Clearance resign berhasil diperbarui.');
    }

    public function reactivate(Request $request, Karyawan $karyawan)
    {
        if (! $karyawan->tgl_resign) {
            return $this->respondError($request, route('admin.karyawan.resign'), 'Karyawan ini belum tercatat sebagai resign.', status: 422);
        }

        $karyawan->update([
            'status' => 'aktif',
            'tgl_resign' => null,
            'alasan_resign' => null,
            'clearance_status' => 'draft',
            'clearance_payroll_final' => false,
            'clearance_kasbon_resolved' => false,
            'clearance_asset_returned' => false,
            'clearance_access_revoked' => false,
            'clearance_document_completed' => false,
            'clearance_notes' => null,
            'clearance_completed_at' => null,
        ]);

        return $this->respondSuccess($request, route('admin.karyawan.resign'), 'Karyawan berhasil diaktifkan kembali.');
    }

    public function resetShiftCycle(Request $request, Karyawan $karyawan)
    {
        if ($this->normalizeWorkType($karyawan->jenis_jam_kerja) !== 'rolling') {
            return $this->respondError(
                $request,
                route('admin.karyawan.show', $karyawan),
                'Reset rotasi hanya tersedia untuk karyawan dengan tipe shift rolling.',
                status: 422
            );
        }

        $karyawan->update([
            'shift_rotation_start' => now()->toDateString(),
        ]);

        return $this->respondSuccess($request, route('admin.karyawan.show', $karyawan), 'Rotasi shift berhasil direset ke hari ini.');
    }

    public function pairRfid(Request $request, Karyawan $karyawan)
    {
        $uid = $this->normalizeRfidUid($request->input('uid'));

        if ($uid === null) {
            return $this->respondError($request, route('admin.karyawan'), 'UID RFID tidak valid.', status: 422);
        }

        if (! AttendanceLog::query()->where('uid', $uid)->exists()) {
            return $this->respondError($request, route('admin.karyawan'), 'UID RFID belum pernah discan dari mesin absensi.', status: 422);
        }

        $existingOwner = Karyawan::query()
            ->where('rfid_uid', $uid)
            ->whereKeyNot($karyawan->id)
            ->first();

        if ($existingOwner) {
            return $this->respondError(
                $request,
                route('admin.karyawan'),
                "UID RFID sudah dipakai oleh {$existingOwner->nama_lengkap}.",
                status: 422
            );
        }

        if ($karyawan->rfid_uid === $uid) {
            return $this->respondSuccess($request, route('admin.karyawan'), 'UID RFID ini sudah tertaut ke karyawan tersebut.');
        }

        $previousUid = $karyawan->rfid_uid;

        $karyawan->update([
            'rfid_uid' => $uid,
        ]);

        $message = $previousUid
            ? "UID RFID berhasil diganti dari {$previousUid} ke {$uid} untuk {$karyawan->nama_lengkap}."
            : "UID RFID {$uid} berhasil dipairing ke {$karyawan->nama_lengkap}.";

        return $this->respondSuccess($request, route('admin.karyawan'), $message);
    }

    public function startRfidPairing(Request $request)
    {
        Validator::make($request->all(), [
            'employee_id' => ['nullable', 'integer', Rule::exists('karyawan', 'id')],
        ])->validate();

        return response()->json([
            'ok' => true,
            'anchor_id' => (int) (AttendanceLog::query()->max('id') ?? 0),
            'timeout_seconds' => 30,
            'message' => 'Siap menunggu scan RFID baru.',
        ]);
    }

    public function pollRfidPairing(Request $request)
    {
        $data = Validator::make($request->all(), [
            'after_id' => ['required', 'integer', 'min:0'],
            'employee_id' => ['nullable', 'integer', Rule::exists('karyawan', 'id')],
        ])->validate();

        $afterId = (int) $data['after_id'];
        $employeeId = isset($data['employee_id']) ? (int) $data['employee_id'] : null;

        $log = AttendanceLog::query()
            ->with('device:id,name,serial_number')
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->first();

        if (! $log) {
            return response()->json([
                'ok' => true,
                'found' => false,
                'after_id' => $afterId,
            ]);
        }

        $owner = Karyawan::query()
            ->select('id', 'nik', 'nama_lengkap', 'rfid_uid')
            ->where('rfid_uid', $log->uid)
            ->first();

        $status = 'ready';
        $message = 'UID RFID berhasil terdeteksi.';

        if ($owner && $employeeId && $owner->id === $employeeId) {
            $status = 'owned_by_current';
            $message = 'UID RFID ini sudah terpasang di karyawan yang sedang diedit.';
        } elseif ($owner) {
            $status = 'owned_by_other';
            $message = "UID RFID sudah dipakai oleh {$owner->nama_lengkap}. Silakan scan kartu lain.";
        }

        return response()->json([
            'ok' => true,
            'found' => true,
            'after_id' => $log->id,
            'status' => $status,
            'message' => $message,
            'uid' => $log->uid,
            'scanned_at' => $log->scanned_at?->format('Y-m-d H:i:s'),
            'device' => [
                'id' => $log->device_id,
                'name' => $log->device?->name,
                'serial_number' => $log->device?->serial_number,
            ],
            'owner' => $owner ? [
                'id' => $owner->id,
                'nik' => $owner->nik,
                'nama_lengkap' => $owner->nama_lengkap,
            ] : null,
        ]);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'file_csv' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('file_csv');
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return $this->respondError($request, route('admin.karyawan'), 'File CSV tidak bisa dibaca.', status: 422);
        }

        fgetcsv($handle, 0, ';');

        $successCount = 0;
        $errorCount = 0;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 7) {
                $errorCount++;
                continue;
            }

            $payload = $this->prepareEmployeePayload(
                $this->resolveEmployeeMasterFields($this->normalizeCsvRow(collect($row)))
            );

            if ($payload['nik'] === '' || $payload['nama_lengkap'] === '') {
                $errorCount++;
                continue;
            }

            if (Karyawan::query()->where('nik', $payload['nik'])->exists()) {
                $errorCount++;
                continue;
            }

            if (($payload['rfid_uid'] ?? null) && Karyawan::query()->where('rfid_uid', $payload['rfid_uid'])->exists()) {
                $errorCount++;
                continue;
            }

            Karyawan::query()->create([
                ...$this->stripComponentOnlyFields($payload),
                'no_telp' => $payload['telepon'] ?: null,
                'password' => Hash::make('123456'),
                'status' => 'aktif',
                'gaji_per_hari' => 0,
            ]);

            $successCount++;
        }

        fclose($handle);

        $message = "Import selesai: {$successCount} berhasil, {$errorCount} gagal";
        $type = $errorCount > 0 ? 'warning' : 'success';

        return $this->respondSuccess($request, route('admin.karyawan'), $message, ['level' => $type]);
    }

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['NIK', 'Nama Lengkap', 'Email', 'Telepon', 'Jabatan', 'Departemen', 'Alamat', 'UID RFID'], ';');
            fputcsv($output, ['EMP001', 'Budi Santoso', 'budi@email.com', '081234567890', 'Staff IT', 'Teknologi Informasi', 'Jl. Contoh No. 123', '04A1B2C3D4'], ';');
            fclose($output);
        }, 'template_karyawan.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function validateEmployeePayload(Request $request, ?Karyawan $karyawan = null): array
    {
        $request->merge([
            'rfid_uid' => $this->normalizeRfidUid($request->input('rfid_uid')),
        ]);

        $validator = Validator::make($request->all(), [
            'nik' => ['required', 'string', 'max:20', Rule::unique('karyawan', 'nik')->ignore($karyawan?->id)],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'jabatan_id' => ['nullable', 'integer', Rule::exists('jabatan', 'id')],
            'departemen_id' => ['nullable', 'integer', Rule::exists('departemen', 'id')],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'departemen' => ['nullable', 'string', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'rfid_uid' => ['nullable', 'string', 'max:64', Rule::unique('karyawan', 'rfid_uid')->ignore($karyawan?->id)],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'jenis_karyawan' => ['nullable', Rule::in(self::EMPLOYMENT_TYPE_OPTIONS)],
            'shift_id' => ['nullable', 'integer', Rule::exists('shift', 'id')],
            'shift_rotation_ids' => ['nullable', 'array'],
            'shift_rotation_ids.*' => ['nullable', 'integer', Rule::exists('shift', 'id')],
            'shift_rotation_start' => ['nullable', 'date'],
            'shift_rotation_mode' => ['nullable', Rule::in(self::SHIFT_ROTATION_MODE_OPTIONS)],
            'lokasi_gps_id' => ['nullable', 'integer', Rule::exists('lokasi_gps', 'id')],
            'jenis_jam_kerja' => ['nullable', Rule::in(['shift', 'tetap', 'rolling', 'fleksibel'])],
            'durasi_kerja_fleksibel' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'tgl_lahir' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(self::GENDER_OPTIONS)],
            'tgl_join' => ['nullable', 'date'],
            'tgl_resign' => ['nullable', 'date', 'after_or_equal:tgl_join'],
            'alasan_resign' => ['nullable', 'string', 'max:255'],
            'status_nikah' => ['nullable', Rule::in(self::MARITAL_STATUS_OPTIONS)],
            'masa_berlaku' => ['nullable', 'date'],
            'ktp' => ['nullable', 'string', 'max:50'],
            'kartu_keluarga' => ['nullable', 'string', 'max:50'],
            'bpjs_kesehatan' => ['nullable', 'string', 'max:50'],
            'bpjs_ketenagakerjaan' => ['nullable', 'string', 'max:50'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'tax_counterpart_opt' => ['nullable', Rule::in(self::TAX_COUNTERPART_OPTIONS)],
            'tax_passport_number' => ['nullable', 'string', 'max:60'],
            'tax_has_second_employer' => ['nullable', 'boolean'],
            'tax_prev_withholding_slip_number' => ['nullable', 'string', 'max:120'],
            'tax_prev_gross_income' => ['nullable', 'string', 'max:30'],
            'tax_prev_pph21_paid' => ['nullable', 'string', 'max:30'],
            'tax_prev_retirement_contribution' => ['nullable', 'string', 'max:30'],
            'tax_certificate' => ['nullable', Rule::in(self::TAX_CERTIFICATE_OPTIONS)],
            'sim' => ['nullable', 'string', 'max:50'],
            'no_pkwt' => ['nullable', 'string', 'max:100'],
            'no_kontrak' => ['nullable', 'string', 'max:100'],
            'tanggal_mulai_pkwt' => ['nullable', 'date'],
            'tanggal_berakhir_pkwt' => ['nullable', 'date', 'after_or_equal:tanggal_mulai_pkwt'],
            'nama_bank' => ['nullable', 'string', 'max:100'],
            'rekening' => ['nullable', 'string', 'max:50'],
            'nama_rekening' => ['nullable', 'string', 'max:100'],
            'izin_cuti' => ['nullable', 'integer', 'min:0', 'max:365'],
            'izin_lainnya' => ['nullable', 'integer', 'min:0', 'max:365'],
            'izin_telat' => ['nullable', 'integer', 'min:0', 'max:365'],
            'izin_pulang_cepat' => ['nullable', 'integer', 'min:0', 'max:365'],
            'bpjs_mode' => ['nullable', Rule::in(self::BPJS_MODE_OPTIONS)],
            'pph21_mode' => ['nullable', Rule::in(self::AUTO_MODE_OPTIONS)],
            'thr_mode' => ['nullable', Rule::in(self::THR_MODE_OPTIONS)],
            'tipe_penggajian' => ['nullable', Rule::in(self::PAYROLL_TYPE_OPTIONS)],
            'gaji_pokok' => ['nullable', 'string', 'max:30'],
            'gaji_per_hari' => ['nullable', 'string', 'max:30'],
            'bonus_pribadi' => ['nullable', 'string', 'max:30'],
            'bonus_team' => ['nullable', 'string', 'max:30'],
            'premi_kehadiran' => ['nullable', 'string', 'max:30'],
            'premi_kehadiran_mode' => ['nullable', Rule::in(self::PREMI_KEHADIRAN_MODE_OPTIONS)],
            'premi_kehadiran_toleransi_telat' => ['nullable', 'integer', 'min:0', 'max:365'],
            'premi_kehadiran_toleransi_pulang_cepat' => ['nullable', 'integer', 'min:0', 'max:365'],
            'tunjangan_jabatan' => ['nullable', 'string', 'max:30'],
            'tunjangan_makan' => ['nullable', 'string', 'max:30'],
            'tunjangan_transport' => ['nullable', 'string', 'max:30'],
            'thr_manual_amount' => ['nullable', 'string', 'max:30'],
            'potongan_per_menit' => ['nullable', 'string', 'max:30'],
            'potongan_izin' => ['nullable', 'string', 'max:30'],
            'potongan_mangkir' => ['nullable', 'string', 'max:30'],
            'potongan_terlambat' => ['nullable', 'string', 'max:30'],
            'tarif_lembur_per_jam' => ['nullable', 'string', 'max:30'],
        ], [
            'tanggal_berakhir_pkwt.after_or_equal' => 'Tanggal berakhir PKWT tidak boleh lebih awal dari tanggal mulai PKWT.',
            'tgl_resign.after_or_equal' => 'Tanggal resign tidak boleh lebih awal dari tanggal join.',
            'alasan_resign.max' => 'Alasan resign maksimal 255 karakter.',
        ]);

        $validator->after(function ($validator) use ($request): void {
            $employmentType = $this->normalizeEmploymentType($request->input('jenis_karyawan'));

            if (! in_array($employmentType, ['kontrak', 'magang'], true)) {
                return;
            }

            if (! $request->filled('tanggal_mulai_pkwt')) {
                $validator->errors()->add('tanggal_mulai_pkwt', 'Tanggal mulai kontrak wajib diisi untuk karyawan kontrak atau magang.');
            }

            if (! $request->filled('tanggal_berakhir_pkwt')) {
                $validator->errors()->add('tanggal_berakhir_pkwt', 'Tanggal berakhir kontrak wajib diisi untuk karyawan kontrak atau magang.');
            }
        });

        return $validator->validate();
    }

    private function prepareEmployeePayload(array $payload): array
    {
        $stringFields = [
            'nik',
            'nama_lengkap',
            'email',
            'telepon',
            'jenis_karyawan',
            'jabatan',
            'departemen',
            'alamat',
            'gender',
            'status_nikah',
            'ktp',
            'kartu_keluarga',
            'bpjs_kesehatan',
            'bpjs_ketenagakerjaan',
            'npwp',
            'tax_counterpart_opt',
            'tax_passport_number',
            'tax_prev_withholding_slip_number',
            'tax_certificate',
            'sim',
            'no_pkwt',
            'no_kontrak',
            'nama_bank',
            'rekening',
            'nama_rekening',
            'alasan_resign',
        ];

        foreach ($stringFields as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = $this->nullableTrim($payload[$field]);
            }
        }

        foreach (self::INTEGER_PROFILE_FIELDS as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = max(0, (int) $payload[$field]);
            }
        }

        foreach (self::MONEY_FIELDS as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = $this->parseAmount($payload[$field]);
            }
        }

        $payload['bpjs_mode'] = $this->normalizeBpjsMode($payload['bpjs_mode'] ?? null);
        $payload['pph21_mode'] = $this->normalizeAutoMode($payload['pph21_mode'] ?? null);
        $payload['thr_mode'] = $this->normalizeThrMode($payload['thr_mode'] ?? null);
        $payload['tax_counterpart_opt'] = $this->normalizeTaxCounterpartOpt($payload['tax_counterpart_opt'] ?? null);
        $payload['tax_certificate'] = $this->normalizeTaxCertificate($payload['tax_certificate'] ?? null);
        $payload['tax_has_second_employer'] = filter_var(
            $payload['tax_has_second_employer'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? false;
        $payload['jenis_karyawan'] = $this->normalizeEmploymentType($payload['jenis_karyawan'] ?? null);
        $payload['tipe_penggajian'] = $this->normalizePayrollType($payload['tipe_penggajian'] ?? null);
        if (array_key_exists('tarif_lembur_per_jam', $payload)) {
            $tarifLemburValue = trim((string) ($payload['tarif_lembur_per_jam'] ?? ''));
            $payload['tarif_lembur_per_jam'] = $tarifLemburValue === ''
                ? null
                : $this->parseAmount($tarifLemburValue);
        }

        $payload['premi_kehadiran_mode'] = $this->normalizePremiKehadiranMode($payload['premi_kehadiran_mode'] ?? null);

        $workType = $this->normalizeWorkType($payload['jenis_jam_kerja'] ?? null);
        $payload['jenis_jam_kerja'] = $workType;

        if ($workType === 'fleksibel') {
            $payload['shift_rotation_ids'] = null;
            $payload['shift_rotation_start'] = null;
            $payload['shift_rotation_mode'] = 'daily';
        } elseif ($workType === 'rolling') {
            $rotationIds = $this->normalizeIntegerList($payload['shift_rotation_ids'] ?? []);

            if ($rotationIds === [] && ! empty($payload['shift_id'])) {
                $rotationIds = [(int) $payload['shift_id']];
            }

            $payload['shift_rotation_ids'] = $rotationIds !== [] ? $rotationIds : null;
            $payload['shift_rotation_start'] = $this->nullableTrim($payload['shift_rotation_start'] ?? null) ?: now()->toDateString();
            $payload['shift_rotation_mode'] = $this->normalizeRotationMode($payload['shift_rotation_mode'] ?? null);
        } else {
            $payload['shift_rotation_ids'] = null;
            $payload['shift_rotation_start'] = null;
            $payload['shift_rotation_mode'] = 'daily';
        }

        $payload['jam_fleksibel_mulai'] = null;
        $payload['jam_fleksibel_selesai'] = null;
        $payload['durasi_kerja_fleksibel'] = $workType === 'fleksibel'
            ? (float) ($payload['durasi_kerja_fleksibel'] ?? 8)
            : 8;

        if (! empty($payload['tgl_resign'])) {
            $payload['status'] = 'nonaktif';
        }

        if (($payload['jenis_karyawan'] ?? 'tetap') === 'tetap') {
            $payload['no_pkwt'] = null;
            $payload['no_kontrak'] = null;
            $payload['masa_berlaku'] = null;
            $payload['tanggal_mulai_pkwt'] = null;
            $payload['tanggal_berakhir_pkwt'] = null;
        }

        if (($payload['tax_counterpart_opt'] ?? 'Resident') !== 'Foreign') {
            $payload['tax_passport_number'] = null;
        }

        if (! ($payload['tax_has_second_employer'] ?? false)) {
            $payload['tax_prev_withholding_slip_number'] = null;
            $payload['tax_prev_gross_income'] = 0;
            $payload['tax_prev_pph21_paid'] = 0;
            $payload['tax_prev_retirement_contribution'] = 0;
        }

        if ($payload['tipe_penggajian'] !== 'bulanan') {
            $payload['gaji_pokok'] = 0;
            $payload['potongan_izin'] = 0;
            $payload['potongan_mangkir'] = 0;
        }

        return $payload;
    }

    private function normalizeWorkType(?string $value): string
    {
        return match (trim((string) $value)) {
            'rolling' => 'rolling',
            'fleksibel' => 'fleksibel',
            'shift', 'tetap', '' => 'tetap',
            default => 'tetap',
        };
    }

    private function normalizeEmploymentType(?string $value): string
    {
        return match (trim(strtolower((string) $value))) {
            'kontrak' => 'kontrak',
            'magang' => 'magang',
            default => 'tetap',
        };
    }

    private function normalizeRotationMode(?string $value): string
    {
        return match (trim(strtolower((string) $value))) {
            'weekly' => 'weekly',
            'biweekly' => 'biweekly',
            'monthly' => 'monthly',
            default => 'daily',
        };
    }

    private function normalizeTaxCounterpartOpt(?string $value): string
    {
        return trim((string) $value) === 'Foreign'
            ? 'Foreign'
            : 'Resident';
    }

    private function normalizeTaxCertificate(?string $value): string
    {
        return trim((string) $value) === 'DTP'
            ? 'DTP'
            : 'N/A';
    }

    private function normalizeIntegerList(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->flatten()
            ->map(fn ($item) => (int) $item)
            ->filter(fn (int $item) => $item > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function resolveEmployeeMasterFields(array $payload): array
    {
        $jabatan = $this->resolveJabatan(
            isset($payload['jabatan_id']) ? (int) $payload['jabatan_id'] : null,
            $payload['jabatan'] ?? null,
        );

        $departemen = $this->resolveDepartemen(
            isset($payload['departemen_id']) ? (int) $payload['departemen_id'] : null,
            $payload['departemen'] ?? null,
        );

        $payload['jabatan_id'] = $jabatan?->id;
        $payload['jabatan'] = $jabatan?->nama_jabatan;
        $payload['departemen_id'] = $departemen?->id;
        $payload['departemen'] = $departemen?->nama_departemen;

        return $payload;
    }

    private function normalizeCsvRow(Collection $row): array
    {
        return [
            'nik' => trim((string) $row->get(0, '')),
            'nama_lengkap' => trim((string) $row->get(1, '')),
            'email' => trim((string) $row->get(2, '')),
            'telepon' => trim((string) $row->get(3, '')),
            'jabatan' => trim((string) $row->get(4, '')),
            'departemen' => trim((string) $row->get(5, '')),
            'alamat' => trim((string) $row->get(6, '')),
            'rfid_uid' => $this->normalizeRfidUid($row->get(7)),
        ];
    }

    private function resolveJabatan(?int $jabatanId, ?string $jabatanName): ?Jabatan
    {
        if ($jabatanId) {
            return Jabatan::query()->find($jabatanId);
        }

        $jabatanName = trim((string) ($jabatanName ?? ''));

        if ($jabatanName === '') {
            return null;
        }

        $normalizedName = Str::lower($jabatanName);

        return Jabatan::query()
            ->whereRaw('LOWER(nama_jabatan) = ?', [$normalizedName])
            ->first()
            ?? Jabatan::query()->create([
                'nama_jabatan' => $jabatanName,
            ]);
    }

    private function resolveDepartemen(?int $departemenId, ?string $departemenName): ?Departemen
    {
        if ($departemenId) {
            return Departemen::query()->find($departemenId);
        }

        $departemenName = trim((string) ($departemenName ?? ''));

        if ($departemenName === '') {
            return null;
        }

        $normalizedName = Str::lower($departemenName);

        return Departemen::query()
            ->whereRaw('LOWER(nama_departemen) = ?', [$normalizedName])
            ->first()
            ?? Departemen::query()->create([
                'nama_departemen' => $departemenName,
            ]);
    }

    private function resolvePayrollProfile(Karyawan $karyawan, ?Setting $settings): array
    {
        $component = $karyawan->komponenGaji;
        $gajiPerHari = $this->resolveDailyPayrollBasis($karyawan, $component, $settings);
        $defaultTarifLembur = round(($gajiPerHari / 8) * 1.5, 2);
        $customTarifLembur = $component?->getRawOriginal('tarif_lembur_per_jam');

        return [
            'bpjs_mode' => $this->normalizeBpjsMode($karyawan->bpjs_mode ?? null),
            'pph21_mode' => $this->normalizeAutoMode($karyawan->pph21_mode ?? null),
            'thr_mode' => $this->normalizeThrMode($karyawan->thr_mode ?? null),
            'thr_manual_amount' => (float) ($karyawan->thr_manual_amount ?? 0),
            'tipe_penggajian' => $this->normalizePayrollType($karyawan->tipe_penggajian ?? null),
            'payroll_divisor' => $this->resolvePayrollDivisor($settings),
            'gaji_per_hari' => $this->resolveEmployeeDailyRate($karyawan, $component, $settings),
            'tunjangan_jabatan' => (float) ($component?->tunjangan_jabatan ?? 0),
            'tunjangan_makan' => (float) ($component?->tunjangan_makan ?? $settings?->uang_makan ?? 15000),
            'tunjangan_transport' => (float) ($component?->tunjangan_transport ?? $settings?->tunjangan_transport ?? 10000),
            'potongan_per_menit' => (float) ($component?->potongan_per_menit ?? $settings?->potongan_per_menit ?? 1000),
            'potongan_izin' => (float) ($component?->potongan_izin ?? 0),
            'potongan_mangkir' => (float) ($component?->potongan_mangkir ?? 0),
            'potongan_terlambat' => (float) ($component?->potongan_terlambat ?? $settings?->potongan_terlambat ?? 0),
            'tarif_lembur_per_jam' => $customTarifLembur !== null ? (float) $component?->tarif_lembur_per_jam : null,
            'tarif_lembur_default_per_jam' => $defaultTarifLembur,
            'premi_kehadiran' => (float) ($karyawan->premi_kehadiran ?? 0),
            'premi_kehadiran_mode' => $this->normalizePremiKehadiranMode($karyawan->premi_kehadiran_mode ?? null),
            'premi_kehadiran_toleransi_telat' => (int) ($karyawan->premi_kehadiran_toleransi_telat ?? 0),
            'premi_kehadiran_toleransi_pulang_cepat' => (int) ($karyawan->premi_kehadiran_toleransi_pulang_cepat ?? 0),
        ];
    }

    private function extractPayrollComponentPayload(array $payload): array
    {
        $componentPayload = [];

        foreach (self::PAYROLL_COMPONENT_FIELDS as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            if ($field === 'tarif_lembur_per_jam') {
                $componentPayload[$field] = $payload[$field] === null ? null : (float) $payload[$field];
                continue;
            }

            $componentPayload[$field] = (float) $payload[$field];
        }

        return $componentPayload;
    }

    private function stripComponentOnlyFields(array $payload): array
    {
        foreach (self::COMPONENT_ONLY_FIELDS as $field) {
            unset($payload[$field]);
        }

        return $payload;
    }

    private function normalizePremiKehadiranMode(mixed $value): string
    {
        $value = trim((string) $value);

        return in_array($value, self::PREMI_KEHADIRAN_MODE_OPTIONS, true)
            ? $value
            : 'nonaktif';
    }

    private function normalizeBpjsMode(mixed $value): string
    {
        return trim((string) $value) === 'auto'
            ? 'auto'
            : 'off';
    }

    private function normalizeAutoMode(mixed $value, string $default = 'off'): string
    {
        return trim((string) $value) === 'auto'
            ? 'auto'
            : $default;
    }

    private function normalizeThrMode(mixed $value): string
    {
        $value = trim((string) $value);

        return in_array($value, self::THR_MODE_OPTIONS, true)
            ? $value
            : 'auto';
    }

    private function syncPayrollComponent(Karyawan $karyawan, array $componentPayload): void
    {
        if ($componentPayload === []) {
            return;
        }

        $existingComponent = KomponenGajiKaryawan::query()
            ->where('karyawan_id', $karyawan->id)
            ->first();

        KomponenGajiKaryawan::query()->updateOrCreate(
            ['karyawan_id' => $karyawan->id],
            [
                'gaji_per_hari' => (float) ($componentPayload['gaji_per_hari'] ?? $karyawan->gaji_per_hari ?? 0),
                'tunjangan_jabatan' => (float) ($componentPayload['tunjangan_jabatan'] ?? 0),
                'tunjangan_makan' => (float) ($componentPayload['tunjangan_makan'] ?? 0),
                'tunjangan_transport' => (float) ($componentPayload['tunjangan_transport'] ?? 0),
                'tunjangan_bpjs_kesehatan' => 0,
                'tunjangan_bpjs_ketenagakerjaan' => 0,
                'potongan_per_menit' => (float) ($componentPayload['potongan_per_menit'] ?? $existingComponent?->potongan_per_menit ?? 0),
                'potongan_izin' => (float) ($componentPayload['potongan_izin'] ?? 0),
                'potongan_mangkir' => (float) ($componentPayload['potongan_mangkir'] ?? 0),
                'potongan_terlambat' => (float) ($componentPayload['potongan_terlambat'] ?? 0),
                'potongan_bpjs_kesehatan' => 0,
                'potongan_bpjs_ketenagakerjaan' => 0,
                'tarif_lembur_per_jam' => array_key_exists('tarif_lembur_per_jam', $componentPayload)
                    ? $componentPayload['tarif_lembur_per_jam']
                    : null,
            ],
        );
    }

    private function resolveDailyPayrollBasis(?Karyawan $karyawan, ?KomponenGajiKaryawan $component, ?Setting $settings): float
    {
        $payrollType = $this->normalizePayrollType($karyawan?->tipe_penggajian ?? null);
        $monthlySalary = (float) ($karyawan?->gaji_pokok ?? 0);
        $divisor = $this->resolvePayrollDivisor($settings);

        if ($payrollType === 'bulanan' && $monthlySalary > 0) {
            return round($monthlySalary / max(1, $divisor), 2);
        }

        $componentRate = $component?->getRawOriginal('gaji_per_hari');

        if ($componentRate !== null && (float) $componentRate > 0) {
            return (float) $componentRate;
        }

        if ($monthlySalary > 0) {
            return round($monthlySalary / max(1, $divisor), 2);
        }

        $employeeRate = $karyawan?->getRawOriginal('gaji_per_hari');

        if ($employeeRate !== null && (float) $employeeRate > 0) {
            return (float) $employeeRate;
        }

        return (float) ($settings?->gaji_per_hari ?? 0);
    }

    private function resolveEmployeeDailyRate(?Karyawan $karyawan, ?KomponenGajiKaryawan $component, ?Setting $settings): float
    {
        $componentRate = $component?->getRawOriginal('gaji_per_hari');

        if ($componentRate !== null && (float) $componentRate > 0) {
            return (float) $componentRate;
        }

        $employeeRate = $karyawan?->getRawOriginal('gaji_per_hari');

        if ($employeeRate !== null && (float) $employeeRate > 0) {
            return (float) $employeeRate;
        }

        return (float) ($settings?->gaji_per_hari ?? 0);
    }

    private function normalizePayrollType(?string $value): string
    {
        return trim((string) $value) === 'harian'
            ? 'harian'
            : 'bulanan';
    }

    private function resolvePayrollDivisor(?Setting $settings): int
    {
        $settingsDivisor = (int) ($settings?->payroll_divisor_bulanan ?? 0);

        return $settingsDivisor > 0 ? $settingsDivisor : 26;
    }

    private function parseAmount(mixed $value): float
    {
        $normalized = preg_replace('/[^0-9,.-]/', '', trim((string) $value)) ?? '';
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : 0;
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function normalizeRfidUid(mixed $uid): ?string
    {
        $uid = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', trim((string) ($uid ?? ''))) ?? '');

        return $uid === '' ? null : $uid;
    }

    private function deriveClearanceStatus(array $payload): string
    {
        $flags = [
            (bool) ($payload['clearance_payroll_final'] ?? false),
            (bool) ($payload['clearance_kasbon_resolved'] ?? false),
            (bool) ($payload['clearance_asset_returned'] ?? false),
            (bool) ($payload['clearance_access_revoked'] ?? false),
            (bool) ($payload['clearance_document_completed'] ?? false),
        ];

        if (collect($flags)->every(fn (bool $flag): bool => $flag === true)) {
            return 'selesai';
        }

        if (collect($flags)->contains(true)) {
            return 'proses';
        }

        return 'draft';
    }

    private function buildResignClearanceSnapshot(Karyawan $karyawan, ?\Illuminate\Support\Collection $payrollRows = null): array
    {
        $resignMonth = $karyawan->tgl_resign?->copy()->startOfMonth();
        $payrollRow = null;

        if ($resignMonth && $payrollRows) {
            $payrollRow = $payrollRows->first(function ($row) use ($resignMonth) {
                $bulan = $row->bulan instanceof Carbon
                    ? $row->bulan->copy()->startOfMonth()
                    : Carbon::parse($row->bulan)->startOfMonth();

                return $bulan->equalTo($resignMonth);
            });
        } elseif ($resignMonth) {
            $payrollRow = GajiKaryawan::query()
                ->where('karyawan_id', $karyawan->id)
                ->whereDate('bulan', $resignMonth->toDateString())
                ->first(['id', 'karyawan_id', 'bulan', 'status', 'is_finalized', 'finalized_at', 'total_gaji']);
        }

        $payrollFinal = $payrollRow
            ? ((bool) ($payrollRow->is_finalized ?? false) || (string) ($payrollRow->status ?? '') === 'selesai')
            : false;
        $kasbonBalance = max(0, (float) ($karyawan->saldo_kasbon ?? 0));

        return [
            'payroll_exists' => $payrollRow !== null,
            'payroll_final' => $payrollFinal,
            'payroll_month_label' => $resignMonth?->translatedFormat('F Y') ?? '-',
            'payroll_total' => (float) ($payrollRow?->total_gaji ?? 0),
            'kasbon_balance' => $kasbonBalance,
            'kasbon_resolved_auto' => $kasbonBalance <= 0,
            'clearance_progress' => $karyawan->clearance_progress_label,
            'clearance_status_label' => $karyawan->clearance_status_label,
        ];
    }

    private function syncResignClearanceState(Karyawan $karyawan, ?Collection $payrollRows = null, bool $persist = false): array
    {
        $snapshot = $this->buildResignClearanceSnapshot($karyawan, $payrollRows);

        $payload = [
            'clearance_payroll_final' => (bool) ($snapshot['payroll_final'] ?? false),
            'clearance_kasbon_resolved' => (bool) ($snapshot['kasbon_resolved_auto'] ?? false),
            'clearance_asset_returned' => (bool) ($karyawan->clearance_asset_returned ?? false),
            'clearance_access_revoked' => (bool) ($karyawan->clearance_access_revoked ?? false),
            'clearance_document_completed' => (bool) ($karyawan->clearance_document_completed ?? false),
        ];

        $status = $this->deriveClearanceStatus($payload);
        $completedAt = $status === 'selesai'
            ? ($karyawan->clearance_completed_at ?? now())
            : null;

        $dirtyPayload = [
            'clearance_payroll_final' => $payload['clearance_payroll_final'],
            'clearance_kasbon_resolved' => $payload['clearance_kasbon_resolved'],
            'clearance_status' => $status,
            'clearance_completed_at' => $completedAt,
        ];

        $hasChanges = (bool) (
            (bool) ($karyawan->getRawOriginal('clearance_payroll_final') ?? false) !== $dirtyPayload['clearance_payroll_final']
            || (bool) ($karyawan->getRawOriginal('clearance_kasbon_resolved') ?? false) !== $dirtyPayload['clearance_kasbon_resolved']
            || (string) ($karyawan->getRawOriginal('clearance_status') ?? 'draft') !== $dirtyPayload['clearance_status']
            || (($karyawan->clearance_completed_at?->toDateTimeString()) !== ($completedAt?->toDateTimeString()))
        );

        if ($persist && $hasChanges) {
            $karyawan->forceFill($dirtyPayload)->saveQuietly();
        }

        $karyawan->setAttribute('clearance_payroll_final', $payload['clearance_payroll_final']);
        $karyawan->setAttribute('clearance_kasbon_resolved', $payload['clearance_kasbon_resolved']);
        $karyawan->setAttribute('clearance_status', $status);
        $karyawan->setAttribute('clearance_completed_at', $completedAt);

        return [
            ...$snapshot,
            ...$payload,
            'clearance_status' => $status,
            'clearance_status_label' => $karyawan->clearance_status_label,
            'clearance_progress' => $karyawan->clearance_progress_label,
        ];
    }
}
