<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardStatsService;
use Illuminate\View\View;

class DashboardHomeController extends Controller
{
    public function __construct(
        private readonly DashboardStatsService $stats
    ) {}

    public function index(): View
    {
        return view('dashboard.home', [
            'unpublishedArticles' => $this->stats->unpublishedArticlesCount(),
            'inactiveServices' => $this->stats->inactiveServicesCount(),
            'inactivePageSections' => $this->stats->inactivePageSectionsCount(),
            'activeServices' => $this->stats->activeServicesCount(),
            'countries' => $this->stats->countriesCount(),
            'publishedArticles' => $this->stats->publishedArticlesCount(),
            'activeFaqs' => $this->stats->activeFaqsCount(),
            'activeTestimonials' => $this->stats->activeTestimonialsCount(),
            'media' => $this->stats->mediaCount(),
            'leadsToday' => $this->stats->leadsTodayCount(),
            'leadsThisWeek' => $this->stats->leadsThisWeekCount(),
            'totalLeads' => $this->stats->totalLeadsCount(),
            'pendingComments' => $this->stats->pendingCommentsCount(),
        ]);
    }
}
