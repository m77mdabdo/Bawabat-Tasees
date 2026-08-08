@props(['stacked' => false])

{{--
    Sibling of x-whatsapp-float-button, and positioned to match it.

    POSITIONING NOTE. This uses the literal `left-6`, NOT the logical
    `start-6`, for the same reason the WhatsApp button does (see that
    component): it is deliberately pinned to one physical corner so it
    does not jump across the screen when the language toggle flips
    direction. Using `start-6` here would be actively worse — in Arabic
    (RTL) it would resolve to the RIGHT edge while the WhatsApp button
    stayed on the left, splitting the pair across opposite corners
    instead of stacking them. Matching the sibling is what keeps this
    RTL-correct.

    `stacked` is true when the WhatsApp button is also rendering, which
    lifts this one clear of it: WhatsApp occupies 24-80px from the
    bottom, so bottom-24 (96px) leaves a 16px gap. With no WhatsApp
    number configured there is nothing to clear, so it drops to the same
    bottom-6 the other button would have used.

    z-30 matches the WhatsApp button and the header, staying below the
    mobile drawer's backdrop (z-40) and panel (z-50) so neither float
    fights the drawer while it is open.
--}}
<div
    x-data="{
        shown: false,
        onScroll() { this.shown = window.scrollY > 350 },
        toTop() {
            /* Honour prefers-reduced-motion: an instant jump instead of a
               long smooth scroll for users who ask for less animation. */
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
        },
    }"
    x-init="onScroll()"
    @scroll.window.passive="onScroll()"
>
    <button
        type="button"
        x-show="shown"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        @click="toTop()"
        aria-label="{{ __('site.nav.back_to_top') }}"
        class="fixed {{ $stacked ? 'bottom-24' : 'bottom-6' }} left-6 z-30 flex h-14 w-14 items-center justify-center rounded-full bg-primary-green text-white shadow-lg transition hover:scale-105 hover:bg-dark-green focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2"
    >
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 19V5" />
            <path d="M5 12l7-7 7 7" />
        </svg>
    </button>
</div>
