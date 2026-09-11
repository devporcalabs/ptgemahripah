<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\Setting;
use App\Services\LeaveService;
use App\Services\MonthlyScheduleService;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly MonthlyScheduleService $monthlyScheduleService,
        private readonly LeaveService $leaveService,
    ) {
    }

    public function index(Request $request)
    {
        [$period, $tanggalList, $rows, $statistics] = $this->buildReport($request);
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);
        $filteredRows = $this->filterReportRows($rows, $search);
        $rows = $this->paginateCollection($filteredRows, $request, $perPage);

        return view('admin.laporan', [
            'period' => $period,
            'tanggalList' => $tanggalList,
            'rows' => $rows,
            'statistics' => $statistics,
            'search' => $search,
            'perPage' => $perPage,
            'settings' => Setting::query()->find(1),
        ]);
    }

    public function exportExcel(Request $request): Response|StreamedResponse
    {
        [$period, $tanggalList, $rows, $statistics] = $this->buildReport($request);
        $rows = $this->filterReportRows($rows, $this->resolveSearch($request));
        $type = $request->string('type')->toString() ?: 'excel';

        if ($type === 'pdf') {
            return $this->renderPdf([
                'period' => $period,
                'tanggalList' => $tanggalList,
                'rows' => $rows,
                'statistics' => $statistics,
                'settings' => Setting::query()->find(1),
            ], 'Laporan_Absensi_'.$period->format('Y-m').'.pdf');
        }

        return response()->streamDownload(function () use ($period, $tanggalList, $rows, $statistics): void {
            echo view('admin.exports.laporan', [
                'period' => $period,
                'tanggalList' => $tanggalList,
                'rows' => $rows,
                'statistics' => $statistics,
                'settings' => Setting::query()->find(1),
                'exportMode' => 'excel',
            ])->render();
        }, 'Laporan_Absensi_'.$period->format('Y-m').'.xls', [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    private function buildReport(Request $request): array
    {
        $period = $this->resolvePeriod($request);
        $tanggalList = [];
        $statistics = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'alpha' => 0];
        $today = now()->toDateString();

        for ($day = 1; $day <= $period->daysInMonth; $day++) {
            $tanggal = $period->copy()->day($day);
            $tanggalList[] = [
                'date' => $tanggal->toDateString(),
                'day' => $day,
                'hari' => $tanggal->translatedFormat('l'),
            ];
        }

        $activeEmployees = Karyawan::query()
            ->with('shift:id,nama_shift,jam_masuk,jam_keluar')
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap')
            ->get(['id', 'nik', 'nama_lengkap', 'shift_id']);

        $rows = $activeEmployees->map(function (Karyawan $employee) use ($tanggalList, $period, $today, &$statistics) {
            $summary = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'alpha' => 0];
            $scheduleCalendar = $this->monthlyScheduleService->calendarForEmployee($employee, $period);

            $attendanceMap = $employee->absensi()
                ->whereMonth('tanggal', $period->month)
                ->whereYear('tanggal', $period->year)
                ->get()
                ->keyBy(fn ($row) => Carbon::parse($row->tanggal)->toDateString());
            $approvedLeaves = Izin::query()
                ->with('jenisIzin:id,nama,legacy_code,is_paid')
                ->where('karyawan_id', $employee->id)
                ->where('status', 'disetujui')
                ->whereRaw('COALESCE(tanggal_mulai, tanggal_izin, tanggal) <= ?', [$period->copy()->endOfMonth()->toDateString()])
                ->whereRaw('COALESCE(tanggal_selesai, tanggal_mulai, tanggal_izin, tanggal) >= ?', [$period->copy()->startOfMonth()->toDateString()])
                ->get();
            $izinMap = $this->leaveService
                ->expandLeavesAgainstCalendar($approvedLeaves, $scheduleCalendar, $period)
                ->keyBy('date');

            $data = collect($tanggalList)->map(function (array $tanggalInfo) use ($attendanceMap, $izinMap, $scheduleCalendar, $today, &$summary, &$statistics) {
                $display = 'L';
                $tanggal = $tanggalInfo['date'];
                $absensi = $attendanceMap->get($tanggal);
                $izin = $izinMap->get($tanggal);
                $schedule = $scheduleCalendar->get($tanggal);
                $isFuture = $tanggal > $today;

                if ($izin) {
                    $display = match ($izin['legacy_code'] ?? 'lainnya') {
                        'sakit' => 'S',
                        'cuti' => 'C',
                        default => 'I',
                    };
                    $summary['izin']++;
                    $statistics['izin']++;
                } elseif ($absensi) {
                    if ($absensi->status === 'izin') {
                        $display = 'I';
                        $summary['izin']++;
                        $statistics['izin']++;
                    } elseif ($absensi->status === 'cuti') {
                        $display = 'C';
                        $summary['izin']++;
                        $statistics['izin']++;
                    } elseif ($absensi->status === 'terlambat') {
                        $display = 'T';
                        $summary['terlambat']++;
                        $statistics['terlambat']++;
                    } else {
                        $jamMasuk = $absensi->jam_masuk ? substr($absensi->jam_masuk, 0, 5) : '-';
                        $jamKeluar = $absensi->jam_keluar ? substr($absensi->jam_keluar, 0, 5) : '-';
                        $display = $jamMasuk.'/'.$jamKeluar;
                        $summary['hadir']++;
                        $statistics['hadir']++;
                    }
                } elseif (($schedule['is_workday'] ?? false) && ! $isFuture) {
                    $display = 'A';
                    $summary['alpha']++;
                    $statistics['alpha']++;
                } elseif ($isFuture) {
                    $display = '?';
                }

                return $display;
            })->all();

            return [
                'nik' => $employee->nik,
                'nama' => $employee->nama_lengkap,
                'data' => $data,
                'summary' => $summary,
            ];
        })->filter(function (array $row) {
            foreach ($row['data'] as $value) {
                if (! in_array($value, ['L', '?'], true)) {
                    return true;
                }
            }

            return false;
        })->values();

        return [$period, $tanggalList, $rows, $statistics];
    }

    private function resolvePeriod(Request $request): Carbon
    {
        $value = $request->string('bulan')->toString();

        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        }

        return now()->startOfMonth();
    }

    private function renderPdf(array $data, string $fileName): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('admin.exports.laporan', $data + ['exportMode' => 'pdf'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    private function filterReportRows(iterable $rows, string $search)
    {
        $keyword = strtolower($search);

        return collect($rows)->filter(function (array $row) use ($keyword) {
            if ($keyword === '') {
                return true;
            }

            return str_contains(strtolower($row['nik']), $keyword)
                || str_contains(strtolower($row['nama']), $keyword);
        })->values();
    }
}
