<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreServiceRequest;
use App\Http\Requests\Dashboard\UpdateServiceRequest;
use App\Models\Service;
use App\Services\Cms\ContentPublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        private readonly ContentPublishingService $contentPublishingService
    ) {}

    public function index(): View
    {
        $services = Service::orderBy('sort_order')->paginate(20);

        return view('dashboard.services.index', ['services' => $services]);
    }

    public function create(): View
    {
        return view('dashboard.services.create');
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['cover_image']);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->contentPublishingService->storeImage(
                $request->file('cover_image'),
                'services'
            );
        }

        Service::create($data);

        return redirect()
            ->route('dashboard.services.index')
            ->with('status', __('dashboard.flash.service_created'));
    }

    public function edit(Service $service): View
    {
        return view('dashboard.services.edit', ['service' => $service]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->safe()->except(['cover_image']);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->contentPublishingService->replaceImage(
                $request->file('cover_image'),
                'services',
                $service->cover_image
            );
        }

        $service->update($data);

        return redirect()
            ->route('dashboard.services.index')
            ->with('status', __('dashboard.flash.service_updated'));
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()
            ->route('dashboard.services.index')
            ->with('status', __('dashboard.flash.service_deleted'));
    }
}
