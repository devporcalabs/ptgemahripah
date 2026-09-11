<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            if ($request->user()?->hasAdminPanelAccess()) {
                return redirect()->route('admin.dashboard');
            }

            if ($request->user()?->hasEmployeePanelAccess()) {
                return redirect()->route('karyawan.dashboard');
            }

            $this->logoutCurrentUser($request);
        }

        return view('auth.login', [
            'settings' => $this->resolveSettings(),
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if ($validator->fails()) {
            if ($this->expectsJsonResponse($request)) {
                return response()->json([
                    'ok' => false,
                    'level' => 'error',
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                    'csrf_token' => csrf_token(),
                ], 422);
            }

            return back()
                ->withErrors($validator)
                ->onlyInput('username');
        }

        $credentials = $validator->validated();

        if (! Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ])) {
            return $this->respondLoginError(
                $request,
                'Username atau password salah.',
                ['username' => ['Username atau password salah.']],
            );
        }

        $request->session()->regenerate();

        $redirectUrl = null;
        $user = $request->user();

        if ($user?->hasAdminPanelAccess()) {
            $redirectUrl = redirect()->intended(route('admin.dashboard'))->getTargetUrl();
        } elseif ($user?->hasEmployeePanelAccess()) {
            $redirectUrl = redirect()->intended(route('karyawan.dashboard'))->getTargetUrl();
        } else {
            $this->logoutCurrentUser($request);

            return $this->respondLoginError(
                $request,
                'Akun ini tidak aktif atau belum memiliki akses login.',
                ['username' => ['Akun ini tidak aktif atau belum memiliki akses login.']],
            );
        }

        if ($this->expectsJsonResponse($request)) {
            return response()->json([
                'ok' => true,
                'level' => 'success',
                'message' => 'Login berhasil.',
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl);
    }

    public function logout(Request $request)
    {
        $this->logoutCurrentUser($request);

        return redirect()->route('login');
    }

    private function resolveSettings(): ?Setting
    {
        try {
            return Setting::query()->find(1);
        } catch (Throwable) {
            return null;
        }
    }

    private function logoutCurrentUser(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function respondLoginError(Request $request, string $message, array $errors)
    {
        if ($this->expectsJsonResponse($request)) {
            return response()->json([
                'ok' => false,
                'level' => 'error',
                'message' => $message,
                'errors' => $errors,
                'csrf_token' => csrf_token(),
            ], 422);
        }

        return back()
            ->withErrors($errors)
            ->onlyInput('username');
    }
}
