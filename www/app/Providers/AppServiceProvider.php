<?php

namespace App\Providers;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
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
        // South African dates are day-month-year. Filament defaults to US
        // style, and native date inputs follow the browser's locale, so use
        // the non-native picker with an explicit format.
        DatePicker::configureUsing(fn (DatePicker $picker) => $picker->native(false)->displayFormat('d-m-Y'));
        Table::configureUsing(fn (Table $table) => $table
            ->defaultDateDisplayFormat('d-m-Y')
            ->defaultDateTimeDisplayFormat('d-m-Y H:i')
            ->defaultTimeDisplayFormat('H:i'));
        Schema::configureUsing(fn (Schema $schema) => $schema
            ->defaultDateDisplayFormat('d-m-Y')
            ->defaultDateTimeDisplayFormat('d-m-Y H:i')
            ->defaultTimeDisplayFormat('H:i'));

        RateLimiter::for('portal-api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
