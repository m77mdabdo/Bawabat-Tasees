{{-- Same visual language as x-auth.link, but a real <button> — used where
     the action is a form submit (logout) and an <a> would be wrong
     semantically and unreachable without JS. --}}
<button
    {{ $attributes->merge([
        'type' => 'submit',
        'class' => 'rounded text-sm font-medium text-luxury-gold underline-offset-4 transition hover:text-primary-green hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2',
    ]) }}
>
    {{ $slot }}
</button>
