<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetDashboardLocale;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackVisitorSession;
use App\Services\Analytics\VisitorTrackingGate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global: every web response gets the security headers, public
        // site and dashboard alike.
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        /*
         * The three first-party analytics cookies are read and written by
         * JavaScript as well as by PHP, so they cannot be encrypted — an
         * encrypted cookie is opaque to the client that has to set it.
         *
         * Safe to except: none of them carries anything sensitive.
         * bts_first_touch / bts_latest_touch hold UTM parameters that were
         * already visible in the URL the visitor arrived on, and bts_consent
         * holds the literal string "granted" or "denied". The session id
         * cookie (bts_session) is deliberately NOT in this list — it is
         * written and read only by PHP, so it keeps the encryption that
         * stops it being forged to an arbitrary value.
         */
        $middleware->encryptCookies(except: [
            'bts_first_touch',
            'bts_latest_touch',
            VisitorTrackingGate::CONSENT_COOKIE,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'setlocale' => SetLocale::class,
            'dashboardlocale' => SetDashboardLocale::class,
            'trackvisitor' => TrackVisitorSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Exception responses (404s, the auth redirect a guest gets from
        // /dashboard) are rendered by the handler and never bubble back
        // out through the middleware stack, so they would otherwise ship
        // with no security headers at all.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            return SecurityHeaders::apply($response, $request);
        });
    })->create();
