<?php

namespace App\Services\Dashboard;

use App\Models\Article;
use App\Models\Comment;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\Media;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\Testimonial;

/**
 * Every method here is a live COUNT query against real data — nothing on
 * the dashboard home is hardcoded or fabricated. Kept out of the
 * controller per the project's standing thin-controller rule.
 */
class DashboardStatsService
{
    public function unpublishedArticlesCount(): int
    {
        return Article::where('is_published', false)->count();
    }

    public function inactiveServicesCount(): int
    {
        return Service::where('is_active', false)->count();
    }

    public function inactivePageSectionsCount(): int
    {
        return PageSection::where('is_active', false)->count();
    }

    public function activeServicesCount(): int
    {
        return Service::active()->count();
    }

    public function countriesCount(): int
    {
        return Country::count();
    }

    public function publishedArticlesCount(): int
    {
        return Article::where('is_published', true)->count();
    }

    public function activeFaqsCount(): int
    {
        return Faq::active()->count();
    }

    public function activeTestimonialsCount(): int
    {
        return Testimonial::active()->count();
    }

    public function mediaCount(): int
    {
        return Media::count();
    }

    public function leadsTodayCount(): int
    {
        return Lead::whereDate('created_at', today())->count();
    }

    public function leadsThisWeekCount(): int
    {
        return Lead::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
    }

    public function totalLeadsCount(): int
    {
        return Lead::count();
    }

    public function pendingCommentsCount(): int
    {
        return Comment::pending()->count();
    }
}
