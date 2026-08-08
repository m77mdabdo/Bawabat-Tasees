<x-guest-layout>
    <p class="text-sm leading-relaxed text-text-secondary">
        {{ __('dashboard.auth.confirm_password_intro') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-5">
        @csrf

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

        <x-auth.button>{{ __('dashboard.auth.confirm') }}</x-auth.button>
    </form>
</x-guest-layout>
