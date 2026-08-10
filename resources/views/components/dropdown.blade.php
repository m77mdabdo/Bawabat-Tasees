@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white'])

@php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
    default => $width,
};
@endphp

{{--
    Keyboard handling, which this had none of: Escape closes the panel and
    puts focus back on the trigger, so a keyboard user is not stranded at the
    top of the document after dismissing it. The public navbar dropdown
    (components/public/nav-item) already worked this way; this one — the
    dashboard user menu — did not.

    `open` is in scope for the trigger slot too, so callers bind
    :aria-expanded on their own button rather than this wrapper announcing a
    state for an element it is not.

    No role="menu"/menuitem: those come with an expectation of arrow-key
    navigation and typeahead, and claiming the pattern without implementing
    it reads worse to a screen reader than plain links do.
--}}
<div
    class="relative"
    x-data="{ open: false }"
    @click.outside="open = false"
    @close.stop="open = false"
    @keydown.escape.stop="if (open) { open = false; $refs.trigger?.querySelector('button, [href]')?.focus() }"
>
    <div x-ref="trigger" @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-2 {{ $width }} rounded-md shadow-lg {{ $alignmentClasses }}"
            style="display: none;"
            @click="open = false">
        <div class="rounded-md ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
