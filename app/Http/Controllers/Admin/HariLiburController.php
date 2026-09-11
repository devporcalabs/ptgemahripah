<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Services\ApiCoIdHolidaySyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;
use Illuminate\Validation\Rule;

class HariLiburController extends Controller
{
    public function index(Request $request)
    {
        $search = $this->resolveSearch($request);
        $perPage = $this->resolvePerPage($request);
        $viewMode = $this->normalizeViewMode($request->string('view')->toString());
        $sourceFilter = $this->normalizeSourceFilter($request->string('source')->toString());
        $monthInput = trim($request->string('bulan')->toString());
        $calendarMonth = preg_match('/^\d{4}-\d{2}$/', $monthInput) === 1
            ? Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth()
            : now()->startOfMonth();
        $month = $calendarMonth->format('Y-m');
        $editId = $request->integer('edit');

        if (! $editId && old('form_type') === 'edit') {
            $editId = (int) old('edit_id');
        }

        $editHoliday = $editId ? HariLibur::query()->find($editId) : null;
        $calendarHolidays = HariLibur::query()
            ->when($sourceFilter !== 'all', function ($query) use ($sourceFilter): void {
                $query->where('source', $sourceFilter === 'api' ? HariLibur::SOURCE_API_CO_ID : HariLibur::SOURCE_MANUAL);
            })
            ->whereYear('tanggal', (int) $calendarMonth->format('Y'))
            ->whereMonth('tanggal', (int) $calendarMonth->format('m'))
            ->orderBy('tanggal')
            ->get()
            ->keyBy(fn (HariLibur $holiday) => $holiday->tanggal?->format('Y-m-d'));

        $holidayList = HariLibur::query()
            ->when($sourceFilter !== 'all', function ($query) use ($sourceFilter): void {
                $query->where('source', $sourceFilter === 'api' ? HariLibur::SOURCE_API_CO_ID : HariLibur::SOURCE_MANUAL);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('nama_libur', 'like', "%{$search}%")
                        ->orWhere('keterangan', 'like', "%{$search}%");
                });
            })
            ->when(preg_match('/^\d{4}-\d{2}$/', $month) === 1, function ($query) use ($month): void {
                [$year, $monthNumber] = explode('-', $month);
                $query
                    ->whereYear('tanggal', (int) $year)
                    ->whereMonth('tanggal', (int) $monthNumber);
            })
            ->orderByDesc('aktif')
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.hari-libur', [
            'viewMode' => $viewMode,
            'sourceFilter' => $sourceFilter,
            'calendarPrevUrl' => $this->buildRouteUrl($request, [
                'bulan' => $calendarMonth->copy()->subMonth()->format('Y-m'),
                'view' => 'calendar',
            ]),
            'calendarNextUrl' => $this->buildRouteUrl($request, [
                'bulan' => $calendarMonth->copy()->addMonth()->format('Y-m'),
                'view' => 'calendar',
            ]),
            'calendarListUrl' => $this->buildRouteUrl($request, ['view' => 'list']),
            'calendarViewUrl' => $this->buildRouteUrl($request, ['view' => 'calendar']),
            'holidayList' => $holidayList,
            'editHoliday' => $editHoliday,
            'search' => $search,
            'perPage' => $perPage,
            'month' => $month,
            'calendarMonth' => $calendarMonth,
            'calendarWeeks' => $this->buildCalendarWeeks($calendarMonth, $calendarHolidays),
            'calendarHolidayCount' => $calendarHolidays->where('aktif', true)->count(),
            'calendarSundayCount' => $this->countSundays($calendarMonth),
            'apiCoIdConfigured' => trim((string) config('services.api_co_id.key', '')) !== '',
        ]);
    }

    public function store(Request $request)
    {
        HariLibur::query()->create($this->validatedData($request));

        return $this->respondSuccess($request, route('admin.kalender'), 'Hari libur berhasil ditambahkan.');
    }

    public function update(Request $request, HariLibur $hariLibur)
    {
        $hariLibur->update($this->validatedData($request, $hariLibur));

        return $this->respondSuccess($request, route('admin.kalender'), 'Hari libur berhasil diperbarui.');
    }

    public function sync(Request $request, ApiCoIdHolidaySyncService $syncService)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ], [
            'year.required' => 'Tahun sinkron wajib diisi.',
            'year.integer' => 'Format tahun sinkron tidak valid.',
        ]);

        try {
            $result = $syncService->syncYear((int) $validated['year']);
        } catch (RuntimeException $exception) {
            return $this->respondError($request, route('admin.kalender'), $exception->getMessage(), status: 422);
        }

        $message = sprintf(
            'Sinkron hari libur %d selesai. Baru: %d, diperbarui: %d, manual dipertahankan: %d, dihapus: %d.',
            $result['year'],
            $result['created'],
            $result['updated'],
            $result['manual_skipped'],
            $result['deleted'],
        );

        return $this->respondSuccess($request, route('admin.kalender'), $message);
    }

    public function destroy(Request $request, HariLibur $hariLibur)
    {
        $hariLibur->delete();

        return $this->respondSuccess($request, route('admin.kalender'), 'Hari libur berhasil dihapus.');
    }

    private function validatedData(Request $request, ?HariLibur $hariLibur = null): array
    {
        $data = $request->validate([
            'tanggal' => [
                'required',
                'date',
                Rule::unique('hari_libur', 'tanggal')->ignore($hariLibur?->id),
            ],
            'nama_libur' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string'],
            'aktif' => ['required', 'in:0,1'],
        ], [
            'tanggal.required' => 'Tanggal libur wajib diisi.',
            'tanggal.date' => 'Format tanggal libur tidak valid.',
            'tanggal.unique' => 'Tanggal libur tersebut sudah terdaftar.',
            'nama_libur.required' => 'Nama hari libur wajib diisi.',
            'nama_libur.max' => 'Nama hari libur maksimal 150 karakter.',
            'aktif.required' => 'Status hari libur wajib dipilih.',
        ]);

        return [
            'tanggal' => $data['tanggal'],
            'nama_libur' => trim((string) $data['nama_libur']),
            'keterangan' => isset($data['keterangan']) && trim((string) $data['keterangan']) !== ''
                ? trim((string) $data['keterangan'])
                : null,
            'aktif' => (int) $data['aktif'] === 1,
            'source' => $hariLibur?->source === HariLibur::SOURCE_API_CO_ID
                ? HariLibur::SOURCE_MANUAL
                : ($hariLibur?->source ?: HariLibur::SOURCE_MANUAL),
            'external_id' => $hariLibur?->source === HariLibur::SOURCE_API_CO_ID ? null : $hariLibur?->external_id,
            'external_type' => $hariLibur?->source === HariLibur::SOURCE_API_CO_ID ? null : $hariLibur?->external_type,
            'sync_meta' => $hariLibur?->source === HariLibur::SOURCE_API_CO_ID ? null : $hariLibur?->sync_meta,
            'last_synced_at' => $hariLibur?->source === HariLibur::SOURCE_API_CO_ID ? null : $hariLibur?->last_synced_at,
        ];
    }

    private function buildCalendarWeeks(Carbon $month, Collection $holidayMap): array
    {
        $weeks = [];
        $week = [];
        $startOffset = $month->dayOfWeekIso - 1;

        for ($offset = 0; $offset < $startOffset; $offset++) {
            $week[] = null;
        }

        foreach (range(1, $month->daysInMonth) as $dayNumber) {
            $date = $month->copy()->day($dayNumber);
            $holiday = $holidayMap->get($date->toDateString());

            $week[] = [
                'date' => $date->toDateString(),
                'day_number' => $dayNumber,
                'is_sunday' => $date->isSunday(),
                'holiday' => $holiday,
                'is_global_holiday' => (bool) ($holiday?->aktif ?? false),
                'is_red' => $date->isSunday() || (bool) ($holiday?->aktif ?? false),
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week = [];
            }
        }

        if ($week !== []) {
            while (count($week) < 7) {
                $week[] = null;
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    private function countSundays(Carbon $month): int
    {
        return collect(range(1, $month->daysInMonth))
            ->filter(fn (int $dayNumber) => $month->copy()->day($dayNumber)->isSunday())
            ->count();
    }

    private function normalizeViewMode(string $value): string
    {
        return in_array($value, ['calendar', 'list'], true) ? $value : 'calendar';
    }

    private function normalizeSourceFilter(string $value): string
    {
        return in_array($value, ['all', 'manual', 'api'], true) ? $value : 'all';
    }

    private function buildRouteUrl(Request $request, array $overrides = []): string
    {
        return route('admin.kalender', array_merge($request->query(), $overrides));
    }
}
