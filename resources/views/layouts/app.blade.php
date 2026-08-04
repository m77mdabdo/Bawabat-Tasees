<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        <link rel="icon" type="image/x-icon" href="{{ asset('images/brand/favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden bg-bg-soft">
            <x-dashboard.sidebar />

            <div class="flex min-w-0 flex-1 flex-col overflow-y-auto">
                <!-- Top bar -->
                <header class="sticky top-0 z-20 flex h-20 shrink-0 items-center justify-between border-b border-border-default bg-white px-4 sm:px-6 lg:px-8">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="text-text-secondary hover:text-text-main lg:hidden"
                        aria-label="{{ __('فتح القائمة') }}"
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <span class="hidden lg:block"></span>

                    <x-dropdown align="left" width="48">
                        <x-slot name="trigger">
                            <button type="button" class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-luxury-gold text-sm font-semibold text-white">
                                    {{ mb_substr(Auth::user()->name, 0, 1) }}
                                </span>
                                <span class="hidden text-sm font-medium text-text-main sm:block">
                                    {{ Auth::user()->name }}
                                </span>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('الملف الشخصي') }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('تسجيل الخروج') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </header>

                <!-- Page Heading -->
                @isset($header)
                    <div class="border-b border-border-default bg-white px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                @endisset

                <!-- Page Content -->
                <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
