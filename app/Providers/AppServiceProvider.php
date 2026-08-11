<?php

namespace App\Providers;

use App\Models\MenuItem;
use App\Services\Cms\SettingsRepository;
use App\Services\Crm\ContactMessageService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton so the settings table is read at most once per
        // request, however many callers ask for a value.
        $this->app->singleton(SettingsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->forceHttpsInProduction();
        $this->configureRateLimiting();

        // spatie/laravel-translatable ships no publishable config file in
        // this version (just an in-memory Translatable singleton) — this
        // is the actual equivalent of "set the package's fallback_locale
        // config". Without it, HasTranslations already falls back to
        // config('app.fallback_locale') implicitly (see
        // HasTranslations::getFallbackLocale()), which is already 'ar' —
        // but that's an implicit cascade through an uninitialized
        // property default, not a deliberate setting. Made explicit here
        // so a Service/Page/etc. with no English translation yet shows
        // its Arabic value instead of blank once the new language toggle
        // switches the app locale to 'en'.
        Translatable::fallback(fallbackLocale: config('app.fallback_locale'));

        // The WhatsApp CTA renders on every public page via
        // layouts/public.blade.php, which is used by ~10 different
        // controllers — a View composer avoids passing the same query
        // from every one of them individually.
        //
        // The services list this used to supply was dropped along with the
        // navbar's services dropdown: that query ran on EVERY public page
        // render purely to populate a menu that has since become a plain
        // link to the services index.
        View::composer('layouts.public', function ($view) {
            $settings = app(SettingsRepository::class);

            $view->with([
                'navWhatsapp' => $settings->string('contact_whatsapp'),
                'menu' => $this->publicMenu(),
                'siteName' => $settings->translated('site_name') ?? __('site.brand.name'),
                'siteLogo' => $settings->string('site_logo'),
                'socialLinks' => $this->socialLinks($settings),
                // Drives whether the cookie banner renders at all. Read off
                // the same already-loaded settings collection as everything
                // above it, so it costs no extra query.
                'visitorTrackingEnabled' => $settings->boolean('visitor_sessions_enabled', true),
            ]);
        });

        // The unread badge on the Contact Messages sidebar item. Composed
        // onto the sidebar rather than passed from each controller,
        // because every dashboard section renders it and none of them
        // otherwise care about leads.
        //
        // One indexed COUNT per dashboard page render
        // (leads_type_is_handled_index).
        View::composer('components.dashboard.sidebar', function ($view) {
            $view->with('unhandledContactMessages', app(ContactMessageService::class)->unhandledCount());
        });
    }

    /**
     * Generate https:// links in production.
     *
     * config/trustedproxy.php is what makes $request->secure() true behind a
     * TLS-terminating load balancer, and that alone is normally enough — the
     * UrlGenerator follows the request's scheme. This is the second half, for
     * the cases where there is no request scheme to follow (artisan-generated
     * URLs, a queue worker) or where the proxy forwards no X-Forwarded-Proto
     * at all.
     *
     * Gated on APP_URL actually being https, so a production deployment that
     * genuinely runs over http is not broken by this. Never applied locally,
     * where forcing https would break the dev server outright.
     */
    private function forceHttpsInProduction(): void
    {
        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Rate limits.
     *
     * The public forms carry an inline `throttle:5,1`; the dashboard had
     * nothing at all, so every admin write — including the transactional menu
     * reorder — was unlimited, and a stolen session had no ceiling.
     *
     * Safe methods return Limit::none() so the limiter can be applied to the
     * whole dashboard group without rationing ordinary browsing: only writes
     * are counted. Keyed on the user id (falling back to IP for the
     * theoretically-unauthenticated case), so two admins never share a
     * bucket.
     *
     * 60/minute is deliberately far above human editing speed — this is a
     * runaway/abuse ceiling, not a usability constraint. The public forms
     * stay at 5/minute because they are unauthenticated.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('dashboard-writes', function (Request $request) {
            if ($request->isMethodSafe()) {
                return Limit::none();
            }

            return Limit::perMinute(60)->by(
                $request->user()?->getAuthIdentifier() ?? $request->ip()
            );
        });
    }

    /**
     * The visible, ordered navbar tree.
     *
     * Two queries total (parents + their children), not one per item.
     * Children are eager-loaded already filtered to visible ones, so a
     * hidden child can never leak into a rendered dropdown.
     */
    private function publicMenu(): Collection
    {
        return MenuItem::query()
            ->topLevel()
            ->visible()
            ->ordered()
            ->with(['visibleChildren'])
            ->get();
    }

    /**
     * Configured social links only, keyed by platform. The settings table
     * holds a row per platform whether or not it is set, so the blanks are
     * dropped here rather than in the view.
     *
     * @return array<string, string>
     */
    private function socialLinks(SettingsRepository $settings): array
    {
        $links = [];

        foreach (['facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok', 'snapchat'] as $platform) {
            $url = $settings->string('social_'.$platform);

            if ($url !== null) {
                $links[$platform] = $url;
            }
        }

        return $links;
    }
}
