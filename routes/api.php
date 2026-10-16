<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'time' => now()->toIso8601String(),
    ]));

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');

        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/me', [AuthController::class, 'me'])->name('me');
        });
    });
});
