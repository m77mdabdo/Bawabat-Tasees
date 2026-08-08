<x-public-layout :title="__('site.countries.heading') . ' — ' . __('site.brand.name')" :seo-description="__('site.countries.subheading')">
    <section class="bg-bg-soft py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">
                    {{ __('site.countries.eyebrow') }}
                </span>
                <h1 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">
                    {{ __('site.countries.heading') }}
                </h1>
                <p class="mt-4 text-lg text-text-secondary">
                    {{ __('site.countries.subheading') }}
                </p>
            </div>

            @if ($countries->isEmpty())
                <div class="mt-16 rounded-xl border border-dashed border-border-default bg-white p-12 text-center">
                    <p class="text-text-secondary">
                        {{ __('site.countries.empty') }}
                    </p>
                </div>
            @else
                <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($countries as $country)
                        <x-card class="flex items-start gap-4 p-6">
                            @if ($country->flag_image)
                                <img
                                    src="{{ Illuminate\Support\Facades\Storage::url($country->flag_image) }}"
                                    alt="{{ $country->name }}"
                                    class="h-12 w-12 shrink-0 rounded-full object-cover border border-border-default"
                                >
                            @else
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bg-soft text-lg">
                                    🌍
                                </div>
                            @endif

                            <div>
                                <h2 class="font-semibold text-text-main">
                                    {{ $country->name }}
                                </h2>
                                @if ($country->notes)
                                    <p class="mt-1 text-sm text-text-secondary">
                                        {{ $country->notes }}
                                    </p>
                                @endif
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-public-layout>
