<x-layouts.guest title="Log in">
    <h1 class="mb-6 text-lg font-semibold text-slate-900">Log in</h1>

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

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300">
            Remember me
        </label>

        <x-forms.button>Log in</x-forms.button>
    </form>

    <div class="mt-6 flex items-center justify-between text-sm">
        <a href="{{ route('password.request') }}" class="text-slate-600 hover:text-slate-900">Forgot your password?</a>
        <a href="{{ route('register') }}" class="text-slate-600 hover:text-slate-900">Apply as a franchisee</a>
    </div>
</x-layouts.guest>
