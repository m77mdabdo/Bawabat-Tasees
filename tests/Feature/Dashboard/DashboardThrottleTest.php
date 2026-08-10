<?php

namespace Tests\Feature\Dashboard;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

/**
 * The public forms have carried throttle:5,1 since they were built; the
 * dashboard had no limit at all, so every admin write — including the
 * transactional menu reorder — was unbounded and a stolen session had no
 * ceiling.
 *
 * The limiter is keyed on the method: safe requests return Limit::none(), so
 * it can wrap the whole group without rationing ordinary browsing.
 */
class DashboardThrottleTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private const LIMIT = 60;

    public function test_browsing_the_dashboard_is_never_throttled(): void
    {
        $admin = $this->makeAdmin();

        for ($i = 0; $i < self::LIMIT + 5; $i++) {
            $this->actingAs($admin)->get(route('dashboard.faqs.index'))->assertOk();
        }
    }

    public function test_a_write_is_throttled_past_the_limit(): void
    {
        $admin = $this->makeAdmin();

        $payload = [
            'question' => ['ar' => 'سؤال', 'en' => 'Question'],
            'answer' => ['ar' => 'جواب', 'en' => 'Answer'],
            'sort_order' => 0,
            'is_active' => 1,
        ];

        for ($i = 0; $i < self::LIMIT; $i++) {
            $this->actingAs($admin)
                ->post(route('dashboard.faqs.store'), $payload)
                ->assertRedirect();
        }

        $this->actingAs($admin)
            ->post(route('dashboard.faqs.store'), $payload)
            ->assertStatus(429);
    }

    /**
     * Keyed on the user, not the application — one admin hitting the ceiling
     * must not lock the other one out.
     */
    public function test_the_limit_is_per_admin(): void
    {
        $first = $this->makeAdmin();
        $second = $this->makeAdmin();

        $payload = [
            'question' => ['ar' => 'سؤال', 'en' => 'Question'],
            'answer' => ['ar' => 'جواب', 'en' => 'Answer'],
            'sort_order' => 0,
            'is_active' => 1,
        ];

        for ($i = 0; $i < self::LIMIT; $i++) {
            $this->actingAs($first)->post(route('dashboard.faqs.store'), $payload);
        }

        $this->actingAs($first)->post(route('dashboard.faqs.store'), $payload)->assertStatus(429);
        $this->actingAs($second)->post(route('dashboard.faqs.store'), $payload)->assertRedirect();
    }

    /**
     * The same limiter covers the profile routes, which sit outside the
     * dashboard prefix but are equally authenticated writes.
     */
    public function test_profile_writes_are_throttled_too(): void
    {
        $admin = $this->makeAdmin();

        for ($i = 0; $i < self::LIMIT; $i++) {
            $this->actingAs($admin)->patch(route('profile.update'), [
                'name' => 'Admin '.$i,
                'email' => $admin->email,
            ]);
        }

        $this->actingAs($admin)
            ->patch(route('profile.update'), ['name' => 'Admin', 'email' => $admin->email])
            ->assertStatus(429);
    }

    public function test_a_throttled_write_did_not_reach_the_database(): void
    {
        $admin = $this->makeAdmin();

        $payload = [
            'question' => ['ar' => 'سؤال', 'en' => 'Question'],
            'answer' => ['ar' => 'جواب', 'en' => 'Answer'],
            'sort_order' => 0,
            'is_active' => 1,
        ];

        for ($i = 0; $i < self::LIMIT; $i++) {
            $this->actingAs($admin)->post(route('dashboard.faqs.store'), $payload);
        }

        $before = Faq::count();

        $this->actingAs($admin)->post(route('dashboard.faqs.store'), $payload)->assertStatus(429);

        $this->assertSame($before, Faq::count());
    }
}
