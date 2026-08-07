@php
    $brand = app(\App\Settings\BrandSettings::class);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $brand->site_name }}</title>

    <style>
        :root {
            --brand-primary: {{ $brand->color_primary }};
            --brand-secondary: {{ $brand->color_secondary }};
            --brand-accent: {{ $brand->color_accent }};
            --brand-font: {{ $brand->font_family }};
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased" style="font-family: var(--brand-font)">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <a href="{{ url('/') }}" class="mb-8 text-xl font-semibold" style="color: var(--brand-primary)">
            {{ $brand->site_name }}
        </a>

        <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
