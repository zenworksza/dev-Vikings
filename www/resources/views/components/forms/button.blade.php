<button {{ $attributes->merge(['type' => 'submit', 'class' => 'w-full rounded-md px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90']) }} style="background-color: var(--brand-primary)">
    {{ $slot }}
</button>
