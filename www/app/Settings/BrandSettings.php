<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class BrandSettings extends Settings
{
    public string $site_name;

    // Public marketing site + auth screens — "Ember" concept
    // (Cinzel headings / Inter body, dark navy ground, gold wordmark).
    public ?string $public_logo_path;

    public string $public_color_surface;

    public string $public_color_surface_alt;

    public string $public_color_ink;

    public string $public_color_ink_muted;

    public string $public_color_accent;

    public string $public_color_rule;

    public string $public_radius;

    public string $public_heading_font;

    public string $public_body_font;

    // Filament dashboards (/admin platform-admin + /portal franchisee) —
    // "Hearthwood" concept (Bitter headings / Nunito Sans body, warm bone
    // ground, black wordmark). Deliberately distinct from the public brand:
    // these are working back-office tools, not the marketing face.
    public ?string $dashboard_logo_path;

    public string $dashboard_color_surface;

    public string $dashboard_color_surface_alt;

    public string $dashboard_color_ink;

    public string $dashboard_color_ink_muted;

    public string $dashboard_color_accent;

    public string $dashboard_color_rule;

    public string $dashboard_radius;

    public string $dashboard_heading_font;

    public string $dashboard_body_font;

    // Bare family name (no fallback stack/quotes) for Filament's ->font(),
    // which builds a Bunny Fonts request from it — keep in sync with
    // dashboard_body_font above.
    public string $dashboard_body_font_family;

    public static function group(): string
    {
        return 'brand';
    }
}
