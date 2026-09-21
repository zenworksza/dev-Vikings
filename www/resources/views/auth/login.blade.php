<x-layouts.guest title="Log in">
    <h1 class="mb-6 text-lg font-semibold" style="font-family: var(--brand-heading-font)">Log in</h1>

    <x-forms.errors />

    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-forms.label for="email">Email</x-forms.label>
            <x-forms.input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus />
        </div>

        <div>
            <x-forms.label for="password">Password</x-forms.label>
            <x-forms.input id="password" name="password" type="password" required />
        </div>

        <label class="flex items-center gap-2 text-sm" style="color: var(--brand-ink-muted)">
            <input type="checkbox" name="remember" class="rounded" style="border-color: var(--brand-rule)">
            Remember me
        </label>

        <x-forms.button>Log in</x-forms.button>
    </form>

    <div class="mt-6 flex items-center justify-between text-sm" style="color: var(--brand-ink-muted)">
        <a href="{{ route('password.request') }}" class="hover:opacity-80">Forgot your password?</a>
        <a href="{{ route('register') }}" class="hover:opacity-80">Apply as a franchisee</a>
    </div>
</x-layouts.guest>
