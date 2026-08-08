<x-guest-layout>
    <x-auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-auth.label for="email" :value="__('dashboard.auth.email')" />
            <x-auth.input
                id="email"
                name="email"
                type="email"
                icon="email"
                class="mt-1.5"
                :value="old('email')"
                :has-error="$errors->has('email')"
                required
                autofocus
                autocomplete="username"
            />
            <x-auth.error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-auth.label for="password" :value="__('dashboard.auth.password')" />
            <x-auth.input
                id="password"
                name="password"
                type="password"
                icon="lock"
                class="mt-1.5"
                :has-error="$errors->has('password')"
                required
                autocomplete="current-password"
            />
            <x-auth.error :messages="$errors->get('password')" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-auth.checkbox id="remember_me" name="remember" :label="__('dashboard.auth.remember_me')" />

            @if (Route::has('password.request'))
                <x-auth.link href="{{ route('password.request') }}">
                    {{ __('dashboard.auth.forgot_password') }}
                </x-auth.link>
            @endif
        </div>

        <x-auth.button>{{ __('dashboard.auth.login') }}</x-auth.button>
    </form>
</x-guest-layout>
