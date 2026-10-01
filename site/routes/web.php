<?php

use App\Http\Controllers\LocationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
Route::get('/locations/{slug}', [LocationController::class, 'show'])->name('locations.show');
