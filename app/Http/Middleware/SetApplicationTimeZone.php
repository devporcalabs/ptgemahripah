<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SetApplicationTimeZone
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $zonaWaktu = Setting::query()->whereKey(1)->value('zona_waktu');
            $zonaValid = ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'];

            if (in_array($zonaWaktu, $zonaValid, true)) {
                Config::set('app.timezone', $zonaWaktu);
                date_default_timezone_set($zonaWaktu);
            }
        } catch (Throwable) {
        }

        return $next($request);
    }
}
