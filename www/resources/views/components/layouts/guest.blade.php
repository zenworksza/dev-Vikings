@php
    // guest.blade.php only ever serves the franchise portal (login,
    // register, forgot/reset password) — always the Hearthwood dashboard
    // theme, regardless of which host it's reached on. See Plan.md's brand
    // palette decision and App\Support\SiteTheme.
    $brand = app(\App\Settings\BrandSettings::class);
    $prefix = 'dashboard';
    $homeUrl = \App\Support\SiteTheme::otherSiteUrl('/');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $brand->site_name }}</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bitter:wght@500;600;700&family=Nunito+Sans:wght@400;600;700;800&display=swap">

    <style>
        :root {
            --brand-surface: {{ $brand->{$prefix.'_color_surface'} }};
            --brand-surface-alt: {{ $brand->{$prefix.'_color_surface_alt'} }};
            --brand-ink: {{ $brand->{$prefix.'_color_ink'} }};
            --brand-ink-muted: {{ $brand->{$prefix.'_color_ink_muted'} }};
            --brand-accent: {{ $brand->{$prefix.'_color_accent'} }};
            --brand-rule: {{ $brand->{$prefix.'_color_rule'} }};
            --brand-radius: {{ $brand->{$prefix.'_radius'} }};
            --brand-heading-font: {{ $brand->{$prefix.'_heading_font'} }};
            --brand-body-font: {{ $brand->{$prefix.'_body_font'} }};
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased" style="background-color: var(--brand-surface); color: var(--brand-ink); font-family: var(--brand-body-font)">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <a href="{{ $homeUrl }}" class="mb-8 flex items-center gap-3">
            <img src="{{ $brand->{$prefix.'_logo_path'} }}" alt="{{ $brand->site_name }}" class="h-10 w-auto">
        </a>

        <div class="w-full max-w-md border p-8 shadow-sm"
            style="background-color: var(--brand-surface-alt); border-color: color-mix(in srgb, var(--brand-rule) 30%, transparent); border-radius: var(--brand-radius)">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
