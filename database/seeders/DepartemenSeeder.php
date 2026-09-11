<?php

namespace Database\Seeders;

use App\Models\Departemen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartemenSeeder extends Seeder
{
    private const DEFAULT_ITEMS = [
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

    public function run(): void
    {
        foreach ($this->resolveItems() as $namaDepartemen) {
            Departemen::query()->firstOrCreate([
                'nama_departemen' => $namaDepartemen,
            ]);
        }
    }

    private function resolveItems(): array
    {
        $items = self::DEFAULT_ITEMS;

        if (DB::getSchemaBuilder()->hasTable('karyawan')) {
            $existingItems = DB::table('karyawan')
                ->whereNotNull('departemen')
                ->where('departemen', '!=', '')
                ->distinct()
                ->orderBy('departemen')
                ->pluck('departemen')
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->all();

            $items = [...$items, ...$existingItems];
        }

        $uniqueItems = [];

        foreach ($items as $item) {
            $label = trim((string) $item);

            if ($label === '') {
                continue;
            }

            $uniqueItems[mb_strtolower($label)] = $label;
        }

        return array_values($uniqueItems);
    }
}
