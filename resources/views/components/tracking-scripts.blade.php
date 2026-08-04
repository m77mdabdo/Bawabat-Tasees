@php
    /**
     * Every snippet below is conditional on its own row being both
     * is_active AND non-empty — a platform with no ID entered, or one
     * that's entered but toggled off, renders nothing at all. IDs are
     * never hardcoded here; they only ever come from the tracking_settings
     * table, edited via the dashboard's Tracking Settings screen.
     *
     * Every script tag loads asynchronously (async / dynamically-inserted
     * <script> elements), matching each platform's own recommended
     * snippet — none of this blocks page rendering.
     */
    $tracking = \App\Models\TrackingSetting::query()
        ->where('is_active', true)
        ->whereNotNull('value')
        ->where('value', '!=', '')
        ->get()
        ->keyBy('key');

    $metaPixel = $tracking->get('meta_pixel_id');
    $gtm = $tracking->get('gtm_container_id');
    $gtagTargets = collect([$tracking->get('ga4_measurement_id'), $tracking->get('google_ads_conversion_id')])->filter();
    $tiktok = $tracking->get('tiktok_pixel_id');
@endphp
@if ($metaPixel)
    {{-- Meta (Facebook) Pixel — official base snippet, fires PageView automatically. --}}
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $metaPixel->value }}');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none" alt=""
            src="https://www.facebook.com/tr?id={{ $metaPixel->value }}&ev=PageView&noscript=1">
    </noscript>
@endif

@if ($gtm)
    {{-- Google Tag Manager --}}
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtm->value }}');
    </script>
@endif

@if ($gtagTargets->isNotEmpty())
    {{-- Google Analytics 4 / Google Ads — one shared gtag.js loader, one
         gtag('config', ...) call per active ID, so both can be active at
         once without loading the loader script twice. --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gtagTargets->first()->value }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        @foreach ($gtagTargets as $target)
            gtag('config', '{{ $target->value }}');
        @endforeach
    </script>
@endif

@if ($tiktok)
    {{-- TikTok Pixel — official base snippet, fires page view automatically. --}}
    <script>
        !function (w, d, t) {
            w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<e.length;n++)ttq.setAndDefer(e,e[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
            ttq.load('{{ $tiktok->value }}');
            ttq.page();
        }(window, document, 'ttq');
    </script>
@endif
