<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only the public site calls the API, but a leaked token should not be
        // free to hammer the portal.
        RateLimiter::for('portal-api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
