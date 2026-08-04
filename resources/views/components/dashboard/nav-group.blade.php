@props(['label', 'active' => false])

<div x-data="{ open: {{ $active ? 'true' : 'false' }} }">
    <button
        type="button"
        @click="open = ! open"
        class="flex w-full items-center justify-between rounded-lg px-4 py-2.5 text-sm font-medium transition {{ $active ? 'text-light-gold' : 'text-white/75 hover:bg-white/5 hover:text-white' }}"
    >
        <span class="flex items-center gap-3">
            {{ $icon ?? '' }}
            <span>{{ $label }}</span>
        </span>

        <svg
            class="h-4 w-4 shrink-0 transition-transform duration-200"
            :class="{ '-rotate-90': ! open }"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
        >
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="mt-1 space-y-1 ps-4"
    >
        {{ $slot }}
    </div>
</div>
