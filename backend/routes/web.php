<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Named route used only for generating signed verification URLs.
// The Vue frontend handles the actual page — this route never renders a view.
Route::get('/email/verify/{id}/{hash}', fn() => null)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
