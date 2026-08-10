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
    | DEFAULT. Null (trust nothing) everywhere except production, where it is
    | '*' — trust whatever proxy is directly in front of us. '*' is safe in
    | the normal deployment shape, where the application is only reachable
    | through the load balancer: TrustProxies resolves '*' to the calling
    | REMOTE_ADDR, not to "anybody", so a client cannot forge headers unless
    | it can talk to the origin directly. If the app IS directly reachable,
    | set TRUSTED_PROXIES to the load balancer's actual IPs or CIDRs
    | (comma-separated) instead of leaving it on '*'.
    |
    | Local and testing deliberately trust nothing, so no test can pass by
    | forging X-Forwarded-Proto.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', env('APP_ENV') === 'production' ? '*' : null),

];
