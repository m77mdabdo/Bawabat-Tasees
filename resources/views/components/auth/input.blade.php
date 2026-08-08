@props([
    'id',
    'name',
    'type' => 'text',
    'icon' => null,          // 'email' | 'lock' | null
    'value' => null,
    'hasError' => false,
])

@php
    // A password field renders as a small Alpine island so the show/hide
    // toggle needs no global state and no extra dependency.
    $isPassword = $type === 'password';
@endphp

<div class="relative" @if ($isPassword) x-data="{ show: false }" @endif>
    @if ($icon)
        {{-- start-0 / ps-11 rather than left/pl: the icon must sit on the
             leading edge, which flips under RTL. --}}
        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5 text-text-secondary" aria-hidden="true">
            @if ($icon === 'email')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <rect x="3" y="5" width="18" height="14" rx="2" />
                    <path d="M3 7l9 6 9-6" />
                </svg>
            @elseif ($icon === 'lock')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <rect x="4" y="10" width="16" height="11" rx="2" />
                    <path d="M8 10V7a4 4 0 118 0v3" />
                </svg>
            @endif
        </span>
    @endif

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($isPassword)
            :type="show ? 'text' : 'password'"
            type="password"
        @else
            type="{{ $type }}"
        @endif
        value="{{ $value }}"
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->merge([
            'class' => 'block w-full rounded-xl border bg-white/80 py-3 text-sm text-text-main placeholder:text-text-secondary/70 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-primary-green focus:border-primary-green '
                .($icon ? 'ps-11 ' : 'ps-4 ')
                .($isPassword ? 'pe-11 ' : 'pe-4 ')
                .($hasError ? 'border-red-400 focus:ring-red-500 focus:border-red-500' : 'border-border-default'),
        ]) }}
    >

    @if ($isPassword)
        <button
            type="button"
            @click="show = ! show"
            :aria-label="show ? '{{ __('dashboard.auth.hide_password') }}' : '{{ __('dashboard.auth.show_password') }}'"
            :aria-pressed="show.toString()"
            class="absolute inset-y-0 end-0 flex items-center pe-3.5 text-text-secondary transition hover:text-primary-green focus:outline-none focus-visible:text-primary-green"
        >
            {{-- Eye / eye-off, swapped by the same Alpine flag. --}}
            <svg x-show="! show" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            <svg x-show="show" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path d="M2.5 12S6 5.5 12 5.5c1.6 0 3 .45 4.2 1.1M21.5 12S18 18.5 12 18.5c-1.6 0-3-.45-4.2-1.1" />
                <path d="M4 4l16 16" />
            </svg>
        </button>
    @endif
</div>
