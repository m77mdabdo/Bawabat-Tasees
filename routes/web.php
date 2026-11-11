<?php

use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\CommentController;
use App\Http\Controllers\Public\ConsultationController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\CountryController;
use App\Http\Controllers\Public\FaqController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\ServiceController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\VisitorBeaconController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketing routes — bilingual, /en/... prefix for English
|--------------------------------------------------------------------------
| Arabic is the default with no URL prefix (/, /services, ...); English
| lives under /en/... (/en, /en/services, ...).
|
| Why not a single optional {locale?} prefix segment: Laravel/Symfony
| route compilation does not support an optional parameter followed by
| required literal segments. Confirmed by direct testing — a route like
| `{locale?}/services` 404s on `/services` once the locale is omitted,
| and only ever matches `/en/services`.
|
| The real fix: every route below is registered TWICE via the same
| closure — once with canonical names and no prefix (Arabic), once with
| an ".en" name suffix and an "en" prefix (English). Blade views call the
| `lroute()` helper (app/helpers.php) instead of the bare `route()`
| helper for any of these public routes; it transparently resolves to
| the "{name}.en" variant when the current locale is English and falls
| straight through to plain route() otherwise — so it's always safe to
| use, including on routes (like dashboard/auth) that have no English
| variant at all.
*/
$registerPublicRoutes = function (string $nameSuffix = ''): void {
    Route::get('/', [HomeController::class, 'index'])->name("home{$nameSuffix}");

    Route::get('/services', [ServiceController::class, 'index'])->name("services.index{$nameSuffix}");
    Route::get('/services/{service:slug}', [ServiceController::class, 'show'])->name("services.show{$nameSuffix}");

    Route::get('/countries', [CountryController::class, 'index'])->name("countries.index{$nameSuffix}");

    Route::get('/faqs', [FaqController::class, 'index'])->name("faqs.index{$nameSuffix}");

    Route::get('/articles', [ArticleController::class, 'index'])->name("articles.index{$nameSuffix}");
    Route::get('/articles/{article:slug}', [ArticleController::class, 'show'])->name("articles.show{$nameSuffix}");
    Route::post('/articles/{article:slug}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name("articles.comments.store{$nameSuffix}");

    Route::get('/about', [PageController::class, 'about'])->name("pages.about{$nameSuffix}");
    Route::get('/why-invest', [PageController::class, 'whyInvest'])->name("pages.why-invest{$nameSuffix}");
    Route::get('/formation-process', [PageController::class, 'formationProcess'])->name("pages.formation-process{$nameSuffix}");
    Route::get('/requirements', [PageController::class, 'requirements'])->name("pages.requirements{$nameSuffix}");
    Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name("pages.privacy-policy{$nameSuffix}");
    Route::get('/terms-and-conditions', [PageController::class, 'termsAndConditions'])->name("pages.terms-and-conditions{$nameSuffix}");

    Route::get('/consultation', [ConsultationController::class, 'create'])->name("consultation{$nameSuffix}");
    Route::post('/consultation', [ConsultationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name("consultation.store{$nameSuffix}");

    Route::get('/contact', [ContactController::class, 'create'])->name("contact{$nameSuffix}");
    Route::post('/contact', [ContactController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name("contact.store{$nameSuffix}");
};

// Arabic — canonical names, no prefix.
//
// 'trackvisitor' records the visitor journey. It is on the PUBLIC groups
// only: an admin browsing the site from the dashboard is not a visitor, and
// the middleware itself writes nothing until the gate agrees (consent, DNT,
// bot filter, the admin switch — see VisitorTrackingGate).
Route::middleware(['setlocale', 'trackvisitor'])->group(fn () => $registerPublicRoutes());

// English — "{name}.en" names, "en" prefix.
Route::prefix('en')->middleware(['setlocale', 'trackvisitor'])->group(fn () => $registerPublicRoutes('.en'));

/*
 * The staff dashboard is the Filament panel at /dashboard (App\Providers\Filament\DashboardPanelProvider):
 * sign-in, password reset, profile and the emailed sign-in code all live there.
 * The old addresses below keep working so bookmarks and the robots.txt rules stay valid.
 */
Route::redirect('login', 'dashboard/login')->name('login');
Route::redirect('profile', 'dashboard/profile');

// Switches the signed-in staff member's dashboard language (Arabic / English).
Route::post('dashboard/locale', function (Request $request) {
    $user = $request->user();
    $user->forceFill(['locale' => $user->dashboardLocale() === 'ar' ? 'en' : 'ar'])->save();

    return back();
})->middleware(['auth', 'throttle:dashboard-writes'])->name('dashboard.locale');

/*
 * The time-on-page beacon.
 *
 * Registered ONCE, outside the dual-locale closure: it is an endpoint, not
 * a page, so it has no Arabic and English variants to keep in step.
 *
 * Deliberately NOT in the 'trackvisitor' group — it is a POST, so the
 * middleware would skip it anyway, but keeping it out makes that
 * independent of the middleware's internals.
 *
 * Throttled hard. A beacon fires a few times per page at most, so 60/minute
 * is far above real use and well below what would let someone inflate a
 * session's duration by hammering it.
 */
Route::post('_visitor/beacon', VisitorBeaconController::class)
    ->middleware('throttle:60,1')
    ->name('visitor.beacon');

// Single document listing BOTH locales, so it is registered once rather
// than through the dual-locale closure above.
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

// Served from a route rather than public/robots.txt so the Sitemap line
// is an ABSOLUTE url — the sitemaps.org spec requires it, and a static
// file cannot know the deployed domain.
Route::get('robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Disallow: /dashboard',
        'Disallow: /login',
        'Disallow: /forgot-password',
        'Disallow: /reset-password',
        'Disallow: /confirm-password',
        'Disallow: /verify-email',
        'Disallow: /profile',
        'Disallow: /_visitor',
        '',
        'Sitemap: '.route('sitemap'),
    ];

    return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain');
})->name('robots');
