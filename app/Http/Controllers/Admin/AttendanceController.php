<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\AttendanceCorrection;
use App\Models\Shift;
use App\Models\Setting;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.absensi', $this->buildAttendanceData($request));
    }

    public function export(Request $request): Response|StreamedResponse
    {
        $data = $this->buildAttendanceData($request, false);
        $type = $request->string('type')->toString() ?: 'excel';
        $datePart = $data['tanggalFilter'] ?: now()->format('Y-m-d');

        if ($type === 'pdf') {
            return $this->renderPdf(
                'admin.exports.absensi',
                $data,
                'Laporan_Absensi_'.$datePart.'.pdf'
            );
        }

        return response()->streamDownload(function () use ($data): void {
            echo view('admin.exports.absensi', $data + ['exportMode' => 'excel'])->render();
        }, 'Laporan_Absensi_'.$datePart.'.xls', [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    private function buildAttendanceData(Request $request, bool $paginate = true): array
    {
        $tanggalFilter = $request->string('tanggal')->toString() ?: now()->toDateString();
        $statusFilter = $request->string('status')->toString();
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request, 15);
        $shiftOptions = Shift::query()
            ->where('aktif', 1)
            ->orderBy('jam_masuk')
            ->get(['id', 'nama_shift', 'jam_masuk', 'jam_keluar', 'toleransi']);

        $statsQuery = DB::table('absensi as a')
            ->join('karyawan as k', 'a.karyawan_id', '=', 'k.id')
            ->select('a.*', 'k.nama_lengkap', 'k.nik', 'k.jabatan')
            ->whereDate('a.tanggal', $tanggalFilter);

        $query = DB::table('absensi as a')
            ->join('karyawan as k', 'a.karyawan_id', '=', 'k.id')
            ->select(
                'a.*',
                'k.nama_lengkap',
                'k.nik',
                'k.jabatan',
                DB::raw('(SELECT d.name FROM attendance_logs al INNER JOIN devices d ON d.id = al.device_id WHERE al.uid = k.rfid_uid AND DATE(al.scanned_at) = a.tanggal ORDER BY al.scanned_at DESC LIMIT 1) as device_name')
            )
            ->orderByDesc('a.tanggal')
            ->orderByDesc('a.jam_masuk');

        if ($tanggalFilter !== '') {
            $query->whereDate('a.tanggal', $tanggalFilter);
        }

        if ($tanggalFilter !== '') {
            $statsQuery->whereDate('a.tanggal', $tanggalFilter);
        }

        if ($statusFilter !== '') {
            $query->where('a.status', $statusFilter);
        }

        if ($search !== '') {
            $query->where(function ($innerQuery) use ($search): void {
                $innerQuery
                    ->where('k.nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('k.nik', 'like', "%{$search}%")
                    ->orWhere('k.jabatan', 'like', "%{$search}%")
                    ->orWhere('a.lokasi_masuk', 'like', "%{$search}%");
            });
        }

        $absensiRows = $paginate ? $query->paginate($perPage)->withQueryString() : $query->get();
        $absensiCollection = $paginate ? $absensiRows->getCollection() : $absensiRows;
        $shiftMap = $shiftOptions->keyBy('id');
        $correctionMap = AttendanceCorrection::query()
            ->with(['creator:id,name', 'approvedBy:id,name'])
            ->whereIn('absensi_id', collect($absensiCollection)->pluck('id')->all())
            ->orderByDesc('id')
            ->get()
            ->unique('absensi_id')
            ->keyBy('absensi_id');

        $absensiCollection = $absensiCollection->map(function ($absen) use ($shiftMap, $correctionMap) {
            $shift = $shiftMap->get($absen->shift_id);
            $correction = $correctionMap->get($absen->id);
            $absen->shift_nama = $shift?->nama_shift;
            $absen->menit_terlambat_display = (int) ($absen->menit_terlambat ?? 0);
            $absen->menit_pulang_cepat_display = (int) ($absen->menit_pulang_cepat ?? 0);
            $absen->menit_lembur_display = (int) ($absen->menit_lembur ?? 0);
            $absen->schedule_source_label = match ($absen->schedule_source) {
                'tetap' => 'Shift Tetap',
                'rolling' => 'Shift Rolling',
                'fleksibel' => 'Shift Fleksibel',
                'libur' => 'Hari Libur',
                'libur_global' => 'Lembur',
                'libur_mingguan' => 'Libur Mingguan',
                'manual' => 'Override',
                'template' => 'Template',
                'default' => 'Default',
                'none' => 'Tanpa Shift',
                default => 'Legacy',
            };

            if ($shift && $absen->jam_masuk && $shift->jam_masuk) {
                $selisih = floor((strtotime($absen->jam_masuk) - strtotime($shift->jam_masuk)) / 60);
                if ($selisih > ($shift->toleransi ?? 15)) {
                    $absen->menit_terlambat_display = $selisih - ($shift->toleransi ?? 15);
                } else {
                    $absen->menit_terlambat_display = 0;
                }
            }

            $absen->menit_terlambat_display_label = format_duration_minutes_label($absen->menit_terlambat_display);
            $absen->menit_pulang_cepat_display_label = format_duration_minutes_label($absen->menit_pulang_cepat_display);
            $absen->menit_lembur_display_label = format_duration_minutes_label($absen->menit_lembur_display);

            $absen->correction = $correction;
            $absen->correction_id = $correction?->id;
            $absen->correction_status = $correction?->status ?: 'none';
            $absen->correction_note = $correction?->note;
            $absen->correction_review_note = $correction?->review_note;
            $absen->correction_requested_by = $correction?->creator?->nama_lengkap;
            $absen->correction_approved_by = $correction?->approvedBy?->nama_lengkap;
            $absen->correction_payload = $correction?->requested_data ?: [];

            return $absen;
        });

        if ($paginate) {
            $absensiRows->setCollection($absensiCollection);
        }

        return [
            'settings' => Setting::query()->find(1),
            'tanggalFilter' => $tanggalFilter,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'perPage' => $perPage,
            'shiftOptions' => $shiftOptions,
            'absensiList' => $paginate ? $absensiRows : $absensiCollection,
            'totalKaryawan' => (clone $statsQuery)->count(),
            'totalHadir' => (clone $statsQuery)->where('a.status', 'hadir')->count(),
            'totalIzinSakit' => (clone $statsQuery)->whereIn('a.status', ['izin', 'cuti'])->count(),
            'totalTerlambat' => (clone $statsQuery)->where('a.status', 'terlambat')->count(),
        ];
    }

    private function renderPdf(string $view, array $data, string $fileName): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, $data + ['exportMode' => 'pdf'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }
}
