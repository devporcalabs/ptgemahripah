<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\GajiKaryawan;
use App\Models\Setting;
use App\Services\SalaryService;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PayrollController extends Controller
{
    public function __construct(
        private readonly SalaryService $salaryService,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->hasEmployeePanelAccess(), 403);

        $employee = $user->karyawan;
        $perPage = $this->resolvePerPage($request, 10);
        $year = max(2000, min(2100, (int) $request->query('tahun', now()->year)));

        $query = GajiKaryawan::query()
            ->where('karyawan_id', $employee->id)
            ->whereYear('bulan', $year);

        $rows = (clone $query)
            ->orderByDesc('bulan')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $rows->getCollection()->transform(function (GajiKaryawan $row) {
            $row->loadMissing('karyawan');

            $snapshot = is_array($row->detail_payload) && $row->detail_payload !== []
                ? $row->detail_payload
                : $this->salaryService->historySnapshot($row);

            $row->setAttribute('portal_breakdown_payload', $this->buildPortalBreakdownPayload($row, $snapshot));

            return $row;
        });

        return view('karyawan.payroll', [
            'panel' => 'karyawan',
            'pageTitle' => 'Payroll Saya',
            'employee' => $employee,
            'year' => $year,
            'perPage' => $perPage,
            'salaryRows' => $rows,
            'stats' => [
                'periode' => (clone $query)->count(),
                'total_gaji' => (float) (clone $query)->sum('total_gaji'),
                'total_pph21' => (float) (clone $query)->sum('pph21'),
                'total_thr' => (float) (clone $query)->sum('thr'),
            ],
        ]);
    }

    private function buildPortalBreakdownPayload(GajiKaryawan $history, array $snapshot): array
    {
        $periodLabel = optional($history->bulan)->translatedFormat('F Y') ?: '-';
        $employeeName = $history->karyawan?->nama_lengkap ?: 'Karyawan';

        $incomeItems = [
            ['label' => 'Gaji Pokok', 'amount' => (float) ($snapshot['base_salary_total'] ?? $snapshot['gaji_kehadiran'] ?? 0), 'always' => true],
            ['label' => 'Tunjangan Makan', 'amount' => (float) ($snapshot['tunjangan_makan_total'] ?? 0)],
            ['label' => 'Tunjangan Transport', 'amount' => (float) ($snapshot['tunjangan_transport_total'] ?? 0)],
            ['label' => 'Tunjangan BPJS Kesehatan', 'amount' => (float) ($snapshot['tunjangan_bpjs_kesehatan_total'] ?? 0)],
            ['label' => 'Tunjangan BPJS Ketenagakerjaan', 'amount' => (float) ($snapshot['tunjangan_bpjs_ketenagakerjaan_total'] ?? 0)],
            ['label' => 'Lembur', 'amount' => (float) ($snapshot['total_lembur_tarif'] ?? 0), 'note' => $this->formatMinutesNote($snapshot['total_menit_lembur'] ?? null)],
            ['label' => 'Premi Kehadiran', 'amount' => (float) ($snapshot['premi_kehadiran_total'] ?? 0)],
            ['label' => 'Bonus Personal', 'amount' => (float) ($snapshot['bonus_pribadi_total'] ?? 0)],
            ['label' => 'Bonus Team', 'amount' => (float) ($snapshot['bonus_team_total'] ?? 0)],
            ['label' => 'Bonus Manual', 'amount' => (float) ($snapshot['bonus_manual_total'] ?? 0)],
            ['label' => 'THR', 'amount' => (float) ($snapshot['thr_amount'] ?? $history->thr ?? 0)],
            ['label' => 'Penyesuaian Plus', 'amount' => max(0, (float) ($snapshot['total_penyesuaian'] ?? 0))],
        ];

        $deductionItems = [
            ['label' => 'PPh21', 'amount' => (float) ($snapshot['pph21_amount'] ?? $history->pph21 ?? 0), 'always' => true],
            ['label' => 'BPJS Kesehatan', 'amount' => (float) ($snapshot['potongan_bpjs_kesehatan_total'] ?? 0)],
            ['label' => 'BPJS Ketenagakerjaan', 'amount' => (float) ($snapshot['potongan_bpjs_ketenagakerjaan_total'] ?? 0)],
            ['label' => 'Potongan Terlambat', 'amount' => (float) ($snapshot['potongan_terlambat'] ?? 0)],
            ['label' => 'Potongan Mangkir', 'amount' => (float) ($snapshot['potongan_mangkir'] ?? 0)],
            ['label' => 'Potongan Izin', 'amount' => (float) ($snapshot['potongan_izin'] ?? 0)],
            ['label' => 'Kasbon', 'amount' => (float) ($snapshot['potongan_kasbon'] ?? 0)],
            ['label' => 'Penyesuaian Minus', 'amount' => abs(min(0, (float) ($snapshot['total_penyesuaian'] ?? 0)))],
        ];

        $summaryItems = [
            ['label' => 'Status Payroll', 'text' => $history->is_finalized ? 'Final' : 'Draft'],
            ['label' => 'Total Hadir', 'text' => number_format((int) ($snapshot['total_hadir'] ?? 0), 0, ',', '.').' hari'],
            ['label' => 'Total Terlambat', 'text' => number_format((int) ($snapshot['total_terlambat'] ?? 0), 0, ',', '.').' kali'],
            ['label' => 'Durasi Terlambat', 'text' => $this->formatMinutesLabel((int) ($snapshot['total_menit_terlambat'] ?? 0))],
            ['label' => 'Total Izin', 'text' => number_format((int) ($snapshot['total_izin'] ?? 0), 0, ',', '.').' hari'],
            ['label' => 'Total Alpha', 'text' => number_format((int) ($snapshot['total_alpha'] ?? 0), 0, ',', '.').' hari'],
        ];

        return [
            'title' => 'Rincian Perhitungan Gaji',
            'subtitle' => $employeeName.' - '.$periodLabel,
            'summary_label' => 'Gaji Diterima',
            'summary_amount' => (float) ($snapshot['total_gaji'] ?? $history->total_gaji ?? 0),
            'sections' => [
                [
                    'title' => 'Pendapatan',
                    'tone' => 'income',
                    'items' => $this->filterBreakdownMoneyItems($incomeItems),
                ],
                [
                    'title' => 'Potongan',
                    'tone' => 'expense',
                    'items' => $this->filterBreakdownMoneyItems($deductionItems),
                ],
                [
                    'title' => 'Ringkasan Kehadiran',
                    'tone' => 'neutral',
                    'items' => $this->filterBreakdownTextItems($summaryItems),
                ],
            ],
        ];
    }

    private function filterBreakdownMoneyItems(array $items): array
    {
        return array_values(array_map(
            static fn (array $item) => [
                'label' => $item['label'],
                'amount' => (float) ($item['amount'] ?? 0),
                'note' => $item['note'] ?? null,
            ],
            array_filter($items, static function (array $item) {
                return ($item['always'] ?? false) || abs((float) ($item['amount'] ?? 0)) > 0;
            })
        ));
    }

    private function filterBreakdownTextItems(array $items): array
    {
        return array_values(array_map(
            static fn (array $item) => [
                'label' => $item['label'],
                'text' => $item['text'] ?? '-',
            ],
            array_filter($items, static function (array $item) {
                return isset($item['text']) && $item['text'] !== '';
            })
        ));
    }

    private function formatMinutesNote(mixed $minutes): ?string
    {
        if (! is_numeric($minutes) || (int) $minutes <= 0) {
            return null;
        }

        return $this->formatMinutesLabel((int) $minutes);
    }

    private function formatMinutesLabel(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 menit';
        }

        if ($minutes < 60) {
            return $minutes.' menit';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0
            ? $hours.' jam '.$remainingMinutes.' menit'
            : $hours.' jam';
    }

    public function slip(Request $request, GajiKaryawan $gajiKaryawan): Response
    {
        $user = $request->user();
        abort_unless($user?->hasEmployeePanelAccess(), 403);
        abort_unless((int) $gajiKaryawan->karyawan_id === (int) $user->karyawan_id, 404);

        $gajiKaryawan->loadMissing('karyawan');
        $snapshot = $gajiKaryawan->detail_payload;

        if (! is_array($snapshot) || $snapshot === []) {
            $salary = $this->salaryService->forEmployee(
                $gajiKaryawan->karyawan,
                Carbon::parse($gajiKaryawan->bulan)->startOfMonth()
            );
            $snapshot = $this->salaryService->snapshot($salary);
        }

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('admin.exports.slip-gaji', [
            'history' => $gajiKaryawan,
            'snapshot' => $snapshot,
            'settings' => Setting::query()->find(1),
        ])->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $period = Carbon::parse($gajiKaryawan->bulan)->format('Y-m');
        $nik = $gajiKaryawan->karyawan?->nik ?: 'UNKNOWN';
        $fileName = 'Slip_Gaji_'.$nik.'_'.$period.'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }
}
