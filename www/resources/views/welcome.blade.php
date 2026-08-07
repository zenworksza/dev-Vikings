<x-layouts.app>
    <section class="mx-auto max-w-6xl px-4 py-24 text-center sm:px-6">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">
            Own a location. Grow the brand.
        </h1>
        <p class="mx-auto mt-6 max-w-2xl text-lg text-slate-600">
            Apply to become a franchisee, manage your locations, and let customers
            book directly with you &mdash; all from one portal.
        </p>
        <div class="mt-10 flex items-center justify-center gap-4">
            <a href="{{ route('register') }}"
                class="rounded-md px-6 py-3 text-sm font-medium text-white shadow-sm transition hover:opacity-90"
                style="background-color: var(--brand-primary)">
                Apply as a franchisee
            </a>
            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">
                Log in
            </a>
        </div>
    </section>
</x-layouts.app>
