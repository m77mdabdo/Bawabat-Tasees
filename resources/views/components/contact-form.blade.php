@props(['redirectTo' => 'contact'])

{{--
    Single source of truth for the contact form — used by both the
    standalone /contact page and the homepage Contact section. Same
    route, same StoreContactRequest, same honeypot/attribution/rate-
    limiting; the only thing that varies between the two usages is
    `redirectTo`, which ContactController::store() reads from the
    hidden `redirect_to` field to decide where to send the visitor back
    to after a successful submit — the homepage passes 'home' (redirect
    lands back on `/#contact`), the standalone page uses the 'contact'
    default (redirect lands back on /contact). Never used to build an
    arbitrary redirect URL from user input — the controller only checks
    for the literal string 'home', anything else falls back to
    'contact', so there is no open-redirect surface here.
--}}
@if (session('status'))
    <div class="mb-6 rounded-xl border border-primary-green/30 bg-primary-green/5 p-4 text-center text-primary-green">
        {{ session('status') }}
    </div>
    {{-- Guarded: fbq only exists if Meta Pixel is active in Tracking
         Settings — see resources/views/components/tracking-scripts.blade.php. --}}
    <script>if (typeof fbq === 'function') { fbq('track', 'Contact'); }</script>
@endif

<form method="POST" action="{{ lroute('contact.store') }}" class="space-y-6 rounded-2xl border border-border-default bg-white p-6 shadow-sm sm:p-8">
    @csrf

    <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">

    {{--
        Honeypot — see the same note in consultation.blade.php.
        NOT `sr-only`: that utility's entire purpose is to stay VISIBLE
        to screen readers while hidden visually — the opposite of what
        a honeypot needs (invisible to every real human, sighted or
        using assistive tech, while still present for bots that
        blanket-fill fields). Relying on `aria-hidden` to override
        `sr-only`'s designed behavior is fragile. This combination —
        1x1px, opacity-0, overflow-hidden, pointer-events-none, no
        positional offset — hides it from everyone while staying in
        its normal document position (no `-left-[9999px]`-style escape
        that could expand the page's scrollable width).
    --}}
    <div class="absolute h-px w-px overflow-hidden opacity-0 pointer-events-none" aria-hidden="true" tabindex="-1">
        <label for="website_url">{{ __('site.common.honeypot_label') }}</label>
        <input type="text" name="website_url" id="website_url" autocomplete="off" tabindex="-1" value="{{ old('website_url') }}">
    </div>

    <input type="hidden" name="first_touch_snapshot" id="first_touch_snapshot" value="">
    <input type="hidden" name="latest_touch_snapshot" id="latest_touch_snapshot" value="">

    <div>
        <label for="full_name" class="block text-sm font-medium text-text-main">{{ __('site.contact.full_name') }} *</label>
        <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" required
            class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
        @error('full_name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label for="email" class="block text-sm font-medium text-text-main">{{ __('site.contact.email') }} *</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-text-main">{{ __('site.contact.phone_optional') }}</label>
            <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
            @error('phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="message" class="block text-sm font-medium text-text-main">{{ __('site.contact.message') }} *</label>
        <textarea name="message" id="message" rows="5" required
            class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">{{ old('message') }}</textarea>
        @error('message')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="flex items-start gap-2">
            <input type="checkbox" name="consent_given" value="1" required @checked(old('consent_given'))
                class="mt-1 rounded border-border-default text-primary-green shadow-sm">
            <span class="text-sm text-text-secondary">{{ __('site.contact.consent') }} *</span>
        </label>
        @error('consent_given')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <button type="submit" class="w-full rounded-md bg-primary-green px-8 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-primary-green/90">
        {{ __('site.contact.submit') }}
    </button>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.BtsAttribution) {
            return;
        }

        var firstTouch = window.BtsAttribution.getFirstTouch();
        var latestTouch = window.BtsAttribution.getLatestTouch();

        document.getElementById('first_touch_snapshot').value = firstTouch ? JSON.stringify(firstTouch) : '';
        document.getElementById('latest_touch_snapshot').value = latestTouch ? JSON.stringify(latestTouch) : '';
    });
</script>
