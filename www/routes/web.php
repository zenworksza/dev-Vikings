<?php

use App\Http\Controllers\ApplicationDocumentController;
use Illuminate\Support\Facades\Route;

// Laravel only serves the franchise portal (franchise.powerbear.co.za);
// the public marketing site is a separate app (../site) with its own
// database. So `/` has no page of its own.
Route::get('/', fn () => redirect(auth()->check() ? '/dashboard' : '/login'));

// Post-login landing: admins go to the back office, everyone else to /portal.
Route::get('/dashboard', fn () => redirect(auth()->user()->hasRole('platform_admin') ? '/admin' : '/portal'))
    ->middleware('auth')
    ->name('dashboard');

Route::get('/admin/application-documents/{document}', ApplicationDocumentController::class)
    ->middleware('auth')
    ->name('admin.application-documents.download');

require __DIR__.'/auth.php';
