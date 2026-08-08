<x-public-layout :title="__('site.consultation.heading') . ' — ' . __('site.brand.name')" :seo-description="__('site.consultation.subheading')">
    <section class="bg-bg-soft py-16 sm:py-24">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">
                    {{ __('site.consultation.eyebrow') }}
                </span>
                <h1 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">
                    {{ __('site.consultation.heading') }}
                </h1>
                <p class="mt-4 text-lg text-text-secondary">
                    {{ __('site.consultation.subheading') }}
                </p>
            </div>

            @if (session('status'))
                <div class="mt-8 rounded-xl border border-primary-green/30 bg-primary-green/5 p-4 text-center text-primary-green">
                    {{ session('status') }}
                </div>
                {{-- Guarded: fbq only exists if Meta Pixel is active in
                     Tracking Settings — see resources/views/components/
                     tracking-scripts.blade.php. --}}
                <script>if (typeof fbq === 'function') { fbq('track', 'Lead'); }</script>
            @endif

            <form method="POST" action="{{ lroute('consultation.store') }}" class="mt-10 space-y-6 rounded-2xl border border-border-default bg-white p-6 shadow-sm sm:p-8">
                @csrf

                {{-- Honeypot: real visitors never see or fill this (CSS-hidden,
                     not type="hidden", so bots that blanket-fill visible-looking
                     text inputs get caught). If filled, the controller silently
                     accepts the request without creating a Lead. NOT `sr-only` —
                     that utility stays VISIBLE to screen readers by design, the
                     opposite of what a honeypot needs; see the same note in
                     contact-form.blade.php for the full reasoning. --}}
                <div class="absolute h-px w-px overflow-hidden opacity-0 pointer-events-none" aria-hidden="true" tabindex="-1">
                    <label for="website_url">{{ __('site.common.honeypot_label') }}</label>
                    <input type="text" name="website_url" id="website_url" autocomplete="off" tabindex="-1" value="{{ old('website_url') }}">
                </div>

                {{-- Populated client-side from the bts_first_touch/bts_latest_touch
                     cookies just before submit — see resources/js/attribution.js
                     and the script block at the bottom of this page. --}}
                <input type="hidden" name="first_touch_snapshot" id="first_touch_snapshot" value="">
                <input type="hidden" name="latest_touch_snapshot" id="latest_touch_snapshot" value="">

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-text-main">{{ __('site.consultation.full_name') }} *</label>
                        <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}" required
                            class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
                        @error('full_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-text-main">{{ __('site.consultation.phone') }} *</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required
                            class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="whatsapp_number" class="block text-sm font-medium text-text-main">{{ __('site.consultation.whatsapp_optional') }}</label>
                        <input type="tel" name="whatsapp_number" id="whatsapp_number" value="{{ old('whatsapp_number') }}"
                            class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
                        @error('whatsapp_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-text-main">{{ __('site.consultation.email') }} *</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nationality" class="block text-sm font-medium text-text-main">{{ __('site.consultation.nationality_optional') }}</label>
                        <input type="text" name="nationality" id="nationality" value="{{ old('nationality') }}"
                            class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
                    </div>

                    <div>
                        <label for="country_of_residence" class="block text-sm font-medium text-text-main">{{ __('site.consultation.country_of_residence_optional') }}</label>
                        <input type="text" name="country_of_residence" id="country_of_residence" value="{{ old('country_of_residence') }}"
                            class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
                    </div>
                </div>

                <div>
                    <label for="requested_service_id" class="block text-sm font-medium text-text-main">{{ __('site.consultation.service_label') }} *</label>
                    <select name="requested_service_id" id="requested_service_id" required
                        class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('site.consultation.service_placeholder') }}</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected(old('requested_service_id') == $service->id)>
                                {{ $service->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('requested_service_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="requested_activity" class="block text-sm font-medium text-text-main">{{ __('site.consultation.activity_optional') }}</label>
                    <input type="text" name="requested_activity" id="requested_activity" value="{{ old('requested_activity') }}"
                        class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" name="owns_external_company" value="1" @checked(old('owns_external_company'))
                        class="rounded border-border-default text-primary-green shadow-sm">
                    <span class="text-sm text-text-main">{{ __('site.consultation.owns_external_company') }}</span>
                </label>

                <div>
                    <label for="message" class="block text-sm font-medium text-text-main">{{ __('site.consultation.message_optional') }}</label>
                    <textarea name="message" id="message" rows="4"
                        class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">{{ old('message') }}</textarea>
                </div>

                <div>
                    <label class="flex items-start gap-2">
                        <input type="checkbox" name="consent_given" value="1" required @checked(old('consent_given'))
                            class="mt-1 rounded border-border-default text-primary-green shadow-sm">
                        <span class="text-sm text-text-secondary">{{ __('site.consultation.consent') }} *</span>
                    </label>
                    @error('consent_given')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full rounded-md bg-primary-green px-8 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-primary-green/90">
                    {{ __('site.consultation.submit') }}
                </button>
            </form>
        </div>
    </section>

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
</x-public-layout>
