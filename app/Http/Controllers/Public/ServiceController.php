<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $services = Service::active()->orderBy('sort_order')->get();

        return view('public.services.index', ['services' => $services]);
    }

    public function show(Service $service): View
    {
        abort_unless($service->is_active, 404);

        return view('public.services.show', ['service' => $service]);
    }
}
