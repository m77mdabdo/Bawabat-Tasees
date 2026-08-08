<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreConversionEventRequest;
use App\Models\ConversionEvent;
use App\Models\Lead;
use App\Services\Marketing\ConversionEventService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConversionEventController extends Controller
{
    public function __construct(
        private readonly ConversionEventService $conversionEventService,
    ) {}

    public function store(StoreConversionEventRequest $request, Lead $lead): RedirectResponse
    {
        $this->conversionEventService->log($lead, $request->validated());

        return redirect()
            ->route('dashboard.leads.show', $lead)
            ->with('status', __('dashboard.flash.conversion_logged'));
    }

    public function destroy(Lead $lead, ConversionEvent $conversionEvent): RedirectResponse
    {
        // Nested binding is not scoped automatically here, so verify the
        // event really belongs to this lead — otherwise an admin could
        // delete another lead's event by editing the URL.
        if ($conversionEvent->lead_id !== $lead->getKey()) {
            throw new NotFoundHttpException;
        }

        $this->conversionEventService->delete($conversionEvent);

        return redirect()
            ->route('dashboard.leads.show', $lead)
            ->with('status', __('dashboard.flash.conversion_deleted'));
    }
}
