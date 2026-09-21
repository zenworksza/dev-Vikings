<button {{ $attributes->merge(['type' => 'submit', 'class' => 'w-full px-4 py-2 text-sm font-medium shadow-sm transition hover:opacity-90']) }}
    style="background-color: var(--brand-accent); color: var(--brand-ink); border-radius: var(--brand-radius)">
    {{ $slot }}
</button>
