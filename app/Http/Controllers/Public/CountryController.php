<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function index(): View
    {
        $countries = Country::active()->orderBy('sort_order')->get();

        return view('public.countries.index', ['countries' => $countries]);
    }
}
