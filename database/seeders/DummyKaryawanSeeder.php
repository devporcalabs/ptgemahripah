<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\KomponenGajiKaryawan;
use App\Models\LokasiGps;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DummyKaryawanSeeder extends Seeder
{
    private const TOTAL = 100;

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

    private const WORK_TYPES = [
        'tetap',
        'rolling',
        'fleksibel',
    ];

    private const ROTATION_MODES = [
        'daily',
        'weekly',
        'biweekly',
        'monthly',
    ];

    private const PREMI_MODES = [
        'nonaktif',
        'penuh',
        'toleran',
        'prorata',
    ];

    private const BANKS = [
        'BCA',
        'BRI',
        'BNI',
        'Mandiri',
        'BSI',
        'BTN',
    ];

    private const DEFAULT_JABATAN = [
        'Direktur',
        'Manager',
        'Supervisor',
        'Koordinator',
        'Administrator',
        'HRD',
        'Staff IT',
        'Staff Keuangan',
        'Staff Admin',
        'Operator',
    ];

    private const DEFAULT_DEPARTEMEN = [
        'Manajemen',
        'Human Resource',
        'Keuangan',
        'Operasional',
        'Produksi',
        'Gudang',
        'Teknologi Informasi',
        'Marketing',
        'Penjualan',
        'Umum',
    ];

    private const DEFAULT_SHIFTS = [
        [
            'nama_shift' => 'Pagi',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '16:00:00',
            'toleransi' => 15,
            'checkin_window_before' => 30,
            'kode_warna' => '#3B82F6',
        ],
        [
            'nama_shift' => 'Siang',
            'jam_masuk' => '12:00:00',
            'jam_keluar' => '20:00:00',
            'toleransi' => 15,
            'checkin_window_before' => 30,
            'kode_warna' => '#10B981',
        ],
        [
            'nama_shift' => 'Malam',
            'jam_masuk' => '20:00:00',
            'jam_keluar' => '04:00:00',
            'toleransi' => 15,
            'checkin_window_before' => 30,
            'kode_warna' => '#8B5CF6',
        ],
    ];

    public function run(): void
    {
        if (! Schema::hasTable('karyawan')) {
            return;
        }

        $faker = fake('id_ID');
        $now = now()->startOfDay();
        $karyawanColumns = array_flip(Schema::getColumnListing('karyawan'));
        $komponenColumns = Schema::hasTable('komponen_gaji_karyawan')
            ? array_flip(Schema::getColumnListing('komponen_gaji_karyawan'))
            : [];
        $jabatanOptions = $this->resolveNamedOptions(Jabatan::query()->orderBy('id')->get(['id', 'nama_jabatan']), 'id', 'nama_jabatan', self::DEFAULT_JABATAN);
        $departemenOptions = $this->resolveNamedOptions(Departemen::query()->orderBy('id')->get(['id', 'nama_departemen']), 'id', 'nama_departemen', self::DEFAULT_DEPARTEMEN);
        $shiftIds = $this->resolveShiftIds();
        $locationIds = Schema::hasTable('lokasi_gps')
            ? LokasiGps::query()->orderBy('id')->pluck('id')->map(static fn ($id) => (int) $id)->all()
            : [];

        for ($index = 1; $index <= self::TOTAL; $index++) {
            $fullName = $faker->unique()->name();
            $gender = self::GENDER_OPTIONS[($index - 1) % count(self::GENDER_OPTIONS)];
            $statusNikah = self::MARITAL_STATUS_OPTIONS[($index - 1) % count(self::MARITAL_STATUS_OPTIONS)];
            $jabatan = $jabatanOptions[($index - 1) % count($jabatanOptions)];
            $departemen = $departemenOptions[($index - 1) % count($departemenOptions)];
            $workType = $this->resolveWorkType($index);
            $payrollType = $workType === 'fleksibel' ? 'harian' : (($index % 4 === 0) ? 'harian' : 'bulanan');
            $monthlyBase = $this->resolveBaseSalary($jabatan['name'], $index);
            $dailyRate = (int) round($monthlyBase / 26 / 1000) * 1000;
            $birthDate = Carbon::instance($faker->dateTimeBetween('-45 years', '-22 years'))->startOfDay();
            $joinStart = $birthDate->copy()->addYears(18);
            $joinEnd = $workType === 'fleksibel' && $payrollType === 'harian'
                ? $now->copy()->subMonths(1)
                : $now;

            if ($joinStart->greaterThan($joinEnd)) {
                $joinStart = $joinEnd->copy()->subYears(1);
            }

            $joinDate = Carbon::instance($faker->dateTimeBetween($joinStart, $joinEnd))->startOfDay();
            $status = $index % 10 === 0 ? 'nonaktif' : 'aktif';
            $resignDate = $this->resolveResignDate($status, $joinDate, $now);
            $rotationMode = $workType === 'rolling'
                ? self::ROTATION_MODES[($index - 1) % count(self::ROTATION_MODES)]
                : 'daily';
            $shiftId = in_array($workType, ['tetap', 'rolling'], true) && $shiftIds !== []
                ? $shiftIds[($index - 1) % count($shiftIds)]
                : null;
            $rotationIds = $workType === 'rolling' && $shiftIds !== []
                ? $this->resolveRotationIds($shiftIds, $index)
                : null;
            $premiMode = self::PREMI_MODES[($index - 1) % count(self::PREMI_MODES)];
            $premiAmount = $this->resolvePremiumAmount($premiMode, $payrollType);
            $toleransiPremium = $premiMode === 'toleran' ? 15 : 0;
            $bonusPribadi = $this->resolveBonusPribadi($jabatan['name'], $index);
            $bonusTeam = $index % 9 === 0 ? 150000 : 0;
            $bankName = self::BANKS[($index - 1) % count(self::BANKS)];
            $accountNumber = (string) (7000000000 + $index);
            $telephone = sprintf('08%09d', 100000000 + $index);
            $rfidUid = sprintf('%08X', 0xA0000000 + $index);
            $expectedEndPkwt = $joinDate->copy()->addYear();
            $lokasiGpsId = $locationIds !== []
                ? $locationIds[($index - 1) % count($locationIds)]
                : null;

            $employeePayload = $this->filterByColumns([
                'nik' => sprintf('9900%012d', $index),
                'nama_lengkap' => $fullName,
                'email' => sprintf('karyawan%03d@demo.test', $index),
                'no_telp' => $telephone,
                'telepon' => $telephone,
                'jabatan' => $jabatan['name'],
                'departemen' => $departemen['name'],
                'jabatan_id' => $jabatan['id'],
                'departemen_id' => $departemen['id'],
                'alamat' => $faker->address(),
                'rfid_uid' => $rfidUid,
                'status' => $status,
                'shift_id' => $shiftId,
                'shift_rotation_ids' => $rotationIds,
                'shift_rotation_start' => $rotationIds !== null ? $joinDate->toDateString() : null,
                'shift_rotation_mode' => $rotationMode,
                'lokasi_gps_id' => $lokasiGpsId,
                'jenis_jam_kerja' => $workType,
                'durasi_kerja_fleksibel' => $workType === 'fleksibel' ? [6.5, 7.5, 8, 9][($index - 1) % 4] : 8,
                'tgl_lahir' => $birthDate->toDateString(),
                'gender' => $gender,
                'tgl_join' => $joinDate->toDateString(),
                'tgl_resign' => $resignDate?->toDateString(),
                'status_nikah' => $statusNikah,
                'masa_berlaku' => $joinDate->copy()->addYears(5)->toDateString(),
                'ktp' => sprintf('KTP%012d', $index),
                'kartu_keluarga' => sprintf('KK%012d', $index),
                'bpjs_kesehatan' => sprintf('BPJSK%012d', $index),
                'bpjs_ketenagakerjaan' => sprintf('BPJSTK%012d', $index),
                'npwp' => sprintf('NPWP%012d', $index),
                'sim' => sprintf('SIM%010d', $index),
                'no_pkwt' => sprintf('PKWT-%04d', $index),
                'no_kontrak' => sprintf('KONTRAK-%04d', $index),
                'tanggal_mulai_pkwt' => $joinDate->toDateString(),
                'tanggal_berakhir_pkwt' => $expectedEndPkwt->toDateString(),
                'nama_bank' => $bankName,
                'rekening' => $accountNumber,
                'nama_rekening' => $fullName,
                'izin_cuti' => $index % 6,
                'izin_lainnya' => $index % 4,
                'izin_telat' => $index % 3,
                'izin_pulang_cepat' => $index % 5,
                'gaji_pokok' => $payrollType === 'bulanan' ? $monthlyBase : 0,
                'gaji_per_hari' => $dailyRate,
                'bonus_pribadi' => $bonusPribadi,
                'bonus_team' => $bonusTeam,
                'premi_kehadiran' => $premiAmount,
                'premi_kehadiran_mode' => $premiMode,
                'premi_kehadiran_toleransi_telat' => $toleransiPremium,
                'premi_kehadiran_toleransi_pulang_cepat' => $toleransiPremium,
                'bpjs_mode' => $payrollType === 'bulanan' ? 'auto' : 'off',
                'pph21_mode' => $payrollType === 'bulanan' ? 'auto' : 'off',
                'thr_mode' => $payrollType === 'bulanan' ? 'auto' : 'off',
                'thr_manual_amount' => 0,
                'tipe_penggajian' => $payrollType,
                'payroll_divisor' => $payrollType === 'bulanan' ? 26 : null,
                'password' => Hash::make('password123'),
                'foto' => null,
            ], $karyawanColumns);

            $karyawan = Karyawan::query()->updateOrCreate(
                ['nik' => $employeePayload['nik']],
                $employeePayload
            );

            if ($komponenColumns !== []) {
                $componentPayload = $this->filterByColumns([
                    'karyawan_id' => $karyawan->id,
                    'gaji_per_hari' => $dailyRate,
                    'tunjangan_jabatan' => $this->resolvePositionAllowance($jabatan['name'], $payrollType),
                    'tunjangan_makan' => 15000,
                    'tunjangan_transport' => 10000,
                    'potongan_per_menit' => 1000,
                    'potongan_izin' => $dailyRate,
                    'potongan_mangkir' => $dailyRate,
                    'potongan_terlambat' => 1000,
                    'tarif_lembur_per_jam' => max(15000, (float) round(($dailyRate / 8) * 1.5, -2)),
                    'tunjangan_bpjs_kesehatan' => 0,
                    'tunjangan_bpjs_ketenagakerjaan' => 0,
                    'potongan_bpjs_kesehatan' => 0,
                    'potongan_bpjs_ketenagakerjaan' => 0,
                ], $komponenColumns);

                KomponenGajiKaryawan::query()->updateOrCreate(
                    ['karyawan_id' => $karyawan->id],
                    $componentPayload
                );
            }
        }
    }

    private function resolveNamedOptions($rows, string $idField, string $nameField, array $fallbackNames): array
    {
        $options = collect($rows)
            ->map(fn ($row) => [
                'id' => (int) ($row->{$idField} ?? 0),
                'name' => trim((string) ($row->{$nameField} ?? '')),
            ])
            ->filter(fn (array $item) => $item['name'] !== '')
            ->values()
            ->all();

        if ($options !== []) {
            return $options;
        }

        return array_map(static fn (string $name) => [
            'id' => null,
            'name' => $name,
        ], $fallbackNames);
    }

    private function resolveShiftIds(): array
    {
        if (! Schema::hasTable('shift')) {
            return [];
        }

        $shiftIds = Shift::query()
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if ($shiftIds !== []) {
            return $shiftIds;
        }

        $shiftColumns = array_flip(Schema::getColumnListing('shift'));

        foreach (self::DEFAULT_SHIFTS as $shift) {
            $payload = $this->filterByColumns($shift + ['aktif' => true], $shiftColumns);
            Shift::query()->updateOrCreate(
                ['nama_shift' => $shift['nama_shift']],
                $payload
            );
        }

        return Shift::query()
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    private function resolveWorkType(int $index): string
    {
        if ($index % 10 === 0) {
            return 'fleksibel';
        }

        if ($index % 5 === 0 || $index % 7 === 0) {
            return 'rolling';
        }

        return 'tetap';
    }

    private function resolveBaseSalary(string $jabatanName, int $index): int
    {
        return match ($jabatanName) {
            'Direktur' => 16000000 + ($index % 3) * 1000000,
            'Manager' => 10000000 + ($index % 4) * 500000,
            'Supervisor' => 7000000 + ($index % 4) * 250000,
            'Koordinator' => 5500000 + ($index % 4) * 200000,
            'Administrator', 'HRD', 'Staff IT', 'Staff Keuangan', 'Staff Admin' => 4000000 + ($index % 5) * 150000,
            default => 3000000 + ($index % 6) * 100000,
        };
    }

    private function resolvePositionAllowance(string $jabatanName, string $payrollType): int
    {
        if ($payrollType !== 'bulanan') {
            return 0;
        }

        return match ($jabatanName) {
            'Direktur' => 2000000,
            'Manager' => 1000000,
            'Supervisor' => 750000,
            'Koordinator' => 500000,
            'Administrator', 'HRD', 'Staff IT', 'Staff Keuangan', 'Staff Admin' => 250000,
            default => 150000,
        };
    }

    private function resolvePremiumAmount(string $premiMode, string $payrollType): int
    {
        if ($premiMode === 'nonaktif') {
            return 0;
        }

        return match ($premiMode) {
            'penuh' => $payrollType === 'bulanan' ? 300000 : 100000,
            'toleran' => $payrollType === 'bulanan' ? 200000 : 75000,
            default => $payrollType === 'bulanan' ? 150000 : 50000,
        };
    }

    private function resolveResignDate(string $status, Carbon $joinDate, Carbon $now): ?Carbon
    {
        if ($status !== 'nonaktif') {
            return null;
        }

        $candidate = $joinDate->copy()->addMonths(random_int(6, 24));

        return $candidate->greaterThan($now)
            ? $now->copy()->subDays(random_int(1, 45))
            : $candidate;
    }

    private function resolveRotationIds(array $shiftIds, int $index): array
    {
        if ($shiftIds === []) {
            return [];
        }

        $rotationSize = min(3, count($shiftIds));
        $startIndex = ($index - 1) % count($shiftIds);
        $rotationIds = [];

        for ($offset = 0; $offset < $rotationSize; $offset++) {
            $rotationIds[] = $shiftIds[($startIndex + $offset) % count($shiftIds)];
        }

        return array_values(array_unique($rotationIds));
    }

    private function resolveBonusPribadi(string $jabatanName, int $index): int
    {
        return match ($jabatanName) {
            'Direktur' => 1000000,
            'Manager' => 750000,
            'Supervisor' => 500000,
            'Koordinator' => 350000,
            default => $index % 6 === 0 ? 250000 : 0,
        };
    }

    private function filterByColumns(array $payload, array $columnMap): array
    {
        return array_intersect_key($payload, $columnMap);
    }
}
