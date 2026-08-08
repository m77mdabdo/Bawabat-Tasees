<x-guest-layout>
    <p class="text-sm leading-relaxed text-text-secondary">
        {{ __('dashboard.auth.verify_email_intro') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status class="mt-6" :status="__('dashboard.auth.verify_email_sent')" />
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <x-auth.button>{{ __('dashboard.auth.resend_verification_email') }}</x-auth.button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <x-auth.link-button>{{ __('dashboard.auth.logout') }}</x-auth.link-button>
    </form>
</x-guest-layout>
