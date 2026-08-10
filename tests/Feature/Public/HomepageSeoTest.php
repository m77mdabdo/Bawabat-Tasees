<?php

namespace Tests\Feature\Public;

use App\Models\Setting;
use App\Services\Cms\SettingsRepository;
use App\Services\Cms\SettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

/**
 * The homepage is the only public page with no backing record, so it had
 * nothing to hang a SeoMeta row off and fell all the way through the
 * resolver to default_meta_title — which ships as a self-labelling
 * placeholder. The result: the site's single most-shared <title>, in both
 * locales, read "Bawabat Taasees Al Sharikat (placeholder — update in
 * Settings)", and og:title and twitter:title with it.
 *
 * home_meta_title / home_meta_description are its equivalent of a SeoMeta
 * row: bilingual, admin-editable through the Settings screen that already
 * exists, and seeded with real copy so a fresh install is correct before
 * anyone opens it.
 */
class HomepageSeoTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function homepages(): array
    {
        return [
            'arabic' => ['/', 'ar'],
            'english' => ['/en', 'en'],
        ];
    }

    #[DataProvider('homepages')]
    public function test_the_homepage_title_is_not_a_placeholder(string $uri): void
    {
        $html = $this->get($uri)->assertOk()->getContent();

        preg_match('#<title>(.*?)</title>#s', $html, $matches);

        $this->assertNotEmpty($matches[1] ?? '');
        $this->assertStringNotContainsString('placeholder', $matches[1]);
    }

    #[DataProvider('homepages')]
    public function test_the_seeded_homepage_title_is_used_in_the_right_locale(string $uri, string $locale): void
    {
        $expected = app(SettingsRepository::class)->translated('home_meta_title', $locale);

        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringContainsString('<title>'.e($expected).'</title>', $html);
    }

    public function test_the_two_locales_get_different_titles(): void
    {
        preg_match('#<title>(.*?)</title>#s', $this->get('/')->getContent(), $ar);
        preg_match('#<title>(.*?)</title>#s', $this->get('/en')->getContent(), $en);

        $this->assertNotSame($ar[1], $en[1], 'A crawler sees / and /en as separate pages; they need separate titles.');
    }

    #[DataProvider('homepages')]
    public function test_the_social_tags_follow_the_same_value(string $uri, string $locale): void
    {
        $expected = e(app(SettingsRepository::class)->translated('home_meta_title', $locale));

        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringContainsString('property="og:title" content="'.$expected.'"', $html);
        $this->assertStringContainsString('name="twitter:title" content="'.$expected.'"', $html);
    }

    #[DataProvider('homepages')]
    public function test_the_homepage_description_is_its_own(string $uri, string $locale): void
    {
        $expected = e(app(SettingsRepository::class)->translated('home_meta_description', $locale));

        $html = $this->get($uri)->assertOk()->getContent();

        $this->assertStringContainsString('name="description" content="'.$expected.'"', $html);
        $this->assertStringNotContainsString('placeholder', $expected);
    }

    /**
     * The setting is the homepage's SeoMeta row, so clearing it has to fall
     * back the same way an empty SeoMeta row does — never to nothing.
     */
    public function test_clearing_the_setting_falls_back_rather_than_emptying_the_tag(): void
    {
        Setting::where('key', 'home_meta_title')->update(['value' => null]);
        Setting::where('key', 'home_meta_description')->update(['value' => null]);
        app(SettingsRepository::class)->flush();

        preg_match('#<title>(.*?)</title>#s', $this->get('/')->assertOk()->getContent(), $matches);

        $this->assertNotEmpty(trim($matches[1] ?? ''));
    }

    /**
     * A setting with only Arabic filled in must show Arabic on /en rather
     * than rendering blank — the same fallback every translatable model
     * field uses.
     */
    public function test_an_untranslated_value_falls_back_to_arabic(): void
    {
        Setting::where('key', 'home_meta_title')->update(['value' => ['ar' => 'عنوان عربي فقط']]);
        app(SettingsRepository::class)->flush();

        $this->assertStringContainsString('<title>عنوان عربي فقط</title>', $this->get('/en')->assertOk()->getContent());
    }

    // -----------------------------------------------------------------
    // Admin editing
    // -----------------------------------------------------------------

    public function test_the_settings_screen_exposes_both_fields(): void
    {
        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.settings.edit'))
            ->assertOk()
            ->getContent();

        foreach (['home_meta_title', 'home_meta_description'] as $key) {
            $this->assertStringContainsString('settings['.$key.'][ar]', $html);
            $this->assertStringContainsString('settings['.$key.'][en]', $html);
        }
    }

    public function test_an_admin_edit_reaches_the_homepage(): void
    {
        $payload = ['settings' => []];

        foreach (SettingsService::keys() as $key => $type) {
            if ($type === SettingsService::TYPE_IMAGE) {
                continue;
            }

            $payload['settings'][$key] = $type === SettingsService::TYPE_TRANSLATED
                ? ['ar' => 'قيمة', 'en' => 'Value']
                : null;
        }

        $payload['settings']['home_meta_title'] = ['ar' => 'عنوان جديد', 'en' => 'A New Title'];

        $this->actingAs($this->makeAdmin())
            ->put(route('dashboard.settings.update'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertStringContainsString('<title>عنوان جديد</title>', $this->get('/')->getContent());
        $this->assertStringContainsString('<title>A New Title</title>', $this->get('/en')->getContent());
    }

    /**
     * The homepage reads two more settings than it used to, and the whole
     * point of SettingsRepository is that this costs nothing.
     */
    public function test_the_homepage_still_queries_settings_only_once(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->get('/')->assertOk();

        // Matched on the table, not the substring: `tracking_settings` also
        // contains "settings" and is a separate, legitimate query.
        $this->assertLessThanOrEqual(
            1,
            count(array_filter($queries, fn ($sql) => str_contains($sql, 'from `settings`') || str_contains($sql, 'from "settings"'))),
            'The settings table must still be read at most once per request.'
        );
    }
}
