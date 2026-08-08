<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the security response headers the app was shipping without.
 *
 * Applied globally to the web group, so it covers the public site, the
 * dashboard and the auth screens alike.
 *
 * The header logic lives in a static apply() because responses generated
 * from EXCEPTIONS — a 404, or the auth redirect a guest gets from
 * /dashboard — are rendered by the exception handler and never bubble
 * back out through this middleware. bootstrap/app.php calls the same
 * method from withExceptions()->respond() so those responses are covered
 * too. Verified: without it, /dashboard (302) and /no-such-page (404)
 * shipped with no security headers at all.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        return self::apply($next($request), $request);
    }

    public static function apply(Response $response, Request $request): Response
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // SAMEORIGIN rather than DENY: the dashboard may legitimately want
        // to preview its own public pages in a frame later. Still blocks
        // third-party framing, which is what clickjacking needs.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Send the full URL to our own origin, but only the origin
        // cross-site — keeps UTM-tagged landing URLs out of third-party
        // referer logs while preserving analytics on our own domain.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Deny the browser features this site never uses. Note geolocation
        // and payment are NOT needed by any current feature; add them back
        // deliberately if that changes.
        $response->headers->set('Permissions-Policy', implode(', ', [
            'accelerometer=()',
            'autoplay=(self)',
            'camera=()',
            'display-capture=()',
            'encrypted-media=()',
            'fullscreen=(self)',
            'geolocation=()',
            'gyroscope=()',
            'magnetometer=()',
            'microphone=()',
            'midi=()',
            'payment=()',
            'usb=()',
        ]));

        // HSTS is only meaningful — and only safe — over HTTPS. Sending it
        // in local http development would pin the browser to https for
        // localhost and break the dev server.
        if ($request->secure() && app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        /*
         * CSP is REPORT-ONLY on purpose — it must not be enforced yet.
         *
         * The site currently relies on things a strict policy would break:
         *   - Alpine evaluates its x-* expressions with new Function(),
         *     which needs 'unsafe-eval'
         *   - inline event handlers and the @stack('scripts') block need
         *     'unsafe-inline'
         *   - Meta Pixel / GTM / GA4 inject scripts from their own CDNs
         *   - fonts come from fonts.bunny.net
         *
         * Report-only lets violations surface in the browser console
         * without breaking anything. TIGHTEN THIS LATER: the real fix is
         * nonce-based script-src plus Alpine's CSP build, at which point
         * 'unsafe-eval' and 'unsafe-inline' can be dropped and the header
         * switched to enforcing Content-Security-Policy.
         */
        $response->headers->set('Content-Security-Policy-Report-Only', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://connect.facebook.net https://www.googletagmanager.com https://www.google-analytics.com https://analytics.tiktok.com",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' https://fonts.bunny.net data:",
            "img-src 'self' data: https:",
            "media-src 'self'",
            "connect-src 'self' https://www.google-analytics.com https://analytics.tiktok.com",
            "frame-src 'self' https://www.googletagmanager.com",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]));

        // Leaks the PHP version to anyone reading response headers.
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
