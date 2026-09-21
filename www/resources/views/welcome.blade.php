@php
    // The franchisee application/login live on the portal subdomain, not
    // here — see App\Support\SiteTheme.
    $portalRegisterUrl = \App\Support\SiteTheme::otherSiteUrl('/register');
    $portalLoginUrl = \App\Support\SiteTheme::otherSiteUrl('/login');
@endphp

<x-layouts.app>
    <section class="mx-auto max-w-6xl px-4 py-24 text-center sm:px-6">
        <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl" style="font-family: var(--brand-heading-font)">
            Own a location. Grow the brand.
        </h1>
        <p class="mx-auto mt-6 max-w-2xl text-lg" style="color: var(--brand-ink-muted)">
            Apply to become a franchisee, manage your locations, and let customers
            book directly with you &mdash; all from one portal.
        </p>
        <div class="mt-10 flex items-center justify-center gap-4">
            <a href="{{ $portalRegisterUrl }}"
                class="px-6 py-3 text-sm font-medium shadow-sm transition hover:opacity-90"
                style="background-color: var(--brand-accent); color: var(--brand-ink); border-radius: var(--brand-radius)">
                Apply as a franchisee
            </a>
            <a href="{{ $portalLoginUrl }}" class="text-sm font-medium hover:opacity-80" style="color: var(--brand-ink-muted)">
                Log in
            </a>
        </div>
    </section>
</x-layouts.app>
