<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\ConversionEvent;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\TrackingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every model has a working factory, so tests stop hand-rolling
 * ::create([...]) payloads. These assertions are deliberately thin — the
 * point is that each factory actually persists against the real schema,
 * including NOT NULL columns with no portable default.
 */
class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('models')]
    public function test_factory_creates_a_persisted_model(string $model): void
    {
        $instance = $model::factory()->create();

        $this->assertTrue($instance->exists);
        $this->assertDatabaseHas($instance->getTable(), [$instance->getKeyName() => $instance->getKey()]);
    }

    #[DataProvider('models')]
    public function test_factory_can_make_several_without_unique_collisions(string $model): void
    {
        $this->assertCount(3, $model::factory()->count(3)->create());
    }

    public static function models(): array
    {
        return [
            'Article' => [Article::class],
            'Campaign' => [Campaign::class],
            'Comment' => [Comment::class],
            'ConversionEvent' => [ConversionEvent::class],
            'Country' => [Country::class],
            'Faq' => [Faq::class],
            'Lead' => [Lead::class],
            'LeadSource' => [LeadSource::class],
            'Media' => [Media::class],
            'Page' => [Page::class],
            'PageSection' => [PageSection::class],
            'SeoMeta' => [SeoMeta::class],
            'Service' => [Service::class],
            'Setting' => [Setting::class],
            'Testimonial' => [Testimonial::class],
            'TrackingSetting' => [TrackingSetting::class],
            'User' => [User::class],
        ];
    }

    // ---------------------------------------------------------------
    // States that encode real behaviour
    // ---------------------------------------------------------------

    public function test_article_states(): void
    {
        $this->assertTrue(Article::factory()->create()->is_published);
        $this->assertFalse(Article::factory()->draft()->create()->is_published);
        $this->assertTrue(Article::factory()->scheduled()->create()->published_at->isFuture());
    }

    public function test_conversion_event_always_sets_occurred_at(): void
    {
        // NOT NULL with no portable default — SQLite rejects an omission.
        $this->assertNotNull(ConversionEvent::factory()->create()->occurred_at);
        $this->assertSame(0, ConversionEvent::whereNull('occurred_at')->count());
    }

    public function test_conversion_event_milestone_state_is_not_a_won_type(): void
    {
        $event = ConversionEvent::factory()->milestone()->create();

        $this->assertNotContains($event->event_type, ConversionEvent::WON_TYPES);
        $this->assertFalse($event->lead->isConverted());
    }

    public function test_comment_defaults_to_pending(): void
    {
        $this->assertSame('pending', Comment::factory()->create()->status);
        $this->assertSame('approved', Comment::factory()->approved()->create()->status);
    }

    public function test_relationship_factories_wire_up_correctly(): void
    {
        $this->assertNotNull(PageSection::factory()->create()->page);
        $this->assertNotNull(Comment::factory()->create()->article);
        $this->assertNotNull(ConversionEvent::factory()->create()->lead);
        $this->assertNotNull(SeoMeta::factory()->create()->seoMetable);
    }

    public function test_translatable_factories_populate_both_locales(): void
    {
        $service = Service::factory()->create();

        $this->assertNotSame('', $service->getTranslation('name', 'ar', false));
        $this->assertNotSame('', $service->getTranslation('name', 'en', false));
    }

    public function test_inactive_states_are_excluded_by_the_active_scopes(): void
    {
        Service::factory()->create();
        Service::factory()->inactive()->create();
        Country::factory()->inactive()->create();
        Faq::factory()->inactive()->create();
        Testimonial::factory()->inactive()->create();

        $this->assertSame(1, Service::active()->count());
        $this->assertSame(0, Country::active()->count());
        $this->assertSame(0, Faq::active()->count());
        $this->assertSame(0, Testimonial::active()->count());
    }

    public function test_lead_campaign_state_matches_a_campaign_external_id(): void
    {
        $campaign = Campaign::factory()->create();
        $lead = Lead::factory()->fromCampaign($campaign->external_campaign_id)->create();

        $this->assertSame($campaign->external_campaign_id, $lead->campaign_id);
    }
}
