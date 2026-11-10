<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreContactRequest;
use App\Leads\LeadIntake;
use App\Models\Lead;
use App\Services\Cms\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(
        private readonly LeadIntake $intake,
        private readonly SettingsRepository $settings,
    ) {}

    public function create(): View
    {
        return view('public.contact', [
            'contactPhone' => $this->settings->string('contact_phone'),
            'contactWhatsapp' => $this->settings->string('contact_whatsapp'),
            'contactEmail' => $this->settings->string('contact_email'),
            'contactAddress' => $this->settings->translated('contact_address'),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        if (! $this->intake->isSpam($request)) {
            $this->intake->submit($request, Lead::TYPE_CONTACT);
        }

        return redirect($this->redirectTarget($request))
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
