<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/email/verify/{id}/{hash}', function (Request $request) {
    // Signature already validated by the 'signed' middleware above.
    // Redirect the browser to the Vue frontend — it will POST the params to the API.
    $query        = $request->getQueryString(); // preserves expires & signature
    $frontendBase = rtrim(config('app.frontend_url'), '/');

    return redirect(
        "{$frontendBase}/email/verify/{$request->id}/{$request->hash}"
            . ($query ? "?{$query}" : '')
    );
})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
