<?php

use App\Http\Controllers\DashcamApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashcam app endpoints (mounted under /api)
|--------------------------------------------------------------------------
| Called by the dashcam Android app. Authentication is the device code itself
| (X-Dashcam-Code header or "code" in the JSON body), so no session or CSRF.
*/

Route::prefix('dashcam')->group(function () {
    Route::post('/register', [DashcamApiController::class, 'register'])->name('api.dashcam.register');
    Route::post('/location', [DashcamApiController::class, 'location'])->name('api.dashcam.location');
    Route::post('/locations', [DashcamApiController::class, 'locations'])->name('api.dashcam.locations');
});
