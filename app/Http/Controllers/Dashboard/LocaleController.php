<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Toggles the CURRENT user's own dashboard locale — never accepts a
     * user id, always operates on auth()->user() so one admin can never
     * change another's preference.
     */
    public function update(Request $request): RedirectResponse
    {
        $locale = $request->user()->locale === 'en' ? 'ar' : 'en';

        $request->user()->update(['locale' => $locale]);

        return back();
    }
}
