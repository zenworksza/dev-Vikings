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
<body class="min-h-screen bg-white text-slate-900 antialiased" style="font-family: var(--brand-font)">
    <header class="border-b border-slate-200">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ url('/') }}" class="text-lg font-semibold" style="color: var(--brand-primary)">
                {{ $brand->site_name }}
            </a>

            <nav class="flex items-center gap-6 text-sm font-medium text-slate-600">
                @auth
                    @if (auth()->user()->hasRole('platform_admin'))
                        <a href="{{ url('/admin') }}" class="hover:text-slate-900">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hover:text-slate-900">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-slate-900">Log in</a>
                    <a href="{{ route('register') }}"
                        class="rounded-md px-4 py-2 text-white shadow-sm transition hover:opacity-90"
                        style="background-color: var(--brand-primary)">
                        Apply as a franchisee
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200">
        <div class="mx-auto max-w-6xl px-4 py-8 text-sm text-slate-500 sm:px-6">
            &copy; {{ now()->year }} {{ $brand->site_name }}. All rights reserved.
        </div>
    </footer>
</body>
</html>
