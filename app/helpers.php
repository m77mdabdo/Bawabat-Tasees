<?php

use Illuminate\Support\Facades\Route;

if (! function_exists('lroute')) {
    /**
     * Locale-aware route(). Every public route is registered twice (see
     * routes/web.php) — canonical names for Arabic, "{name}.en" for
     * English. This resolves to whichever variant matches the CURRENT
     * locale automatically, so Blade views never need an if/else around
     * a plain route() call. Falls straight through to the given name
     * unchanged if no ".en" variant is registered for it (e.g. dashboard
     * and auth routes, which are single-locale) — always safe to use.
     */
    function lroute(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        if (app()->getLocale() === 'en' && ! str_ends_with($name, '.en') && Route::has("{$name}.en")) {
            return route("{$name}.en", $parameters, $absolute);
        }

        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('current_route_base_name')) {
    /**
     * The current route's name with any ".en" suffix stripped — e.g.
     * "services.show.en" and "services.show" both return "services.show".
     */
    function current_route_base_name(): ?string
    {
        $name = Route::currentRouteName();

        if (! $name) {
            return null;
        }

        return str_ends_with($name, '.en') ? substr($name, 0, -3) : $name;
    }
}

if (! function_exists('route_in_locale')) {
    /**
     * Builds the URL for the CURRENT page in the given locale, preserving
     * every route parameter (e.g. a service/article slug) — the basis
     * for both the navbar language toggle and the <head> hreflang tags.
     * Returns null if the current request isn't on one of the
     * dual-registered public routes (e.g. dashboard/auth pages, which
     * have no English variant and no hreflang tags).
     */
    function route_in_locale(string $locale): ?string
    {
        $currentRoute = request()->route();
        $baseName = current_route_base_name();

        if (! $currentRoute || ! $baseName) {
            return null;
        }

        $name = $locale === 'en' ? "{$baseName}.en" : $baseName;

        if (! Route::has($name)) {
            return null;
        }

        return route($name, $currentRoute->parameters());
    }
}
