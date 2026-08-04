<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

/**
 * Four thin dedicated methods rather than one generic show($slug) action:
 * the four pages share the same underlying data shape (intro body +
 * ordered active sections) but need meaningfully different visual
 * treatment — About is prose-only, Why Invest is an advantage-card grid,
 * Formation Process is a numbered timeline, Requirements is a checklist.
 * A single templated view would need slug-based branching for layout
 * anyway, so a dedicated view per page stays simpler to read and edit.
 */
class PageController extends Controller
{
    public function about(): View
    {
        return view('public.pages.about', ['page' => $this->publishedPage('about')]);
    }

    public function whyInvest(): View
    {
        $page = $this->publishedPage('why-invest-saudi-arabia');

        return view('public.pages.why-invest', [
            'page' => $page,
            'sections' => $page->sections()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function formationProcess(): View
    {
        $page = $this->publishedPage('formation-process');

        return view('public.pages.formation-process', [
            'page' => $page,
            'sections' => $page->sections()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function requirements(): View
    {
        $page = $this->publishedPage('required-documents');

        return view('public.pages.requirements', [
            'page' => $page,
            'sections' => $page->sections()->active()->orderBy('sort_order')->get(),
        ]);
    }

    private function publishedPage(string $slug): Page
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        abort_unless($page->is_published, 404);

        return $page;
    }
}
