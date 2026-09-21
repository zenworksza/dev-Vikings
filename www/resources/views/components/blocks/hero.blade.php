@props(['data'])

<section class="mx-auto max-w-6xl px-4 py-24 text-center sm:px-6">
    @if (!empty($data['eyebrow']))
        <p class="text-sm font-medium uppercase tracking-widest" style="color: var(--brand-accent)">
            {{ $data['eyebrow'] }}
        </p>
    @endif

    <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl" style="font-family: var(--brand-heading-font)">
        {{ $data['headline'] ?? '' }}
    </h1>

    @if (!empty($data['subtext']))
        <p class="mx-auto mt-6 max-w-2xl text-lg" style="color: var(--brand-ink-muted)">
            {{ $data['subtext'] }}
        </p>
    @endif

    @if (!empty($data['primary_cta_label']) || !empty($data['secondary_cta_label']))
        <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
            @if (!empty($data['primary_cta_label']))
                <a href="{{ $data['primary_cta_url'] ?? '#' }}"
                    class="px-6 py-3 text-sm font-medium shadow-sm transition hover:opacity-90"
                    style="background-color: var(--brand-accent); color: var(--brand-ink); border-radius: var(--brand-radius)">
                    {{ $data['primary_cta_label'] }}
                </a>
            @endif

            @if (!empty($data['secondary_cta_label']))
                <a href="{{ $data['secondary_cta_url'] ?? '#' }}"
                    class="text-sm font-medium hover:opacity-80" style="color: var(--brand-ink-muted)">
                    {{ $data['secondary_cta_label'] }}
                </a>
            @endif
        </div>
    @endif
</section>
