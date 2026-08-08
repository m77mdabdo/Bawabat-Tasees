<button
    {{ $attributes->merge([
        'type' => 'submit',
        'class' => 'inline-flex w-full items-center justify-center rounded-xl bg-primary-green px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-dark-green focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2 active:scale-[.99]',
    ]) }}
>
    {{ $slot }}
</button>
