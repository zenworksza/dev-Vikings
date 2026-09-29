<?php

namespace App\Providers\Filament;

use App\Filament\Portal\Pages\FranchiseApplication;
use App\Settings\BrandSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The investor / franchisee panel at /portal. Any signed-in user can open it:
 * before approval it holds the application wizard; franchisee tools (locations,
 * bookings) arrive in later phases. Shares the Fortify session with /admin.
 */
class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('portal')
            ->path('portal')
            ->authGuard('web')
            ->topNavigation()
            ->colors(fn () => [
                'primary' => Color::hex(app(BrandSettings::class)->dashboard_color_accent),
            ])
            ->font(fn () => app(BrandSettings::class)->dashboard_body_font_family)
            ->brandName(fn () => app(BrandSettings::class)->site_name)
            ->brandLogo(fn () => app(BrandSettings::class)->dashboard_logo_path)
            ->brandLogoHeight('2rem')
            ->discoverResources(in: app_path('Filament/Portal/Resources'), for: 'App\\Filament\\Portal\\Resources')
            ->pages([
                FranchiseApplication::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
