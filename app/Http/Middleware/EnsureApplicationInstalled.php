<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.installed')) {
            return $next($request);
        }

        if ($request->routeIs('install.*') || $request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        return redirect()->route('install.requirements');
    }
}
