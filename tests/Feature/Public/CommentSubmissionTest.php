<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function makeArticle(array $overrides = []): Article
    {
        return Article::create(array_merge([
            'slug' => 'welcome-post',
            'title' => ['ar' => 'مرحباً بكم'],
            'excerpt' => ['ar' => 'مقتطف قصير'],
            'body' => ['ar' => '<p>محتوى</p>'],
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'سارة القحطاني',
            'email' => 'sara@example.com',
            'body' => 'مقال رائع، شكرًا لكم.',
            'website_url' => '',
        ], $overrides);
    }

    public function test_submitting_a_comment_creates_it_as_pending(): void
    {
        $article = $this->makeArticle();

        $response = $this->post(route('articles.comments.store', $article), $this->validPayload());

        $response->assertRedirect(route('articles.show', $article));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('comments', [
            'article_id' => $article->id,
            'name' => 'سارة القحطاني',
            'email' => 'sara@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_pending_comment_does_not_appear_on_the_public_article_page(): void
    {
        $article = $this->makeArticle();
        $this->post(route('articles.comments.store', $article), $this->validPayload());

        $response = $this->get(route('articles.show', $article));

        $response->assertOk();
        $response->assertDontSee('مقال رائع، شكرًا لكم.');
    }

    public function test_approved_comment_appears_on_the_public_article_page(): void
    {
        $article = $this->makeArticle();
        Comment::create([
            'article_id' => $article->id,
            'name' => 'أحمد العتيبي',
            'email' => 'ahmed@example.com',
            'body' => 'تعليق معتمد يظهر للجميع.',
            'status' => 'approved',
        ]);

        $response = $this->get(route('articles.show', $article));

        $response->assertOk();
        $response->assertSee('أحمد العتيبي');
        $response->assertSee('تعليق معتمد يظهر للجميع.');
    }

    public function test_rejected_comment_does_not_appear_publicly(): void
    {
        $article = $this->makeArticle();
        Comment::create([
            'article_id' => $article->id,
            'name' => 'زائر',
            'email' => 'x@example.com',
            'body' => 'تعليق مرفوض.',
            'status' => 'rejected',
        ]);

        $response = $this->get(route('articles.show', $article));

        $response->assertOk();
        $response->assertDontSee('تعليق مرفوض.');
    }

    /**
     * The public form never exposes a status field, and StoreCommentRequest
     * doesn't validate one either — the controller force-creates every
     * submission as 'pending' regardless of anything in the payload. This
     * proves a forged 'status' field in the POST body can't bypass
     * moderation.
     */
    public function test_forging_a_status_field_in_the_request_cannot_bypass_moderation(): void
    {
        $article = $this->makeArticle();

        $this->post(route('articles.comments.store', $article), $this->validPayload([
            'status' => 'approved',
        ]));

        $this->assertDatabaseHas('comments', [
            'article_id' => $article->id,
            'status' => 'pending',
        ]);
    }

    public function test_comment_body_is_escaped_and_never_rendered_as_html(): void
    {
        $article = $this->makeArticle();
        Comment::create([
            'article_id' => $article->id,
            'name' => 'زائر',
            'email' => 'x@example.com',
            'body' => '<script>alert(1)</script>',
            'status' => 'approved',
        ]);

        $response = $this->get(route('articles.show', $article));

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_honeypot_filled_does_not_create_a_comment_but_still_shows_success(): void
    {
        $article = $this->makeArticle();

        $response = $this->post(route('articles.comments.store', $article), $this->validPayload([
            'website_url' => 'https://spambot.example.com',
        ]));

        $response->assertRedirect(route('articles.show', $article));
        $response->assertSessionHas('status');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_more_than_five_submissions_per_minute_are_rate_limited(): void
    {
        $article = $this->makeArticle();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('articles.comments.store', $article), $this->validPayload([
                'email' => "visitor{$i}@example.com",
            ]))->assertRedirect(route('articles.show', $article));
        }

        $response = $this->post(route('articles.comments.store', $article), $this->validPayload([
            'email' => 'visitor6@example.com',
        ]));

        $response->assertStatus(429);
        $this->assertDatabaseCount('comments', 5);
    }

    public function test_name_email_and_body_are_required(): void
    {
        $article = $this->makeArticle();

        $response = $this->post(route('articles.comments.store', $article), [
            'name' => '',
            'email' => '',
            'body' => '',
            'website_url' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'body']);
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_invalid_email_is_rejected(): void
    {
        $article = $this->makeArticle();

        $response = $this->post(route('articles.comments.store', $article), $this->validPayload([
            'email' => 'not-an-email',
        ]));

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_cannot_comment_on_an_unpublished_article(): void
    {
        $article = $this->makeArticle(['is_published' => false]);

        $response = $this->post(route('articles.comments.store', $article), $this->validPayload());

        $response->assertNotFound();
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_ip_address_is_recorded(): void
    {
        $article = $this->makeArticle();

        $this->post(route('articles.comments.store', $article), $this->validPayload());

        $comment = Comment::firstOrFail();
        $this->assertNotNull($comment->ip_address);
    }
}
