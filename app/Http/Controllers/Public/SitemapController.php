<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Service;
use Illuminate\Http\Response;

/**
 * Generates sitemap.xml from the routes and content that actually exist,
 * rather than shipping a static file that silently goes stale every time
 * a service or article is published.
 *
 * Every URL is emitted once with xhtml:link alternates for both locales
 * (plus x-default -> Arabic), matching the hreflang tags the pages
 * themselves render.
 */
class SitemapController extends Controller
{
    /**
     * Parameter-free public routes, with the change frequency/priority
     * hint appropriate to each.
     *
     * @var array<string, array{priority: string, changefreq: string}>
     */
    private const STATIC_ROUTES = [
        'home' => ['priority' => '1.0', 'changefreq' => 'weekly'],
        'services.index' => ['priority' => '0.9', 'changefreq' => 'weekly'],
        'consultation' => ['priority' => '0.9', 'changefreq' => 'monthly'],
        'contact' => ['priority' => '0.8', 'changefreq' => 'monthly'],
        'pages.about' => ['priority' => '0.7', 'changefreq' => 'monthly'],
        'pages.why-invest' => ['priority' => '0.7', 'changefreq' => 'monthly'],
        'pages.formation-process' => ['priority' => '0.7', 'changefreq' => 'monthly'],
        'pages.requirements' => ['priority' => '0.7', 'changefreq' => 'monthly'],
        'countries.index' => ['priority' => '0.6', 'changefreq' => 'monthly'],
        'faqs.index' => ['priority' => '0.6', 'changefreq' => 'monthly'],
        'articles.index' => ['priority' => '0.6', 'changefreq' => 'weekly'],
        'pages.privacy-policy' => ['priority' => '0.3', 'changefreq' => 'yearly'],
        'pages.terms-and-conditions' => ['priority' => '0.3', 'changefreq' => 'yearly'],
    ];

    public function __invoke(): Response
    {
        $entries = [];

        foreach (self::STATIC_ROUTES as $name => $meta) {
            $entries[] = [
                'ar' => route($name),
                'en' => route($name.'.en'),
                'lastmod' => null,
                'priority' => $meta['priority'],
                'changefreq' => $meta['changefreq'],
            ];
        }

        // Only content a visitor can actually reach — an unpublished
        // article or inactive service in the sitemap is a soft 404.
        foreach (Service::active()->orderBy('sort_order')->get() as $service) {
            $entries[] = [
                'ar' => route('services.show', $service),
                'en' => route('services.show.en', $service),
                'lastmod' => $service->updated_at,
                'priority' => '0.8',
                'changefreq' => 'monthly',
            ];
        }

        foreach (Article::where('is_published', true)->where('published_at', '<=', now())->orderByDesc('published_at')->get() as $article) {
            $entries[] = [
                'ar' => route('articles.show', $article),
                'en' => route('articles.show.en', $article),
                'lastmod' => $article->updated_at,
                'priority' => '0.5',
                'changefreq' => 'yearly',
            ];
        }

        return response()
            ->view('public.sitemap', ['entries' => $entries])
            ->header('Content-Type', 'application/xml');
    }
}
