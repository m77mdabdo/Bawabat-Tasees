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

    /**
     * Regression guard: the honeypot must be invisible to sighted users
     * AND screen readers. `sr-only` was tried in an earlier task to fix
     * a horizontal-overflow bug, but `sr-only`'s entire purpose is to
     * stay VISIBLE to screen readers while hidden visually — the
     * opposite of what a honeypot needs. Asserts the field carries
     * `aria-hidden="true"` and `tabindex="-1"`, and that the `sr-only`
     * class is gone from this specific wrapper.
     */
    public function test_honeypot_is_hidden_from_screen_readers_not_just_visually(): void
    {
        $html = $this->get(route('contact'))->getContent();

        $this->assertMatchesRegularExpression(
            '/<div[^>]*aria-hidden="true"[^>]*tabindex="-1"[^>]*>\s*<label for="website_url"/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression('/class="sr-only"[^>]*>\s*<label for="website_url"/', $html);
    }

    public function test_homepage_shows_the_real_contact_form(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="contact"', false);
        $response->assertSee('name="redirect_to" value="home"', false);
        $response->assertSee('action="'.route('contact.store').'"', false);
    }

    public function test_submission_from_the_homepage_creates_a_contact_lead_and_redirects_to_home_anchor(): void
    {
        $response = $this->post(route('contact.store'), $this->validPayload([
            'redirect_to' => 'home',
        ]));

        $response->assertRedirect(route('home').'#contact');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('leads', [
            'full_name' => 'Sara Al-Qahtani',
            'email' => 'sara@example.com',
            'type' => 'contact',
            'consent_given' => true,
        ]);
    }

    public function test_submission_without_redirect_to_still_defaults_to_the_contact_page(): void
    {
        // Guards the /contact page's own behavior against any regression
        // introduced by making the redirect conditional.
        $response = $this->post(route('contact.store'), $this->validPayload());

        $response->assertRedirect(route('contact'));
    }

    public function test_honeypot_filled_from_the_homepage_still_redirects_to_home_anchor_with_no_lead_created(): void
    {
        $response = $this->post(route('contact.store'), $this->validPayload([
            'redirect_to' => 'home',
            'website_url' => 'https://spambot.example.com',
        ]));

        $response->assertRedirect(route('home').'#contact');
        $response->assertSessionHas('status');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_attribution_snapshots_populate_lead_source_fields_when_submitted_from_the_homepage(): void
    {
        $touch = json_encode([
            'utm_source' => 'facebook',
            'utm_medium' => 'paid_social',
            'landing_page' => '/',
            'referrer' => 'https://facebook.com/',
        ]);

        $this->post(route('contact.store'), $this->validPayload([
            'redirect_to' => 'home',
            'first_touch_snapshot' => $touch,
            'latest_touch_snapshot' => $touch,
        ]))->assertRedirect(route('home').'#contact');

        $lead = Lead::firstOrFail();

        $this->assertSame('facebook', $lead->source_platform);
        $this->assertSame('paid_social', $lead->utm_medium);
        $this->assertSame('/', $lead->landing_page_url);
        $this->assertSame('contact', $lead->type);
    }

    /**
     * Pre-existing gap fixed as part of this task: the redirect used to
     * hardcode the Arabic `route('contact')` regardless of locale, so an
     * English-locale visitor submitting from /en/contact would land back
     * on the Arabic /contact page. Now goes through lroute() for both
     * the 'contact' and 'home' targets.
     */
    public function test_english_locale_submission_redirects_to_the_english_contact_page_not_arabic(): void
    {
        $response = $this->post(route('contact.store.en'), $this->validPayload());

        $response->assertRedirect(route('contact.en'));
    }

    public function test_english_locale_homepage_submission_redirects_to_the_english_home_anchor(): void
    {
        $response = $this->post(route('contact.store.en'), $this->validPayload([
            'redirect_to' => 'home',
        ]));

        $response->assertRedirect(route('home.en').'#contact');
    }
}
