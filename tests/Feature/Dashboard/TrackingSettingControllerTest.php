<?php

namespace Tests\Feature\Dashboard;

use App\Models\TrackingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        TrackingSetting::create(['key' => 'meta_pixel_id', 'value' => '123', 'is_active' => true]);
        foreach (['gtm_container_id', 'ga4_measurement_id', 'google_ads_conversion_id', 'google_ads_conversion_label', 'tiktok_pixel_id'] as $key) {
            TrackingSetting::create(['key' => $key, 'value' => null, 'is_active' => false]);
        }

        $this->actingAs($admin)->put(route('dashboard.tracking-settings.update'), [
            'settings' => [
                'meta_pixel_id' => ['value' => '123'],
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
            'settings' => ['meta_pixel_id' => ['value' => '123', 'is_active' => '1']],
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('tracking_settings', ['key' => 'meta_pixel_id', 'is_active' => false]);
    }
}
