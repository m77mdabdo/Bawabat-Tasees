<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreConsultationRequest;
use App\Leads\LeadIntake;
use App\Models\Lead;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function __construct(private readonly LeadIntake $intake) {}

    public function create(): View
    {
        return view('public.consultation', [
            'services' => Service::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreConsultationRequest $request): RedirectResponse
    {
        if (! $this->intake->isSpam($request)) {
            $this->intake->submit($request, Lead::TYPE_CONSULTATION);
        }

        return redirect()
            ->to(lroute('consultation'))
            ->with('status', __('site.flash.consultation_submitted'));
    }
}
