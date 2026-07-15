<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Auth\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Auth\Customer\EmailVerificationController as CustomerEmailVerificationController;
// Admin Controllers
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\Merchant\AuthController as MerchantAuthController;
// Merchant Controllers
use App\Http\Controllers\Auth\Merchant\EmailVerificationController as MerchantEmailVerificationController;
use App\Http\Controllers\Auth\Merchant\PasswordResetController;
use App\Http\Controllers\Auth\Mobile\Customer\AuthController as MobileCustomerAuthController;
// Customer Controllers
use App\Http\Controllers\Auth\Mobile\LogoutController as MobileLogoutController;
use App\Http\Controllers\Auth\Mobile\Merchant\AuthController as MobileMerchantAuthController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
//  Mobile Controllers
use App\Http\Controllers\Merchant\DashboardController as MerchantDashboardController;
// Mobile - Merchant Controllers
use Illuminate\Http\Request;
// Mobile - Customer Controllers
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes — rate limited to 5 attempts per minute
|--------------------------------------------------------------------------
*/
// Customer
Route::middleware('throttle:5,1')
    ->group(function () {
        Route::post('register', [CustomerAuthController::class, 'register']);
        Route::post('login', [CustomerAuthController::class, 'login']);
    });

// Route::prefix('customer')
//     ->middleware('throttle:5,1')
//     ->group(function () {
//         Route::post('email/verify', [CustomerEmailVerificationController::class, 'verify']);
//     });

// Merchant
Route::prefix('merchant')
    ->middleware('throttle:5,1')
    ->group(function () {
        Route::post('register', [MerchantAuthController::class, 'register']);
        Route::post('login', [MerchantAuthController::class, 'login']);
        Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword']);
        Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);
        Route::post('email/verify', [MerchantEmailVerificationController::class, 'verify']);
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

    // Authenticated user — works for all roles, used by the SPA on every page load
    Route::get('user', function (Request $request) {
        $user = $request->user()->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'User retrieved.',
            'data' => [
                'first_name' => $user->first_name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'is_email_verified' => $user->email_verified_at ? true : false,
            ],
        ]);
    });

    // Shared logout — works for all roles
    Route::post('logout', [LogoutController::class, 'destroy']);

    // Customer protected routes
    Route::prefix('customer')
        ->middleware('role:customer')
        ->group(function () {
            Route::get('dashboard', [CustomerDashboardController::class, 'index']);
            // Route::post('email/resend', [CustomerEmailVerificationController::class, 'resend'])
            //     ->middleware('throttle:3,1440');
        });

    // Merchant protected routes
    Route::prefix('merchant')
        ->middleware('role:merchant')
        ->group(function () {
            Route::get('dashboard', [MerchantDashboardController::class, 'index']);
            Route::post('email/resend', [MerchantEmailVerificationController::class, 'resend'])
                ->middleware('throttle:3,1440');
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
        Route::post('login', [MobileCustomerAuthController::class, 'login']);
    });

    // Merchant
    Route::prefix('merchant')
        ->middleware('throttle:5,1')
        ->group(function () {
            Route::post('register', [MobileMerchantAuthController::class, 'register']);
            Route::post('login', [MobileMerchantAuthController::class, 'login']);
        });

    // Protected — auth via Bearer token
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('logout', [MobileLogoutController::class, 'destroy']);
    });
});
