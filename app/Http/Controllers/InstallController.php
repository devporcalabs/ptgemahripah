<?php

namespace App\Http\Controllers;

use App\Services\InstallerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class InstallController extends Controller
{
    public function index(Request $request, InstallerService $installerService): View
    {
        $request->session()->put('_installer_booted', now()->timestamp);

        return view('install.index', [
            'step' => (int) old('wizard_step', session('installer_step', 1)),
            'completed' => false,
            'requirements' => $installerService->requirements(),
            'allRequirementsPassed' => $installerService->requirementsPass(),
            'timezones' => $installerService->supportedTimezones(),
            'values' => [
                'db_host' => old('db_host', '127.0.0.1'),
                'db_port' => old('db_port', '3306'),
                'db_database' => old('db_database', ''),
                'db_username' => old('db_username', ''),
                'db_password' => old('db_password', ''),
                'app_name' => old('app_name', config('app.name', 'Absensi Karyawan')),
                'app_timezone' => old('app_timezone', config('app.timezone', 'Asia/Jakarta')),
                'admin_name' => old('admin_name', 'Administrator'),
                'admin_username' => old('admin_username', 'admin'),
                'admin_email' => old('admin_email', ''),
            ],
        ]);
    }

    public function testDatabase(Request $request, InstallerService $installerService): JsonResponse
    {
        if (! $installerService->requirementsPass()) {
            return response()->json([
                'ok' => false,
                'message' => 'Persyaratan server belum terpenuhi.',
            ], 422);
        }

        $validator = Validator::make(
            $request->all(),
            $this->databaseRules(),
            [],
            $this->validationAttributes(),
        );

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $result = $installerService->testDatabase([
            'db_host' => trim((string) $validated['db_host']),
            'db_port' => (string) $validated['db_port'],
            'db_database' => trim((string) $validated['db_database']),
            'db_username' => trim((string) $validated['db_username']),
            'db_password' => (string) ($validated['db_password'] ?? ''),
        ]);

        return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    public function install(Request $request, InstallerService $installerService): View|RedirectResponse
    {
        if (! $installerService->requirementsPass()) {
            return redirect()
                ->route('install.requirements')
                ->with('installer_step', 1)
                ->withErrors([
                    'requirements' => 'Persyaratan server belum terpenuhi.',
                ]);
        }

        $validated = $request->validate(
            [
                ...$this->databaseRules(),
                'app_name' => ['required', 'string', 'max:100'],
                'app_timezone' => ['required', 'timezone:all'],
                'admin_name' => ['required', 'string', 'max:100'],
                'admin_username' => ['required', 'string', 'max:50'],
                'admin_email' => ['nullable', 'email', 'max:100'],
                'admin_password' => ['required', 'string', 'min:6', 'confirmed'],
            ],
            [
                'admin_password.confirmed' => 'Konfirmasi password admin tidak sama.',
            ],
            $this->validationAttributes(),
        );

        $payload = [
            'db_host' => trim((string) $validated['db_host']),
            'db_port' => (string) $validated['db_port'],
            'db_database' => trim((string) $validated['db_database']),
            'db_username' => trim((string) $validated['db_username']),
            'db_password' => (string) ($validated['db_password'] ?? ''),
            'app_name' => trim((string) $validated['app_name']),
            'app_timezone' => (string) $validated['app_timezone'],
            'admin_name' => trim((string) $validated['admin_name']),
            'admin_username' => trim((string) $validated['admin_username']),
            'admin_email' => trim((string) ($validated['admin_email'] ?? '')),
            'admin_password' => (string) $validated['admin_password'],
            'app_url' => rtrim($request->root(), '/'),
            'seed_dummy_karyawan' => false,
        ];

        try {
            $installerService->install($payload);
        } catch (Throwable $throwable) {
            report($throwable);

            return view('install.index', [
                'step' => 4,
                'completed' => false,
                'requirements' => $installerService->requirements(),
                'allRequirementsPassed' => $installerService->requirementsPass(),
                'timezones' => $installerService->supportedTimezones(),
                'installError' => 'Instalasi gagal: '.$throwable->getMessage(),
                'values' => [
                    'db_host' => $payload['db_host'],
                    'db_port' => $payload['db_port'],
                    'db_database' => $payload['db_database'],
                    'db_username' => $payload['db_username'],
                    'db_password' => $payload['db_password'],
                    'app_name' => $payload['app_name'],
                    'app_timezone' => $payload['app_timezone'],
                    'admin_name' => $payload['admin_name'],
                    'admin_username' => $payload['admin_username'],
                    'admin_email' => $payload['admin_email'],
                ],
            ]);
        }

        return view('install.index', [
            'step' => 4,
            'completed' => true,
            'requirements' => $installerService->requirements(),
            'allRequirementsPassed' => true,
            'timezones' => $installerService->supportedTimezones(),
            'values' => [],
            'websiteName' => $payload['app_name'],
            'accounts' => [[
                'role' => 'Super Admin',
                'username' => $payload['admin_username'],
                'password' => $payload['admin_password'],
                'name' => $payload['admin_name'],
            ]],
            'loginUrl' => route('login'),
        ]);
    }

    private function databaseRules(): array
    {
        return [
            'db_host' => ['required', 'string', 'max:100'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:100'],
            'db_username' => ['required', 'string', 'max:100'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function validationAttributes(): array
    {
        return [
            'app_name' => 'nama aplikasi',
            'app_timezone' => 'zona waktu',
            'db_host' => 'host database',
            'db_port' => 'port database',
            'db_database' => 'nama database',
            'db_username' => 'username database',
            'db_password' => 'password database',
            'admin_name' => 'nama admin',
            'admin_username' => 'username admin',
            'admin_email' => 'email admin',
            'admin_password' => 'password admin',
        ];
    }
}
