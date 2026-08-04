<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UpdateTrackingSettingsRequest;
use App\Models\TrackingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrackingSettingController extends Controller
{
    public function edit(): View
    {
        return view('dashboard.tracking-settings.edit', [
            'settings' => TrackingSetting::all()->keyBy('key'),
        ]);
    }

    /**
     * The 6 rows are fixed (seeded once, never created/deleted here), so
     * this only ever updates existing TrackingSetting records looked up
     * by their own DB-defined key — the request's array keys are read,
     * never trusted as column/row identifiers to write to.
     */
    public function update(UpdateTrackingSettingsRequest $request): RedirectResponse
    {
        $data = $request->safe()['settings'];

        foreach (TrackingSetting::all() as $setting) {
            $row = $data[$setting->key] ?? [];

            $setting->update([
                'value' => $row['value'] ?? null,
                'is_active' => (bool) ($row['is_active'] ?? false),
            ]);
        }

        return redirect()
            ->route('dashboard.tracking-settings.edit')
            ->with('status', __('تم تحديث إعدادات التتبع.'));
    }
}
