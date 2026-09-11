<?php

use App\Http\Controllers\Api\DeviceController;
use Illuminate\Support\Facades\Route;

Route::post('/device/activate', [DeviceController::class, 'activate']);

Route::prefix('device')
    ->middleware('device')
    ->group(function (): void {
        Route::post('/cek', [DeviceController::class, 'cek']);
        Route::post('/attendance', [DeviceController::class, 'attendance']);
        Route::post('/meal', [DeviceController::class, 'mealAttendance']);
        Route::post('/heartbeat', [DeviceController::class, 'heartbeat']);
    });
