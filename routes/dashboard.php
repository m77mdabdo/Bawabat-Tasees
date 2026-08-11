<?php

use App\Http\Controllers\Dashboard\ArticleController;
use App\Http\Controllers\Dashboard\CampaignController;
use App\Http\Controllers\Dashboard\CommentController;
use App\Http\Controllers\Dashboard\ContactMessageController;
use App\Http\Controllers\Dashboard\ConversionEventController;
use App\Http\Controllers\Dashboard\CountryController;
use App\Http\Controllers\Dashboard\DashboardHomeController;
use App\Http\Controllers\Dashboard\FaqController;
use App\Http\Controllers\Dashboard\LeadController;
use App\Http\Controllers\Dashboard\LeadSourceController;
use App\Http\Controllers\Dashboard\LocaleController;
use App\Http\Controllers\Dashboard\MediaController;
use App\Http\Controllers\Dashboard\MenuItemController;
use App\Http\Controllers\Dashboard\PageController;
use App\Http\Controllers\Dashboard\PageSectionController;
use App\Http\Controllers\Dashboard\ReportController;
use App\Http\Controllers\Dashboard\ServiceController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\Dashboard\TestimonialController;
use App\Http\Controllers\Dashboard\TrackingSettingController;
use App\Http\Controllers\Dashboard\VisitorSessionController;
use Illuminate\Support\Facades\Route;

// 'throttle:dashboard-writes' is applied to the whole group but only counts
// unsafe methods — the limiter returns Limit::none() for GET/HEAD (see
// AppServiceProvider::configureRateLimiting), so browsing is unrationed and
// every write is capped at 60/minute per admin.
Route::prefix('dashboard')->middleware(['auth', 'admin', 'dashboardlocale', 'throttle:dashboard-writes'])->group(function () {
    Route::get('/', [DashboardHomeController::class, 'index'])->name('dashboard');

    Route::name('dashboard.')->group(function () {
        Route::resource('services', ServiceController::class)->except('show');
        Route::resource('countries', CountryController::class)->except('show');
        Route::resource('faqs', FaqController::class)->except('show');
        Route::resource('testimonials', TestimonialController::class)->except('show');
        Route::resource('articles', ArticleController::class)->except('show');
        Route::resource('campaigns', CampaignController::class);
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::resource('media', MediaController::class)
            ->only(['index', 'store', 'destroy'])
            ->parameters(['media' => 'media']);

        // The four pages this project needs are fixed/seeded, not
        // admin-creatable — only index/edit/update, no create/destroy.
        Route::resource('pages', PageController::class)->only(['index', 'edit', 'update']);
        Route::resource('pages.sections', PageSectionController::class)->except('show');

        // Navigation manager. reorder is POST (it writes) and sits above
        // the resource so "menu/reorder" is not swallowed by
        // "menu/{menu}".
        Route::post('menu/reorder', [MenuItemController::class, 'reorder'])->name('menu.reorder');
        Route::patch('menu/{menu}/visibility', [MenuItemController::class, 'toggleVisibility'])->name('menu.visibility');
        Route::resource('menu', MenuItemController::class)->except('show')->parameters(['menu' => 'menu']);

        // Route name stays dashboard.lead-sources.* — the sidebar links
        // to it, and orphaning that name 500s every dashboard page.
        Route::patch('lead-sources/{leadSource}/active', [LeadSourceController::class, 'toggleActive'])
            ->name('lead-sources.active');
        Route::resource('lead-sources', LeadSourceController::class)
            ->except('show')
            ->parameters(['lead-sources' => 'leadSource']);

        /*
         * Visitor Sessions.
         *
         * ROUTE ORDER MATTERS: the two export routes are registered BEFORE
         * the resource, or "visitor-sessions/export" would be swallowed by
         * "visitor-sessions/{visitorSession}" and Laravel would try to
         * resolve a session with the slug "export".
         *
         * The sidebar links to dashboard.visitor-sessions.index by name, so
         * renaming any of these orphans that link and 500s every dashboard
         * page — the same trap the lead-sources and contact-messages routes
         * carry a note about.
         */
        Route::get('visitor-sessions/export', [VisitorSessionController::class, 'export'])
            ->name('visitor-sessions.export');
        Route::get('visitor-sessions/{visitorSession}/export', [VisitorSessionController::class, 'exportOne'])
            ->name('visitor-sessions.export-one');
        Route::resource('visitor-sessions', VisitorSessionController::class)
            ->only(['index', 'show'])
            ->parameters(['visitor-sessions' => 'visitorSession']);

        Route::resource('leads', LeadController::class)->only(['index', 'show', 'destroy']);

        // Conversions are always logged against a specific lead, so they
        // hang off the lead rather than being a top-level resource.
        Route::post('leads/{lead}/conversions', [ConversionEventController::class, 'store'])
            ->name('leads.conversions.store');
        Route::delete('leads/{lead}/conversions/{conversionEvent}', [ConversionEventController::class, 'destroy'])
            ->name('leads.conversions.destroy');

        Route::resource('comments', CommentController::class)->only(['index', 'destroy']);
        Route::patch('comments/{comment}/approve', [CommentController::class, 'approve'])->name('comments.approve');
        Route::patch('comments/{comment}/reject', [CommentController::class, 'reject'])->name('comments.reject');

        // Site-wide settings: a fixed-key form like tracking-settings,
        // not a CRUD resource — rows are seeded, never created or deleted
        // from the dashboard.
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

        // Single fixed-key settings form, not a list/CRUD resource — only
        // 6 rows exist and are never created/deleted from the dashboard.
        Route::get('tracking-settings', [TrackingSettingController::class, 'edit'])->name('tracking-settings.edit');
        Route::put('tracking-settings', [TrackingSettingController::class, 'update'])->name('tracking-settings.update');

        // Toggles the CURRENT user's own dashboard locale (see
        // SetDashboardLocale) — never accepts a user id.
        Route::patch('locale', [LocaleController::class, 'update'])->name('locale.update');

        // Contact Messages. A focused inbox over the SAME leads table —
        // contact submissions are leads with type = 'contact' (see
        // ContactController@store), not a separate store.
        //
        // The name dashboard.contact-messages.index is unchanged from the
        // placeholder it replaces: the sidebar links to it by name
        // (components/dashboard/sidebar.blade.php:96), so renaming it
        // would 500 every dashboard page.
        Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::patch('contact-messages/{lead}/handled', [ContactMessageController::class, 'handle'])->name('contact-messages.handled');
    });
});
