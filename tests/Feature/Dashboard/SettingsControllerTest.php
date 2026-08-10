<?php

namespace Tests\Feature\Dashboard;

use App\Models\Setting;
use App\Models\User;
use App\Services\Cms\SettingsRepository;
use App\Services\Cms\SettingsService;
use App\Services\Seo\SeoTagService;
use Database\Seeders\MenuItemSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
    }

    /**
     * A full valid payload — individual tests override one field at a time
     * so a failure points at the field, not at an incomplete submission.
     */
    private function payload(array $overrides = []): array
    {
        return ['settings' => array_replace_recursive([
            'site_name' => ['ar' => 'بوابة تأسيس', 'en' => 'Bawabat Taasees'],
            'site_tagline' => ['ar' => 'شعارنا', 'en' => 'Our tagline'],
            'contact_phone' => '+966 55 123 4567',
            'contact_email' => 'hello@example.test',
            'contact_address' => ['ar' => 'الرياض', 'en' => 'Riyadh'],
            'contact_whatsapp' => '+966551234567',
            'social_facebook' => 'https://facebook.com/real',
            'social_instagram' => '',
            'social_twitter' => '',
            'social_linkedin' => '',
            'social_youtube' => '',
            'social_tiktok' => '',
            'social_snapchat' => '',
            'default_meta_title' => 'Title',
            'default_meta_description' => 'Description',
        ], $overrides['settings'] ?? [])] + array_diff_key($overrides, ['settings' => null]);
    }

    private function save(array $overrides = [])
    {
        return $this->actingAs($this->makeAdmin())
            ->put(route('dashboard.settings.update'), $this->payload($overrides));
    }

    // ---------------------------------------------------------------
    // The section exists at all
    // ---------------------------------------------------------------

    public function test_settings_is_a_real_section_not_a_coming_soon_placeholder(): void
    {
        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.settings.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('dashboard.settings.title'), $html);
        $this->assertStringNotContainsString('coming soon', $html);
        $this->assertStringNotContainsString('🚧', $html);
    }

    public function test_the_editor_renders_every_defined_key(): void
    {
        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.settings.edit'))
            ->assertOk()
            ->getContent();

        foreach (SettingsService::keys() as $key => $type) {
            $needle = $type === SettingsService::TYPE_IMAGE
                ? 'name="'.$key.'"'
                : 'settings['.$key.']';

            $this->assertStringContainsString($needle, $html, "Editor is missing {$key}");
        }
    }

    public function test_the_editor_shows_current_values(): void
    {
        // Must go through the model: a query-builder update bypasses the
        // 'array' cast and writes an unencoded string past the json column.
        Setting::where('key', 'contact_email')->first()->update(['value' => 'current@example.test']);

        $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.settings.edit'))
            ->assertOk()
            ->assertSee('current@example.test');
    }

    // ---------------------------------------------------------------
    // Persistence per group
    // ---------------------------------------------------------------

    public function test_general_group_persists(): void
    {
        $this->save()->assertRedirect(route('dashboard.settings.edit'));

        $repo = app(SettingsRepository::class);
        $this->assertSame('بوابة تأسيس', $repo->translated('site_name', 'ar'));
        $this->assertSame('Bawabat Taasees', $repo->translated('site_name', 'en'));
    }

    public function test_contact_group_persists_and_re_renders_on_the_public_pages(): void
    {
        $this->seed(MenuItemSeeder::class);

        $this->save([
            'settings' => [
                'contact_phone' => '+966 55 999 8888',
                'contact_email' => 'real@example.test',
                'contact_address' => ['ar' => 'جدة، السعودية', 'en' => 'Jeddah, Saudi Arabia'],
            ],
        ])->assertRedirect();

        $this->get('/')->assertOk()->assertSee('+966 55 999 8888')->assertSee('real@example.test');
        $this->get('/contact')->assertOk()->assertSee('جدة، السعودية', escape: false);
        $this->get('/en/contact')->assertOk()->assertSee('Jeddah, Saudi Arabia');
    }

    public function test_whatsapp_group_persists_and_drives_the_floating_button(): void
    {
        $this->seed(MenuItemSeeder::class);

        $this->save(['settings' => ['contact_whatsapp' => '+966512345678']])->assertRedirect();

        $this->get('/')->assertOk()->assertSee('wa.me/966512345678', escape: false);
    }

    public function test_social_group_persists_and_renders_in_the_footer(): void
    {
        $this->seed(MenuItemSeeder::class);

        $this->save(['settings' => [
            'social_facebook' => 'https://facebook.com/bawabat',
            'social_linkedin' => 'https://linkedin.com/company/bawabat',
        ]])->assertRedirect();

        $this->get('/')->assertOk()
            ->assertSee('https://facebook.com/bawabat', escape: false)
            ->assertSee('https://linkedin.com/company/bawabat', escape: false);
    }

    public function test_seo_defaults_persist_and_are_used_as_the_last_resort(): void
    {
        $this->save(['settings' => [
            'default_meta_title' => 'Fallback Title',
            'default_meta_description' => 'A real site description.',
        ]])->assertRedirect();

        app(SettingsRepository::class)->flush();

        // Asserted against the resolver rather than a page: every public
        // view passes an explicit :seo-description, which by design
        // outranks the site-wide default — so no page would show it.
        $resolved = app(SeoTagService::class)->resolve();

        $this->assertSame('Fallback Title', $resolved['title']);
        $this->assertSame('A real site description.', $resolved['description']);
    }

    public function test_the_logo_upload_is_stored_and_used(): void
    {
        Storage::fake('public');
        $this->seed(MenuItemSeeder::class);

        $this->actingAs($this->makeAdmin())
            ->put(route('dashboard.settings.update'), $this->payload() + [
                'site_logo' => UploadedFile::fake()->image('logo.png'),
            ])->assertRedirect();

        $path = app(SettingsRepository::class)->string('site_logo');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->get('/')->assertOk()->assertSee($path, escape: false);
    }

    public function test_omitting_the_logo_keeps_the_existing_one(): void
    {
        Setting::where('key', 'site_logo')->first()->update(['value' => 'settings/existing.png']);

        $this->save()->assertRedirect();

        $this->assertSame('settings/existing.png', app(SettingsRepository::class)->string('site_logo'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function acceptedLogoFormats(): array
    {
        return [
            'png' => ['logo.png', 'png'],
            'jpeg' => ['logo.jpg', 'jpg'],
            'webp' => ['logo.webp', 'webp'],
        ];
    }

    #[DataProvider('acceptedLogoFormats')]
    public function test_every_offered_logo_format_is_actually_accepted(string $name, string $extension): void
    {
        Storage::fake('public');

        $this->actingAs($this->makeAdmin())
            ->put(route('dashboard.settings.update'), $this->payload() + [
                'site_logo' => UploadedFile::fake()->image($name),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $path = app(SettingsRepository::class)->string('site_logo');

        $this->assertNotNull($path);
        $this->assertStringEndsWith('.'.$extension, $path);
    }

    /**
     * The form used to offer SVG in both `accept` and `mimes:` while the
     * `image` rule — which runs first — rejected it, so picking one failed
     * with no explanation. SVG is now refused deliberately and consistently:
     * it is an executable document, and storage/settings/*.svg is served
     * same-origin, so navigating straight to it would run any script it
     * carries in this site's origin.
     */
    public function test_an_svg_logo_is_rejected_and_leaves_the_current_one_alone(): void
    {
        Storage::fake('public');
        Setting::where('key', 'site_logo')->first()->update(['value' => 'settings/existing.png']);

        $svg = UploadedFile::fake()->createWithContent(
            'logo.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
        );

        $this->actingAs($this->makeAdmin())
            ->put(route('dashboard.settings.update'), $this->payload() + ['site_logo' => $svg])
            ->assertSessionHasErrors('site_logo');

        $this->assertSame('settings/existing.png', app(SettingsRepository::class)->string('site_logo'));
        Storage::disk('public')->assertDirectoryEmpty('settings');
    }

    /**
     * The form must not advertise a format the validator refuses — that
     * mismatch is what this pair of changes existed to remove.
     */
    public function test_the_form_does_not_offer_a_format_the_validator_rejects(): void
    {
        $html = $this->actingAs($this->makeAdmin())
            ->get(route('dashboard.settings.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('accept="image/jpeg,image/png,image/webp"', $html);
        // Narrow, not a bare 'svg': the dashboard chrome is full of inline
        // <svg> icons.
        $this->assertStringNotContainsString('image/svg+xml', $html);
    }

    // ---------------------------------------------------------------
    // Empty socials must not render a broken icon
    // ---------------------------------------------------------------

    public function test_a_social_with_no_value_renders_nothing_in_the_footer(): void
    {
        $this->seed(MenuItemSeeder::class);

        $this->save(['settings' => ['social_facebook' => 'https://facebook.com/only-one']])->assertRedirect();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('https://facebook.com/only-one', $html);
        // Exactly one social anchor, not seven empty ones.
        $this->assertSame(1, substr_count($html, 'rel="noopener noreferrer"'));
    }

    public function test_no_social_row_at_all_when_none_are_configured(): void
    {
        $this->seed(MenuItemSeeder::class);

        // The seeder leaves every social empty.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('rel="noopener noreferrer"', $html);
    }

    public function test_the_seeder_leaves_socials_empty_rather_than_placeholder_urls(): void
    {
        foreach (['facebook', 'instagram', 'twitter', 'linkedin'] as $platform) {
            $this->assertNull(
                app(SettingsRepository::class)->string('social_'.$platform),
                'Placeholder social URLs would render as broken footer links.'
            );
        }
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_an_invalid_email_is_rejected(): void
    {
        $this->save(['settings' => ['contact_email' => 'not-an-email']])
            ->assertSessionHasErrors('settings.contact_email');
    }

    public function test_an_invalid_social_url_is_rejected(): void
    {
        $this->save(['settings' => ['social_facebook' => 'not a url']])
            ->assertSessionHasErrors('settings.social_facebook');
    }

    public function test_a_non_http_social_url_is_rejected(): void
    {
        $this->save(['settings' => ['social_facebook' => 'javascript:alert(1)']])
            ->assertSessionHasErrors('settings.social_facebook');
    }

    public function test_an_invalid_phone_is_rejected(): void
    {
        $this->save(['settings' => ['contact_phone' => 'call me maybe']])
            ->assertSessionHasErrors('settings.contact_phone');
    }

    public function test_english_only_bilingual_value_is_rejected(): void
    {
        $this->save(['settings' => ['contact_address' => ['ar' => '', 'en' => 'English only']]])
            ->assertSessionHasErrors('settings.contact_address.ar');
    }

    public function test_blank_values_are_stored_as_null_not_empty_strings(): void
    {
        $this->save(['settings' => ['contact_phone' => '']])->assertRedirect();

        $this->assertNull(app(SettingsRepository::class)->string('contact_phone'));
    }

    // ---------------------------------------------------------------
    // Arbitrary-key protection
    // ---------------------------------------------------------------

    /**
     * Mirrors TrackingSettingController: writes iterate the known
     * definition, so a crafted key can neither create a row nor overwrite
     * one that is not editable.
     */
    public function test_an_unknown_settings_key_is_ignored(): void
    {
        $before = Setting::count();

        $this->save(['settings' => ['evil_key' => 'pwned', 'app_key' => 'stolen']])->assertRedirect();

        $this->assertSame($before, Setting::count());
        $this->assertDatabaseMissing('settings', ['key' => 'evil_key']);
        $this->assertDatabaseMissing('settings', ['key' => 'app_key']);
    }

    public function test_the_definition_and_the_seeder_agree(): void
    {
        foreach (array_keys(SettingsService::keys()) as $key) {
            $this->assertTrue(
                Setting::where('key', $key)->exists(),
                'Seeder is missing '.$key
            );
        }
    }

    // ---------------------------------------------------------------
    // Cache coherence
    // ---------------------------------------------------------------

    /**
     * SettingsRepository caches the table per request, so a write has to
     * flush it — otherwise the redirect re-renders the OLD values.
     */
    public function test_the_edit_page_shows_the_new_value_immediately_after_saving(): void
    {
        $this->actingAs($this->makeAdmin())
            ->followingRedirects()
            ->put(route('dashboard.settings.update'), $this->payload([
                'settings' => ['contact_email' => 'fresh@example.test'],
            ]))
            ->assertOk()
            ->assertSee('fresh@example.test');
    }

    // ---------------------------------------------------------------
    // Gating
    // ---------------------------------------------------------------

    public function test_guest_is_redirected(): void
    {
        $this->get(route('dashboard.settings.edit'))->assertRedirect(route('login'));
        $this->put(route('dashboard.settings.update'), $this->payload())->assertRedirect(route('login'));

        $this->assertNotSame('hello@example.test', app(SettingsRepository::class)->string('contact_email'));
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.settings.edit'))->assertForbidden();
        $this->actingAs($user)->put(route('dashboard.settings.update'), $this->payload())->assertForbidden();

        $this->assertNotSame('hello@example.test', app(SettingsRepository::class)->string('contact_email'));
    }

    // ---------------------------------------------------------------
    // Re-seeding must not wipe real data
    // ---------------------------------------------------------------

    public function test_reseeding_does_not_overwrite_admin_entered_values(): void
    {
        $this->save(['settings' => ['contact_email' => 'kept@example.test']])->assertRedirect();

        $this->seed(SettingsSeeder::class);

        app(SettingsRepository::class)->flush();
        $this->assertSame('kept@example.test', app(SettingsRepository::class)->string('contact_email'));
    }
}
