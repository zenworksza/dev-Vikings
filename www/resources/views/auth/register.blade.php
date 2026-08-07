<x-layouts.guest title="Apply as a franchisee">
    <h1 class="mb-2 text-lg font-semibold text-slate-900">Apply as a franchisee</h1>
    <p class="mb-6 text-sm text-slate-600">Create your account to start the franchisee application.</p>

    <x-forms.errors />

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-forms.label for="name">Full name</x-forms.label>
            <x-forms.input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus />
        </div>

        <div>
            <x-forms.label for="email">Email</x-forms.label>
            <x-forms.input id="email" name="email" type="email" value="{{ old('email') }}" required />
        </div>

        <div>
            <x-forms.label for="password">Password</x-forms.label>
            <x-forms.input id="password" name="password" type="password" required />
        </div>

        <div>
            <x-forms.label for="password_confirmation">Confirm password</x-forms.label>
            <x-forms.input id="password_confirmation" name="password_confirmation" type="password" required />
        </div>

        <x-forms.button>Create account</x-forms.button>
    </form>

    <div class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-900">Already have an account? Log in</a>
    </div>
</x-layouts.guest>
