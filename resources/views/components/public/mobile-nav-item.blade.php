@props(['item'])

@php
    $isActive = $item->isActive();
    $children = $item->visibleChildren;
@endphp

@if ($children->isNotEmpty())
    {{-- In the drawer a dropdown becomes an expandable section rather
         than a floating panel — there is no room for an overlay. --}}
    <div x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
        <button
            type="button"
            @click="open = ! open"
            :aria-expanded="open.toString()"
            @class([
                'flex w-full items-center justify-between rounded-md px-3 py-2.5 hover:bg-bg-soft',
                'bg-bg-soft text-primary-green font-semibold' => $isActive,
                'text-text-main' => ! $isActive,
            ])
        >
            <span>{{ $item->label }}</span>
            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M6 9l6 6 6-6" />
            </svg>
        </button>

        <div x-show="open" x-cloak class="mt-1 space-y-1 ps-4">
            @foreach ($children as $child)
                <a
                    href="{{ $child->href() ?? '#' }}"
                    target="{{ $child->target }}"
                    @if ($child->linkRel()) rel="{{ $child->linkRel() }}" @endif
                    @class([
                        'block rounded-md px-3 py-2 text-sm hover:bg-bg-soft',
                        'text-primary-green font-semibold' => $child->isActive(),
                        'text-text-secondary' => ! $child->isActive(),
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
            'block rounded-md px-3 py-2.5 hover:bg-bg-soft',
            'bg-bg-soft text-primary-green font-semibold' => $isActive,
            'text-text-main' => ! $isActive,
        ])
    >
        {{ $item->label }}
    </a>
@endif
