{{-- One service card inside a marquee. $item is a Service.
     Clones are inert: no tab stop, and no alt text to re-announce. --}}
<x-card class="flex h-full flex-col">
    <a href="{{ lroute('services.show', $item) }}" class="block" @if ($isClone) tabindex="-1" @endif>
        @if ($item->cover_image)
            <img
                src="{{ Illuminate\Support\Facades\Storage::url($item->cover_image) }}"
                alt="{{ $isClone ? '' : $item->name }}"
                class="h-44 w-full object-cover"
                loading="lazy"
            >
        @else
            <div class="flex h-44 w-full items-center justify-center bg-bg-soft">
                <span class="text-4xl" aria-hidden="true">🏢</span>
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-6">
        @if ($item->is_flagship)
            <span class="mb-3 inline-flex w-fit items-center rounded-full bg-luxury-gold/10 px-3 py-1 text-xs font-semibold text-gold-deep">
                {{ __('site.common.flagship_badge') }}
            </span>
        @endif

        <h3 class="text-xl font-semibold text-text-main">
            <a
                href="{{ lroute('services.show', $item) }}"
                class="hover:text-primary-green focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2"
                @if ($isClone) tabindex="-1" @endif
            >
                {{ $item->name }}
            </a>
        </h3>

        <p class="mt-2 flex-1 text-sm text-text-secondary">
            {{ Illuminate\Support\Str::limit($item->summary, 100) }}
        </p>

        <a
            href="{{ lroute('services.show', $item) }}"
            class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary-green hover:text-primary-green/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2"
            @if ($isClone) tabindex="-1" @endif
        >
            {{ __('site.common.learn_more') }} <span aria-hidden="true">&larr;</span>
        </a>
    </div>
</x-card>
