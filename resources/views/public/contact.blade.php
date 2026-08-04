<x-public-layout :title="__('site.contact.heading') . ' — ' . __('site.brand.name')">
    <section class="bg-bg-soft py-16 sm:py-24">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">
                    {{ __('site.contact.eyebrow') }}
                </span>
                <h1 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">
                    {{ __('site.contact.heading') }}
                </h1>
                <p class="mt-4 text-lg text-text-secondary">
                    {{ __('site.contact.subheading') }}
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-10 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <x-card class="p-6">
                        <h2 class="font-semibold text-text-main">{{ __('site.contact.info_heading') }}</h2>

                        <dl class="mt-4 space-y-4 text-sm">
                            @if ($contactPhone)
                                <div>
                                    <dt class="font-medium text-text-secondary">{{ __('site.contact.phone_label') }}</dt>
                                    <dd class="mt-1 text-text-main">{{ $contactPhone }}</dd>
                                </div>
                            @endif

                            @if ($contactWhatsapp)
                                <div>
                                    <dt class="font-medium text-text-secondary">{{ __('site.contact.whatsapp_label') }}</dt>
                                    <dd class="mt-1 text-text-main">{{ $contactWhatsapp }}</dd>
                                </div>
                            @endif

                            @if ($contactEmail)
                                <div>
                                    <dt class="font-medium text-text-secondary">{{ __('site.contact.email_label') }}</dt>
                                    <dd class="mt-1 text-text-main">{{ $contactEmail }}</dd>
                                </div>
                            @endif

                            @if ($contactAddress)
                                <div>
                                    <dt class="font-medium text-text-secondary">{{ __('site.contact.address_label') }}</dt>
                                    <dd class="mt-1 text-text-main">{{ $contactAddress }}</dd>
                                </div>
                            @endif
                        </dl>
                    </x-card>
                </div>

                <div class="lg:col-span-3">
                    @if (session('status'))
                        <div class="mb-6 rounded-xl border border-primary-green/30 bg-primary-green/5 p-4 text-center text-primary-green">
                            {{ session('status') }}
                        </div>
                        {{-- Guarded: fbq only exists if Meta Pixel is active
                             in Tracking Settings — see resources/views/
                             components/tracking-scripts.blade.php. --}}
                        <script>if (typeof fbq === 'function') { fbq('track', 'Contact'); }</script>
                    @endif

                    <form method="POST" action="{{ lroute('contact.store') }}" class="space-y-6 rounded-2xl border border-border-default bg-white p-6 shadow-sm sm:p-8">
                        @csrf

                        {{-- Honeypot — see the same note in consultation.blade.php. --}}
                        <div class="absolute -left-[9999px]" aria-hidden="true" tabindex="-1">
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
                </div>
            </div>
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
