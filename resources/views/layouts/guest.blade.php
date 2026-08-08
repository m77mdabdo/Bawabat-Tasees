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
    <body class="font-sans text-text-main antialiased">
        <div class="relative flex min-h-screen w-full flex-col items-center justify-center overflow-hidden py-6">
            <video
                class="absolute inset-0 h-full w-full object-cover"
                poster="{{ asset('images/login-poster.jpg') }}"
                autoplay
                muted
                loop
                playsinline
            >
                <source src="{{ asset('videos/login-bg.webm') }}" type="video/webm">
                <source src="{{ asset('videos/login-bg.mp4') }}" type="video/mp4">
            </video>

            <div class="absolute inset-0 bg-dark-green/70"></div>

            <div class="relative z-10 flex w-full flex-col items-center px-4">
                {{--
                    Deliberately NOT heavily transparent: the video behind is
                    in motion, and text over a low-opacity surface becomes
                    unreadable as the footage changes. bg-white/95 + a blur
                    lets the footage register at the edges while keeping the
                    form itself at full contrast.
                --}}
                <div class="w-full rounded-2xl border border-white/40 bg-white/95 p-8 shadow-2xl shadow-dark-green/30 backdrop-blur-xl sm:max-w-md sm:p-10">
                    <div class="flex flex-col items-center text-center">
                        <a href="{{ url('/') }}" class="rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2">
                            <x-application-logo class="h-16 w-auto" />
                        </a>
                        <p class="mt-3 text-base font-semibold text-text-main">{{ __('site.brand.name') }}</p>
                    </div>

                    <div class="mt-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
