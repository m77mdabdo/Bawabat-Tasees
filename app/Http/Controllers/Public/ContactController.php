<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreContactRequest;
use App\Models\Lead;
use App\Models\Setting;
use App\Services\Marketing\AttributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(
        private readonly AttributionService $attributionService
    ) {}

    public function create(): View
    {
        return view('public.contact', [
            'contactPhone' => Setting::where('key', 'contact_phone')->value('value'),
            'contactWhatsapp' => Setting::where('key', 'contact_whatsapp')->value('value'),
            'contactEmail' => Setting::where('key', 'contact_email')->value('value'),
            'contactAddress' => Setting::where('key', 'contact_address')->value('value'),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        // Honeypot check happens before any DB write — see the same note
        // in ConsultationController@store.
        if ($request->filled('website_url')) {
            return redirect()
                ->route('contact')
                ->with('status', 'شكرًا لتواصلك معنا! سنرد عليك في أقرب وقت ممكن.');
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
            'type' => 'contact',
            'consent_given' => true,
            'consented_at' => now(),
        ]);

        return redirect()
            ->route('contact')
            ->with('status', 'شكرًا لتواصلك معنا! سنرد عليك في أقرب وقت ممكن.');
    }
}
