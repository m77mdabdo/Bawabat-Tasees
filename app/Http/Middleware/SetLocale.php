<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * URL-based rather than session-based: the locale belongs in the URL so
 * every page has one canonical address per language, which is what makes
 * the hreflang alternates and the sitemap possible at all. Every public
 * route is registered twice (see
 * routes/web.php): canonical names with no prefix for Arabic, "{name}.en"
 * names under an "en" prefix for English. The route NAME itself is the
 * one unambiguous signal for which locale matched — a route named
 * "services.show.en" is definitionally the English variant regardless of
 * what its URI parameters happen to contain.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = str_ends_with((string) Route::currentRouteName(), '.en') ? 'en' : 'ar';

        app()->setLocale($locale);

        return $next($request);
    }
}
