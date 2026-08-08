<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateSettingsRequest;
use App\Services\Cms\SettingsRepository;
use App\Services\Cms\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly SettingsRepository $settings,
    ) {}

    public function edit(): View
    {
        return view('dashboard.settings.edit', [
            'definition' => SettingsService::DEFINITION,
            'values' => $this->settings->all(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        // validated() has already dropped anything not in the definition;
        // the service then iterates the definition rather than this array,
        // so an unknown key can never reach a write.
        $this->settingsService->update(
            $request->validated('settings', []),
            $request->file('site_logo')
        );

        return redirect()
            ->route('dashboard.settings.edit')
            ->with('status', __('dashboard.flash.settings_updated'));
    }
}
