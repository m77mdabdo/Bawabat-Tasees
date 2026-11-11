<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The dashboard speaks each staff member's own language (users.locale), independent of the
 * public site's URL locale. Guests on the sign-in screens get Arabic, the site's default.
 */
class SetDashboardLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->dashboardLocale() ?? $request->session()->get('dashboard_locale', 'ar');

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
