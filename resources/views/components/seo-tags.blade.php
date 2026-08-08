@props([
    'model' => null,
    'title' => null,
    'description' => null,
    'type' => null,
])

@php
    $seo = app(App\Services\Seo\SeoTagService::class)->resolve($model, [
        'title' => $title,
        'description' => $description,
        'type' => $type,
    ]);
@endphp

{{--
    Canonical + Open Graph + Twitter card. Deliberately does NOT emit
    hreflang/x-default — those are rendered once in layouts/public.blade.php
    from route_in_locale(), and duplicating them here would produce
    conflicting alternates. og:locale below is the social-network
    equivalent and is a different tag entirely, so the two coexist.
--}}
<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">

<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:type" content="{{ $seo['og_type'] }}">
<meta property="og:url" content="{{ $seo['og_url'] }}">
<meta property="og:locale" content="{{ $seo['og_locale'] }}">
<meta property="og:locale:alternate" content="{{ $seo['og_locale_alternate'] }}">
@if ($seo['og_image'])
    <meta property="og:image" content="{{ $seo['og_image'] }}">
@endif

<meta name="twitter:card" content="{{ $seo['og_image'] ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
@if ($seo['og_image'])
    <meta name="twitter:image" content="{{ $seo['og_image'] }}">
@endif
