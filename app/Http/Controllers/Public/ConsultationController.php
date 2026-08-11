<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreConsultationRequest;
use App\Models\Lead;
use App\Models\Service;
use App\Services\Analytics\VisitorSessionService;
use App\Services\Marketing\AttributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function __construct(
        private readonly AttributionService $attributionService,
        private readonly VisitorSessionService $visitorSessions,
    ) {}

    public function create(): View
    {
        return view('public.consultation', [
            'services' => Service::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreConsultationRequest $request): RedirectResponse
    {
        // Honeypot check happens before any DB write. A filled
        // website_url means a bot filled every visible-looking field —
        // respond exactly as if the submission succeeded so the bot
        // never learns it was caught, but skip creating the Lead.
        if ($request->filled('website_url')) {
            return redirect()
                ->to(lroute('consultation'))
                ->with('status', __('site.flash.consultation_submitted'));
        }

        $data = $request->safe()->except([
            'first_touch_snapshot',
            'latest_touch_snapshot',
            'website_url',
            'consent_given',
        ]);

        $lead = Lead::create([
            ...$data,
            ...$this->attributionService->resolve($request),
            'type' => 'consultation',
            'consent_given' => true,
            'consented_at' => now(),
        ]);

        // After the Lead exists, and after the honeypot return above — a
        // caught bot leaves no journey entry either. Silent if the visitor
        // never consented; the form works regardless.
        $this->visitorSessions->recordConversion($request, 'consultation_submitted', $lead);

        return redirect()
            ->to(lroute('consultation'))
            ->with('status', __('site.flash.consultation_submitted'));
    }
}
