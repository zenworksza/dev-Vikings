@php
    $brand = app(\App\Settings\BrandSettings::class);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $brand->site_name }}</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Inter:wght@400;500;600&display=swap">

    <style>
        :root {
            --brand-surface: {{ $brand->public_color_surface }};
            --brand-surface-alt: {{ $brand->public_color_surface_alt }};
            --brand-ink: {{ $brand->public_color_ink }};
            --brand-ink-muted: {{ $brand->public_color_ink_muted }};
            --brand-accent: {{ $brand->public_color_accent }};
            --brand-rule: {{ $brand->public_color_rule }};
            --brand-radius: {{ $brand->public_radius }};
            --brand-heading-font: {{ $brand->public_heading_font }};
            --brand-body-font: {{ $brand->public_body_font }};
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased" style="background-color: var(--brand-surface); color: var(--brand-ink); font-family: var(--brand-body-font)">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3">
            <img src="{{ $brand->public_logo_path }}" alt="{{ $brand->site_name }}" class="h-10 w-auto">
        </a>

        <div class="w-full max-w-md border p-8 shadow-sm"
            style="background-color: var(--brand-surface-alt); border-color: color-mix(in srgb, var(--brand-rule) 30%, transparent); border-radius: var(--brand-radius)">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
