<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreFaqRequest;
use App\Http\Requests\Dashboard\UpdateFaqRequest;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::orderBy('sort_order')->paginate(20);

        return view('dashboard.faqs.index', ['faqs' => $faqs]);
    }

    public function create(): View
    {
        return view('dashboard.faqs.create');
    }

    public function store(StoreFaqRequest $request): RedirectResponse
    {
        Faq::create($request->validated());

        return redirect()
            ->route('dashboard.faqs.index')
            ->with('status', __('dashboard.flash.faq_created'));
    }

    public function edit(Faq $faq): View
    {
        return view('dashboard.faqs.edit', ['faq' => $faq]);
    }

    public function update(UpdateFaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->validated());

        return redirect()
            ->route('dashboard.faqs.index')
            ->with('status', __('dashboard.flash.faq_updated'));
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()
            ->route('dashboard.faqs.index')
            ->with('status', __('dashboard.flash.faq_deleted'));
    }
}
