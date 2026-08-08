<x-public-layout :title="__('site.services.index_eyebrow') . ' — ' . __('site.brand.name')" :seo-description="__('site.services.index_subheading')">
    <section class="bg-bg-soft py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">
                    {{ __('site.services.index_eyebrow') }}
                </span>
                <h1 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">
                    {{ __('site.services.index_heading') }}
                </h1>
                <p class="mt-4 text-lg text-text-secondary">
                    {{ __('site.services.index_subheading') }}
                </p>
            </div>

            @if ($services->isEmpty())
                <div class="mt-16 rounded-xl border border-dashed border-border-default bg-white p-12 text-center">
                    <p class="text-text-secondary">
                        {{ __('site.services.index_empty') }}
                    </p>
                </div>
            @else
                <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        <x-card class="flex flex-col">
                            <a href="{{ lroute('services.show', $service) }}" class="block">
                                @if ($service->cover_image)
                                    <img
                                        src="{{ Illuminate\Support\Facades\Storage::url($service->cover_image) }}"
                                        alt="{{ $service->name }}"
                                        class="h-48 w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-48 w-full items-center justify-center bg-bg-soft">
                                        <span class="text-4xl">🏢</span>
                                    </div>
                                @endif
                            </a>

                            <div class="flex flex-1 flex-col p-6">
                                @if ($service->is_flagship)
                                    <span class="mb-3 inline-flex w-fit items-center rounded-full bg-luxury-gold/10 px-3 py-1 text-xs font-semibold text-luxury-gold">
                                        {{ __('site.common.flagship_badge') }}
                                    </span>
                                @endif

                                <h2 class="text-xl font-semibold text-text-main">
                                    <a href="{{ lroute('services.show', $service) }}" class="hover:text-primary-green">
                                        {{ $service->name }}
                                    </a>
                                </h2>

                                <p class="mt-2 flex-1 text-sm text-text-secondary">
                                    {{ Illuminate\Support\Str::limit($service->summary, 120) }}
                                </p>

                                <a
                                    href="{{ lroute('services.show', $service) }}"
                                    class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary-green hover:text-primary-green/80"
                                >
                                    {{ __('site.common.learn_more') }}
                                    <span aria-hidden="true">&larr;</span>
                                </a>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-public-layout>
