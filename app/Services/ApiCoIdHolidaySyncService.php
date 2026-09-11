<?php

namespace App\Services;

use App\Models\HariLibur;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ApiCoIdHolidaySyncService
{
    public function syncYear(int $year): array
    {
        if ($year < 2000 || $year > 2100) {
            throw new RuntimeException('Tahun sinkron tidak valid.');
        }

        $baseUrl = rtrim((string) config('services.api_co_id.base_url', 'https://use.api.co.id'), '/');
        $apiKey = trim((string) config('services.api_co_id.key', ''));

        if ($apiKey === '') {
            throw new RuntimeException('API key `api.co.id` belum diatur. Isi `API_CO_ID_KEY` di file `.env`.');
        }

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'x-api-co-id' => $apiKey,
            ])
            ->get($baseUrl.'/holidays/indonesia', [
                'year' => $year,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal mengambil data hari libur dari API.co.id.');
        }

        $items = $this->extractItems($response->json());
        $normalizedHolidays = $this->normalizeItems($items, $year);

        if ($items === [] || $normalizedHolidays === []) {
            throw new RuntimeException('API.co.id tidak mengembalikan data hari libur yang valid untuk tahun tersebut.');
        }

        $syncedDates = [];
        $created = 0;
        $updated = 0;
        $skippedManual = 0;

        foreach ($normalizedHolidays as $holiday) {
            $syncedDates[] = $holiday['tanggal'];

            $existing = HariLibur::query()
                ->whereDate('tanggal', $holiday['tanggal'])
                ->first();

            if ($existing && $existing->source === HariLibur::SOURCE_MANUAL) {
                $skippedManual++;
                continue;
            }

            if ($existing) {
                $existing->update($holiday);
                $updated++;
                continue;
            }

            HariLibur::query()->create($holiday);
            $created++;
        }

        $staleRows = HariLibur::query()
            ->whereYear('tanggal', $year)
            ->where('source', HariLibur::SOURCE_API_CO_ID)
            ->get()
            ->reject(function (HariLibur $holiday) use ($syncedDates): bool {
                return in_array($holiday->tanggal?->toDateString(), $syncedDates, true);
            })
            ->values();

        $deleted = $staleRows->count();
        $staleRows->each->delete();

        return [
            'year' => $year,
            'total_api_items' => count($items),
            'total_synced_days' => count($syncedDates),
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
            'manual_skipped' => $skippedManual,
        ];
    }

    private function extractItems(mixed $payload): array
    {
        if (is_array($payload) && array_is_list($payload)) {
            return $payload;
        }

        if (! is_array($payload)) {
            return [];
        }

        foreach (['data', 'results', 'holidays', 'items'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }

        return [];
    }

    private function normalizeItems(array $items, int $year): array
    {
        return collect($items)
            ->map(fn ($item) => is_array($item) ? $item : [])
            ->map(function (array $item): ?array {
                $date = $this->resolveDate($item);
                $name = $this->resolveName($item);

                if ($date === null || $name === '') {
                    return null;
                }

                $isObservance = $this->toBool($item['is_observance'] ?? null)
                    || str_contains(strtolower((string) ($item['type'] ?? '')), 'observ');
                $isHoliday = array_key_exists('is_holiday', $item)
                    ? $this->toBool($item['is_holiday'])
                    : ! $isObservance;

                if ($isObservance || ! $isHoliday) {
                    return null;
                }

                $isJointHoliday = $this->toBool($item['is_joint_holiday'] ?? null)
                    || str_contains(strtolower((string) ($item['type'] ?? '')), 'joint')
                    || str_contains(strtolower((string) ($item['type'] ?? '')), 'cuti');
                $typeLabel = $isJointHoliday ? 'Cuti Bersama' : null;

                return [
                    'tanggal' => $date,
                    'nama_libur' => $name,
                    'external_type' => $isJointHoliday ? 'joint_holiday' : 'public_holiday',
                    'sync_meta' => $item,
                    'description_label' => $typeLabel,
                    'external_id' => $this->resolveExternalId($item, $date),
                ];
            })
            ->filter()
            ->groupBy('tanggal')
            ->map(function (Collection $group, string $date) use ($year): array {
                $names = $group->pluck('nama_libur')->filter()->unique()->values()->all();
                $typeLabels = $group->pluck('description_label')->filter()->unique()->values()->all();
                $externalTypes = $group->pluck('external_type')->filter()->unique()->values()->all();
                $externalIds = $group->pluck('external_id')->filter()->unique()->values()->all();
                $rawItems = $group->pluck('sync_meta')->values()->all();
                $keteranganParts = array_merge(['Libur Nasional'], $typeLabels);

                return [
                    'tanggal' => $date,
                    'nama_libur' => implode(' / ', $names),
                    'keterangan' => implode(' · ', $keteranganParts).' · Tahun '.$year,
                    'aktif' => true,
                    'source' => HariLibur::SOURCE_API_CO_ID,
                    'external_id' => implode('|', $externalIds),
                    'external_type' => implode('|', $externalTypes),
                    'sync_meta' => [
                        'provider' => 'api.co.id',
                        'year' => $year,
                        'items' => $rawItems,
                    ],
                    'last_synced_at' => now(),
                ];
            })
            ->sortKeys()
            ->values()
            ->all();
    }

    private function resolveDate(array $item): ?string
    {
        foreach (['date', 'tanggal', 'holiday_date'] as $key) {
            $value = trim((string) ($item[$key] ?? ''));

            if ($value === '') {
                continue;
            }

            try {
                return Carbon::parse($value)->toDateString();
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function resolveName(array $item): string
    {
        foreach (['name', 'nama', 'holiday_name', 'title'] as $key) {
            $value = trim((string) ($item[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function resolveExternalId(array $item, string $date): string
    {
        foreach (['id', 'slug', 'code'] as $key) {
            $value = trim((string) ($item[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return 'api-co-id-'.$date;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y'], true);
    }
}
