<?php

namespace Tests\Feature\Dashboard;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private function makeArticle(array $overrides = []): Article
    {
        return Article::create(array_merge([
            'slug' => 'welcome-post-'.uniqid(),
            'title' => ['ar' => 'مرحباً بكم'],
            'body' => ['ar' => '<p>محتوى</p>'],
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    private function makeComment(array $overrides = []): Comment
    {
        return Comment::create(array_merge([
            'article_id' => $this->makeArticle()->id,
            'name' => 'زائر',
            'email' => 'visitor@example.com',
            'body' => 'تعليق تجريبي',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('dashboard.comments.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.comments.index'))->assertForbidden();
    }

    public function test_index_lists_comments_for_admin(): void
    {
        $admin = $this->makeAdmin();
        $this->makeComment(['name' => 'محمد الشهري']);

        $response = $this->actingAs($admin)->get(route('dashboard.comments.index'));

        $response->assertOk();
        $response->assertSee('محمد الشهري');
    }

    public function test_index_filters_by_status(): void
    {
        $admin = $this->makeAdmin();
        // Names deliberately avoid the substring "قيد المراجعة" — that
        // exact phrase is always present in the status filter <select>
        // regardless of which status is currently selected, so using it
        // as test data would collide with unrelated page chrome.
        $this->makeComment(['name' => 'علي الحربي', 'status' => 'pending']);
        $this->makeComment(['name' => 'تعليق معتمد', 'status' => 'approved']);

        $response = $this->actingAs($admin)->get(route('dashboard.comments.index', ['status' => 'approved']));

        $response->assertOk();
        $response->assertSee('تعليق معتمد');
        $response->assertDontSee('علي الحربي');
    }

    public function test_index_filters_by_article(): void
    {
        $admin = $this->makeAdmin();
        $articleA = $this->makeArticle(['slug' => 'article-a', 'title' => ['ar' => 'مقال أ']]);
        $articleB = $this->makeArticle(['slug' => 'article-b', 'title' => ['ar' => 'مقال ب']]);
        $this->makeComment(['article_id' => $articleA->id, 'name' => 'تعليق أ']);
        $this->makeComment(['article_id' => $articleB->id, 'name' => 'تعليق ب']);

        $response = $this->actingAs($admin)->get(route('dashboard.comments.index', ['article_id' => $articleA->id]));

        $response->assertOk();
        $response->assertSee('تعليق أ');
        $response->assertDontSee('تعليق ب');
    }

    public function test_admin_can_approve_a_pending_comment(): void
    {
        $admin = $this->makeAdmin();
        $comment = $this->makeComment(['status' => 'pending']);

        $response = $this->actingAs($admin)->patch(route('dashboard.comments.approve', $comment));

        $response->assertRedirect(route('dashboard.comments.index'));
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 'approved']);
    }

    public function test_approved_comment_appears_publicly_after_approval(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle();
        $comment = $this->makeComment(['article_id' => $article->id, 'name' => 'عميل سعيد', 'status' => 'pending']);

        $this->get(route('articles.show', $article))->assertDontSee('عميل سعيد');

        $this->actingAs($admin)->patch(route('dashboard.comments.approve', $comment));

        $this->get(route('articles.show', $article))->assertSee('عميل سعيد');
    }

    public function test_admin_can_reject_a_comment(): void
    {
        $admin = $this->makeAdmin();
        $comment = $this->makeComment(['status' => 'pending']);

        $response = $this->actingAs($admin)->patch(route('dashboard.comments.reject', $comment));

        $response->assertRedirect(route('dashboard.comments.index'));
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 'rejected']);
    }

    public function test_rejected_comment_stays_hidden_from_the_public_page(): void
    {
        $admin = $this->makeAdmin();
        $article = $this->makeArticle();
        $comment = $this->makeComment(['article_id' => $article->id, 'name' => 'تعليق مرفوض', 'status' => 'pending']);

        $this->actingAs($admin)->patch(route('dashboard.comments.reject', $comment));

        $this->get(route('articles.show', $article))->assertDontSee('تعليق مرفوض');
    }

    public function test_admin_can_delete_a_comment(): void
    {
        $admin = $this->makeAdmin();
        $comment = $this->makeComment();

        $response = $this->actingAs($admin)->delete(route('dashboard.comments.destroy', $comment));

        $response->assertRedirect(route('dashboard.comments.index'));
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_guest_cannot_approve_reject_or_delete(): void
    {
        $comment = $this->makeComment();

        $this->patch(route('dashboard.comments.approve', $comment))->assertRedirect(route('login'));
        $this->patch(route('dashboard.comments.reject', $comment))->assertRedirect(route('login'));
        $this->delete(route('dashboard.comments.destroy', $comment))->assertRedirect(route('login'));

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 'pending']);
    }
}
