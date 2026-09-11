<?php

use App\Http\Middleware\EnsureApplicationInstalled;
use App\Http\Middleware\RedirectIfInstalled;
use App\Http\Middleware\SetApplicationTimeZone;
use App\Support\DeviceApiResponder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'device' => \App\Http\Middleware\AuthenticateDevice::class,
            'install.guest' => RedirectIfInstalled::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'install',
            'install/database',
            'install/test-database',
            'install/website',
        ]);

        $middleware->web(prepend: [
            EnsureApplicationInstalled::class,
        ]);

        $middleware->web(append: [
            SetApplicationTimeZone::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            if (DeviceApiResponder::isDeviceRequest($request)) {
                return DeviceApiResponder::error($request, 'Unauthorized', 401, [
                    'reason' => 'unauthenticated',
                ]);
            }

            return null;
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            if (DeviceApiResponder::isDeviceRequest($request)) {
                return DeviceApiResponder::error($request, 'Unauthorized', 403, [
                    'reason' => 'forbidden',
                ]);
            }

            return null;
        });

        $exceptions->render(function (SpatieUnauthorizedException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            if (DeviceApiResponder::isDeviceRequest($request)) {
                return DeviceApiResponder::error($request, 'Unauthorized', 403, [
                    'reason' => 'forbidden',
                ]);
            }

            return null;
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            if (DeviceApiResponder::isDeviceRequest($request)) {
                return DeviceApiResponder::error($request, 'Validation error', $exception->status, [
                    'reason' => 'validation_error',
                    'errors' => $exception->errors(),
                ]);
            }

            return null;
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($request->expectsJson() && DeviceApiResponder::isDeviceRequest($request)) {
                $statusCode = $exception->getStatusCode();
                $message = $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : (SymfonyResponse::$statusTexts[$statusCode] ?? 'HTTP error.');

                return DeviceApiResponder::error($request, $message, $statusCode, null, $exception->getHeaders());
            }

            if ($exception->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi halaman telah berakhir.',
                ], 419);
            }

            return back()
                ->withInput($request->except('password'))
                ->with('error', 'Sesi halaman telah berakhir. Silakan coba login lagi.');
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->expectsJson() || ! DeviceApiResponder::isDeviceRequest($request)) {
                return null;
            }

            return DeviceApiResponder::error($request, 'Internal server error', 500);
        });
    })->create();
