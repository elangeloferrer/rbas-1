<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
| These routes do not require authentication.
*/

// Customer auth
// POST /api/login
// POST /api/register
// POST /api/forgot-password
// POST /api/reset-password

// Merchant auth
Route::prefix('merchant')->group(function () {
    // POST /api/merchant/login
    // POST /api/merchant/register
    // POST /api/merchant/forgot-password
    // POST /api/merchant/reset-password
});

// Admin auth
Route::prefix('admin')->group(function () {
    // POST /api/admin/login
});

/*
|--------------------------------------------------------------------------
| Protected routes — require Sanctum authentication
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // Customer protected routes
    // GET /api/dashboard etc. (added later)

    // Merchant protected routes
    Route::prefix('merchant')->middleware('role:merchant')->group(function () {
        // POST /api/merchant/email/verify
        // POST /api/merchant/logout
    });

    // Admin protected routes
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        // GET /api/admin/merchants
        // GET /api/admin/customers
    });

    // Shared logout (works for all roles)
    Route::post('logout', [\App\Http\Controllers\Auth\LogoutController::class, 'destroy']);
});
