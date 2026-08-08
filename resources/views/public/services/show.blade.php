<x-public-layout :title="$service->name . ' — ' . __('site.brand.name')" :seo-model="$service">
    @if ($service->cover_image)
        <div class="relative h-64 w-full overflow-hidden sm:h-80">
            <img
                src="{{ Illuminate\Support\Facades\Storage::url($service->cover_image) }}"
                alt="{{ $service->name }}"
                class="h-full w-full object-cover"
            >
            <div class="absolute inset-0 bg-dark-green/50"></div>
            <div class="absolute inset-0 flex items-center">
                <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8">
                    @if ($service->is_flagship)
                        <span class="mb-3 inline-flex w-fit items-center rounded-full bg-luxury-gold/90 px-3 py-1 text-xs font-semibold text-white">
                            {{ __('site.common.flagship_badge') }}
                        </span>
                    @endif
                    <h1 class="max-w-2xl text-3xl font-bold text-white sm:text-4xl">
                        {{ $service->name }}
                    </h1>
                </div>
            </div>
        </div>
    @else
        <section class="bg-dark-green py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @if ($service->is_flagship)
                    <span class="mb-3 inline-flex w-fit items-center rounded-full bg-luxury-gold/90 px-3 py-1 text-xs font-semibold text-white">
                        {{ __('site.common.flagship_badge') }}
                    </span>
                @endif
                <h1 class="max-w-2xl text-3xl font-bold text-white sm:text-4xl">
                    {{ $service->name }}
                </h1>
            </div>
        </section>
    @endif

    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-lg text-text-secondary">
                {{ $service->summary }}
            </p>

            @if ($service->body)
                <div class="mt-8 text-text-main leading-relaxed">
                    {{ $service->body }}
                </div>
            @endif

            @if ($service->requirements)
                <div class="mt-10 rounded-xl border border-border-default bg-bg-soft p-6">
                    <h2 class="text-lg font-semibold text-text-main">
                        {{ __('site.services.requirements_heading') }}
                    </h2>
                    <p class="mt-2 text-text-secondary">
                        {{ $service->requirements }}
                    </p>
                </div>
            @endif

            @if ($service->process)
                <div class="mt-6 rounded-xl border border-border-default bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-text-main">
                        {{ __('site.services.process_heading') }}
                    </h2>
                    <p class="mt-2 text-text-secondary">
                        {{ $service->process }}
                    </p>
                </div>
            @endif

            <div class="mt-12 rounded-xl bg-primary-green px-8 py-10 text-center">
                <h2 class="text-2xl font-bold text-white">
                    {{ __('site.services.cta_heading') }}
                </h2>
                <p class="mt-2 text-white/85">
                    {{ __('site.services.cta_subheading') }}
                </p>
                {{-- Was a dead href="#" TODO link — /consultation has existed
                     since the Leads/Consultation task; fixed while this file
                     was already being touched for string extraction. --}}
                <a
                    href="{{ lroute('consultation') }}"
                    class="mt-6 inline-flex items-center justify-center rounded-md bg-white px-8 py-3 text-base font-semibold text-primary-green shadow-lg transition hover:bg-white/90"
                >
                    {{ __('site.services.cta_button') }}
                </a>
            </div>

            <div class="mt-10">
                <a href="{{ lroute('services.index') }}" class="text-sm font-medium text-primary-green hover:text-primary-green/80">
                    &rarr; {{ __('site.services.back_link') }}
                </a>
            </div>
        </div>
    </section>
</x-public-layout>
