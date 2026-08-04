<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::active()->orderBy('sort_order')->get();

        return view('public.faqs.index', ['faqs' => $faqs]);
    }
}
