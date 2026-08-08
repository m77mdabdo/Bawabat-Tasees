@props(['item'])

@php
    $isActive = $item->isActive();
    $children = $item->visibleChildren;
@endphp

@if ($children->isNotEmpty())
    {{-- Dropdown: opens on hover AND on click/keyboard, and closes on
         Escape or focus leaving the group, so it is usable without a
         mouse. --}}
    <div
        class="relative"
        x-data="{ open: false }"
        @mouseenter="open = true"
        @mouseleave="open = false"
        @focusin="open = true"
        @focusout="if (! $el.contains($event.relatedTarget)) open = false"
        @keydown.escape.stop="open = false"
    >
        <button
            type="button"
            @click="open = ! open"
            :aria-expanded="open.toString()"
            aria-haspopup="true"
            @class([
                'flex items-center gap-1 hover:text-primary-green',
                'text-primary-green font-semibold' => $isActive,
                'text-text-main' => ! $isActive,
            ])
        >
            {{ $item->label }}
            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M6 9l6 6 6-6" />
            </svg>
        </button>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="absolute start-0 top-full z-40 mt-2 w-64 rounded-lg border border-border-default bg-white py-2 shadow-lg"
            style="display: none;"
        >
            @foreach ($children as $child)
                <a
                    href="{{ $child->href() ?? '#' }}"
                    target="{{ $child->target }}"
                    @if ($child->linkRel()) rel="{{ $child->linkRel() }}" @endif
                    @class([
                        'block px-4 py-2 text-sm hover:bg-bg-soft hover:text-primary-green',
                        'text-primary-green font-semibold' => $child->isActive(),
                        'text-text-main' => ! $child->isActive(),
                    ])
                >
                    {{ $child->label }}
                </a>
            @endforeach
        </div>
    </div>
@else
    <a
        href="{{ $item->href() ?? '#' }}"
        target="{{ $item->target }}"
        @if ($item->linkRel()) rel="{{ $item->linkRel() }}" @endif
        @class([
            'hover:text-primary-green',
            'text-primary-green font-semibold' => $isActive,
            'text-text-main' => ! $isActive,
        ])
    >
        {{ $item->label }}
    </a>
@endif
