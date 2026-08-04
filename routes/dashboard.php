<?php

use App\Http\Controllers\Dashboard\ArticleController;
use App\Http\Controllers\Dashboard\CommentController;
use App\Http\Controllers\Dashboard\CountryController;
use App\Http\Controllers\Dashboard\DashboardHomeController;
use App\Http\Controllers\Dashboard\FaqController;
use App\Http\Controllers\Dashboard\LeadController;
use App\Http\Controllers\Dashboard\MediaController;
use App\Http\Controllers\Dashboard\PageController;
use App\Http\Controllers\Dashboard\PageSectionController;
use App\Http\Controllers\Dashboard\ServiceController;
use App\Http\Controllers\Dashboard\TestimonialController;
use App\Http\Controllers\Dashboard\TrackingSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardHomeController::class, 'index'])->name('dashboard');

    Route::name('dashboard.')->group(function () {
        Route::resource('services', ServiceController::class)->except('show');
        Route::resource('countries', CountryController::class)->except('show');
        Route::resource('faqs', FaqController::class)->except('show');
        Route::resource('testimonials', TestimonialController::class)->except('show');
        Route::resource('articles', ArticleController::class)->except('show');
        Route::resource('media', MediaController::class)
            ->only(['index', 'store', 'destroy'])
            ->parameters(['media' => 'media']);

        // The four pages this project needs are fixed/seeded, not
        // admin-creatable — only index/edit/update, no create/destroy.
        Route::resource('pages', PageController::class)->only(['index', 'edit', 'update']);
        Route::resource('pages.sections', PageSectionController::class)->except('show');

        Route::resource('leads', LeadController::class)->only(['index', 'show', 'destroy']);

        Route::resource('comments', CommentController::class)->only(['index', 'destroy']);
        Route::patch('comments/{comment}/approve', [CommentController::class, 'approve'])->name('comments.approve');
        Route::patch('comments/{comment}/reject', [CommentController::class, 'reject'])->name('comments.reject');

        // Single fixed-key settings form, not a list/CRUD resource — only
        // 6 rows exist and are never created/deleted from the dashboard.
        Route::get('tracking-settings', [TrackingSettingController::class, 'edit'])->name('tracking-settings.edit');
        Route::put('tracking-settings', [TrackingSettingController::class, 'update'])->name('tracking-settings.update');

        // "قريبًا" placeholders — none of these sections are built yet.
        // Each renders a clean branded placeholder, never a 404/500, so
        // the sidebar never links to something broken.
        $comingSoon = [
            'campaigns' => __('قسم الحملات التسويقية — قيد التطوير، قريبًا.'),
            'lead-sources' => __('قسم مصادر العملاء — قيد التطوير، قريبًا.'),
            'contact-messages' => __('قسم رسائل التواصل — قيد التطوير، قريبًا.'),
            'reports' => __('قسم التقارير — قيد التطوير، قريبًا.'),
            'settings' => __('قسم الإعدادات — قيد التطوير، قريبًا.'),
        ];

        $comingSoonTitles = [
            'campaigns' => __('الحملات'),
            'lead-sources' => __('مصادر العملاء'),
            'contact-messages' => __('رسائل التواصل'),
            'reports' => __('التقارير'),
            'settings' => __('الإعدادات'),
        ];

        foreach ($comingSoon as $slug => $message) {
            Route::get($slug, fn () => view('dashboard.coming-soon', [
                'title' => $comingSoonTitles[$slug],
                'message' => $message,
            ]))->name("{$slug}.index");
        }
    });
});
