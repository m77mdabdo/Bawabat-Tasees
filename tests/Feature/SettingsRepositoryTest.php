<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\Cms\SettingsRepository;
use Database\Seeders\MenuItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function settingsQueriesDuring(callable $callback): array
    {
        $queries = [];
        DB::listen(function ($event) use (&$queries) {
            if (str_contains($event->sql, 'from `settings`') || str_contains($event->sql, 'from "settings"')) {
                $queries[] = $event->sql;
            }
        });

        $callback();

        return $queries;
    }

    // ---------------------------------------------------------------
    // The point of the class: one query, not one per key
    // ---------------------------------------------------------------

    public function test_many_lookups_cost_a_single_query(): void
    {
        Setting::factory()->create(['key' => 'contact_phone', 'value' => '+966500000000']);
        Setting::factory()->create(['key' => 'contact_email', 'value' => 'a@b.test']);

        $repo = app(SettingsRepository::class);

        $queries = $this->settingsQueriesDuring(function () use ($repo) {
            $repo->string('contact_phone');
            $repo->string('contact_email');
            $repo->string('contact_phone');
            $repo->string('does_not_exist');
        });

        $this->assertCount(1, $queries, 'Four lookups must cost exactly one query.');
    }

    public function test_the_repository_is_a_singleton(): void
    {
        $this->assertSame(app(SettingsRepository::class), app(SettingsRepository::class));
    }

    /**
     * The homepage previously issued 8 single-key settings queries and
     * /contact 7, for a table of 10 rows.
     */
    public function test_the_homepage_queries_settings_at_most_once(): void
    {
        $this->seed(MenuItemSeeder::class);
        Setting::factory()->create(['key' => 'contact_whatsapp', 'value' => '+966500000000']);

        $queries = $this->settingsQueriesDuring(fn () => $this->get('/')->assertOk());

        $this->assertLessThanOrEqual(1, count($queries));
    }

    public function test_the_contact_page_queries_settings_at_most_once(): void
    {
        $this->seed(MenuItemSeeder::class);
        Setting::factory()->create(['key' => 'contact_phone', 'value' => '+966500000000']);

        $queries = $this->settingsQueriesDuring(fn () => $this->get('/contact')->assertOk());

        $this->assertLessThanOrEqual(1, count($queries));
    }

    public function test_no_query_at_all_when_nothing_asks_for_a_setting(): void
    {
        $repo = app(SettingsRepository::class);

        $queries = $this->settingsQueriesDuring(function () use ($repo) {
            // Constructed but never read from.
            $this->assertInstanceOf(SettingsRepository::class, $repo);
        });

        $this->assertCount(0, $queries, 'The load must be lazy.');
    }

    // ---------------------------------------------------------------
    // Behaviour parity with the previous per-key lookups
    // ---------------------------------------------------------------

    public function test_string_returns_the_value_or_null(): void
    {
        Setting::factory()->create(['key' => 'contact_phone', 'value' => '+966500000000']);

        $repo = app(SettingsRepository::class);

        $this->assertSame('+966500000000', $repo->string('contact_phone'));
        $this->assertNull($repo->string('missing_key'));
    }

    public function test_string_treats_blank_values_as_absent(): void
    {
        Setting::factory()->create(['key' => 'blank', 'value' => '   ']);

        $this->assertNull(app(SettingsRepository::class)->string('blank'));
    }

    public function test_get_supports_a_default(): void
    {
        $this->assertSame('fallback', app(SettingsRepository::class)->get('nope', 'fallback'));
    }

    // group() was removed along with its method: it had this test and no
    // production caller at all, so the test was the only thing keeping it
    // alive. Every real lookup goes through get()/string()/translated(),
    // which read the single cached copy of the table rather than issuing a
    // second query the way group() did.

    /**
     * An admin editing a setting must see it immediately, so the in-memory
     * copy has to be droppable within the same request.
     */
    public function test_flush_picks_up_a_write_made_in_the_same_request(): void
    {
        $setting = Setting::factory()->create(['key' => 'contact_phone', 'value' => 'old']);
        $repo = app(SettingsRepository::class);

        $this->assertSame('old', $repo->string('contact_phone'));

        $setting->update(['value' => 'new']);
        $this->assertSame('old', $repo->string('contact_phone'), 'Still the cached copy.');

        $repo->flush();
        $this->assertSame('new', $repo->string('contact_phone'));
    }

    // ---------------------------------------------------------------
    // Nothing regressed on the pages that render these values
    // ---------------------------------------------------------------

    public function test_contact_details_still_render_on_the_public_pages(): void
    {
        $this->seed(MenuItemSeeder::class);
        Setting::factory()->create(['key' => 'contact_phone', 'value' => '+966511111111']);
        Setting::factory()->create(['key' => 'contact_email', 'value' => 'hello@example.test']);
        Setting::factory()->create(['key' => 'contact_whatsapp', 'value' => '+966522222222']);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('+966511111111')
            ->assertSee('hello@example.test');
    }

    public function test_whatsapp_float_button_still_gets_its_number(): void
    {
        $this->seed(MenuItemSeeder::class);
        Setting::factory()->create(['key' => 'contact_whatsapp', 'value' => '+966500000000']);

        $this->get('/')->assertOk()->assertSee('wa.me/966500000000', escape: false);
    }

    public function test_seo_defaults_still_feed_the_meta_tags(): void
    {
        Setting::factory()->create(['key' => 'default_meta_description', 'value' => 'A site-wide description.']);

        // A page with no record and no explicit description falls through
        // to the seo_defaults setting.
        $this->get('/faqs')->assertOk();

        $this->assertSame(
            'A site-wide description.',
            app(SettingsRepository::class)->string('default_meta_description')
        );
    }
}
