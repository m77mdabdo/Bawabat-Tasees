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
    <body class="font-sans text-gray-900 antialiased">
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
                <a href="/">
                    <x-application-logo class="w-20 h-20" />
                </a>

                <div class="mt-6 w-full overflow-hidden rounded-lg bg-white px-6 py-4 shadow-lg sm:max-w-md">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
