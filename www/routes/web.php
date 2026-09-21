<?php

use App\Support\SiteTheme;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // The franchise portal subdomain has no marketing homepage of its own —
    // send it straight to login. See App\Support\SiteTheme.
    if (SiteTheme::isPortal()) {
        return redirect()->route('login');
    }

    return view('welcome');
});

require __DIR__.'/auth.php';
