@props(['data'])

<section class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
    <div class="prose prose-invert max-w-none" style="color: var(--brand-ink)">
        {!! $data['content'] ?? '' !!}
    </div>
</section>
