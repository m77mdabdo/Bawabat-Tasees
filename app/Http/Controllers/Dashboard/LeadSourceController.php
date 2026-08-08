<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreLeadSourceRequest;
use App\Http\Requests\Dashboard\UpdateLeadSourceRequest;
use App\Models\LeadSource;
use App\Services\Marketing\LeadSourceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeadSourceController extends Controller
{
    public function __construct(
        private readonly LeadSourceService $leadSourceService,
    ) {}

    public function index(): View
    {
        return view('dashboard.lead-sources.index', [
            'sources' => LeadSource::ordered()->get(),
            // One grouped query for every count, not one per row.
            'leadCounts' => $this->leadSourceService->leadCounts(),
            'unrecognised' => $this->leadSourceService->unrecognisedSources(),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.lead-sources.create');
    }

    public function store(StoreLeadSourceRequest $request): RedirectResponse
    {
        $this->leadSourceService->create($request->validated());

        return redirect()
            ->route('dashboard.lead-sources.index')
            ->with('status', __('dashboard.flash.lead_source_created'));
    }

    public function edit(LeadSource $leadSource): View
    {
        return view('dashboard.lead-sources.edit', [
            'source' => $leadSource,
            'leadsCount' => $leadSource->leadsCount(),
        ]);
    }

    public function update(UpdateLeadSourceRequest $request, LeadSource $leadSource): RedirectResponse
    {
        $this->leadSourceService->update($leadSource, $request->validated());

        return redirect()
            ->route('dashboard.lead-sources.index')
            ->with('status', __('dashboard.flash.lead_source_updated'));
    }

    public function destroy(LeadSource $leadSource): RedirectResponse
    {
        $this->leadSourceService->delete($leadSource);

        return redirect()
            ->route('dashboard.lead-sources.index')
            ->with('status', __('dashboard.flash.lead_source_deleted'));
    }

    public function toggleActive(LeadSource $leadSource): RedirectResponse
    {
        $this->leadSourceService->toggleActive($leadSource);

        return redirect()
            ->route('dashboard.lead-sources.index')
            ->with('status', __('dashboard.flash.lead_source_updated'));
    }
}
