<x-layouts.guest title="Forgot password">
    <h1 class="mb-2 text-lg font-semibold text-slate-900">Forgot your password?</h1>
    <p class="mb-6 text-sm text-slate-600">Enter your email and we'll send you a password reset link.</p>

    <x-forms.errors />

    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-forms.label for="email">Email</x-forms.label>
            <x-forms.input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus />
        </div>

        <x-forms.button>Email password reset link</x-forms.button>
    </form>

    <div class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-900">Back to log in</a>
    </div>
</x-layouts.guest>
