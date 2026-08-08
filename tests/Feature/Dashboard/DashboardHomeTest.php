<?php

namespace Tests\Feature\Dashboard;

use App\Models\Article;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class DashboardHomeTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard_home(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_dashboard_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    public function test_dashboard_home_shows_exact_real_counts(): void
    {
        $admin = $this->makeAdmin();

        // Attention: 2 unpublished articles, 1 inactive service, 1 inactive section.
        Article::create(['slug' => 'a1', 'title' => ['ar' => 'مقال 1'], 'body' => ['ar' => '<p>x</p>'], 'is_published' => false]);
        Article::create(['slug' => 'a2', 'title' => ['ar' => 'مقال 2'], 'body' => ['ar' => '<p>x</p>'], 'is_published' => false]);
        Article::create(['slug' => 'a3', 'title' => ['ar' => 'مقال 3'], 'body' => ['ar' => '<p>x</p>'], 'is_published' => true, 'published_at' => now()]);

        Service::create(['slug' => 's1', 'name' => ['ar' => 'خدمة 1'], 'summary' => ['ar' => 'x'], 'body' => ['ar' => 'x'], 'requirements' => ['ar' => 'x'], 'process' => ['ar' => 'x'], 'is_active' => false]);
        Service::create(['slug' => 's2', 'name' => ['ar' => 'خدمة 2'], 'summary' => ['ar' => 'x'], 'body' => ['ar' => 'x'], 'requirements' => ['ar' => 'x'], 'process' => ['ar' => 'x'], 'is_active' => true]);
        Service::create(['slug' => 's3', 'name' => ['ar' => 'خدمة 3'], 'summary' => ['ar' => 'x'], 'body' => ['ar' => 'x'], 'requirements' => ['ar' => 'x'], 'process' => ['ar' => 'x'], 'is_active' => true]);

        $page = Page::create(['slug' => 'about', 'title' => ['ar' => 'x'], 'body' => ['ar' => 'x'], 'meta_title' => ['ar' => 'x'], 'meta_description' => ['ar' => 'x']]);
        PageSection::create(['page_id' => $page->id, 'key' => 'k1', 'sort_order' => 0, 'is_active' => false, 'content' => ['title' => ['ar' => 'x'], 'description' => null, 'icon' => null]]);

        Country::create(['slug' => 'c1', 'name' => ['ar' => 'دولة 1'], 'is_active' => true]);
        Country::create(['slug' => 'c2', 'name' => ['ar' => 'دولة 2'], 'is_active' => false]);

        Faq::create(['question' => ['ar' => 'x'], 'answer' => ['ar' => 'x'], 'is_active' => true]);
        Faq::create(['question' => ['ar' => 'x'], 'answer' => ['ar' => 'x'], 'is_active' => false]);

        Testimonial::create(['client_name' => 'Test', 'quote' => ['ar' => 'x'], 'is_active' => true]);

        Media::create(['path' => 'x.jpg', 'disk' => 'public', 'mime_type' => 'image/jpeg', 'size' => 100, 'type' => 'image']);
        Media::create(['path' => 'y.jpg', 'disk' => 'public', 'mime_type' => 'image/jpeg', 'size' => 100, 'type' => 'image']);

        Lead::create(['full_name' => 'Lead 1', 'phone' => '0500000001', 'email' => 'l1@example.com', 'type' => 'contact', 'message' => 'x']);

        // created_at isn't fillable on Lead (it's not a form field), so it
        // has to be set after create() — mass assignment would silently
        // drop it and Eloquent would stamp "now" instead.
        $oldLead = Lead::create(['full_name' => 'Lead 2', 'phone' => '0500000002', 'email' => 'l2@example.com', 'type' => 'contact', 'message' => 'x']);
        $oldLead->created_at = now()->subWeek();
        $oldLead->save();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();

        // Needs-attention counts.
        $response->assertSeeText('مقالات غير منشورة');
        $response->assertSeeInOrder(['مقالات غير منشورة', '2']);
        $response->assertSeeInOrder(['خدمات غير نشطة', '1']);
        $response->assertSeeInOrder(['أقسام صفحات غير نشطة', '1']);

        // Overview counts.
        $response->assertSeeInOrder(['إجمالي الخدمات المنشورة', '2']);
        $response->assertSeeInOrder(['إجمالي الدول', '2']);
        $response->assertSeeInOrder(['إجمالي المقالات المنشورة', '1']);
        $response->assertSeeInOrder(['إجمالي الأسئلة الشائعة النشطة', '1']);
        $response->assertSeeInOrder(['إجمالي الشهادات النشطة', '1']);
        $response->assertSeeInOrder(['إجمالي عناصر المكتبة الإعلامية', '2']);

        // Leads overview counts.
        $response->assertSeeInOrder(['عملاء محتملون اليوم', '1']);
        $response->assertSeeInOrder(['إجمالي العملاء المحتملين', '2']);
    }

    public function test_dashboard_home_counts_update_when_data_changes(): void
    {
        $admin = $this->makeAdmin();
        $article = Article::create(['slug' => 'a1', 'title' => ['ar' => 'مقال'], 'body' => ['ar' => '<p>x</p>'], 'is_published' => true, 'published_at' => now()]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertSeeInOrder(['إجمالي المقالات المنشورة', '1'])
            ->assertSeeInOrder(['مقالات غير منشورة', '0']);

        $article->update(['is_published' => false]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertSeeInOrder(['إجمالي المقالات المنشورة', '0'])
            ->assertSeeInOrder(['مقالات غير منشورة', '1']);
    }

    #[DataProvider('comingSoonRoutes')]
    public function test_coming_soon_placeholder_returns_200_for_admin(string $routeName, string $expectedMessage): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route($routeName));

        $response->assertOk();
        $response->assertSee($expectedMessage);
    }

    #[DataProvider('comingSoonRoutes')]
    public function test_coming_soon_placeholder_redirects_guest(string $routeName): void
    {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }

    public static function comingSoonRoutes(): array
    {
        // campaigns is no longer here — it became a real CRUD section, so
        // it is covered by CampaignControllerTest instead.
        return [
            'lead-sources' => ['dashboard.lead-sources.index', 'قسم مصادر العملاء'],
            'contact-messages' => ['dashboard.contact-messages.index', 'قسم رسائل التواصل'],
            'reports' => ['dashboard.reports.index', 'قسم التقارير'],
            'settings' => ['dashboard.settings.index', 'قسم الإعدادات'],
        ];
    }

    /**
     * Spot-check existing CRUD screens still render correctly inside the
     * new sidebar shell — this task must not break already-working pages.
     */
    public function test_existing_crud_index_pages_still_work_inside_new_shell(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('dashboard.services.index'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.articles.index'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.pages.index'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.leads.index'))->assertOk();
    }
}
