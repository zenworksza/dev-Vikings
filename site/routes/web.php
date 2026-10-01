<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\StaffBookingController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
Route::get('/locations/{slug}', [LocationController::class, 'show'])->name('locations.show');

Route::get('/locations/{slug}/book', [BookingController::class, 'create'])->name('bookings.create');
Route::post('/locations/{slug}/book', [BookingController::class, 'store'])->middleware('throttle:6,1')->name('bookings.store');

// Customer's own booking, reached by its unguessable token (from the email).
Route::get('/bookings/{booking:token}', [BookingController::class, 'show'])->name('bookings.show');
Route::post('/bookings/{booking:token}/cancel', [BookingController::class, 'cancel'])->middleware('throttle:6,1')->name('bookings.cancel');

// Restaurant staff, from signed links in the notification email.
Route::middleware('signed')->prefix('staff/bookings/{booking}')->name('staff.bookings.')->group(function () {
    Route::get('/', [StaffBookingController::class, 'show'])->name('show');
    Route::post('confirm', [StaffBookingController::class, 'confirm'])->name('confirm');
    Route::post('decline', [StaffBookingController::class, 'decline'])->name('decline');
});
