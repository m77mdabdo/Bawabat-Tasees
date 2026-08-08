<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\Service;
use Database\Seeders\MenuItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    private function xml(): \SimpleXMLElement
    {
        $body = $this->get('/sitemap.xml')->assertOk()->getContent();

        return new \SimpleXMLElement($body);
    }

    public function test_sitemap_is_served_as_xml(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml');
    }

    public function test_sitemap_lists_every_static_page_in_both_locales(): void
    {
        $locs = array_map('strval', iterator_to_array($this->xml()->xpath('//*[local-name()="loc"]')));

        foreach ([
            'home', 'services.index', 'countries.index', 'faqs.index', 'articles.index',
            'pages.about', 'pages.why-invest', 'pages.formation-process', 'pages.requirements',
            'pages.privacy-policy', 'pages.terms-and-conditions', 'consultation', 'contact',
        ] as $name) {
            $this->assertContains(route($name), $locs, "Missing Arabic URL for {$name}");
            $this->assertContains(route($name.'.en'), $locs, "Missing English URL for {$name}");
        }
    }

    public function test_every_entry_carries_all_three_hreflang_alternates(): void
    {
        $xml = $this->xml();
        $urls = $xml->xpath('//*[local-name()="url"]');

        $this->assertNotEmpty($urls);

        foreach ($urls as $url) {
            $alternates = $url->xpath('*[local-name()="link"]');
            $this->assertCount(3, $alternates, 'Each entry needs ar, en and x-default.');

            $hreflangs = array_map(fn ($a) => (string) $a['hreflang'], $alternates);
            $this->assertEqualsCanonicalizing(['ar', 'en', 'x-default'], $hreflangs);
        }
    }

    public function test_x_default_points_at_the_arabic_url(): void
    {
        $url = $this->xml()->xpath('//*[local-name()="url"]')[0];
        $byLang = [];

        foreach ($url->xpath('*[local-name()="link"]') as $a) {
            $byLang[(string) $a['hreflang']] = (string) $a['href'];
        }

        $this->assertSame($byLang['ar'], $byLang['x-default']);
        $this->assertNotSame($byLang['en'], $byLang['x-default']);
    }

    public function test_published_services_and_articles_are_included(): void
    {
        $service = Service::factory()->create(['slug' => 'company-formation']);
        $article = Article::factory()->create(['slug' => 'welcome-post']);

        $locs = array_map('strval', iterator_to_array($this->xml()->xpath('//*[local-name()="loc"]')));

        $this->assertContains(route('services.show', $service), $locs);
        $this->assertContains(route('services.show.en', $service), $locs);
        $this->assertContains(route('articles.show', $article), $locs);
        $this->assertContains(route('articles.show.en', $article), $locs);
    }

    /**
     * An unreachable URL in a sitemap is a soft 404 and actively harms
     * crawling.
     */
    public function test_unpublished_and_inactive_content_is_excluded(): void
    {
        $inactive = Service::factory()->inactive()->create(['slug' => 'hidden-service']);
        $draft = Article::factory()->draft()->create(['slug' => 'draft-post']);
        $scheduled = Article::factory()->scheduled()->create(['slug' => 'future-post']);

        $locs = array_map('strval', iterator_to_array($this->xml()->xpath('//*[local-name()="loc"]')));

        $this->assertNotContains(route('services.show', $inactive), $locs);
        $this->assertNotContains(route('articles.show', $draft), $locs);
        $this->assertNotContains(route('articles.show', $scheduled), $locs, 'A future-dated post is not live yet.');
    }

    public function test_sitemap_never_exposes_admin_or_auth_urls(): void
    {
        $body = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (['/dashboard', '/login', '/profile', '/forgot-password'] as $private) {
            $this->assertStringNotContainsString($private, $body);
        }
    }

    public function test_seeded_site_produces_the_expected_url_count(): void
    {
        $this->seed();

        $locs = $this->xml()->xpath('//*[local-name()="loc"]');

        // 13 static + 6 seeded services + 4 seeded articles = 23, x2 locales.
        $this->assertCount(46, $locs);
    }

    // ---------------------------------------------------------------
    // robots.txt
    // ---------------------------------------------------------------

    public function test_robots_is_served_as_plain_text(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * The sitemaps.org spec requires an ABSOLUTE sitemap URL — which is
     * why robots.txt is a route rather than a static file.
     */
    public function test_robots_points_at_the_sitemap_with_an_absolute_url(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.route('sitemap'), escape: false)
            ->assertSee('Sitemap: http', escape: false);
    }

    public function test_robots_disallows_the_private_areas(): void
    {
        $response = $this->get('/robots.txt')->assertOk();

        foreach (['/dashboard', '/login', '/profile'] as $private) {
            $response->assertSee('Disallow: '.$private, escape: false);
        }
    }

    public function test_public_pages_are_not_disallowed(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringNotContainsString('Disallow: /services', $body);
        $this->assertStringNotContainsString('Disallow: /'."\n", $body, 'Must not block the whole site.');
    }

    public function test_sitemap_and_robots_are_reachable_alongside_the_seeded_menu(): void
    {
        $this->seed(MenuItemSeeder::class);

        $this->get('/sitemap.xml')->assertOk();
        $this->get('/robots.txt')->assertOk();
    }
}
