@props(['href', 'active' => false])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => $active
        ? 'flex items-center gap-3 rounded-lg px-4 py-2.5 text-sm font-medium bg-white/10 text-light-gold'
        : 'flex items-center gap-3 rounded-lg px-4 py-2.5 text-sm font-medium text-white/75 transition hover:bg-white/5 hover:text-white'
    ]) }}
>
    {{ $slot }}
</a>
