<x-layouts.guest title="Reset password">
    <h1 class="mb-6 text-lg font-semibold text-slate-900">Reset your password</h1>

    <x-forms.errors />

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <x-forms.label for="email">Email</x-forms.label>
            <x-forms.input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus />
        </div>

        <div>
            <x-forms.label for="password">New password</x-forms.label>
            <x-forms.input id="password" name="password" type="password" required />
        </div>

        <div>
            <x-forms.label for="password_confirmation">Confirm new password</x-forms.label>
            <x-forms.input id="password_confirmation" name="password_confirmation" type="password" required />
        </div>

        <x-forms.button>Reset password</x-forms.button>
    </form>
</x-layouts.guest>
