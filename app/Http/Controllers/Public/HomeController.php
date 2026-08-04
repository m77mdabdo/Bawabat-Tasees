<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('public.home', [
            'whatsappNumber' => Setting::where('key', 'contact_whatsapp')->value('value'),
            'homeServices' => Service::active()->orderByDesc('is_flagship')->orderBy('sort_order')->take(6)->get(),
            'whyInvestSections' => $this->pageSections('why-invest-saudi-arabia', 4),
            'formationSections' => $this->pageSections('formation-process', 10),
            'testimonials' => Testimonial::active()->orderBy('sort_order')->get(),
            'latestArticles' => Article::where('is_published', true)
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->take(3)
                ->get(),
        ]);
    }

    /**
     * Both the Why Invest and Formation Process homepage previews read
     * live from the same PageSection rows the full /why-invest and
     * /formation-process pages already use — no separate homepage-only
     * copy to maintain. Returns an empty collection (not an error) if the
     * page itself doesn't exist or isn't published, so the homepage
     * section can just hide itself.
     */
    private function pageSections(string $slug, int $limit)
    {
        $page = Page::where('slug', $slug)->where('is_published', true)->first();

        if (! $page) {
            return collect();
        }

        return $page->sections()->active()->orderBy('sort_order')->take($limit)->get();
    }
}
