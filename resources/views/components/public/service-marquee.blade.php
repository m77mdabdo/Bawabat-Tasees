@props(['services'])

@php
    $dir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';

    /*
     * One "unit" is the run of cards the animation translates by. The
     * track holds exactly TWO units and animates by 50% of its own
     * width, so the frame at 100% is pixel-identical to the frame at 0%
     * — that is what makes the loop seamless with no jump.
     *
     * A unit must be WIDER than the viewport or the marquee would show
     * empty space at the far edge for part of every cycle. Six services
     * clear 1280px comfortably; a site with two would not, so the set
     * repeats until it does. The repeat is per-unit, which keeps the
     * two halves of the track identical.
     */
    $services = collect($services)->values();
    $perUnit = max(1, (int) ceil(6 / max(1, $services->count())));
    $unit = collect()->times($perUnit, fn () => $services)->flatten(1);

    // Constant speed regardless of how many services exist: seconds of
    // travel per card, not per loop. ~46px/s — a slow drift rather than
    // anything that pulls the eye away from the heading.
    $duration = $unit->count() * 8;
@endphp

{{--
    Direction is driven from the locale, not from an ancestor [dir]
    selector, so the hook is explicit in the markup and cannot be
    changed by a wrapper. rtl flows right-to-left (cards travel left),
    ltr flows left-to-right (cards travel right).

    The Alpine here is three attributes and exists for one reason: on a
    touch screen there is no :hover to pause with, so a swipe would be
    composed with a still-running transform. touchstart freezes the
    animation for the length of the gesture. Pointer and keyboard users
    are handled by :hover / :focus-within in CSS, so the marquee is
    fully usable with JavaScript off.
--}}
<div
    class="bt-marquee"
    data-marquee-direction="{{ $dir }}"
    x-data="{ held: false }"
    :data-paused="held ? 'true' : 'false'"
    @touchstart.passive="held = true"
    @touchend.window="held = false"
    @touchcancel.window="held = false"
>
    <div class="bt-marquee__viewport">
        <div class="bt-marquee__track" style="--bt-marquee-duration: {{ $duration }}s">
            @foreach (range(0, 1) as $half)
                @foreach ($unit as $index => $service)
                    @php
                        // Only the very first pass is the real, reachable
                        // set. Every later copy exists to fill width and
                        // to close the loop, so it is hidden from assistive
                        // tech and taken out of the tab order — otherwise a
                        // keyboard user would walk the same six services
                        // four times over.
                        $isClone = $half > 0 || $index >= $services->count();
                    @endphp

                    <article
                        class="bt-marquee__item {{ $isClone ? 'bt-marquee__clone' : '' }}"
                        dir="{{ $dir }}"
                        @if ($isClone) aria-hidden="true" @endif
                    >
                        <x-card class="flex h-full flex-col">
                            <a
                                href="{{ lroute('services.show', $service) }}"
                                class="block"
                                @if ($isClone) tabindex="-1" @endif
                            >
                                @if ($service->cover_image)
                                    <img
                                        src="{{ Illuminate\Support\Facades\Storage::url($service->cover_image) }}"
                                        alt="{{ $isClone ? '' : $service->name }}"
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
                                @if ($service->is_flagship)
                                    <span class="mb-3 inline-flex w-fit items-center rounded-full bg-luxury-gold/10 px-3 py-1 text-xs font-semibold text-gold-deep">
                                        {{ __('site.common.flagship_badge') }}
                                    </span>
                                @endif

                                <h3 class="text-xl font-semibold text-text-main">
                                    <a
                                        href="{{ lroute('services.show', $service) }}"
                                        class="hover:text-primary-green focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2"
                                        @if ($isClone) tabindex="-1" @endif
                                    >
                                        {{ $service->name }}
                                    </a>
                                </h3>

                                <p class="mt-2 flex-1 text-sm text-text-secondary">
                                    {{ Illuminate\Support\Str::limit($service->summary, 100) }}
                                </p>

                                <a
                                    href="{{ lroute('services.show', $service) }}"
                                    class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary-green hover:text-primary-green/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2"
                                    @if ($isClone) tabindex="-1" @endif
                                >
                                    {{ __('site.common.learn_more') }} <span aria-hidden="true">&larr;</span>
                                </a>
                            </div>
                        </x-card>
                    </article>
                @endforeach
            @endforeach
        </div>
    </div>
</div>
