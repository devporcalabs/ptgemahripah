<?php

namespace Database\Seeders;

use App\Services\EmployeeUserAccountService;
use Illuminate\Database\Seeder;

class EmployeeUserSeeder extends Seeder
{
    public function run(EmployeeUserAccountService $accountService): void
    {
        $accountService->syncAll();
    }
}
