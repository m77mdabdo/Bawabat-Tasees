<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\ReportingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportingService $reporting,
    ) {}

    public function index(Request $request): View
    {
        // Whitelisted rather than taken raw — an arbitrary ?days= value
        // would let a URL drive an unbounded date scan.
        $days = ReportingService::normaliseRange($request->query('days'));

        return view('dashboard.reports.index', [
            'days' => $days,
            'ranges' => ReportingService::RANGES,
            'funnel' => $this->reporting->funnel($days),
            'leadsOverTime' => $this->reporting->leadsOverTime($days),
            'leadsByType' => $this->reporting->leadsByType($days),
            'leadsBySource' => $this->reporting->leadsBySourcePlatform($days),
            'leadsByLeadSource' => $this->reporting->leadsByLeadSource($days),
            'revenueByCampaign' => $this->reporting->revenueByCampaign($days),
            'revenueBySource' => $this->reporting->revenueBySource($days),
        ]);
    }
}
