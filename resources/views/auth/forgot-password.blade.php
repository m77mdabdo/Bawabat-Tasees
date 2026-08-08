<x-guest-layout>
    <p class="text-sm leading-relaxed text-text-secondary">
        {{ __('dashboard.auth.forgot_password_intro') }}
    </p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
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
            />
            <x-auth.error :messages="$errors->get('email')" />
        </div>

        <x-auth.button>{{ __('dashboard.auth.send_reset_link') }}</x-auth.button>

        <p class="text-center">
            <x-auth.link href="{{ route('login') }}">{{ __('dashboard.auth.login') }}</x-auth.link>
        </p>
    </form>
</x-guest-layout>
