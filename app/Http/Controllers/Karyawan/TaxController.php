<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\GajiKaryawan;
use App\Services\BuktiPotongA1Service;
use App\Services\SalaryService;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function __construct(
        private readonly SalaryService $salaryService,
        private readonly BuktiPotongA1Service $buktiPotongA1Service,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user()?->loadMissing('karyawan');
        $employee = $user?->karyawan;

        abort_unless($employee, 404);

        $year = $this->resolveYear($request);
        $perPage = $this->resolvePerPage($request, 10, [10, 25, 50, 100]);

        $taxRows = GajiKaryawan::query()
            ->with('karyawan')
            ->where('karyawan_id', $employee->id)
            ->whereYear('bulan', $year)
            ->orderByDesc('bulan')
            ->paginate($perPage)
            ->withQueryString();

        $taxRows->getCollection()->transform(function (GajiKaryawan $history): GajiKaryawan {
            $snapshot = is_array($history->detail_payload)
                ? $history->detail_payload
                : $this->salaryService->historySnapshot($history);

            $history->setAttribute('portal_tax_snapshot', $snapshot);

            return $history;
        });

        $annualDraft = $this->buktiPotongA1Service->draftForEmployee($employee, $year);

        $summary = [
            'periods' => $taxRows->total(),
            'gross_total' => (float) $taxRows->getCollection()->sum(fn (GajiKaryawan $row) => (float) (($row->portal_tax_snapshot['pph21_gross_basis'] ?? 0))),
            'pph21_total' => (float) $taxRows->getCollection()->sum(fn (GajiKaryawan $row) => max(0, (float) (($row->portal_tax_snapshot['pph21_amount'] ?? $row->pph21 ?? 0)))),
            'refund_total' => (float) $taxRows->getCollection()->sum(fn (GajiKaryawan $row) => max(0, abs(min(0, (float) (($row->portal_tax_snapshot['pph21_amount'] ?? $row->pph21 ?? 0)))))),
            'annual_tax' => (float) ($annualDraft['pph21_annual_tax'] ?? 0),
        ];

        return view('karyawan.pajak', [
            'user' => $user,
            'employee' => $employee,
            'year' => $year,
            'perPage' => $perPage,
            'taxRows' => $taxRows,
            'annualDraft' => $annualDraft,
            'summary' => $summary,
        ]);
    }

    private function resolveYear(Request $request): int
    {
        $currentYear = now()->year;
        $value = (int) $request->integer('tahun', $currentYear);

        return $value >= 2020 && $value <= ($currentYear + 1)
            ? $value
            : $currentYear;
    }
}
