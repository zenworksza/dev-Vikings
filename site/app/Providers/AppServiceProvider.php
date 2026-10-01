<?php

namespace App\Providers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
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
        // Short timeouts: a slow portal should fall back to the cached copy,
        // not hang a public page.
        Http::macro('portal', fn (): PendingRequest => Http::baseUrl(config('site.portal_api_url').'/api/v1/')
            ->withToken((string) config('site.portal_api_token'))
            ->acceptJson()
            ->connectTimeout(2)
            ->timeout(5));
    }
}
