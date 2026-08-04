<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlePageTest extends TestCase
{
    use RefreshDatabase;

    private function makeArticle(array $overrides = []): Article
    {
        return Article::create(array_merge([
            'slug' => 'welcome-post',
            'title' => ['ar' => 'مرحباً بكم'],
            'excerpt' => ['ar' => 'مقتطف قصير'],
            'body' => ['ar' => '<p>محتوى <strong>مهم</strong></p>'],
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    public function test_index_shows_only_published_and_past_articles(): void
    {
        $this->makeArticle();
        $this->makeArticle(['slug' => 'future-post', 'title' => ['ar' => 'مقال مستقبلي'], 'published_at' => now()->addWeek()]);
        $this->makeArticle(['slug' => 'draft-post', 'title' => ['ar' => 'مسودة'], 'is_published' => false, 'published_at' => null]);

        $response = $this->get(route('articles.index'));

        $response->assertOk();
        $response->assertSee('مرحباً بكم');
        $response->assertDontSee('مقال مستقبلي');
        $response->assertDontSee('مسودة');
    }

    public function test_index_orders_newest_first(): void
    {
        $this->makeArticle(['slug' => 'older', 'title' => ['ar' => 'المقال الأقدم'], 'published_at' => now()->subWeek()]);
        $this->makeArticle(['slug' => 'newer', 'title' => ['ar' => 'المقال الأحدث'], 'published_at' => now()->subHour()]);

        $content = $this->get(route('articles.index'))->getContent();

        $this->assertTrue(strpos($content, 'المقال الأحدث') < strpos($content, 'المقال الأقدم'));
    }

    public function test_index_shows_empty_state_when_no_articles(): void
    {
        $response = $this->get(route('articles.index'));

        $response->assertOk();
        $response->assertSee('لا توجد مقالات منشورة حالياً');
    }

    public function test_show_returns_200_and_renders_sanitized_html_unescaped(): void
    {
        $article = $this->makeArticle();

        $response = $this->get(route('articles.show', $article));

        $response->assertOk();
        $response->assertSee('مرحباً بكم');
        // Raw HTML tag must appear unescaped, not as &lt;strong&gt;.
        $response->assertSee('<strong>مهم</strong>', false);
        $response->assertDontSee('&lt;strong&gt;', false);
    }

    public function test_show_returns_404_for_future_article(): void
    {
        $article = $this->makeArticle(['slug' => 'future-post', 'published_at' => now()->addWeek()]);

        $this->get(route('articles.show', $article))->assertNotFound();
    }

    public function test_show_returns_404_for_draft_article(): void
    {
        $article = $this->makeArticle(['slug' => 'draft-post', 'is_published' => false, 'published_at' => null]);

        $this->get(route('articles.show', $article))->assertNotFound();
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->get('/articles/does-not-exist')->assertNotFound();
    }
}
