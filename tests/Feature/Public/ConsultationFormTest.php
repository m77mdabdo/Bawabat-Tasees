<?php

namespace Tests\Feature\Public;

use App\Models\Lead;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationFormTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(array $overrides = []): Service
    {
        return Service::create(array_merge([
            'slug' => 'company-formation',
            'name' => ['ar' => 'تأسيس الشركات'],
            'summary' => ['ar' => 'ملخص'],
            'body' => ['ar' => 'محتوى'],
            'requirements' => ['ar' => 'متطلبات'],
            'process' => ['ar' => 'خطوات'],
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Ahmed Al-Otaibi',
            'phone' => '+966500000000',
            'email' => 'ahmed@example.com',
            'consent_given' => '1',
            'website_url' => '',
        ], $overrides);
    }

    public function test_consultation_page_loads_with_active_services_listed(): void
    {
        $this->makeService();

        $response = $this->get(route('consultation'));

        $response->assertOk();
        $response->assertSee('تأسيس الشركات');
    }

    public function test_valid_submission_creates_a_lead_and_redirects_back_with_flash_status(): void
    {
        $service = $this->makeService();

        $response = $this->post(route('consultation.store'), $this->validPayload([
            'requested_service_id' => $service->id,
        ]));

        $response->assertRedirect(route('consultation'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('leads', [
            'full_name' => 'Ahmed Al-Otaibi',
            'email' => 'ahmed@example.com',
            'type' => 'consultation',
            'requested_service_id' => $service->id,
            'consent_given' => true,
        ]);

        $lead = Lead::first();
        $this->assertNotNull($lead->consented_at);
    }

    /**
     * Mirrors the acceptance-criteria URL exactly:
     * /consultation?utm_source=facebook&utm_medium=paid_social&utm_campaign=test&campaign_id=123&adset_id=456&ad_id=789
     *
     * attribution.js runs client-side and can't execute inside a PHPUnit
     * HTTP test, so this simulates its output directly: the GET below
     * proves the page still renders correctly when hit with that exact
     * query string, and the POST sends the same first_touch_snapshot/
     * latest_touch_snapshot JSON a real browser's JS would have derived
     * from it and written into the hidden fields before submit.
     */
    public function test_submission_with_utm_and_click_ids_populates_lead_attribution_columns(): void
    {
        $service = $this->makeService();

        $this->get('/consultation?utm_source=facebook&utm_medium=paid_social&utm_campaign=test&campaign_id=123&adset_id=456&ad_id=789')
            ->assertOk();

        $touch = json_encode([
            'utm_source' => 'facebook',
            'utm_medium' => 'paid_social',
            'utm_campaign' => 'test',
            'campaign_id' => '123',
            'adset_id' => '456',
            'ad_id' => '789',
            'landing_page' => '/consultation',
            'referrer' => null,
            'captured_at' => now()->toISOString(),
        ]);

        $response = $this->post(route('consultation.store'), $this->validPayload([
            'requested_service_id' => $service->id,
            'first_touch_snapshot' => $touch,
            'latest_touch_snapshot' => $touch,
        ]));

        $response->assertRedirect(route('consultation'));

        $lead = Lead::firstOrFail();

        $this->assertSame('facebook', $lead->source_platform);
        $this->assertSame('facebook', $lead->utm_source);
        $this->assertSame('paid_social', $lead->utm_medium);
        $this->assertSame('test', $lead->utm_campaign);
        $this->assertSame('123', $lead->campaign_id);
        $this->assertSame('456', $lead->adset_id);
        $this->assertSame('789', $lead->ad_id);
        $this->assertSame('/consultation', $lead->landing_page_url);
        $this->assertSame('facebook', $lead->first_touch['utm_source']);
        $this->assertSame('facebook', $lead->latest_touch['utm_source']);
    }

    /**
     * Proves the server-side half of the first-touch/latest-touch contract:
     * given a first_touch_snapshot that differs from latest_touch_snapshot
     * (exactly what the cookies would contain for a visitor whose first and
     * most recent visits carried different UTM parameters — see the
     * attribution.js Node test for proof the cookies themselves behave
     * this way), the flat reporting columns are populated from LATEST
     * touch while the full first_touch JSON is preserved untouched.
     */
    public function test_flat_attribution_columns_reflect_latest_touch_while_first_touch_json_is_preserved(): void
    {
        $service = $this->makeService();

        $firstTouch = json_encode([
            'utm_source' => 'facebook',
            'utm_medium' => 'paid_social',
            'landing_page' => '/services',
            'referrer' => 'https://facebook.com/',
        ]);

        $latestTouch = json_encode([
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'landing_page' => '/consultation',
            'referrer' => 'https://google.com/',
        ]);

        $response = $this->post(route('consultation.store'), $this->validPayload([
            'requested_service_id' => $service->id,
            'first_touch_snapshot' => $firstTouch,
            'latest_touch_snapshot' => $latestTouch,
        ]));

        $response->assertRedirect(route('consultation'));

        $lead = Lead::firstOrFail();

        // Flat columns follow latest-touch.
        $this->assertSame('google', $lead->source_platform);
        $this->assertSame('google', $lead->utm_source);
        $this->assertSame('cpc', $lead->utm_medium);
        $this->assertSame('/consultation', $lead->landing_page_url);

        // Original first-touch is untouched in the JSON column.
        $this->assertSame('facebook', $lead->first_touch['utm_source']);
        $this->assertSame('paid_social', $lead->first_touch['utm_medium']);
        $this->assertSame('google', $lead->latest_touch['utm_source']);
    }

    public function test_fully_organic_submission_with_no_attribution_cookies_succeeds_with_null_attribution(): void
    {
        $service = $this->makeService();

        $response = $this->post(route('consultation.store'), $this->validPayload([
            'requested_service_id' => $service->id,
        ]));

        $response->assertRedirect(route('consultation'));

        $lead = Lead::firstOrFail();

        $this->assertNull($lead->source_platform);
        $this->assertNull($lead->utm_source);
        $this->assertNull($lead->first_touch);
        $this->assertNull($lead->latest_touch);
    }

    /**
     * Regression guard: see the same test/note in ContactFormTest.
     */
    public function test_honeypot_is_hidden_from_screen_readers_not_just_visually(): void
    {
        $html = $this->get(route('consultation'))->getContent();

        $this->assertMatchesRegularExpression(
            '/<div[^>]*aria-hidden="true"[^>]*tabindex="-1"[^>]*>\s*<label for="website_url"/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression('/class="sr-only"[^>]*>\s*<label for="website_url"/', $html);
    }

    public function test_honeypot_filled_does_not_create_a_lead_but_still_shows_success(): void
    {
        $service = $this->makeService();

        $response = $this->post(route('consultation.store'), $this->validPayload([
            'requested_service_id' => $service->id,
            'website_url' => 'https://spambot.example.com',
        ]));

        $response->assertRedirect(route('consultation'));
        $response->assertSessionHas('status');

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_more_than_five_submissions_per_minute_are_rate_limited(): void
    {
        $service = $this->makeService();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('consultation.store'), $this->validPayload([
                'requested_service_id' => $service->id,
                'email' => "lead{$i}@example.com",
            ]))->assertRedirect(route('consultation'));
        }

        $response = $this->post(route('consultation.store'), $this->validPayload([
            'requested_service_id' => $service->id,
            'email' => 'lead6@example.com',
        ]));

        $response->assertStatus(429);
        $this->assertDatabaseCount('leads', 5);
    }

    public function test_consent_is_required(): void
    {
        $service = $this->makeService();

        $response = $this->post(route('consultation.store'), $this->validPayload([
            'requested_service_id' => $service->id,
            'consent_given' => null,
        ]));

        $response->assertSessionHasErrors('consent_given');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_inactive_service_is_rejected(): void
    {
        $inactive = $this->makeService(['slug' => 'inactive', 'name' => ['ar' => 'خدمة غير نشطة'], 'is_active' => false]);

        $response = $this->post(route('consultation.store'), $this->validPayload([
            'requested_service_id' => $inactive->id,
        ]));

        $response->assertSessionHasErrors('requested_service_id');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_invalid_email_is_rejected(): void
    {
        $service = $this->makeService();

        $response = $this->post(route('consultation.store'), $this->validPayload([
            'requested_service_id' => $service->id,
            'email' => 'not-an-email',
        ]));

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('leads', 0);
    }

    /**
     * An English visitor must land back on the English consultation page.
     * Before the lroute() fix this redirected to the Arabic URL, silently
     * switching the visitor's language mid-journey.
     */
    public function test_english_submission_redirects_to_the_english_page(): void
    {
        $service = $this->makeService();

        $this->post(route('consultation.store.en'), $this->validPayload([
            'requested_service_id' => $service->id,
        ]))->assertRedirect(route('consultation.en'));
    }

    public function test_english_honeypot_submission_also_redirects_to_the_english_page(): void
    {
        $service = $this->makeService();

        $this->post(route('consultation.store.en'), $this->validPayload([
            'requested_service_id' => $service->id,
            'website_url' => 'http://spam.example',
        ]))->assertRedirect(route('consultation.en'));

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_english_submission_flashes_the_english_success_message(): void
    {
        $service = $this->makeService();

        $this->post(route('consultation.store.en'), $this->validPayload([
            'requested_service_id' => $service->id,
        ]))->assertSessionHas('status', __('site.flash.consultation_submitted', [], 'en'));
    }

    public function test_arabic_submission_still_redirects_to_the_arabic_page(): void
    {
        $service = $this->makeService();

        $this->post(route('consultation.store'), $this->validPayload([
            'requested_service_id' => $service->id,
        ]))->assertRedirect(route('consultation'));
    }
}
