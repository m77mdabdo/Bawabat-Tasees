@props([
    // Announced while the thing IS moving (so the button offers "pause").
    'playingLabel',
    // Announced while it is stopped (so the button offers "play").
    'pausedLabel',
    // Which state property the surrounding Alpine component exposes:
    // 'playing' for backgroundVideo, 'stopped' for autoAdvance. They are
    // inverses of each other, hence the flip rather than a second component.
    'state' => 'playing',
])

@php
    $moving = $state === 'stopped' ? '! stopped' : 'playing';
@endphp

{{--
    The pause control WCAG 2.2.2 asks for, shared by the homepage hero video,
    the login background and the testimonial carousel. It must exist whether
    or not prefers-reduced-motion already stopped the thing: the guideline
    wants a mechanism, and a visitor without that OS setting still has to be
    able to stop something moving under their reading.

    aria-pressed rather than a changing label alone, so the control reads as a
    toggle rather than as two different buttons appearing in the same place.

    h-11 w-11 is 44px — the same touch target every other control on the site
    uses.
--}}
{{--
    The two labels ride in data- attributes rather than being interpolated
    into the Alpine expression. @js() would JSON-escape the Arabic to \uXXXX
    sequences, so the real text would never appear in the served HTML — bad
    for anything reading the markup, and bad for asserting on it.
--}}
<button
    type="button"
    @click="toggle()"
    data-label-playing="{{ $playingLabel }}"
    data-label-paused="{{ $pausedLabel }}"
    :aria-label="{{ $moving }} ? $el.dataset.labelPlaying : $el.dataset.labelPaused"
    :aria-pressed="{{ $moving }} ? 'false' : 'true'"
    {{ $attributes->class([
        'inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition hover:bg-white/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-transparent',
    ]) }}
>
    {{-- Pause glyph while it moves. Rendered by default (no x-cloak) because
         the common first paint is "moving". --}}
    <svg x-show="{{ $moving }}" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <rect x="6" y="5" width="4" height="14" rx="1" />
        <rect x="14" y="5" width="4" height="14" rx="1" />
    </svg>

    {{-- Play glyph while it is stopped. x-cloak so it does not flash before
         Alpine evaluates the condition. --}}
    <svg x-show="! ({{ $moving }})" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M8 5.5v13a1 1 0 001.53.85l10-6.5a1 1 0 000-1.7l-10-6.5A1 1 0 008 5.5z" />
    </svg>
</button>
