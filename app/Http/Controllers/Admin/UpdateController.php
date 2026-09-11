<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AppUpdaterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UpdateController extends Controller
{
    public function index(): View
    {
        $updatesRoot = storage_path('app/updates');
        $lastUpdate = null;
        $lastUpdatePath = $updatesRoot . DIRECTORY_SEPARATOR . 'last_update.json';

        if (is_file($lastUpdatePath)) {
            try {
                $payload = json_decode((string) file_get_contents($lastUpdatePath), true);
                if (is_array($payload)) {
                    $lastUpdate = $payload;
                }
            } catch (\Throwable $e) {
                $lastUpdate = null;
            }
        }

        return view('admin.update-aplikasi', [
            'currentVersion' => (string) config('app.version', ''),
            'manifestUrl' => (string) config('updater.manifest_url', ''),
            'allowedHosts' => (array) config('updater.allowed_hosts', []),
            'publicKeyConfigured' => trim((string) config('updater.public_key', '')) !== '',
            'licenseConfigured' => trim((string) config('updater.license_key', '')) !== '',
            'lastUpdate' => $lastUpdate,
        ]);
    }

    public function progress(): JsonResponse
    {
        $progressPath = storage_path('app/updates/progress.json');

        if (! is_file($progressPath)) {
            return response()->json([
                'status' => 'idle',
            ]);
        }

        try {
            $payload = json_decode((string) file_get_contents($progressPath), true);
            if (is_array($payload)) {
                return response()->json($payload);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return response()->json([
            'status' => 'idle',
        ]);
    }

    public function check(AppUpdaterService $updater): JsonResponse
    {
        return response()->json($updater->check());
    }

    public function install(Request $request, AppUpdaterService $updater): JsonResponse
    {
        try {
            if ($request->hasSession()) {
                $request->session()->save();
            }

            if (function_exists('session_write_close')) {
                @session_write_close();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return response()->json($updater->installLatest());
    }
}
