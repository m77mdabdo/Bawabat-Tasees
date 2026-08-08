<?php

namespace App\Providers;

use App\Models\MenuItem;
use App\Services\Cms\SettingsRepository;
use Illuminate\Support\Collection;
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
            ]);
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
