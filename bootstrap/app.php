<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetDashboardLocale;
use App\Http\Middleware\SetLocale;
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

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'setlocale' => SetLocale::class,
            'dashboardlocale' => SetDashboardLocale::class,
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
