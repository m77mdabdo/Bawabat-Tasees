<?php

namespace Tests\Feature\Public;

use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Sara Al-Qahtani',
            'email' => 'sara@example.com',
            'message' => 'أرغب في الاستفسار عن خدماتكم.',
            'consent_given' => '1',
            'website_url' => '',
        ], $overrides);
    }

    public function test_contact_page_loads_and_shows_real_contact_info_from_settings(): void
    {
        Setting::create(['key' => 'contact_phone', 'value' => '+966 11 000 0000', 'group' => 'contact']);
        Setting::create(['key' => 'contact_email', 'value' => 'info@example.com', 'group' => 'contact']);

        $response = $this->get(route('contact'));

        $response->assertOk();
        $response->assertSee('+966 11 000 0000');
        $response->assertSee('info@example.com');
    }

    public function test_valid_submission_creates_a_contact_lead_and_redirects_back(): void
    {
        $response = $this->post(route('contact.store'), $this->validPayload());

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('leads', [
            'full_name' => 'Sara Al-Qahtani',
            'email' => 'sara@example.com',
            'type' => 'contact',
            'consent_given' => true,
        ]);
    }

    public function test_honeypot_filled_does_not_create_a_lead_but_still_shows_success(): void
    {
        $response = $this->post(route('contact.store'), $this->validPayload([
            'website_url' => 'https://spambot.example.com',
        ]));

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('status');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_more_than_five_submissions_per_minute_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contact.store'), $this->validPayload([
                'email' => "contact{$i}@example.com",
            ]))->assertRedirect(route('contact'));
        }

        $response = $this->post(route('contact.store'), $this->validPayload([
            'email' => 'contact6@example.com',
        ]));

        $response->assertStatus(429);
        $this->assertDatabaseCount('leads', 5);
    }

    public function test_message_is_required(): void
    {
        $response = $this->post(route('contact.store'), $this->validPayload(['message' => '']));

        $response->assertSessionHasErrors('message');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_consent_is_required(): void
    {
        $response = $this->post(route('contact.store'), $this->validPayload(['consent_given' => null]));

        $response->assertSessionHasErrors('consent_given');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_attribution_snapshots_populate_lead_source_fields(): void
    {
        $touch = json_encode([
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'landing_page' => '/contact',
            'referrer' => 'https://google.com/',
        ]);

        $this->post(route('contact.store'), $this->validPayload([
            'first_touch_snapshot' => $touch,
            'latest_touch_snapshot' => $touch,
        ]))->assertRedirect(route('contact'));

        $lead = Lead::firstOrFail();

        $this->assertSame('google', $lead->source_platform);
        $this->assertSame('cpc', $lead->utm_medium);
        $this->assertSame('/contact', $lead->landing_page_url);
    }
}
