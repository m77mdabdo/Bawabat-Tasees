<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\CommentController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\ConsultationController;
use App\Http\Controllers\Public\CountryController;
use App\Http\Controllers\Public\FaqController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketing routes — bilingual, /en/... prefix for English
|--------------------------------------------------------------------------
| Arabic is the default with no URL prefix (/, /services, ...); English
| lives under /en/... (/en, /en/services, ...).
|
| docs/decisions/00-technical-decisions.md, which this task's own
| instructions pointed to for "the exact original decision", does not
| exist anywhere in this project (confirmed — no such file was ever
| created in any earlier task). This structure follows this task's own
| explicit, repeated specification instead — see TASKS.md for the full
| note, including why a plain optional {locale?} prefix segment doesn't
| work here: Laravel/Symfony route compilation does not support an
| optional parameter followed by required literal segments (confirmed by
| direct testing — a route like `{locale?}/services` 404s on `/services`
| once locale is omitted, only ever matching `/en/services`).
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
Route::middleware('setlocale')->group(fn () => $registerPublicRoutes());

// English — "{name}.en" names, "en" prefix.
Route::prefix('en')->middleware('setlocale')->group(fn () => $registerPublicRoutes('.en'));

Route::middleware(['auth', 'dashboardlocale'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/dashboard.php';
