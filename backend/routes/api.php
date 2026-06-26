<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Auth\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Auth\Merchant\AuthController as MerchantAuthController;
use App\Http\Controllers\Auth\LogoutController;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Merchant\DashboardController as MerchantDashboardController;

use App\Http\Controllers\Auth\Mobile\Customer\AuthController as MobileCustomerAuthController;
use App\Http\Controllers\Auth\Mobile\Merchant\AuthController as MobileMerchantAuthController;
use App\Http\Controllers\Auth\Mobile\LogoutController as MobileLogoutController;

/*
|--------------------------------------------------------------------------
| Public routes — rate limited to 5 attempts per minute
|--------------------------------------------------------------------------
*/

// Customer
Route::middleware('throttle:5,1')
    ->group(function () {
        Route::post('register', [CustomerAuthController::class, 'register']);
        Route::post('login',    [CustomerAuthController::class, 'login']);
    });

// Merchant
Route::prefix('merchant')
    ->middleware('throttle:5,1')
    ->group(function () {
        Route::post('register', [MerchantAuthController::class, 'register']);
        Route::post('login',    [MerchantAuthController::class, 'login']);
        // Password reset routes added in Part 9
        // Email verification routes added in Part 10
    });

// Admin
Route::prefix('admin')
    ->middleware('throttle:3,1')
    ->group(function () {
        Route::post('login', [AdminAuthController::class, 'login']);
    });

/*
|--------------------------------------------------------------------------
| Protected routes — require Sanctum authentication
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // Shared logout — works for all roles
    Route::post('logout', [LogoutController::class, 'destroy']);

    // Customer protected routes
    Route::prefix('customer')
        ->middleware('role:customer')
        ->group(function () {
            Route::get('dashboard', [CustomerDashboardController::class, 'index']);
        });

    // Merchant protected routes
    Route::prefix('merchant')
        ->middleware('role:merchant')
        ->group(function () {
            Route::get('dashboard', [MerchantDashboardController::class, 'index']);
        });

    // Admin protected routes
    Route::prefix('admin')
        ->middleware('role:admin')
        ->group(function () {
            Route::get('dashboard', [AdminDashboardController::class, 'index']);
        });
});

/*
|--------------------------------------------------------------------------
| Mobile routes — token-based auth (no CSRF, no session)
|--------------------------------------------------------------------------
*/

Route::prefix('mobile')->group(function () {

    // Customer
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('register', [MobileCustomerAuthController::class, 'register']);
        Route::post('login',    [MobileCustomerAuthController::class, 'login']);
    });

    // Merchant
    Route::prefix('merchant')
        ->middleware('throttle:5,1')
        ->group(function () {
            Route::post('register', [MobileMerchantAuthController::class, 'register']);
            Route::post('login',    [MobileMerchantAuthController::class, 'login']);
        });

    // Protected — auth via Bearer token
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('logout', [MobileLogoutController::class, 'destroy']);
    });
});
