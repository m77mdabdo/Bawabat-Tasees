<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreCountryRequest;
use App\Http\Requests\Dashboard\UpdateCountryRequest;
use App\Models\Country;
use App\Services\Cms\ContentPublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function __construct(
        private readonly ContentPublishingService $contentPublishingService
    ) {}

    public function index(): View
    {
        $countries = Country::orderBy('sort_order')->paginate(20);

        return view('dashboard.countries.index', ['countries' => $countries]);
    }

    public function create(): View
    {
        return view('dashboard.countries.create');
    }

    public function store(StoreCountryRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['flag_image']);

        if ($request->hasFile('flag_image')) {
            $data['flag_image'] = $this->contentPublishingService->storeImage(
                $request->file('flag_image'),
                'countries'
            );
        }

        Country::create($data);

        return redirect()
            ->route('dashboard.countries.index')
            ->with('status', __('dashboard.flash.country_created'));
    }

    public function edit(Country $country): View
    {
        return view('dashboard.countries.edit', ['country' => $country]);
    }

    public function update(UpdateCountryRequest $request, Country $country): RedirectResponse
    {
        $data = $request->safe()->except(['flag_image']);

        if ($request->hasFile('flag_image')) {
            $data['flag_image'] = $this->contentPublishingService->replaceImage(
                $request->file('flag_image'),
                'countries',
                $country->flag_image
            );
        }

        $country->update($data);

        return redirect()
            ->route('dashboard.countries.index')
            ->with('status', __('dashboard.flash.country_updated'));
    }

    public function destroy(Country $country): RedirectResponse
    {
        $country->delete();

        return redirect()
            ->route('dashboard.countries.index')
            ->with('status', __('dashboard.flash.country_deleted'));
    }
}
