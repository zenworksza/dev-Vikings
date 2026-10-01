<?php

use App\Http\Controllers\Api\LocationController;
use App\Http\Middleware\AuthenticatePortalApi;
use Illuminate\Support\Facades\Route;

// Called by the public site (../site), never by browsers. Shared bearer token.
Route::prefix('v1')->middleware([AuthenticatePortalApi::class, 'throttle:portal-api'])->group(function () {
    Route::get('locations', [LocationController::class, 'index']);
    Route::get('locations/{slug}', [LocationController::class, 'show']);
});
