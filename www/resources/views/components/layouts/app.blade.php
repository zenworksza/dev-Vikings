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
    <header class="border-b" style="border-color: color-mix(in srgb, var(--brand-rule) 25%, transparent)">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img src="{{ $brand->public_logo_path }}" alt="{{ $brand->site_name }}" class="h-8 w-auto">
            </a>

            <nav class="flex items-center gap-6 text-sm font-medium" style="color: var(--brand-ink-muted)">
                @auth
                    @if (auth()->user()->hasRole('platform_admin'))
                        <a href="{{ url('/admin') }}" class="hover:opacity-80">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hover:opacity-80">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:opacity-80">Log in</a>
                    <a href="{{ route('register') }}"
                        class="px-4 py-2 text-sm font-medium shadow-sm transition hover:opacity-90"
                        style="background-color: var(--brand-accent); color: var(--brand-ink); border-radius: var(--brand-radius)">
                        Apply as a franchisee
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t" style="border-color: color-mix(in srgb, var(--brand-rule) 25%, transparent); background-color: var(--brand-surface-alt)">
        <div class="mx-auto max-w-6xl px-4 py-8 text-sm sm:px-6" style="color: var(--brand-ink-muted)">
            &copy; {{ now()->year }} {{ $brand->site_name }}. All rights reserved.
        </div>
    </footer>
</body>
</html>
