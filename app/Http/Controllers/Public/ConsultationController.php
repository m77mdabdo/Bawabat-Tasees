<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreConsultationRequest;
use App\Models\Lead;
use App\Models\Service;
use App\Services\Marketing\AttributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function __construct(
        private readonly AttributionService $attributionService
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
                ->route('consultation')
                ->with('status', 'شكرًا لك! تم استلام طلبك وسنتواصل معك قريبًا.');
        }

        $data = $request->safe()->except([
            'first_touch_snapshot',
            'latest_touch_snapshot',
            'website_url',
            'consent_given',
        ]);

        Lead::create([
            ...$data,
            ...$this->attributionService->resolve($request),
            'type' => 'consultation',
            'consent_given' => true,
            'consented_at' => now(),
        ]);

        return redirect()
            ->route('consultation')
            ->with('status', 'شكرًا لك! تم استلام طلبك وسنتواصل معك قريبًا.');
    }
}
