<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Campaign;
use App\Models\ConversionEvent;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\Page;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\Testimonial;
use App\Services\Dashboard\ReportingService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the production/demo split. Fabricated testimonials on the public
 * site, or invented revenue driving the Reports ROI figures, would be
 * actively misleading — so the default seed must never produce them.
 */
class DemoDataSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seed_creates_no_fabricated_rows(): void
    {
        $this->seed();

        $this->assertSame(0, Testimonial::count(), 'Testimonials are invented client quotes — demo only.');
        $this->assertSame(0, Campaign::count(), 'Sample campaigns carry made-up budget/spend — demo only.');
        $this->assertSame(0, Lead::count(), 'Sample leads are fabricated — demo only.');
        $this->assertSame(0, ConversionEvent::count(), 'Sample revenue would corrupt the ROI figures — demo only.');
    }

    public function test_default_seed_still_creates_real_content(): void
    {
        $this->seed();

        $this->assertGreaterThan(0, Service::count());
        $this->assertGreaterThan(0, Country::count());
        $this->assertGreaterThan(0, Faq::count());
        $this->assertGreaterThan(0, Page::count());
        $this->assertGreaterThan(0, Article::count());
        $this->assertGreaterThan(0, SeoMeta::count());
        $this->assertDatabaseCount('settings', 10);
        $this->assertDatabaseCount('lead_sources', 11);
        $this->assertDatabaseCount('tracking_settings', 6);
    }

    public function test_reports_show_zero_revenue_on_a_production_seed(): void
    {
        $this->seed();

        $funnel = app(ReportingService::class)->funnel(365);

        $this->assertSame(0, $funnel['total']);
        $this->assertSame(0.0, $funnel['value'], 'A production seed must never report invented revenue.');
    }

    public function test_demo_seeder_adds_the_sample_rows_when_run_explicitly(): void
    {
        $this->seed();
        $this->seed(DemoDataSeeder::class);

        $this->assertGreaterThan(0, Testimonial::count());
        $this->assertGreaterThan(0, Campaign::count());
        $this->assertGreaterThan(0, Lead::count());
        $this->assertGreaterThan(0, ConversionEvent::count());
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        $this->seed();
        $this->seed(DemoDataSeeder::class);

        $counts = [Testimonial::count(), Campaign::count(), Lead::count(), ConversionEvent::count()];

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($counts, [Testimonial::count(), Campaign::count(), Lead::count(), ConversionEvent::count()]);
    }

    /**
     * The public site must render correctly with no testimonials at all —
     * that is now the default state, not an edge case.
     */
    public function test_homepage_renders_without_testimonials(): void
    {
        $this->seed();

        $this->assertSame(0, Testimonial::count());
        $this->get('/')->assertOk();
        $this->get('/en')->assertOk();
    }
}
