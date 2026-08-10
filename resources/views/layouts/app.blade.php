<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('site.brand.name') }}</title>

        <link rel="icon" type="image/x-icon" href="{{ asset('images/brand/favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        {{-- Skip link — see the public layout for the reasoning. z-[60] keeps
             it above the drawer panel (z-40) in every state. --}}
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-[60] focus:rounded-md focus:bg-primary-green focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:outline-none focus:ring-2 focus:ring-white"
        >
            {{ __('dashboard.topbar.skip_to_content') }}
        </a>

        {{--
            The shell's height is set in app.css (100vh with a 100dvh
            override), not with h-screen/h-dvh here: Tailwind emits
            .h-dvh BEFORE .h-screen, so the vh rule would win the
            cascade and the dvh class would do nothing.

            overflow-x-hidden on the scroll pane below is the last line
            of defence against one wide child (a table, a long unbroken
            URL) making the whole dashboard scroll sideways on a phone.
        --}}
        <div x-data="{ sidebarOpen: false }" class="dashboard-shell flex overflow-hidden bg-bg-soft">
            <x-dashboard.sidebar />

            <div class="flex min-w-0 flex-1 flex-col overflow-y-auto overflow-x-hidden">
                <!-- Top bar -->
                <header class="sticky top-0 z-20 flex h-20 shrink-0 items-center border-b border-border-default bg-white px-4 sm:px-6 lg:px-8">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="-ms-2 inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-md text-text-secondary transition hover:text-text-main focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green lg:hidden"
                        aria-label="{{ __('dashboard.topbar.open_menu') }}"
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    {{--
                        `ms-auto` (margin-inline-start: auto), not
                        `justify-between` on the header — the hamburger
                        button above is `lg:hidden`, so at desktop
                        widths it is removed from the box tree entirely
                        and `justify-between` would have exactly one
                        flex item left, which collapses to flex-start
                        per the CSS spec (the wrong side, and worse,
                        the SAME physical side regardless of `dir`).
                        `ms-auto` pushes this cluster to the logical
                        end unconditionally, independent of the
                        hamburger's visibility: end-of-row is the LEFT
                        in `dir="rtl"` and the RIGHT in `dir="ltr"`.
                    --}}
                    <div class="ms-auto flex shrink-0 items-center gap-2 sm:gap-4">
                        <form method="POST" action="{{ route('dashboard.locale.update') }}">
                            @csrf
                            @method('PATCH')
                            <button
                                type="submit"
                                class="inline-flex min-h-[44px] items-center rounded-md border border-border-default px-3 text-sm font-medium text-text-secondary transition hover:border-luxury-gold hover:text-text-main focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green"
                            >
                                {{ app()->getLocale() === 'ar' ? __('dashboard.topbar.switch_to_english') : __('dashboard.topbar.switch_to_arabic') }}
                            </button>
                        </form>

                        {{--
                            align="right" (end-0), NOT "left". This
                            trigger sits at the row's inline END, so
                            pinning the panel's start edge to it throws
                            the 12rem panel off-screen at 375px — off
                            the right in `ltr`, off the left in `rtl`.
                            end-0 anchors it to the same edge as the
                            trigger, so it opens inward in both
                            directions.
                        --}}
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                {{-- `open` comes from x-dropdown's own x-data, which this
                                     slot renders inside — so the state is announced on the
                                     button that owns it rather than on a wrapper div. --}}
                                <button
                                    type="button"
                                    :aria-expanded="open ? 'true' : 'false'"
                                    aria-haspopup="true"
                                    class="flex min-h-[44px] max-w-[12rem] items-center gap-3 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green"
                                >
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gold-deep text-sm font-semibold text-white">
                                        {{ mb_substr(Auth::user()->name, 0, 1) }}
                                    </span>
                                    <span class="hidden truncate text-sm font-medium text-text-main sm:block">
                                        {{ Auth::user()->name }}
                                    </span>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('dashboard.topbar.profile') }}
                                </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        {{ __('dashboard.auth.logout') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                <!-- Page Heading -->
                @isset($header)
                    <div class="min-w-0 border-b border-border-default bg-white px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                @endisset

                <!-- Page Content -->
                {{-- tabindex="-1" so the skip link can move focus here, not
                     just scroll to it. --}}
                <main id="main-content" tabindex="-1" class="min-w-0 flex-1 px-4 py-8 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
        {{-- Page-specific scripts (e.g. the navigation reorder helper).
             Pushed before Alpine initialises so x-data can resolve the
             function it names. --}}
        @stack('scripts')
    </body>
</html>
