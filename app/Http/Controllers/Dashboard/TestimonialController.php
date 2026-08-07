<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreTestimonialRequest;
use App\Http\Requests\Dashboard\UpdateTestimonialRequest;
use App\Models\Testimonial;
use App\Services\Cms\ContentPublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function __construct(
        private readonly ContentPublishingService $contentPublishingService
    ) {}

    public function index(): View
    {
        $testimonials = Testimonial::orderBy('sort_order')->paginate(20);

        return view('dashboard.testimonials.index', ['testimonials' => $testimonials]);
    }

    public function create(): View
    {
        return view('dashboard.testimonials.create');
    }

    public function store(StoreTestimonialRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['avatar']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->contentPublishingService->storeImage(
                $request->file('avatar'),
                'testimonials'
            );
        }

        Testimonial::create($data);

        return redirect()
            ->route('dashboard.testimonials.index')
            ->with('status', __('dashboard.flash.testimonial_created'));
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('dashboard.testimonials.edit', ['testimonial' => $testimonial]);
    }

    public function update(UpdateTestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $request->safe()->except(['avatar']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->contentPublishingService->replaceImage(
                $request->file('avatar'),
                'testimonials',
                $testimonial->avatar
            );
        }

        $testimonial->update($data);

        return redirect()
            ->route('dashboard.testimonials.index')
            ->with('status', __('dashboard.flash.testimonial_updated'));
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return redirect()
            ->route('dashboard.testimonials.index')
            ->with('status', __('dashboard.flash.testimonial_deleted'));
    }
}
