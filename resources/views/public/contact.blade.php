<x-public-layout :title="__('site.contact.heading') . ' — ' . __('site.brand.name')" :seo-description="__('site.contact.subheading')">
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
                    <x-contact-form redirect-to="contact" />
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
