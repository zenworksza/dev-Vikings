@props(['title' => config('site.name'), 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
        <meta property="og:description" content="{{ $description }}">
    @endif
    <meta property="og:title" content="{{ $title }}">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Inter:wght@400;500;600&display=swap">
    {{-- ?v= changes whenever the file does, so browsers never keep a stale stylesheet. --}}
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
    @stack('head')
</head>
<body>
    @php
        // Main menu: label => [url, route patterns that mark it as the current section]
        $menu = [
            'Home' => [route('home'), ['home']],
            'About' => [route('about'), ['about']],
            'Franchise' => [route('franchise'), ['franchise']],
            'Make a booking' => [route('locations.index'), ['locations.*', 'bookings.*']],
            'Contact' => [route('contact'), ['contact']],
        ];
    @endphp
    <div class="topbar">
        <div class="container">
            <a href="{{ config('site.portal_url') }}/login" class="topbar-link">Franchisee login</a>
        </div>
    </div>

    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="brand"><img src="{{ asset('images/logo-gold.png') }}" alt="{{ config('site.name') }}"></a>
            <nav aria-label="Main">
                @foreach ($menu as $label => [$url, $patterns])
                    <a href="{{ $url }}" class="btn btn-sm btn-quiet"@if (request()->routeIs(...$patterns)) aria-current="page"@endif>{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="site-footer">
        <div class="container">&copy; {{ now()->year }} {{ config('site.name') }}. All rights reserved.</div>
    </footer>
</body>
</html>
