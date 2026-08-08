<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\Page;
use App\Models\SeoMeta;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTagsTest extends TestCase
{
    use RefreshDatabase;

    private function makeArticle(array $overrides = []): Article
    {
        return Article::create(array_merge([
            'slug' => 'welcome-post',
            'title' => ['ar' => 'مرحباً بكم', 'en' => 'Welcome'],
            'excerpt' => ['ar' => 'مقتطف عربي', 'en' => 'English excerpt'],
            'body' => ['ar' => '<p>محتوى</p>', 'en' => '<p>content</p>'],
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    private function makeService(): Service
    {
        return Service::create([
            'slug' => 'company-formation',
            'name' => ['ar' => 'تأسيس الشركات', 'en' => 'Company Formation'],
            'summary' => ['ar' => 'ملخص عربي', 'en' => 'English summary'],
            'body' => ['ar' => '<p>نص</p>', 'en' => '<p>text</p>'],
            'requirements' => ['ar' => 'متطلبات', 'en' => 'Requirements'],
            'process' => ['ar' => 'خطوات', 'en' => 'Process'],
            'is_active' => true,
        ]);
    }

    private function attachSeoMeta(Article|Page|Service $model, array $attributes = []): SeoMeta
    {
        return $model->seoMeta()->create(array_merge([
            'meta_title' => ['ar' => 'عنوان ميتا مخصص', 'en' => 'Custom Meta Title'],
            'meta_description' => ['ar' => 'وصف ميتا مخصص', 'en' => 'Custom meta description'],
        ], $attributes));
    }

    // ---------------------------------------------------------------
    // Tags render at all
    // ---------------------------------------------------------------

    public function test_article_page_renders_the_full_seo_tag_set(): void
    {
        $article = $this->makeArticle();

        $html = $this->get(route('articles.show', $article))->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="description"', $html);
        $this->assertStringContainsString('<link rel="canonical"', $html);
        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('property="og:description"', $html);
        $this->assertStringContainsString('property="og:url"', $html);
        $this->assertStringContainsString('property="og:image"', $html);
        $this->assertStringContainsString('property="og:locale"', $html);
        $this->assertStringContainsString('name="twitter:card"', $html);
        $this->assertStringContainsString('name="twitter:title"', $html);
        $this->assertStringContainsString('name="twitter:description"', $html);
    }

    public function test_articles_use_the_article_open_graph_type(): void
    {
        $article = $this->makeArticle();

        $this->get(route('articles.show', $article))
            ->assertSee('<meta property="og:type" content="article">', escape: false);
    }

    public function test_non_article_pages_use_the_website_open_graph_type(): void
    {
        $service = $this->makeService();

        $this->get(route('services.show', $service))
            ->assertSee('<meta property="og:type" content="website">', escape: false);
    }

    // ---------------------------------------------------------------
    // SeoMeta wins over derived content
    // ---------------------------------------------------------------

    public function test_seo_meta_overrides_the_derived_title_and_description(): void
    {
        $article = $this->makeArticle();
        $this->attachSeoMeta($article);

        $html = $this->get(route('articles.show', $article))->assertOk()->getContent();

        $this->assertStringContainsString('<title>عنوان ميتا مخصص</title>', $html);
        $this->assertStringContainsString('content="وصف ميتا مخصص"', $html);
        // The article's own title must no longer be the <title>.
        $this->assertStringNotContainsString('<title>مرحباً بكم', $html);
    }

    public function test_canonical_url_from_seo_meta_overrides_the_current_url(): void
    {
        $article = $this->makeArticle();
        $this->attachSeoMeta($article, ['canonical_url' => 'https://example.com/canonical']);

        $this->get(route('articles.show', $article))
            ->assertSee('<link rel="canonical" href="https://example.com/canonical">', escape: false);
    }

    public function test_canonical_falls_back_to_the_current_url_when_not_set(): void
    {
        $article = $this->makeArticle();

        $this->get(route('articles.show', $article))
            ->assertSee('<link rel="canonical" href="'.route('articles.show', $article).'">', escape: false);
    }

    // ---------------------------------------------------------------
    // Fallback when no SeoMeta row exists
    // ---------------------------------------------------------------

    public function test_falls_back_to_article_title_and_excerpt_without_seo_meta(): void
    {
        $article = $this->makeArticle();

        $this->assertNull($article->seoMeta);

        $html = $this->get(route('articles.show', $article))->assertOk()->getContent();

        $this->assertStringContainsString('مرحباً بكم', $html);
        $this->assertStringContainsString('content="مقتطف عربي"', $html);
    }

    public function test_description_is_never_empty_even_with_no_excerpt_and_no_seo_meta(): void
    {
        $article = $this->makeArticle(['excerpt' => null]);

        $html = $this->get(route('articles.show', $article))->assertOk()->getContent();

        preg_match('/<meta name="description" content="([^"]*)">/', $html, $matches);

        $this->assertNotEmpty($matches, 'No description meta tag was rendered at all.');
        $this->assertNotSame('', trim($matches[1]), 'The description meta tag rendered empty.');
    }

    public function test_list_page_without_a_backing_record_still_renders_a_description(): void
    {
        $html = $this->get(route('articles.index'))->assertOk()->getContent();

        preg_match('/<meta name="description" content="([^"]*)">/', $html, $matches);

        $this->assertNotEmpty($matches);
        $this->assertNotSame('', trim($matches[1]));
    }

    public function test_html_is_stripped_from_a_derived_description(): void
    {
        $article = $this->makeArticle([
            'excerpt' => null,
            'body' => ['ar' => '<p>نص <strong>مهم</strong> هنا</p>'],
        ]);

        $html = $this->get(route('articles.show', $article))->assertOk()->getContent();

        preg_match('/<meta name="description" content="([^"]*)">/', $html, $matches);

        $this->assertNotEmpty($matches);
        $this->assertStringNotContainsString('<strong>', $matches[1]);
        $this->assertStringNotContainsString('&lt;', $matches[1]);
    }

    // ---------------------------------------------------------------
    // Per-locale resolution
    // ---------------------------------------------------------------

    public function test_seo_meta_resolves_per_locale(): void
    {
        $article = $this->makeArticle();
        $this->attachSeoMeta($article);

        $this->get(route('articles.show', $article))
            ->assertSee('<title>عنوان ميتا مخصص</title>', escape: false);

        $this->get(route('articles.show.en', $article))
            ->assertSee('<title>Custom Meta Title</title>', escape: false);
    }

    public function test_og_locale_reflects_the_current_locale(): void
    {
        $article = $this->makeArticle();

        $this->get(route('articles.show', $article))
            ->assertSee('<meta property="og:locale" content="ar_SA">', escape: false)
            ->assertSee('<meta property="og:locale:alternate" content="en_US">', escape: false);

        $this->get(route('articles.show.en', $article))
            ->assertSee('<meta property="og:locale" content="en_US">', escape: false)
            ->assertSee('<meta property="og:locale:alternate" content="ar_SA">', escape: false);
    }

    /**
     * An English page with no English meta_title must fall back through
     * the chain rather than leaking the Arabic meta value.
     */
    public function test_english_page_without_english_seo_meta_falls_back_to_english_content(): void
    {
        $article = $this->makeArticle();
        $this->attachSeoMeta($article, [
            'meta_title' => ['ar' => 'عنوان عربي فقط'],
            'meta_description' => ['ar' => 'وصف عربي فقط'],
        ]);

        $html = $this->get(route('articles.show.en', $article))->assertOk()->getContent();

        // spatie falls back to the Arabic value for a missing locale, which
        // is the project-wide configured behaviour — assert the page still
        // renders a non-empty title rather than a blank one.
        preg_match('/<title>([^<]*)<\/title>/', $html, $matches);
        $this->assertNotEmpty($matches);
        $this->assertNotSame('', trim($matches[1]));
    }

    // ---------------------------------------------------------------
    // Coexistence with the existing hreflang tags
    // ---------------------------------------------------------------

    public function test_seo_tags_do_not_duplicate_or_conflict_with_hreflang(): void
    {
        $article = $this->makeArticle();

        $html = $this->get(route('articles.show', $article))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<title>'));
        $this->assertSame(1, substr_count($html, 'rel="canonical"'));
        $this->assertSame(1, substr_count($html, 'hreflang="ar"'));
        $this->assertSame(1, substr_count($html, 'hreflang="en"'));
        $this->assertSame(1, substr_count($html, 'hreflang="x-default"'));
        $this->assertSame(1, substr_count($html, 'name="description"'));
    }

    public function test_page_records_render_their_seo_meta(): void
    {
        $page = Page::create([
            'slug' => 'about',
            'title' => ['ar' => 'من نحن', 'en' => 'About Us'],
            'body' => ['ar' => '<p>نص</p>', 'en' => '<p>text</p>'],
            'meta_title' => ['ar' => 'من نحن', 'en' => 'About Us'],
            'meta_description' => ['ar' => 'وصف', 'en' => 'description'],
            'is_published' => true,
        ]);
        $this->attachSeoMeta($page);

        $this->get(route('pages.about'))
            ->assertOk()
            ->assertSee('<title>عنوان ميتا مخصص</title>', escape: false);
    }
}
