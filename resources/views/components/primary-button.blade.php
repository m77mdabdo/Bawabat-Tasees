<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-md border border-transparent bg-primary-green px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-dark-green focus:bg-dark-green focus:outline-none focus:ring-2 focus:ring-primary-green focus:ring-offset-2 active:bg-dark-green']) }}>
    {{ $slot }}
</button>
