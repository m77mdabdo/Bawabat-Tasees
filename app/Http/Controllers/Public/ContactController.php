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
        $redirectTarget = $this->redirectTarget($request);

        // Honeypot check happens before any DB write — see the same note
        // in ConsultationController@store.
        if ($request->filled('website_url')) {
            return redirect($redirectTarget)
                ->with('status', __('site.flash.contact_submitted'));
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

        return redirect($redirectTarget)
            ->with('status', __('site.flash.contact_submitted'));
    }

    /**
     * The contact form is embedded in two places (the standalone
     * /contact page and the homepage Contact section, see
     * resources/views/components/contact-form.blade.php) that must
     * redirect back to two different places after a successful submit.
     * The hidden `redirect_to` field carries that hint — it is never
     * used to build an arbitrary URL from user input, only compared
     * against the one literal value 'home', so there is no
     * open-redirect risk regardless of what a client sends. Both
     * targets go through lroute() so an English-locale visitor lands
     * back on the `/en/...` variant, not the Arabic one.
     */
    private function redirectTarget(StoreContactRequest $request): string
    {
        if ($request->input('redirect_to') === 'home') {
            return lroute('home').'#contact';
        }

        return lroute('contact');
    }
}
