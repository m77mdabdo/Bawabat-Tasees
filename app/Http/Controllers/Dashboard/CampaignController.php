<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreCampaignRequest;
use App\Http\Requests\Dashboard\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\ConversionEvent;
use App\Services\Marketing\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService,
    ) {}

    public function index(): View
    {
        // withCount/withSum keep the index to a single query — the list
        // needs per-campaign lead and revenue totals, which would
        // otherwise be two queries per row.
        $campaigns = Campaign::query()
            ->withCount('leads')
            ->withSum(['conversionEvents as conversion_value_sum' => fn ($query) => $query->whereIn('event_type', ConversionEvent::WON_TYPES)], 'value')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20);

        return view('dashboard.campaigns.index', ['campaigns' => $campaigns]);
    }

    public function create(): View
    {
        return view('dashboard.campaigns.create');
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $this->campaignService->create($request->validated());

        return redirect()
            ->route('dashboard.campaigns.index')
            ->with('status', __('dashboard.flash.campaign_created'));
    }

    public function show(Campaign $campaign): View
    {
        $campaign->load([
            'leads' => fn ($query) => $query->with('wonConversionEvents')->latest()->limit(50),
        ]);

        return view('dashboard.campaigns.show', [
            'campaign' => $campaign,
            'leadsCount' => $campaign->leads()->count(),
            'conversionsCount' => $campaign->conversionEvents()->whereIn('event_type', ConversionEvent::WON_TYPES)->count(),
            'conversionValue' => (float) $campaign->conversionEvents()->sum('value'),
        ]);
    }

    public function edit(Campaign $campaign): View
    {
        return view('dashboard.campaigns.edit', ['campaign' => $campaign]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->campaignService->update($campaign, $request->validated());

        return redirect()
            ->route('dashboard.campaigns.index')
            ->with('status', __('dashboard.flash.campaign_updated'));
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->campaignService->delete($campaign);

        return redirect()
            ->route('dashboard.campaigns.index')
            ->with('status', __('dashboard.flash.campaign_deleted'));
    }
}
