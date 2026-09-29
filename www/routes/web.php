<?php

use Illuminate\Support\Facades\Route;

// Laravel only serves the franchise portal (franchise.powerbear.co.za);
// the public marketing site is a separate static PHP site in www/site/,
// served by nginx directly. So `/` has no page of its own — straight to login.
Route::redirect('/', '/login');

require __DIR__.'/auth.php';
