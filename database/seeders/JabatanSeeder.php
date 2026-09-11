<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    private const DEFAULT_ITEMS = [
        'Direktur',
        'Manager',
        'Supervisor',
        'Koordinator',
        'Administrator',
        'HRD',
        'Staff IT',
        'Staff Keuangan',
        'Staff Admin',
        'Operator',
    ];

    public function run(): void
    {
        foreach ($this->resolveItems() as $namaJabatan) {
            Jabatan::query()->firstOrCreate([
                'nama_jabatan' => $namaJabatan,
            ]);
        }
    }

    private function resolveItems(): array
    {
        $items = self::DEFAULT_ITEMS;

        if (DB::getSchemaBuilder()->hasTable('karyawan')) {
            $existingItems = DB::table('karyawan')
                ->whereNotNull('jabatan')
                ->where('jabatan', '!=', '')
                ->distinct()
                ->orderBy('jabatan')
                ->pluck('jabatan')
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
