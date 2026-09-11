<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

abstract class Controller
{
    protected function expectsJsonResponse(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    protected function respondSuccess(
        Request $request,
        string $redirectUrl,
        string $message,
        array $payload = [],
        int $status = 200,
    ): JsonResponse|RedirectResponse {
        $level = $payload['level'] ?? 'success';
        unset($payload['level']);

        if ($this->expectsJsonResponse($request)) {
            return response()->json([
                'ok' => true,
                'level' => $level,
                'message' => $message,
                ...$payload,
            ], $status);
        }

        return redirect()->to($redirectUrl)->with($level, $message);
    }

    protected function respondError(
        Request $request,
        string $redirectUrl,
        string $message,
        array $payload = [],
        int $status = 422,
    ): JsonResponse|RedirectResponse {
        $level = $payload['level'] ?? 'error';
        unset($payload['level']);

        if ($this->expectsJsonResponse($request)) {
            return response()->json([
                'ok' => false,
                'level' => $level,
                'message' => $message,
                ...$payload,
            ], $status);
        }

        return redirect()->to($redirectUrl)->with($level, $message);
    }

    protected function paginateCollection(
        iterable $items,
        Request $request,
        int $perPage = 10,
        string $pageName = 'page',
    ): LengthAwarePaginator {
        $collection = $items instanceof Collection ? $items->values() : collect($items)->values();
        $currentPage = max(1, (int) $request->query($pageName, 1));
        $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $perPage,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
                'query' => $request->query(),
            ],
        );
    }

    protected function resolvePerPage(
        Request $request,
        int $default = 10,
        array $allowed = [10, 25, 50, 100],
    ): int {
        $perPage = (int) $request->query('per_page', $default);

        return in_array($perPage, $allowed, true) ? $perPage : $default;
    }

    protected function resolveSearch(Request $request, string $key = 'q'): string
    {
        return trim($request->string($key)->toString());
    }
}
