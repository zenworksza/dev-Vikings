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
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    @stack('head')
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="brand"><img src="{{ asset('images/logo-gold.png') }}" alt="{{ config('site.name') }}"></a>
            <nav>
                <a href="{{ route('locations.index') }}" class="nav-extra">Locations</a>
                <a href="{{ config('site.portal_url') }}/login" class="nav-extra">Franchisee login</a>
                <a href="{{ config('site.portal_url') }}/register" class="btn btn-sm btn-quiet nav-extra">Apply as a franchisee</a>
                <a href="{{ route('locations.index') }}" class="btn btn-sm">Make a booking</a>
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
