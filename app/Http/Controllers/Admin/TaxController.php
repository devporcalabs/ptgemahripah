<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PayrollPeriodService;
use App\Services\SalaryService;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaxController extends Controller
{
    public function __construct(
        private readonly SalaryService $salaryService,
        private readonly PayrollPeriodService $payrollPeriodService,
    ) {
    }

    public function index(Request $request)
    {
        return view('admin.pajak-pph21', $this->buildTaxData($request));
    }

    public function export(Request $request): Response|StreamedResponse
    {
        $data = $this->buildTaxData($request, false);
        $type = $request->string('type')->toString() ?: 'excel';
        $period = $data['period'];

        if ($type === 'pdf') {
            return $this->renderPdf($data, 'Laporan_Pajak_PPh21_'.$period->format('Y-m').'.pdf');
        }

        return response()->streamDownload(function () use ($data): void {
            echo view('admin.exports.pajak-pph21', $data + ['exportMode' => 'excel'])->render();
        }, 'Laporan_Pajak_PPh21_'.$period->format('Y-m').'.xls', [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    private function buildTaxData(Request $request, bool $paginate = true): array
    {
        $period = $this->resolvePeriod($request);
        $search = $this->resolveSearch($request);
        $methodFilter = $this->resolveMethodFilter($request);
        $statusFilter = $this->resolveStatusFilter($request);
        $perPage = $this->resolvePerPage($request, 15);
        $payrollPeriod = $this->payrollPeriodService->syncDraft($period, auth()->id());
        $rows = $this->salaryService->historyRows($period, $payrollPeriod);
        $filteredRows = $this->filterTaxRows($rows, $search, $methodFilter, $statusFilter);
        $taxRows = $paginate ? $this->paginateCollection($filteredRows, $request, $perPage) : $filteredRows;

        return [
            'period' => $period,
            'settings' => Setting::query()->find(1),
            'payrollPeriod' => $payrollPeriod,
            'taxRows' => $taxRows,
            'search' => $search,
            'methodFilter' => $methodFilter,
            'statusFilter' => $statusFilter,
            'perPage' => $perPage,
            'methodOptions' => [
                'all' => 'Semua Metode',
                'ter' => 'TER Bulanan',
                'annual_reconcile' => 'Final Tahunan',
                'annualized' => 'Estimasi Tahunan',
                'off' => 'Nonaktif / Legacy',
            ],
            'statusOptions' => [
                'all' => 'Semua Status',
                'finalized' => 'Final',
                'draft' => 'Draft',
            ],
            'taxSummary' => [
                'rows' => $filteredRows->count(),
                'ter_rows' => $filteredRows->where('pph21_method', 'ter')->count(),
                'final_rows' => $filteredRows->where('pph21_method', 'annual_reconcile')->count(),
                'gross_total' => $filteredRows->sum(fn (array $row) => (float) ($row['pph21_gross_basis'] ?? 0)),
                'pph21_payable_total' => $filteredRows->sum(fn (array $row) => max(0, (float) ($row['pph21_amount'] ?? 0))),
                'pph21_refund_total' => $filteredRows->sum(fn (array $row) => max(0, abs(min(0, (float) ($row['pph21_amount'] ?? 0))))),
            ],
        ];
    }

    private function filterTaxRows(iterable $rows, string $search, string $methodFilter, string $statusFilter)
    {
        $keyword = strtolower($search);

        return collect($rows)->filter(function (array $row) use ($keyword, $methodFilter, $statusFilter): bool {
            $employee = $row['karyawan'];
            $statusPtkp = strtolower((string) ($employee->status_ptkp ?? $employee->status_nikah ?? 'TK/0'));
            $method = (string) ($row['pph21_method'] ?? 'off');
            $isFinalized = (bool) ($row['is_finalized'] ?? false);

            if ($keyword !== '') {
                $matchesKeyword = str_contains(strtolower((string) $employee->nik), $keyword)
                    || str_contains(strtolower((string) $employee->nama_lengkap), $keyword)
                    || str_contains(strtolower((string) $employee->jabatan), $keyword)
                    || str_contains($statusPtkp, $keyword);

                if (! $matchesKeyword) {
                    return false;
                }
            }

            if ($methodFilter !== 'all' && $method !== $methodFilter) {
                return false;
            }

            if ($statusFilter === 'finalized' && ! $isFinalized) {
                return false;
            }

            if ($statusFilter === 'draft' && $isFinalized) {
                return false;
            }

            return true;
        })->values();
    }

    private function resolvePeriod(Request $request): Carbon
    {
        $value = (string) ($request->input('bulan') ?? $request->query('bulan', ''));

        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        }

        return now()->startOfMonth();
    }

    private function resolveMethodFilter(Request $request): string
    {
        $allowed = ['all', 'ter', 'annual_reconcile', 'annualized', 'off'];
        $value = trim($request->string('method')->toString());

        return in_array($value, $allowed, true) ? $value : 'all';
    }

    private function resolveStatusFilter(Request $request): string
    {
        $allowed = ['all', 'finalized', 'draft'];
        $value = trim($request->string('status')->toString());

        return in_array($value, $allowed, true) ? $value : 'all';
    }

    private function renderPdf(array $data, string $fileName): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('admin.exports.pajak-pph21', $data + ['exportMode' => 'pdf'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }
}
