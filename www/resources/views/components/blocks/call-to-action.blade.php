@props(['data'])

<section class="border-t" style="border-color: color-mix(in srgb, var(--brand-rule) 25%, transparent); background-color: var(--brand-surface-alt)">
    <div class="mx-auto max-w-4xl px-4 py-16 text-center sm:px-6">
        <h2 class="text-2xl font-semibold sm:text-3xl" style="font-family: var(--brand-heading-font)">
            {{ $data['heading'] ?? '' }}
        </h2>

        @if (!empty($data['subtext']))
            <p class="mx-auto mt-4 max-w-xl" style="color: var(--brand-ink-muted)">
                {{ $data['subtext'] }}
            </p>
        @endif

        @if (!empty($data['button_label']))
            <a href="{{ $data['button_url'] ?? '#' }}"
                class="mt-8 inline-block px-6 py-3 text-sm font-medium shadow-sm transition hover:opacity-90"
                style="background-color: var(--brand-accent); color: var(--brand-ink); border-radius: var(--brand-radius)">
                {{ $data['button_label'] }}
            </a>
        @endif
    </div>
</section>
