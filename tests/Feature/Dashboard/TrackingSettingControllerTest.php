<?php

namespace Tests\Feature\Dashboard;

use App\Models\TrackingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class TrackingSettingControllerTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    private function seedKeys(): void
    {
        foreach ([
            'meta_pixel_id', 'gtm_container_id', 'ga4_measurement_id',
            'google_ads_conversion_id', 'google_ads_conversion_label', 'tiktok_pixel_id',
        ] as $key) {
            TrackingSetting::create(['key' => $key, 'value' => null, 'is_active' => false]);
        }
    }

    public function test_guest_is_redirected_from_edit(): void
    {
        $this->get(route('dashboard.tracking-settings.edit'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_edit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard.tracking-settings.edit'))->assertForbidden();
    }

    public function test_edit_shows_all_six_fixed_keys(): void
    {
        $admin = $this->makeAdmin();
        $this->seedKeys();

        $response = $this->actingAs($admin)->get(route('dashboard.tracking-settings.edit'));

        $response->assertOk();
        $response->assertSee('settings[meta_pixel_id][value]', false);
        $response->assertSee('settings[gtm_container_id][value]', false);
        $response->assertSee('settings[ga4_measurement_id][value]', false);
        $response->assertSee('settings[google_ads_conversion_id][value]', false);
        $response->assertSee('settings[google_ads_conversion_label][value]', false);
        $response->assertSee('settings[tiktok_pixel_id][value]', false);
    }

    public function test_update_persists_value_and_active_flag(): void
    {
        $admin = $this->makeAdmin();
        $this->seedKeys();

        $response = $this->actingAs($admin)->put(route('dashboard.tracking-settings.update'), [
            'settings' => [
                'meta_pixel_id' => ['value' => '123456789012345', 'is_active' => '1'],
            ],
        ]);

        $response->assertRedirect(route('dashboard.tracking-settings.edit'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('tracking_settings', [
            'key' => 'meta_pixel_id',
            'value' => '123456789012345',
            'is_active' => true,
        ]);
    }

    /**
     * Submitting the form again without a key present in the payload
     * (an unchecked checkbox simply isn't sent) must turn that key off —
     * proves deactivation actually clears is_active, not just leaves it.
     */
    public function test_update_deactivates_a_key_omitted_from_the_payload(): void
    {
        $admin = $this->makeAdmin();
        TrackingSetting::create(['key' => 'meta_pixel_id', 'value' => '123456789012345', 'is_active' => true]);
        foreach (['gtm_container_id', 'ga4_measurement_id', 'google_ads_conversion_id', 'google_ads_conversion_label', 'tiktok_pixel_id'] as $key) {
            TrackingSetting::create(['key' => $key, 'value' => null, 'is_active' => false]);
        }

        $this->actingAs($admin)->put(route('dashboard.tracking-settings.update'), [
            'settings' => [
                'meta_pixel_id' => ['value' => '123456789012345'],
            ],
        ]);

        $this->assertDatabaseHas('tracking_settings', [
            'key' => 'meta_pixel_id',
            'is_active' => false,
        ]);
    }

    public function test_activating_a_key_without_a_value_fails_validation(): void
    {
        $admin = $this->makeAdmin();
        $this->seedKeys();

        $response = $this->actingAs($admin)->put(route('dashboard.tracking-settings.update'), [
            'settings' => [
                'meta_pixel_id' => ['value' => '', 'is_active' => '1'],
            ],
        ]);

        $response->assertSessionHasErrors('settings.meta_pixel_id.value');
        $this->assertDatabaseHas('tracking_settings', ['key' => 'meta_pixel_id', 'is_active' => false]);
    }

    public function test_guest_cannot_submit_update(): void
    {
        $this->seedKeys();

        $this->put(route('dashboard.tracking-settings.update'), [
            'settings' => ['meta_pixel_id' => ['value' => '123456789012345', 'is_active' => '1']],
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('tracking_settings', ['key' => 'meta_pixel_id', 'is_active' => false]);
    }

    // -----------------------------------------------------------------
    // Format validation
    //
    // These values are interpolated into inline <script> blocks by
    // components/tracking-scripts.blade.php. Blade's escaping does hold
    // there, but pinning each ID to its platform's published shape means
    // nothing that is not an ID reaches the page at all.
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{string, string}>
     */
    public static function validIds(): array
    {
        return [
            'meta pixel' => ['meta_pixel_id', '123456789012345'],
            'gtm container' => ['gtm_container_id', 'GTM-ABC1234'],
            'ga4 measurement' => ['ga4_measurement_id', 'G-ABCDE12345'],
            'google ads conversion' => ['google_ads_conversion_id', 'AW-123456789'],
            'google ads label' => ['google_ads_conversion_label', 'AbC-dEfGhIjKlMnOp'],
            'tiktok pixel' => ['tiktok_pixel_id', 'C4A1B2C3D4E5F6G7H8I9'],
        ];
    }

    #[DataProvider('validIds')]
    public function test_a_well_formed_id_is_accepted(string $key, string $value): void
    {
        $admin = $this->makeAdmin();
        $this->seedKeys();

        $this->actingAs($admin)
            ->put(route('dashboard.tracking-settings.update'), [
                'settings' => [$key => ['value' => $value, 'is_active' => '1']],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tracking_settings', ['key' => $key, 'value' => $value, 'is_active' => true]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function malformedIds(): array
    {
        return [
            'meta pixel with letters' => ['meta_pixel_id', 'GTM-ABC1234'],
            'meta pixel too short' => ['meta_pixel_id', '123'],
            'gtm without prefix' => ['gtm_container_id', 'ABC1234'],
            'ga4 with the wrong prefix' => ['ga4_measurement_id', 'GTM-ABCDE12345'],
            'google ads without digits' => ['google_ads_conversion_id', 'AW-ABCDEFGHI'],
            'label with a space' => ['google_ads_conversion_label', 'AbCd EfGh'],
            'tiktok with punctuation' => ['tiktok_pixel_id', 'C4A1B2C3D4E5F6G7H8I9!'],
        ];
    }

    #[DataProvider('malformedIds')]
    public function test_a_malformed_id_is_rejected(string $key, string $value): void
    {
        $admin = $this->makeAdmin();
        $this->seedKeys();

        $this->actingAs($admin)
            ->put(route('dashboard.tracking-settings.update'), [
                'settings' => [$key => ['value' => $value, 'is_active' => '1']],
            ])
            ->assertSessionHasErrors("settings.{$key}.value");

        $this->assertDatabaseHas('tracking_settings', ['key' => $key, 'value' => null]);
    }

    /**
     * The reason the format rules exist at all: nothing carrying a
     * script-breaking character can be stored, let alone rendered.
     *
     * @return array<string, array{string}>
     */
    public static function scriptBreakingValues(): array
    {
        return [
            'closing script tag' => ['</script><script>alert(1)</script>'],
            'quote break-out' => ["');alert(1);//"],
            'html comment' => ['<!--'],
            'newline injection' => ["123456789012345\nalert(1)"],
        ];
    }

    #[DataProvider('scriptBreakingValues')]
    public function test_a_script_breaking_value_never_reaches_the_database(string $value): void
    {
        $admin = $this->makeAdmin();
        $this->seedKeys();

        $this->actingAs($admin)
            ->put(route('dashboard.tracking-settings.update'), [
                'settings' => ['meta_pixel_id' => ['value' => $value, 'is_active' => '1']],
            ])
            ->assertSessionHasErrors('settings.meta_pixel_id.value');

        $this->assertDatabaseHas('tracking_settings', ['key' => 'meta_pixel_id', 'value' => null]);
    }

    /**
     * The format message has to name the expected shape — "the format is
     * invalid" on its own leaves the admin guessing.
     */
    public function test_the_format_error_names_the_expected_shape(): void
    {
        $admin = $this->makeAdmin();
        $this->seedKeys();

        $errors = $this->actingAs($admin)
            ->put(route('dashboard.tracking-settings.update'), [
                'settings' => ['gtm_container_id' => ['value' => 'nope', 'is_active' => '1']],
            ])
            ->assertSessionHasErrors('settings.gtm_container_id.value')
            ->getSession()
            ->get('errors')
            ->get('settings.gtm_container_id.value');

        $this->assertStringContainsString('GTM-', $errors[0]);
    }

    /**
     * The Arabic string that used to be passed to __() as its own key, so an
     * English-locale admin was shown Arabic.
     */
    public function test_the_required_when_active_error_follows_the_dashboard_locale(): void
    {
        $admin = $this->makeAdmin();
        $admin->update(['locale' => 'en']);
        $this->seedKeys();

        $errors = $this->actingAs($admin)
            ->put(route('dashboard.tracking-settings.update'), [
                'settings' => ['meta_pixel_id' => ['value' => '', 'is_active' => '1']],
            ])
            ->assertSessionHasErrors('settings.meta_pixel_id.value')
            ->getSession()
            ->get('errors')
            ->get('settings.meta_pixel_id.value');

        $this->assertSame(__('dashboard.tracking_settings.validation.required_when_active', [], 'en'), $errors[0]);
        $this->assertMatchesRegularExpression('/^[\x20-\x7E]+$/', $errors[0], 'An English admin must not be shown the Arabic string.');
    }

    /**
     * Every key the editor screen renders must have a format rule — a new
     * key added to the form without one would reach the inline <script>
     * validated only by length.
     */
    public function test_every_editable_key_has_a_format_rule(): void
    {
        $admin = $this->makeAdmin();
        $this->seedKeys();

        $html = $this->actingAs($admin)->get(route('dashboard.tracking-settings.edit'))->assertOk()->getContent();

        foreach (TrackingSetting::pluck('key') as $key) {
            $this->assertStringContainsString("settings[{$key}][value]", $html);
            $this->assertArrayHasKey($key, TrackingSetting::FORMATS);
        }
    }
}
