<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($entries as $entry)
@foreach (['ar', 'en'] as $locale)
    <url>
        <loc>{{ $entry[$locale] }}</loc>
@if ($entry['lastmod'])
        <lastmod>{{ $entry['lastmod']->toAtomString() }}</lastmod>
@endif
        <changefreq>{{ $entry['changefreq'] }}</changefreq>
        <priority>{{ $entry['priority'] }}</priority>
        {{-- Alternates mirror the hreflang tags each page renders; x-default
             points at Arabic, this site's default for an unsignalled visitor. --}}
        <xhtml:link rel="alternate" hreflang="ar" href="{{ $entry['ar'] }}"/>
        <xhtml:link rel="alternate" hreflang="en" href="{{ $entry['en'] }}"/>
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $entry['ar'] }}"/>
    </url>
@endforeach
@endforeach
</urlset>
