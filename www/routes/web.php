<?php

use App\Http\Controllers\PageController;
use App\Models\Page;
use App\Support\SiteTheme;
use Illuminate\Support\Facades\Route;

Route::get('/', function (PageController $pages) {
    // The franchise portal subdomain has no marketing homepage of its own —
    // send it straight to login. See App\Support\SiteTheme.
    if (SiteTheme::isPortal()) {
        return redirect()->route('login');
    }

    // The homepage is just a published CMS page with slug 'home' (see
    // Plan.md's Phase 2 / database/seeders/PageSeeder) — fall back to the
    // static welcome view if it hasn't been seeded/published yet, so
    // draft-editing never breaks the live homepage.
    $home = Page::published()->where('slug', 'home')->first();

    return $home ? $pages($home) : view('welcome');
});

require __DIR__.'/auth.php';

// Catch-all for CMS-driven pages — kept last so it never shadows a named
// route above (e.g. /login) that also happens to match a single path
// segment. Future structured routes (/locations, /book/{location}, etc.,
// Phase 4/5) go before this, same reason.
Route::get('/{page:slug}', PageController::class)->name('page.show');
