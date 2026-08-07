<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class BrandSettings extends Settings
{
    public string $site_name;

    public ?string $logo_path;

    public string $color_primary;

    public string $color_secondary;

    public string $color_accent;

    public string $font_family;

    public static function group(): string
    {
        return 'brand';
    }
}
