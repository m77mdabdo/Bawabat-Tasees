<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $aboutPage = Page::where('slug', 'about')->where('is_published', true)->first();

        return view('public.home', [
            'whatsappNumber' => Setting::where('key', 'contact_whatsapp')->value('value'),
            'contactPhone' => Setting::where('key', 'contact_phone')->value('value'),
            'contactWhatsapp' => Setting::where('key', 'contact_whatsapp')->value('value'),
            'contactEmail' => Setting::where('key', 'contact_email')->value('value'),
            'contactAddress' => Setting::where('key', 'contact_address')->value('value'),
            'homeServices' => Service::active()->orderByDesc('is_flagship')->orderBy('sort_order')->take(6)->get(),
            'aboutPage' => $aboutPage,
            'aboutExcerpt' => $aboutPage ? $this->firstParagraph($aboutPage->body) : null,
            'whyInvestSections' => $this->pageSections('why-invest-saudi-arabia', 4),
            'formationSections' => $this->pageSections('formation-process', 10),
            'testimonials' => Testimonial::active()->orderBy('sort_order')->get(),
            'homeCountries' => Country::active()->orderBy('sort_order')->take(8)->get(),
            'homeFaqs' => Faq::active()->orderBy('sort_order')->take(4)->get(),
            'latestArticles' => Article::where('is_published', true)
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->take(3)
                ->get(),
        ]);
    }

    /**
     * The About page's intro `body` is sanitized HTML, already authored
     * as 2-3 short paragraphs (see PageContentSeeder / the About
     * dashboard edit form) — the homepage teaser reuses the first
     * paragraph verbatim as plain text rather than duplicate-authoring
     * separate teaser copy or crudely truncating mid-sentence by
     * character count.
     */
    private function firstParagraph(?string $html): ?string
    {
        if (! $html) {
            return null;
        }

        if (preg_match('/<p[^>]*>(.*?)<\/p>/s', $html, $matches)) {
            return trim(strip_tags($matches[1]));
        }

        return trim(strip_tags($html)) ?: null;
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
