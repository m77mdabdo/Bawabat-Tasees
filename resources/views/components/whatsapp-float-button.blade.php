@props(['number'])

{{--
    Fixed bottom-left (the mirror of the standard LTR bottom-right spot,
    since this site is RTL-first) — deliberately positioned with a
    literal `left-6`, not the logical `start-6`, so it stays in the same
    physical corner regardless of which direction the language toggle is
    currently in. z-30 matches the header's own stacking level, staying
    below the mobile drawer's backdrop (z-40) / panel (z-50) so it never
    fights the drawer for visibility while it's open. No other
    fixed-position UI (e.g. a "back to top" button) exists anywhere in
    the project to collide with.

    Icon: the real WhatsApp glyph, see x-icons.whatsapp for the source.
--}}
@if ($number)
    <a
        href="https://wa.me/{{ preg_replace('/\D/', '', $number) }}"
        target="_blank"
        rel="noopener"
        onclick="if (typeof fbq === 'function') { fbq('trackCustom', 'WhatsAppClick'); }"
        class="fixed bottom-6 left-6 z-30 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg transition hover:scale-105 hover:bg-[#25D366]/90"
        aria-label="{{ __('site.home.cta_whatsapp') }}"
    >
        <x-icons.whatsapp class="h-7 w-7" />
    </a>
@endif
