<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Read by Illuminate\Http\Middleware\TrustProxies, which already sits in
    | the framework's global middleware stack — this file is the whole of the
    | configuration, no middleware registration is needed.
    |
    | WHY THIS EXISTS. Behind a TLS-terminating load balancer or reverse
    | proxy, PHP sees a plain http request; the original scheme only survives
    | in the X-Forwarded-Proto header, which Symfony ignores until the proxy
    | sending it is trusted. Untrusted, two things silently break:
    |
    |   - $request->secure() stays false, so SecurityHeaders never sends
    |     Strict-Transport-Security (it is gated on secure + production).
    |   - route()/url() generate http:// links on an https:// site, including
    |     the canonical tags, the hreflang alternates and sitemap.xml.
    |
    | DEFAULT. Trust nothing, in every environment: an empty or missing
    | TRUSTED_PROXIES means X-Forwarded-* headers are ignored, so no client
    | can forge its IP (rate limits, visitor analytics) or the scheme. A
    | deployment behind a load balancer or reverse proxy sets TRUSTED_PROXIES
    | to that proxy's IPs or CIDRs, comma-separated. '*' (trust whichever
    | machine connects directly) is accepted, but only safe when the
    | application cannot be reached except through the proxy.
    |
    | Local and testing therefore trust nothing either, so no test can pass
    | by forging X-Forwarded-Proto.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
