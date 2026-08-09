@props([
    'items',
    // 'base' moves the way the locale reads; 'reverse' moves against it.
    // Alternating these down the page is what makes adjacent rows shear
    // past each other, and the whole pattern flips when the locale does.
    'direction' => 'base',
    // Blade view rendered once per card. It receives $item, $isClone and
    // $itemIndex (the position in the ORIGINAL set, so a numbered card
    // keeps its number in every copy).
    'card',
    'cardData' => [],
    // 'default' ~21rem, 'compact' ~15rem for short single-line cards.
    'size' => 'default',
])

@php
    $items = collect($items)->values();

    $readingDir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    $flip = ['rtl' => 'ltr', 'ltr' => 'rtl'];
    $flow = $direction === 'reverse' ? $flip[$readingDir] : $readingDir;

    /*
     * Pitch is card width + its trailing margin at the widest
     * breakpoint, mirroring the CSS. Kept here only to size the unit and
     * the duration; nothing about the loop depends on it being exact.
     */
    $pitch = $size === 'compact' ? 264 : 360;

    /*
     * One "unit" is the run of cards the animation translates by. The
     * track holds exactly TWO units and animates by 50% of its own
     * width, so the frame at 100% is pixel-identical to the frame at 0%
     * — that is what makes the loop seamless with no jump.
     *
     * A unit must be WIDER than the viewport or the marquee would show
     * empty space at the far edge for part of every cycle. 1600px clears
     * the max-w-7xl (1280px) container with room to spare, so a short
     * set repeats until it gets there. The repeat is per-unit, which
     * keeps the two halves of the track identical.
     */
    $minPerUnit = (int) ceil(1600 / $pitch);
    $perUnit = max(1, (int) ceil($minPerUnit / max(1, $items->count())));
    $unitCount = $perUnit * $items->count();

    /*
     * Constant speed — 45px/s — regardless of card size or how many
     * exist. Three marquees are visible on one page, so a shared
     * pixels-per-second is what stops them reading as unrelated widgets
     * moving at random rates.
     */
    $duration = max(20, (int) round($unitCount * $pitch / 45));
@endphp

{{--
    Direction is driven from the locale, not from an ancestor [dir]
    selector, so the hook is explicit in the markup and cannot be
    changed by a wrapper.

    The Alpine here is three attributes and exists for one reason: on a
    touch screen there is no :hover to pause with, so a swipe would be
    composed with a still-running transform. touchstart freezes the
    animation for the length of the gesture. Pointer and keyboard users
    are handled by :hover / :focus-within in CSS, so the marquee is
    fully usable with JavaScript off.
--}}
<div
    class="bt-marquee"
    data-marquee-direction="{{ $flow }}"
    data-marquee-flow="{{ $direction }}"
    x-data="{ held: false }"
    :data-paused="held ? 'true' : 'false'"
    @touchstart.passive="held = true"
    @touchend.window="held = false"
    @touchcancel.window="held = false"
>
    <div class="bt-marquee__viewport">
        <div class="bt-marquee__track" style="--bt-marquee-duration: {{ $duration }}s">
            @foreach (range(0, 1) as $half)
                @foreach (range(0, $perUnit - 1) as $pass)
                    @foreach ($items as $itemIndex => $item)
                        @php
                            // Only the very first pass is the real,
                            // reachable set. Every later copy exists to
                            // fill width and close the loop, so it is
                            // hidden from assistive tech and taken out of
                            // the tab order — otherwise a keyboard user
                            // would walk the same cards several times.
                            $isClone = $half > 0 || $pass > 0;

                            $itemClasses = collect([
                                'bt-marquee__item',
                                $size === 'compact' ? 'bt-marquee__item--compact' : null,
                                $isClone ? 'bt-marquee__clone' : null,
                            ])->filter()->implode(' ');
                        @endphp

                        <article
                            class="{{ $itemClasses }}"
                            dir="{{ $readingDir }}"
                            @if ($isClone) aria-hidden="true" @endif
                        >
                            @include($card, array_merge($cardData, [
                                'item' => $item,
                                'itemIndex' => $itemIndex,
                                'isClone' => $isClone,
                            ]))
                        </article>
                    @endforeach
                @endforeach
            @endforeach
        </div>
    </div>
</div>
