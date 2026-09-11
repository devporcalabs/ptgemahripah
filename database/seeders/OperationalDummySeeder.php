<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\AttendanceCorrection;
use App\Models\GajiKaryawan;
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\JenisIzin;
use App\Models\Karyawan;
use App\Models\KasbonMutation;
use App\Models\Lembur;
use App\Models\LokasiGps;
use App\Models\Shift;
use App\Models\User;
use App\Services\PayrollPeriodService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class OperationalDummySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Admin & HR Accounts
        $adminRole = Role::findOrCreate('super-admin', 'web');
        $staffAdminRole = Role::findOrCreate('admin', 'web');

        $admin = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Super Administrator',
                'email' => 'admin@gemahripah.co.id',
                'password' => Hash::make('123456'),
                'role' => 'super-admin',
            ]
        );
        $admin->syncRoles(['super-admin']);

        $hrd = User::query()->updateOrCreate(
            ['username' => 'hrd'],
            [
                'name' => 'HRD PT Gemah Ripah',
                'email' => 'hrd@gemahripah.co.id',
                'password' => Hash::make('123456'),
                'role' => 'admin',
            ]
        );
        $hrd->syncRoles(['admin']);

        $manager = User::query()->updateOrCreate(
            ['username' => 'manager'],
            [
                'name' => 'Manager Operasional',
                'email' => 'manager@gemahripah.co.id',
                'password' => Hash::make('123456'),
                'role' => 'admin',
            ]
        );
        $manager->syncRoles(['admin']);

        $canteenRole = Role::findOrCreate('petugas-kantin', 'web');
        $kantin = User::query()->updateOrCreate(
            ['username' => 'kantin'],
            [
                'name' => 'Petugas Kantin & Katering',
                'email' => 'kantin@gemahripah.co.id',
                'password' => Hash::make('123456'),
                'role' => 'petugas-kantin',
            ]
        );
        $kantin->syncRoles(['petugas-kantin']);

        // 2. Fetch Active Employees, Shifts, Locations, Holidays
        $employees = Karyawan::query()
            ->where('status', 'aktif')
            ->orderBy('id')
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        $defaultLocation = LokasiGps::query()->where('is_default', 1)->first()
            ?? LokasiGps::query()->first();
        $allLocations = LokasiGps::query()->get();
        $defaultShift = Shift::query()->first();
        $shifts = Shift::query()->get()->keyBy('id');

        $holidays = HariLibur::query()
            ->where('aktif', 1)
            ->pluck('tanggal')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip()
            ->all();

        $leaveTypes = JenisIzin::query()->where('is_active', 1)->get();
        $cutiTahunan = $leaveTypes->firstWhere('kode', 'cuti_tahunan') ?? $leaveTypes->first();
        $izinSakit = $leaveTypes->firstWhere('kode', 'izin_sakit') ?? $leaveTypes->first();
        $izinPribadi = $leaveTypes->firstWhere('kode', 'izin_keperluan_mendesak') ?? $leaveTypes->last();

        // 3. Generate Attendance from 2026-08-01 to 2026-09-11 (today)
        $startDate = Carbon::create(2026, 8, 1)->startOfDay();
        $today = Carbon::create(2026, 9, 11)->startOfDay();
        $datePeriod = CarbonPeriod::create($startDate, $today);

        $attendanceInserts = [];
        $existingAbsensi = Absensi::query()
            ->whereBetween('tanggal', [$startDate->toDateString(), $today->toDateString()])
            ->pluck('id', DB::raw("CONCAT(karyawan_id, '_', tanggal)"))
            ->all();

        foreach ($datePeriod as $date) {
            $isToday = $date->isSameDay($today);
            $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday
            $dateStr = $date->toDateString();

            // Skip Sundays
            if ($dayOfWeek === Carbon::SUNDAY) {
                continue;
            }

            // Skip national holidays
            if (isset($holidays[$dateStr])) {
                continue;
            }

            foreach ($employees as $idx => $emp) {
                $uniqueKey = $emp->id . '_' . $dateStr;
                if (isset($existingAbsensi[$uniqueKey])) {
                    continue;
                }

                // Skip Saturday for non-shift workers (80% of staff)
                if ($dayOfWeek === Carbon::SATURDAY && $emp->jenis_jam_kerja !== 'rolling') {
                    continue;
                }

                $shift = $shifts->get($emp->shift_id) ?? $defaultShift;
                $location = $emp->lokasi_gps_id && $allLocations->firstWhere('id', $emp->lokasi_gps_id)
                    ? $allLocations->firstWhere('id', $emp->lokasi_gps_id)
                    : $defaultLocation;

                // Hash index for determinism
                $hash = crc32($emp->id . '_' . $dateStr);
                $rand = abs($hash) % 100;

                $lat = $location ? (float) $location->latitude + ((($rand % 10) - 5) * 0.00005) : -6.2088;
                $lng = $location ? (float) $location->longitude + ((($rand % 8) - 4) * 0.00005) : 106.8456;
                $locName = $location ? $location->nama_lokasi : 'Kantor Pusat PT Gemah Ripah';

                if ($rand < 82) {
                    // Hadir Tepat Waktu
                    $minuteIn = 45 + ($rand % 14); // 07:45 - 07:58
                    $secondIn = ($rand * 3) % 60;
                    $jamMasuk = sprintf('%02d:%02d:%02d', 7, $minuteIn, $secondIn);

                    $hasOvertime = ($rand % 8 === 0) && ! $isToday;
                    if ($hasOvertime) {
                        $overtimeHours = 2.0 + (($rand % 3) * 0.5);
                        $minuteOut = 30 + ($rand % 25);
                        $jamKeluar = sprintf('%02d:%02d:00', 19 + (int) floor($overtimeHours - 2), $minuteOut);
                        $overtimeMinutes = (int) ($overtimeHours * 60);
                        $overtimeRate = 25000 * $overtimeHours;
                    } else {
                        $minuteOut = 5 + ($rand % 25);
                        $jamKeluar = sprintf('%02d:%02d:00', 17, $minuteOut);
                        $overtimeHours = 0;
                        $overtimeMinutes = 0;
                        $overtimeRate = 0;
                    }

                    if ($isToday) {
                        // For today: 70% have clocked in, some clocked out if half-day/early
                        $jamKeluar = ($rand % 3 === 0) ? sprintf('%02d:%02d:00', 16, 45 + ($rand % 15)) : null;
                    }

                    $attendanceInserts[] = [
                        'karyawan_id' => $emp->id,
                        'tanggal' => $dateStr,
                        'jam_masuk' => $jamMasuk,
                        'jam_keluar' => $jamKeluar,
                        'status' => 'hadir',
                        'lokasi_masuk' => $locName,
                        'lokasi_keluar' => $jamKeluar ? $locName : null,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'latitude_keluar' => $jamKeluar ? $lat : null,
                        'longitude_keluar' => $jamKeluar ? $lng : null,
                        'menit_terlambat' => 0,
                        'menit_pulang_cepat' => 0,
                        'menit_lembur' => $overtimeMinutes,
                        'jam_lembur' => $overtimeHours,
                        'tarif_lembur' => $overtimeRate,
                        'shift_id' => $shift?->id,
                        'schedule_source' => $emp->jenis_jam_kerja ?: 'tetap',
                        'jenis_jam_kerja' => $emp->jenis_jam_kerja ?: 'tetap',
                        'created_at' => Carbon::parse($dateStr . ' ' . $jamMasuk),
                    ];
                } elseif ($rand < 92) {
                    // Terlambat
                    $lateMinutes = 5 + ($rand % 30);
                    $jamMasuk = sprintf('%02d:%02d:00', 8, 15 + $lateMinutes);
                    $jamKeluar = $isToday ? null : sprintf('%02d:%02d:00', 17, 10 + ($rand % 20));

                    $attendanceInserts[] = [
                        'karyawan_id' => $emp->id,
                        'tanggal' => $dateStr,
                        'jam_masuk' => $jamMasuk,
                        'jam_keluar' => $jamKeluar,
                        'status' => 'terlambat',
                        'lokasi_masuk' => $locName,
                        'lokasi_keluar' => $jamKeluar ? $locName : null,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'latitude_keluar' => $jamKeluar ? $lat : null,
                        'longitude_keluar' => $jamKeluar ? $lng : null,
                        'menit_terlambat' => $lateMinutes,
                        'menit_pulang_cepat' => 0,
                        'menit_lembur' => 0,
                        'jam_lembur' => 0,
                        'tarif_lembur' => 0,
                        'shift_id' => $shift?->id,
                        'schedule_source' => $emp->jenis_jam_kerja ?: 'tetap',
                        'jenis_jam_kerja' => $emp->jenis_jam_kerja ?: 'tetap',
                        'created_at' => Carbon::parse($dateStr . ' ' . $jamMasuk),
                    ];
                } elseif ($rand < 96) {
                    // Izin
                    $attendanceInserts[] = [
                        'karyawan_id' => $emp->id,
                        'tanggal' => $dateStr,
                        'jam_masuk' => null,
                        'jam_keluar' => null,
                        'status' => 'izin',
                        'lokasi_masuk' => null,
                        'lokasi_keluar' => null,
                        'latitude' => null,
                        'longitude' => null,
                        'latitude_keluar' => null,
                        'longitude_keluar' => null,
                        'menit_terlambat' => 0,
                        'menit_pulang_cepat' => 0,
                        'menit_lembur' => 0,
                        'jam_lembur' => 0,
                        'tarif_lembur' => 0,
                        'shift_id' => $shift?->id,
                        'schedule_source' => $emp->jenis_jam_kerja ?: 'tetap',
                        'jenis_jam_kerja' => $emp->jenis_jam_kerja ?: 'tetap',
                        'created_at' => Carbon::parse($dateStr . ' 08:00:00'),
                    ];
                } else {
                    // Cuti
                    $attendanceInserts[] = [
                        'karyawan_id' => $emp->id,
                        'tanggal' => $dateStr,
                        'jam_masuk' => null,
                        'jam_keluar' => null,
                        'status' => 'cuti',
                        'lokasi_masuk' => null,
                        'lokasi_keluar' => null,
                        'latitude' => null,
                        'longitude' => null,
                        'latitude_keluar' => null,
                        'longitude_keluar' => null,
                        'menit_terlambat' => 0,
                        'menit_pulang_cepat' => 0,
                        'menit_lembur' => 0,
                        'jam_lembur' => 0,
                        'tarif_lembur' => 0,
                        'shift_id' => $shift?->id,
                        'schedule_source' => $emp->jenis_jam_kerja ?: 'tetap',
                        'jenis_jam_kerja' => $emp->jenis_jam_kerja ?: 'tetap',
                        'created_at' => Carbon::parse($dateStr . ' 08:00:00'),
                    ];
                }

                if (count($attendanceInserts) >= 500) {
                    Absensi::query()->insert($attendanceInserts);
                    $attendanceInserts = [];
                }
            }
        }

        if ($attendanceInserts !== []) {
            Absensi::query()->insert($attendanceInserts);
        }

        // 4. Seed Leave Applications (Izin & Cuti)
        Izin::query()->truncate();

        $sampleLeaves = [
            // Pending Requests (for current date / upcoming)
            [
                'karyawan_id' => $employees[1]->id,
                'jenis_izin_id' => $cutiTahunan?->id,
                'tanggal_mulai' => '2026-09-14',
                'tanggal_selesai' => '2026-09-16',
                'jumlah_hari' => 3,
                'alasan' => 'Acara keluarga di luar kota',
                'jenis_izin' => 'cuti',
                'status' => 'pending',
                'tanggal_pengajuan' => now()->subHours(5),
            ],
            [
                'karyawan_id' => $employees[3]->id,
                'jenis_izin_id' => $izinSakit?->id,
                'tanggal_mulai' => '2026-09-11',
                'tanggal_selesai' => '2026-09-11',
                'jumlah_hari' => 1,
                'alasan' => 'Demam tinggi dan radang tenggorokan',
                'jenis_izin' => 'sakit',
                'status' => 'pending',
                'tanggal_pengajuan' => now()->subHours(2),
            ],
            [
                'karyawan_id' => $employees[5]->id,
                'jenis_izin_id' => $izinPribadi?->id,
                'tanggal_mulai' => '2026-09-15',
                'tanggal_selesai' => '2026-09-15',
                'jumlah_hari' => 1,
                'alasan' => 'Perpanjangan SIM dan dokumen kependudukan',
                'jenis_izin' => 'lainnya',
                'status' => 'pending',
                'tanggal_pengajuan' => now()->subHours(12),
            ],
            [
                'karyawan_id' => $employees[8]->id,
                'jenis_izin_id' => $cutiTahunan?->id,
                'tanggal_mulai' => '2026-09-18',
                'tanggal_selesai' => '2026-09-19',
                'jumlah_hari' => 2,
                'alasan' => 'Keperluan renovasi rumah',
                'jenis_izin' => 'cuti',
                'status' => 'pending',
                'tanggal_pengajuan' => now()->subHours(18),
            ],
            [
                'karyawan_id' => $employees[12]->id,
                'jenis_izin_id' => $izinPribadi?->id,
                'tanggal_mulai' => '2026-09-12',
                'tanggal_selesai' => '2026-09-12',
                'jumlah_hari' => 1,
                'alasan' => 'Mengantar orang tua kontrol kesehatan ke rumah sakit',
                'jenis_izin' => 'lainnya',
                'status' => 'pending',
                'tanggal_pengajuan' => now()->subHours(4),
            ],

            // Approved Requests (Historical & Recent)
            [
                'karyawan_id' => $employees[2]->id,
                'jenis_izin_id' => $izinSakit?->id,
                'tanggal_mulai' => '2026-09-02',
                'tanggal_selesai' => '2026-09-03',
                'jumlah_hari' => 2,
                'alasan' => 'Sakit migrain dan istirahat dokter',
                'jenis_izin' => 'sakit',
                'status' => 'disetujui',
                'tanggal_pengajuan' => Carbon::parse('2026-09-01 20:00:00'),
                'tanggal_disetujui' => Carbon::parse('2026-09-02 08:15:00'),
                'disetujui_oleh' => $admin->id,
                'catatan_admin' => 'Surat dokter terlampir valid, disetujui.',
            ],
            [
                'karyawan_id' => $employees[4]->id,
                'jenis_izin_id' => $cutiTahunan?->id,
                'tanggal_mulai' => '2026-08-18',
                'tanggal_selesai' => '2026-08-21',
                'jumlah_hari' => 4,
                'alasan' => 'Cuti liburan bersama keluarga setelah HUT RI',
                'jenis_izin' => 'cuti',
                'status' => 'disetujui',
                'tanggal_pengajuan' => Carbon::parse('2026-08-10 10:00:00'),
                'tanggal_disetujui' => Carbon::parse('2026-08-11 09:30:00'),
                'disetujui_oleh' => $hrd->id,
                'catatan_admin' => 'Sisa kuota cuti mencukupi.',
            ],
            [
                'karyawan_id' => $employees[7]->id,
                'jenis_izin_id' => $izinSakit?->id,
                'tanggal_mulai' => '2026-08-26',
                'tanggal_selesai' => '2026-08-27',
                'jumlah_hari' => 2,
                'alasan' => 'Tifus ringan istirahat di rumah',
                'jenis_izin' => 'sakit',
                'status' => 'disetujui',
                'tanggal_pengajuan' => Carbon::parse('2026-08-25 19:00:00'),
                'tanggal_disetujui' => Carbon::parse('2026-08-26 08:10:00'),
                'disetujui_oleh' => $admin->id,
                'catatan_admin' => 'Lekas sembuh, approved.',
            ],
            [
                'karyawan_id' => $employees[9]->id,
                'jenis_izin_id' => $izinPribadi?->id,
                'tanggal_mulai' => '2026-09-07',
                'tanggal_selesai' => '2026-09-07',
                'jumlah_hari' => 1,
                'alasan' => 'Urusan administrasi pertanahan',
                'jenis_izin' => 'lainnya',
                'status' => 'disetujui',
                'tanggal_pengajuan' => Carbon::parse('2026-09-05 14:00:00'),
                'tanggal_disetujui' => Carbon::parse('2026-09-06 09:00:00'),
                'disetujui_oleh' => $manager->id,
                'catatan_admin' => 'Disetujui oleh manager operasional.',
            ],
            [
                'karyawan_id' => $employees[11]->id,
                'jenis_izin_id' => $cutiTahunan?->id,
                'tanggal_mulai' => '2026-08-04',
                'tanggal_selesai' => '2026-08-05',
                'jumlah_hari' => 2,
                'alasan' => 'Menghadiri wisuda adik kandung',
                'jenis_izin' => 'cuti',
                'status' => 'disetujui',
                'tanggal_pengajuan' => Carbon::parse('2026-07-28 11:00:00'),
                'tanggal_disetujui' => Carbon::parse('2026-07-29 14:00:00'),
                'disetujui_oleh' => $admin->id,
                'catatan_admin' => 'Approved, selamat untuk adiknya.',
            ],

            // Rejected Requests
            [
                'karyawan_id' => $employees[6]->id,
                'jenis_izin_id' => $cutiTahunan?->id,
                'tanggal_mulai' => '2026-08-14',
                'tanggal_selesai' => '2026-08-15',
                'jumlah_hari' => 2,
                'alasan' => 'Cuti bersama sebelum 17 Agustus',
                'jenis_izin' => 'cuti',
                'status' => 'ditolak',
                'tanggal_pengajuan' => Carbon::parse('2026-08-12 16:00:00'),
                'tanggal_disetujui' => Carbon::parse('2026-08-13 10:00:00'),
                'disetujui_oleh' => $hrd->id,
                'catatan_admin' => 'Mohon maaf jadwal shift sedang penuh menjelang upacara 17 Agustus.',
            ],
            [
                'karyawan_id' => $employees[10]->id,
                'jenis_izin_id' => $izinPribadi?->id,
                'tanggal_mulai' => '2026-09-04',
                'tanggal_selesai' => '2026-09-04',
                'jumlah_hari' => 1,
                'alasan' => 'Keperluan pribadi mendadak tanpa dokumen',
                'jenis_izin' => 'lainnya',
                'status' => 'ditolak',
                'tanggal_pengajuan' => Carbon::parse('2026-09-04 09:30:00'),
                'tanggal_disetujui' => Carbon::parse('2026-09-04 11:00:00'),
                'disetujui_oleh' => $manager->id,
                'catatan_admin' => 'Pengajuan mendadak saat jam kerja sedang berjalan.',
            ],
        ];

        foreach ($sampleLeaves as $leave) {
            Izin::query()->create([
                ...$leave,
                'tanggal' => $leave['tanggal_mulai'],
                'tanggal_izin' => $leave['tanggal_mulai'],
            ]);
        }

        // 5. Seed Overtime (Lembur)
        Lembur::query()->truncate();

        $sampleOvertime = [
            [
                'karyawan_id' => $employees[0]->id,
                'tanggal_lembur' => '2026-09-10',
                'waktu_mulai' => '17:00:00',
                'waktu_selesai' => '20:30:00',
                'durasi_jam' => 3.5,
                'alasan' => 'Deploy update sistem payroll server',
                'status' => 'disetujui',
                'catatan_admin' => 'Pekerjaan kritis berhasil diselesaikan.',
                'jam_lembur' => 3.5,
                'tarif_lembur' => 75000,
                'disetujui_oleh' => $admin->id,
                'tanggal_pengajuan' => Carbon::parse('2026-09-10 16:30:00'),
                'tanggal_disetujui' => Carbon::parse('2026-09-10 17:00:00'),
            ],
            [
                'karyawan_id' => $employees[2]->id,
                'tanggal_lembur' => '2026-09-09',
                'waktu_mulai' => '17:00:00',
                'waktu_selesai' => '19:30:00',
                'durasi_jam' => 2.5,
                'alasan' => 'Penyusunan laporan rekonsiliasi kasbon',
                'status' => 'disetujui',
                'catatan_admin' => 'Disetujui.',
                'jam_lembur' => 2.5,
                'tarif_lembur' => 50000,
                'disetujui_oleh' => $hrd->id,
                'tanggal_pengajuan' => Carbon::parse('2026-09-09 16:45:00'),
                'tanggal_disetujui' => Carbon::parse('2026-09-09 17:05:00'),
            ],
            [
                'karyawan_id' => $employees[4]->id,
                'tanggal_lembur' => '2026-09-11',
                'waktu_mulai' => '17:00:00',
                'waktu_selesai' => '21:00:00',
                'durasi_jam' => 4.0,
                'alasan' => 'Maintenance berkala armada pengiriman gudang',
                'status' => 'pending',
                'catatan_admin' => null,
                'jam_lembur' => 4.0,
                'tarif_lembur' => 80000,
                'disetujui_oleh' => null,
                'tanggal_pengajuan' => now()->subHours(3),
                'tanggal_disetujui' => null,
            ],
            [
                'karyawan_id' => $employees[7]->id,
                'tanggal_lembur' => '2026-09-11',
                'waktu_mulai' => '17:00:00',
                'waktu_selesai' => '20:00:00',
                'durasi_jam' => 3.0,
                'alasan' => 'Penyelesaian pesanan urgent batch September',
                'status' => 'pending',
                'catatan_admin' => null,
                'jam_lembur' => 3.0,
                'tarif_lembur' => 60000,
                'disetujui_oleh' => null,
                'tanggal_pengajuan' => now()->subHours(2),
                'tanggal_disetujui' => null,
            ],
            [
                'karyawan_id' => $employees[6]->id,
                'tanggal_lembur' => '2026-09-05',
                'waktu_mulai' => '17:00:00',
                'waktu_selesai' => '21:00:00',
                'durasi_jam' => 4.0,
                'alasan' => 'Mengerjakan tugas harian yang tertunda',
                'status' => 'ditolak',
                'catatan_admin' => 'Tugas reguler diselesaikan pada jam kerja normal.',
                'jam_lembur' => 0,
                'tarif_lembur' => 0,
                'disetujui_oleh' => $manager->id,
                'tanggal_pengajuan' => Carbon::parse('2026-09-05 16:30:00'),
                'tanggal_disetujui' => Carbon::parse('2026-09-05 17:15:00'),
            ],
        ];

        foreach ($sampleOvertime as $ot) {
            Lembur::query()->create($ot);
        }

        // 6. Seed Kasbon Mutations
        KasbonMutation::query()->truncate();

        $kasbonEmployees = [
            ['emp' => $employees[1], 'pinjaman' => 1500000, 'cicilan' => 500000],
            ['emp' => $employees[3], 'pinjaman' => 2000000, 'cicilan' => 500000],
            ['emp' => $employees[5], 'pinjaman' => 1000000, 'cicilan' => 250000],
            ['emp' => $employees[8], 'pinjaman' => 3000000, 'cicilan' => 1000000],
        ];

        foreach ($kasbonEmployees as $k) {
            $emp = $k['emp'];
            $pinjaman = $k['pinjaman'];
            $cicilan = $k['cicilan'];

            // 1) Pinjaman Masuk (Awal Agustus)
            KasbonMutation::query()->create([
                'karyawan_id' => $emp->id,
                'tanggal' => '2026-08-05',
                'arah' => 'plus',
                'jenis' => 'manual_plus',
                'nominal' => $pinjaman,
                'kasbon_awal' => 0,
                'kasbon_akhir' => $pinjaman,
                'referensi_tipe' => 'manual',
                'referensi_id' => null,
                'catatan' => 'Pinjaman kasbon darurat keperluan keluarga',
                'created_by' => $admin->id,
                'created_at' => Carbon::parse('2026-08-05 10:00:00'),
                'updated_at' => Carbon::parse('2026-08-05 10:00:00'),
            ]);

            // 2) Cicilan Payroll Agustus (Akhir Agustus)
            KasbonMutation::query()->create([
                'karyawan_id' => $emp->id,
                'tanggal' => '2026-08-31',
                'arah' => 'minus',
                'jenis' => 'payroll_deduction',
                'nominal' => $cicilan,
                'kasbon_awal' => $pinjaman,
                'kasbon_akhir' => $pinjaman - $cicilan,
                'referensi_tipe' => 'payroll',
                'referensi_id' => null,
                'catatan' => 'Potongan kasbon payroll periode Agustus 2026',
                'created_by' => $admin->id,
                'created_at' => Carbon::parse('2026-08-31 17:00:00'),
                'updated_at' => Carbon::parse('2026-08-31 17:00:00'),
            ]);
        }

        // 7. Seed Attendance Corrections
        AttendanceCorrection::query()->truncate();

        $recentAbsen = Absensi::query()
            ->where('karyawan_id', $employees[2]->id)
            ->where('tanggal', '2026-09-08')
            ->first();

        if ($recentAbsen) {
            AttendanceCorrection::query()->create([
                'absensi_id' => $recentAbsen->id,
                'previous_data' => [
                    'jam_masuk' => $recentAbsen->jam_masuk,
                    'jam_keluar' => null,
                ],
                'requested_data' => [
                    'jam_masuk' => $recentAbsen->jam_masuk,
                    'jam_keluar' => '17:15:00',
                ],
                'note' => 'Lupa checkout mesin RFID karena pintu darurat terbuka',
                'status' => 'approved',
                'created_by' => $employees[2]->user?->id ?? $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => Carbon::parse('2026-09-09 09:00:00'),
                'review_note' => 'Diverifikasi melalui log CCTV gerbang, jam keluar 17:15 valid.',
                'created_at' => Carbon::parse('2026-09-08 18:00:00'),
                'updated_at' => Carbon::parse('2026-09-09 09:00:00'),
            ]);
        }

        $pendingAbsen = Absensi::query()
            ->where('karyawan_id', $employees[5]->id)
            ->where('tanggal', '2026-09-10')
            ->first();

        if ($pendingAbsen) {
            AttendanceCorrection::query()->create([
                'absensi_id' => $pendingAbsen->id,
                'previous_data' => [
                    'jam_masuk' => $pendingAbsen->jam_masuk,
                    'jam_keluar' => $pendingAbsen->jam_keluar,
                ],
                'requested_data' => [
                    'jam_masuk' => '07:55:00',
                    'jam_keluar' => $pendingAbsen->jam_keluar,
                ],
                'note' => 'Salah tap kartu RFID cadangan rekan kerja',
                'status' => 'pending',
                'created_by' => $employees[5]->user?->id ?? $admin->id,
                'approved_by' => null,
                'approved_at' => null,
                'review_note' => null,
                'created_at' => Carbon::parse('2026-09-10 18:30:00'),
                'updated_at' => Carbon::parse('2026-09-10 18:30:00'),
            ]);
        }

        // 8. Generate Realistic Payroll via PayrollPeriodService
        try {
            $payrollService = app(PayrollPeriodService::class);

            // A) Periode Agustus 2026 (Finalized)
            $augustPeriod = Carbon::create(2026, 8, 1);
            $payrollAugust = $payrollService->syncDraft($augustPeriod, $admin->id);
            $payrollAugust->update([
                'status' => 'finalized',
                'submitted_by' => $hrd->id,
                'submitted_at' => Carbon::parse('2026-08-29 15:00:00'),
                'approved_stage_one_by' => $manager->id,
                'approved_stage_one_at' => Carbon::parse('2026-08-30 10:00:00'),
                'approved_stage_two_by' => $admin->id,
                'approved_stage_two_at' => Carbon::parse('2026-08-31 14:00:00'),
                'finalized_by' => $admin->id,
                'finalized_at' => Carbon::parse('2026-08-31 16:30:00'),
                'catatan' => 'Payroll periode Agustus 2026 PT Gemah Ripah telah diaudit dan disetujui.',
            ]);

            GajiKaryawan::query()
                ->where('payroll_period_id', $payrollAugust->id)
                ->update([
                    'status' => 'selesai',
                    'is_finalized' => 1,
                    'finalized_by' => $admin->id,
                    'finalized_at' => Carbon::parse('2026-08-31 16:30:00'),
                ]);

            // B) Periode September 2026 (Processing/Draft)
            $septemberPeriod = Carbon::create(2026, 9, 1);
            $payrollSeptember = $payrollService->syncDraft($septemberPeriod, $admin->id);
            $payrollSeptember->update([
                'status' => 'processing',
                'submitted_by' => $hrd->id,
                'submitted_at' => now()->subDay(),
                'catatan' => 'Draft payroll berjalan bulan September 2026.',
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
