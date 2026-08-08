<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-auth.label for="email" :value="__('dashboard.auth.email')" />
            <x-auth.input
                id="email"
                name="email"
                type="email"
                icon="email"
                class="mt-1.5"
                :value="old('email', $request->email)"
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
                autocomplete="new-password"
            />
            <x-auth.error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-auth.label for="password_confirmation" :value="__('dashboard.auth.confirm_password_label')" />
            <x-auth.input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                icon="lock"
                class="mt-1.5"
                :has-error="$errors->has('password_confirmation')"
                required
                autocomplete="new-password"
            />
            <x-auth.error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-auth.button>{{ __('dashboard.auth.reset_password') }}</x-auth.button>
    </form>
</x-guest-layout>
