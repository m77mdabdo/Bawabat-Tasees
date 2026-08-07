<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? __('site.brand.name') }}</title>

        <link rel="icon" type="image/x-icon" href="{{ asset('images/brand/favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">

        {{--
            hreflang alternates — only present when the current route has
            a dual-registered locale variant (see app/helpers.php); every
            public marketing route does, so this covers the whole site.
            x-default points at the Arabic (no-prefix) URL since that's
            this site's actual default for a visitor with no language
            preference signalled.
        --}}
        @if ($hreflangAr = route_in_locale('ar'))
            <link rel="alternate" hreflang="ar" href="{{ $hreflangAr }}">
            <link rel="alternate" hreflang="x-default" href="{{ $hreflangAr }}">
        @endif
        @if ($hreflangEn = route_in_locale('en'))
            <link rel="alternate" hreflang="en" href="{{ $hreflangEn }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <x-tracking-scripts />
    </head>
    <body class="font-sans text-text-main antialiased">
        <header
            x-data="{ scrolled: false, mobileOpen: false, servicesOpen: false }"
            @scroll.window="scrolled = window.scrollY > 40"
            :class="scrolled ? 'shadow-md border-border-default' : 'border-transparent'"
            class="sticky top-0 z-30 border-b bg-white transition-shadow"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-20 items-center justify-between">
                    {{-- Logo enlarged from h-10 (40px) to h-14 (56px, +40%) —
                         still comfortably clear of the h-20 (80px) header's
                         edges (12px above/below) at every breakpoint. --}}
                    <a href="{{ lroute('home') }}" class="shrink-0">
                        <img
                            src="{{ asset('images/brand/logo-full-color-256.png') }}"
                            alt="{{ __('site.brand.name') }}"
                            class="hidden sm:block h-14 w-auto"
                        >
                        <img
                            src="{{ asset('images/brand/logo-icon-color-128.png') }}"
                            alt="{{ __('site.brand.name') }}"
                            class="block sm:hidden h-14 w-auto"
                        >
                    </a>

                    <nav class="hidden items-center gap-6 text-sm font-medium lg:flex">
                        <a href="{{ lroute('home') }}" class="text-text-main hover:text-primary-green">
                            {{ __('site.nav.home') }}
                        </a>

                        <div class="relative" @mouseenter="servicesOpen = true" @mouseleave="servicesOpen = false">
                            <button
                                type="button"
                                @click="servicesOpen = ! servicesOpen"
                                class="flex items-center gap-1 text-text-main hover:text-primary-green"
                            >
                                {{ __('site.nav.services') }}
                                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': servicesOpen }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </button>

                            <div
                                x-show="servicesOpen"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="absolute start-0 top-full z-40 mt-2 w-64 rounded-lg border border-border-default bg-white py-2 shadow-lg"
                                style="display: none;"
                            >
                                @forelse ($navServices as $navService)
                                    <a
                                        href="{{ lroute('services.show', $navService) }}"
                                        class="block px-4 py-2 text-sm text-text-main hover:bg-bg-soft hover:text-primary-green"
                                    >
                                        {{ $navService->name }}
                                    </a>
                                @empty
                                    <p class="px-4 py-2 text-sm text-text-secondary">
                                        {{ __('site.nav.no_services') }}
                                    </p>
                                @endforelse

                                <div class="mt-1 border-t border-border-default pt-1">
                                    <a href="{{ lroute('services.index') }}" class="block px-4 py-2 text-sm font-semibold text-primary-green hover:bg-bg-soft">
                                        {{ __('site.nav.all_services') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <a href="{{ lroute('countries.index') }}" class="text-text-main hover:text-primary-green">
                            {{ __('site.nav.countries') }}
                        </a>
                        <a href="{{ lroute('articles.index') }}" class="text-text-main hover:text-primary-green">
                            {{ __('site.nav.blog') }}
                        </a>
                        <a href="{{ lroute('pages.about') }}" class="text-text-main hover:text-primary-green">
                            {{ __('site.nav.about') }}
                        </a>
                        <a href="{{ lroute('contact') }}" class="text-text-main hover:text-primary-green">
                            {{ __('site.nav.contact') }}
                        </a>
                    </nav>

                    <div class="flex items-center gap-4">
                        {{-- Language toggle: preserves the current page —
                             see route_in_locale() in app/helpers.php, which
                             resolves the SAME route (with the same
                             parameters, e.g. a service slug) in the other
                             locale. Falls back to the locale's homepage
                             only if the current route has no dual-registered
                             variant (shouldn't happen for any public page). --}}
                        <a
                            href="{{ route_in_locale(app()->getLocale() === 'ar' ? 'en' : 'ar') ?? lroute('home') }}"
                            class="inline-flex items-center rounded-md border border-border-default px-3 py-1.5 text-sm font-medium text-text-main transition hover:border-primary-green hover:text-primary-green"
                        >
                            {{ app()->getLocale() === 'ar' ? __('site.nav.switch_to_english') : __('site.nav.switch_to_arabic') }}
                        </a>

                        <button
                            type="button"
                            @click="mobileOpen = true"
                            class="text-text-main hover:text-primary-green lg:hidden"
                            aria-label="{{ __('site.nav.open_menu') }}"
                        >
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile drawer -->
            <div
                x-show="mobileOpen"
                x-transition:enter="transition-opacity ease-linear duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="mobileOpen = false"
                class="fixed inset-0 z-40 bg-black/40 lg:hidden"
                style="display: none;"
            ></div>

            <div
                x-show="mobileOpen"
                :class="mobileOpen ? 'translate-x-0' : 'translate-x-full'"
                class="fixed inset-y-0 right-0 z-50 flex w-72 flex-col overflow-y-auto bg-white transition-transform duration-200 ease-in-out lg:hidden"
                style="display: none;"
            >
                <div class="flex h-20 shrink-0 items-center justify-between border-b border-border-default px-6">
                    <img src="{{ asset('images/brand/logo-full-color-256.png') }}" alt="{{ __('site.brand.name') }}" class="h-9 w-auto">
                    <button type="button" @click="mobileOpen = false" class="text-text-secondary hover:text-text-main" aria-label="{{ __('site.nav.close_menu') }}">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 space-y-1 px-4 py-6 text-sm font-medium">
                    <a
                        href="{{ route_in_locale(app()->getLocale() === 'ar' ? 'en' : 'ar') ?? lroute('home') }}"
                        class="mb-4 flex items-center justify-center rounded-md border border-border-default px-5 py-2.5 font-semibold text-text-main hover:border-primary-green hover:text-primary-green"
                    >
                        {{ app()->getLocale() === 'ar' ? __('site.nav.switch_to_english') : __('site.nav.switch_to_arabic') }}
                    </a>

                    <a href="{{ lroute('home') }}" class="block rounded-md px-3 py-2.5 text-text-main hover:bg-bg-soft">{{ __('site.nav.home') }}</a>
                    <a href="{{ lroute('services.index') }}" class="block rounded-md px-3 py-2.5 text-text-main hover:bg-bg-soft">{{ __('site.nav.services') }}</a>
                    <a href="{{ lroute('countries.index') }}" class="block rounded-md px-3 py-2.5 text-text-main hover:bg-bg-soft">{{ __('site.nav.countries') }}</a>
                    <a href="{{ lroute('articles.index') }}" class="block rounded-md px-3 py-2.5 text-text-main hover:bg-bg-soft">{{ __('site.nav.blog') }}</a>
                    <a href="{{ lroute('pages.about') }}" class="block rounded-md px-3 py-2.5 text-text-main hover:bg-bg-soft">{{ __('site.nav.about') }}</a>
                    <a href="{{ lroute('contact') }}" class="block rounded-md px-3 py-2.5 text-text-main hover:bg-bg-soft">{{ __('site.nav.contact') }}</a>
                </nav>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        {{--
            pb-24 (not py-12's plain pb-12) on this specific edge — the
            floating WhatsApp button is `position: fixed` at bottom-6
            left-6 or a 56px-tall circle, so it always sits over
            whatever is at the true bottom of the viewport once a
            visitor scrolls to the end of any page. The extra bottom
            padding here guarantees the copyright line (the last, and
            without this fix the bottom-most-left, piece of content on
            every public page) always has clearance above the button
            instead of sitting behind it.
        --}}
        <footer class="bg-dark-green text-white">
            <div class="max-w-7xl mx-auto px-4 pb-24 pt-12 sm:px-6 lg:px-8">
                <img
                    src="{{ asset('images/brand/logo-full-white-256.png') }}"
                    alt="{{ __('site.brand.name') }}"
                    class="h-10 w-auto mb-4"
                >
                <p class="text-sm text-white/70">
                    &copy; {{ now()->year }} {{ __('site.brand.name') }}. {{ __('site.footer.rights') }}
                </p>

                <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <a href="{{ lroute('pages.privacy-policy') }}" class="text-white/70 underline hover:text-white">
                        {{ __('site.footer.privacy_link') }}
                    </a>
                    <a href="{{ lroute('pages.terms-and-conditions') }}" class="text-white/70 underline hover:text-white">
                        {{ __('site.footer.terms_link') }}
                    </a>
                </div>
            </div>
        </footer>

        <x-whatsapp-float-button :number="$navWhatsapp" />
    </body>
</html>
