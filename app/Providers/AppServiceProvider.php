<?php

namespace App\Providers;

use App\Models\MenuItem;
use App\Models\Setting;
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
        //
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
            $view->with([
                'navWhatsapp' => Setting::where('key', 'contact_whatsapp')->value('value'),
                'menu' => $this->publicMenu(),
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
}
