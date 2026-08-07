<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the dashboard's locale from the authenticated STAFF member's
 * own `locale` column — completely independent of the public site's
 * URL-based locale (see SetLocale), which is resolved per-visitor from
 * the route name instead. Applied only to auth-gated routes (dashboard,
 * profile, post-login auth screens) — there is no user record to read
 * from on pre-login pages (login, forgot-password), so those stay on
 * the app's default locale ('ar').
 */
class SetDashboardLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? 'ar';

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
