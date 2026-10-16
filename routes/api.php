<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PurchaseController;
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

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me/account', [AccountController::class, 'show'])->name('account.show');
        Route::get('/me/transactions', [AccountController::class, 'transactions'])->name('account.transactions');

        Route::middleware('role:staff,admin')->group(function () {
            Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
            Route::post('/purchases/{purchase}/refund', [PurchaseController::class, 'refund'])->name('purchases.refund');
        });

        Route::middleware('role:admin')->group(function () {
            Route::post('/accounts/{account}/adjust', [AccountController::class, 'adjust'])->name('accounts.adjust');
        });
    });
});
