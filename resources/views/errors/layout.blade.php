{{--
    Standalone shell, deliberately NOT extending layouts/public.blade.php:
    that layout's view composer queries menu_items and settings, and an
    error page must render even when the database is unreachable — which
    is exactly the situation a 500 often signals.
--}}
@php
    // No route matched, so the `setlocale` middleware never ran and the
    // app locale is still the boot-time default. Derive it from the path
    // instead, otherwise an English visitor hitting a bad /en/ URL gets
    // an Arabic error page.
    $locale = request()->is('en', 'en/*') ? 'en' : app()->getLocale();
    $home = $locale === 'en' ? url('/en') : url('/');
    $contact = $locale === 'en' ? url('/en/contact') : url('/contact');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">

        <title>{{ $title }} — {{ __('site.brand.name', [], $locale) }}</title>

        <link rel="icon" type="image/x-icon" href="{{ asset('images/brand/favicon.ico') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css'])
    </head>
    <body class="font-sans text-text-main antialiased">
        <main class="flex min-h-screen flex-col items-center justify-center bg-bg-soft px-6 py-16 text-center">
            <a href="{{ $home }}" class="rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2">
                <img
                    src="{{ asset('images/brand/logo-full-color-256.png') }}"
                    alt="{{ __('site.brand.name', [], $locale) }}"
                    width="256"
                    height="236"
                    class="mx-auto h-14 w-auto"
                >
            </a>

            <p class="mt-10 text-6xl font-bold tracking-tight text-primary-green sm:text-7xl">{{ $code }}</p>

            <h1 class="mt-4 text-2xl font-semibold text-text-main sm:text-3xl">{{ $title }}</h1>

            <p class="mt-3 max-w-md text-sm leading-relaxed text-text-secondary">{{ $message }}</p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a
                    href="{{ $home }}"
                    class="inline-flex items-center rounded-xl bg-primary-green px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-dark-green focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2"
                >
                    {{ __('site.errors.back_home', [], $locale) }}
                </a>

                <a
                    href="{{ $contact }}"
                    class="inline-flex items-center rounded-xl border border-border-default bg-white px-5 py-3 text-sm font-semibold text-text-main transition hover:border-primary-green hover:text-primary-green focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2"
                >
                    {{ __('site.errors.contact_us', [], $locale) }}
                </a>
            </div>
        </main>
    </body>
</html>
