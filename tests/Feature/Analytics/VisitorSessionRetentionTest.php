<?php

namespace Tests\Feature\Analytics;

use App\Models\Setting;
use App\Models\VisitorSession;
use App\Services\Cms\SettingsRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Retention is the half of the privacy promise that is easy to forget:
 * consent and truncation control what is collected, this controls how long
 * it survives. The privacy policy tells visitors their session data is
 * deleted after the window, so these tests are checking a published claim.
 */
class VisitorSessionRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function setRetention(int $days): void
    {
        Setting::updateOrCreate(
            ['key' => 'visitor_sessions_retention_days'],
            ['value' => $days, 'group' => 'visitor_sessions']
        );

        app(SettingsRepository::class)->flush();
    }

    public function test_sessions_past_the_window_are_deleted(): void
    {
        $this->setRetention(90);

        $old = VisitorSession::factory()->startedAt(now()->subDays(91))->create();
        $recent = VisitorSession::factory()->startedAt(now()->subDays(89))->create();

        Artisan::call('visitor-sessions:prune');

        $this->assertDatabaseMissing('visitor_sessions', ['id' => $old->id]);
        $this->assertDatabaseHas('visitor_sessions', ['id' => $recent->id]);
    }

    public function test_a_session_exactly_on_the_boundary_survives(): void
    {
        $this->setRetention(90);

        // Just inside the window. The comparison is strictly "older than",
        // so today-minus-90-plus-a-minute must not be swept.
        $edge = VisitorSession::factory()->startedAt(now()->subDays(90)->addMinute())->create();

        Artisan::call('visitor-sessions:prune');

        $this->assertDatabaseHas('visitor_sessions', ['id' => $edge->id]);
    }

    public function test_the_window_follows_the_admin_setting(): void
    {
        $this->setRetention(7);

        $old = VisitorSession::factory()->startedAt(now()->subDays(8))->create();
        $recent = VisitorSession::factory()->startedAt(now()->subDays(6))->create();

        Artisan::call('visitor-sessions:prune');

        $this->assertDatabaseMissing('visitor_sessions', ['id' => $old->id]);
        $this->assertDatabaseHas('visitor_sessions', ['id' => $recent->id]);
    }

    public function test_the_days_option_overrides_the_setting(): void
    {
        $this->setRetention(365);

        $session = VisitorSession::factory()->startedAt(now()->subDays(10))->create();

        Artisan::call('visitor-sessions:prune', ['--days' => 5]);

        $this->assertDatabaseMissing('visitor_sessions', ['id' => $session->id]);
    }

    public function test_a_dry_run_deletes_nothing(): void
    {
        $this->setRetention(30);

        $session = VisitorSession::factory()->startedAt(now()->subDays(60))->create();

        Artisan::call('visitor-sessions:prune', ['--dry-run' => true]);

        $this->assertDatabaseHas('visitor_sessions', ['id' => $session->id]);
        $this->assertStringContainsString('Would delete 1 session', Artisan::output());
    }

    /**
     * The number reaches a DELETE, so a retention of zero would mean "delete
     * everything, including the session open right now". Refused rather than
     * clamped silently, because a zero in Settings is a mistake worth
     * surfacing.
     *
     * @return array<string, array{int}>
     */
    public static function invalidWindows(): array
    {
        return [
            'zero' => [0],
            'negative' => [-30],
        ];
    }

    #[DataProvider('invalidWindows')]
    public function test_an_impossible_retention_window_is_refused(int $days): void
    {
        $session = VisitorSession::factory()->startedAt(now()->subDays(1000))->create();

        $exit = Artisan::call('visitor-sessions:prune', ['--days' => $days]);

        $this->assertSame(1, $exit, 'The command must fail rather than delete everything.');
        $this->assertDatabaseHas('visitor_sessions', ['id' => $session->id]);
    }

    /**
     * A stored setting of 0 must not become "delete everything" either —
     * the gate clamps before the command ever sees it.
     */
    public function test_a_zero_stored_in_settings_is_clamped_not_obeyed(): void
    {
        $this->setRetention(0);

        $session = VisitorSession::factory()->startedAt(now()->subDays(1))->create();

        Artisan::call('visitor-sessions:prune');

        $this->assertDatabaseHas('visitor_sessions', ['id' => $session->id]);
    }

    public function test_pruning_deletes_in_batches_without_missing_rows(): void
    {
        $this->setRetention(30);

        VisitorSession::factory()->count(25)->startedAt(now()->subDays(60))->create();
        VisitorSession::factory()->count(5)->startedAt(now()->subDays(1))->create();

        Artisan::call('visitor-sessions:prune');

        $this->assertSame(5, VisitorSession::count());
    }

    /**
     * Deleting a journey must not touch the lead it produced — the lead is
     * business data with its own lifecycle, the journey is analytics.
     */
    public function test_pruning_a_converted_session_leaves_the_lead_alone(): void
    {
        $this->setRetention(30);

        $session = VisitorSession::factory()->converted()->startedAt(now()->subDays(60))->create();
        $leadId = $session->lead_id;

        Artisan::call('visitor-sessions:prune');

        $this->assertDatabaseMissing('visitor_sessions', ['id' => $session->id]);
        $this->assertDatabaseHas('leads', ['id' => $leadId]);
    }

    public function test_the_prune_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'visitor-sessions:prune'));

        $this->assertCount(1, $events, 'A retention policy nobody runs is not a retention policy.');
        $this->assertSame('10 3 * * *', $events->first()->expression);
    }

    // -----------------------------------------------------------------
    // The published claim
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{string, string}>
     */
    public static function privacyPages(): array
    {
        return [
            'arabic' => ['/privacy-policy', 'تسجيل جلسات الزوار'],
            'english' => ['/en/privacy-policy', 'Visitor Session Recording'],
        ];
    }

    #[DataProvider('privacyPages')]
    public function test_the_privacy_policy_discloses_the_tracking(string $uri, string $heading): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringContainsString($heading, $html);
    }

    public function test_the_policy_states_what_is_not_collected_and_for_how_long(): void
    {
        $html = $this->get('/en/privacy-policy')->assertOk()->getContent();

        $this->assertStringContainsString('never store a full IP address', $html);
        $this->assertStringContainsString('90 days', $html);
        $this->assertStringContainsString('Do Not Track', $html);
        $this->assertStringContainsString('Global Privacy Control', $html);
    }
}
