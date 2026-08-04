<x-public-layout :title="__('site.faqs.heading') . ' — ' . __('site.brand.name')">
    <section class="bg-bg-soft py-16 sm:py-24">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">
                    {{ __('site.faqs.eyebrow') }}
                </span>
                <h1 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">
                    {{ __('site.faqs.heading') }}
                </h1>
                <p class="mt-4 text-lg text-text-secondary">
                    {{ __('site.faqs.subheading') }}
                </p>
            </div>

            @if ($faqs->isEmpty())
                <div class="mt-12 rounded-xl border border-dashed border-border-default bg-white p-12 text-center">
                    <p class="text-text-secondary">
                        {{ __('site.faqs.empty') }}
                    </p>
                </div>
            @else
                <div class="mt-12 space-y-4">
                    @foreach ($faqs as $faq)
                        <div
                            x-data="{ open: false }"
                            class="rounded-xl border border-border-default bg-white shadow-sm"
                        >
                            <button
                                type="button"
                                @click="open = ! open"
                                class="flex w-full items-center justify-between gap-4 px-6 py-4 text-start"
                            >
                                <span class="font-medium text-text-main">
                                    {{ $faq->question }}
                                </span>
                                <span
                                    class="shrink-0 text-primary-green transition-transform"
                                    :class="{ 'rotate-45': open }"
                                    aria-hidden="true"
                                >
                                    +
                                </span>
                            </button>

                            <div
                                x-show="open"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="px-6 pb-4 text-text-secondary"
                            >
                                {{ $faq->answer }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-public-layout>
