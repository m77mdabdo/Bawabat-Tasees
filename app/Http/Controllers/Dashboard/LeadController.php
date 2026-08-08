<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $leads = Lead::query()
            // wonConversionEvents drives the "converted" badge; eager
            // loading it keeps the index at two queries instead of one
            // per row.
            ->with(['requestedService', 'wonConversionEvents'])
            ->when(
                $request->input('conversion') === 'converted',
                fn ($query) => $query->converted()
            )
            ->when(
                $request->input('conversion') === 'not_converted',
                fn ($query) => $query->notConverted()
            )
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->when($request->filled('source_platform'), fn ($query) => $query->where('source_platform', $request->input('source_platform')))
            ->when($request->filled('requested_service_id'), fn ($query) => $query->where('requested_service_id', $request->input('requested_service_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('date_to')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.leads.index', [
            'leads' => $leads,
            'services' => Service::orderBy('sort_order')->get(),
            'sourcePlatforms' => Lead::query()->whereNotNull('source_platform')->distinct()->orderBy('source_platform')->pluck('source_platform'),
        ]);
    }

    public function show(Lead $lead): View
    {
        $lead->load(['requestedService', 'conversionEvents', 'wonConversionEvents']);

        return view('dashboard.leads.show', ['lead' => $lead]);
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()
            ->route('dashboard.leads.index')
            ->with('status', __('dashboard.flash.lead_archived'));
    }
}
