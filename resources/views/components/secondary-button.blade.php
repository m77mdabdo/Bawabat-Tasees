<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-md border border-border-default bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-text-main shadow-sm transition duration-150 ease-in-out hover:bg-bg-soft focus:outline-none focus:ring-2 focus:ring-primary-green focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
