<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BuktiPotongA1Service;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BuktiPotongA1Controller extends Controller
{
    public function __construct(
        private readonly BuktiPotongA1Service $buktiPotongA1Service,
    ) {
    }

    public function index(Request $request)
    {
        return view('admin.bukti-potong-a1', $this->buildPageData($request));
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $data = $this->buildPageData($request, false);
        $type = $request->string('type')->toString() ?: 'xml';
        $readyRows = collect($data['a1Rows'])->filter(fn (array $row): bool => (bool) ($row['ready'] ?? false))->values();

        if ($readyRows->isEmpty()) {
            return redirect()
                ->route('admin.bukti-potong-a1', [
                    'tahun' => $data['year'],
                    'status' => $data['statusFilter'],
                    'q' => $data['search'],
                    'per_page' => $data['perPage'],
                ])
                ->with('error', 'Belum ada draft BPA1 yang siap diexport. Lengkapi identitas pemotong dan pastikan payroll masa pajak akhir sudah final tahunan.');
        }

        $fileYear = (string) $data['year'];

        if ($type === 'csv') {
            $csvRows = $this->buktiPotongA1Service->buildCsvRows($readyRows);
            $headers = array_keys($csvRows[0] ?? []);

            return response()->streamDownload(function () use ($headers, $csvRows): void {
                $output = fopen('php://output', 'w');
                fputcsv($output, $headers);

                foreach ($csvRows as $row) {
                    fputcsv($output, array_values($row));
                }

                fclose($output);
            }, 'Bukti_Potong_A1_'.$fileYear.'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $xml = $this->buktiPotongA1Service->buildXml($readyRows);

        return response()->streamDownload(function () use ($xml): void {
            echo $xml;
        }, 'Bukti_Potong_A1_'.$fileYear.'.xml', [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function buildPageData(Request $request, bool $paginate = true): array
    {
        $year = $this->resolveYear($request);
        $search = $this->resolveSearch($request);
        $statusFilter = $this->resolveStatusFilter($request);
        $perPage = $this->resolvePerPage($request, 15, [15, 25, 50, 100]);
        $rows = $this->buktiPotongA1Service->draftsForYear($year);
        $filteredRows = $this->filterRows($rows, $search, $statusFilter);
        $paginatedRows = $paginate
            ? $this->paginateCollection($filteredRows, $request, $perPage)
            : $filteredRows;

        return [
            'year' => $year,
            'settings' => Setting::query()->find(1),
            'a1Rows' => $paginatedRows,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'perPage' => $perPage,
            'statusOptions' => [
                'all' => 'Semua Status',
                'ready' => 'Siap Export',
                'issue' => 'Perlu Review',
            ],
            'summary' => [
                'total_rows' => $filteredRows->count(),
                'ready_rows' => $filteredRows->where('ready', true)->count(),
                'issue_rows' => $filteredRows->where('ready', false)->count(),
                'annual_tax_total' => $filteredRows->sum(fn (array $row) => (float) ($row['pph21_annual_tax'] ?? 0)),
                'gross_basis_total' => $filteredRows->sum(fn (array $row) => (float) ($row['annual_gross_basis'] ?? 0)),
            ],
        ];
    }

    private function resolveYear(Request $request): int
    {
        $currentYear = now()->year;
        $value = (int) $request->integer('tahun', $currentYear);

        return $value >= 2020 && $value <= ($currentYear + 1)
            ? $value
            : $currentYear;
    }

    private function resolveStatusFilter(Request $request): string
    {
        $allowed = ['all', 'ready', 'issue'];
        $value = trim($request->string('status')->toString());

        return in_array($value, $allowed, true) ? $value : 'all';
    }

    private function filterRows(Collection $rows, string $search, string $statusFilter): Collection
    {
        $keyword = strtolower($search);

        return $rows->filter(function (array $row) use ($keyword, $statusFilter): bool {
            if ($keyword !== '') {
                $employee = $row['employee'];
                $haystack = [
                    strtolower((string) ($employee['nik'] ?? '')),
                    strtolower((string) ($employee['nama_lengkap'] ?? '')),
                    strtolower((string) ($employee['jabatan'] ?? '')),
                    strtolower((string) ($employee['departemen'] ?? '')),
                    strtolower((string) ($employee['status_ptkp'] ?? '')),
                ];

                if (! collect($haystack)->contains(fn (string $value): bool => str_contains($value, $keyword))) {
                    return false;
                }
            }

            if ($statusFilter === 'ready' && ! ($row['ready'] ?? false)) {
                return false;
            }

            if ($statusFilter === 'issue' && ($row['ready'] ?? false)) {
                return false;
            }

            return true;
        })->values();
    }
}
