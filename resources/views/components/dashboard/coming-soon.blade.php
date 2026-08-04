@props(['title', 'message'])

<div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border-default bg-white px-6 py-20 text-center">
    <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-luxury-gold/10 text-3xl text-luxury-gold" aria-hidden="true">
        🚧
    </span>
    <h2 class="text-xl font-semibold text-text-main">{{ $title }}</h2>
    <p class="mt-2 max-w-md text-sm text-text-secondary">{{ $message }}</p>
</div>
