<?php

namespace Tests\Feature\Notifications;

use App\Mail\LeadConfirmation;
use App\Mail\NewLeadAlert;
use App\Mail\TestMail;
use App\Models\Lead;
use App\Models\NotificationRecipient;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class LeadNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function service(): Service
    {
        return Service::create([
            'slug' => 'company-formation',
            'name' => ['ar' => 'تأسيس الشركات', 'en' => 'Company formation'],
            'summary' => ['ar' => 'ملخص'], 'body' => ['ar' => 'محتوى'],
            'requirements' => ['ar' => 'متطلبات'], 'process' => ['ar' => 'خطوات'],
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    private function consultation(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Ahmed Al-Otaibi',
            'phone' => '+966500000000',
            'email' => 'ahmed@example.com',
            'consent_given' => '1',
            'website_url' => '',
            'requested_service_id' => $this->service()->id,
            'message' => "سطر أول\nسطر ثانٍ",
        ], $overrides);
    }

    public function test_every_active_recipient_gets_its_own_alert_for_a_consultation(): void
    {
        Mail::fake();
        NotificationRecipient::factory()->create(['email' => 'a@team.test']);
        NotificationRecipient::factory()->create(['email' => 'b@team.test']);
        NotificationRecipient::factory()->inactive()->create(['email' => 'off@team.test']);
        NotificationRecipient::factory()->create(['email' => 'contact-only@team.test', 'receives_consultations' => false]);

        $this->post(route('consultation.store'), $this->consultation())->assertRedirect();

        Mail::assertSent(NewLeadAlert::class, 2);
        Mail::assertSent(NewLeadAlert::class, fn ($m) => $m->hasTo('a@team.test') && ! $m->hasTo('b@team.test'));
        Mail::assertNotSent(NewLeadAlert::class, fn ($m) => $m->hasTo('off@team.test') || $m->hasTo('contact-only@team.test'));

        $lead = Lead::first();
        $this->assertNotNull($lead->team_notified_at);
        $this->assertNull($lead->mail_error);
        $this->assertNotNull(NotificationRecipient::where('email', 'a@team.test')->first()->last_notified_at);
    }

    public function test_the_team_alert_replies_to_the_client_and_names_the_reference(): void
    {
        Mail::fake();
        NotificationRecipient::factory()->create(['email' => 'a@team.test']);

        $this->post(route('consultation.store'), $this->consultation());
        $lead = Lead::first();

        Mail::assertSent(NewLeadAlert::class, function (NewLeadAlert $mail) use ($lead) {
            $mail->assertHasReplyTo('ahmed@example.com');
            $mail->assertHasSubject('طلب استشارة جديد '.$lead->reference().' — Ahmed Al-Otaibi');
            $mail->assertSeeInHtml('تأسيس الشركات');
            $mail->assertSeeInHtml('dashboard/leads/'.$lead->id);

            return true;
        });
    }

    public function test_the_client_gets_a_confirmation_in_the_language_they_used(): void
    {
        Mail::fake();

        $this->post(route('consultation.store.en'), $this->consultation());

        $lead = Lead::first();
        $this->assertSame('en', $lead->locale);
        Mail::assertSent(LeadConfirmation::class, function (LeadConfirmation $mail) use ($lead) {
            $mail->assertHasSubject('We received your request '.$lead->reference());
            $mail->assertSeeInHtml('Company formation');
            $mail->assertSeeInHtml('dir="ltr"', false);

            return $mail->hasTo('ahmed@example.com');
        });
        $this->assertNotNull($lead->fresh()->client_confirmed_at);
    }

    public function test_arabic_confirmations_are_right_to_left(): void
    {
        Mail::fake();

        $this->post(route('consultation.store'), $this->consultation());

        Mail::assertSent(LeadConfirmation::class, function (LeadConfirmation $mail) {
            $mail->assertSeeInHtml('dir="rtl"', false);
            $mail->assertSeeInHtml('استلمنا طلبك');

            return true;
        });
    }

    public function test_contact_messages_reach_recipients_that_want_them(): void
    {
        Mail::fake();
        NotificationRecipient::factory()->create(['email' => 'inbox@team.test', 'receives_consultations' => false]);
        NotificationRecipient::factory()->create(['email' => 'sales@team.test', 'receives_contact_messages' => false]);

        $this->post(route('contact.store'), [
            'full_name' => 'Sara', 'email' => 'sara@example.com', 'phone' => '+966511111111',
            'message' => 'مرحبا', 'consent_given' => '1', 'website_url' => '',
        ])->assertRedirect();

        Mail::assertSent(NewLeadAlert::class, 1);
        Mail::assertSent(NewLeadAlert::class, fn ($m) => $m->hasTo('inbox@team.test'));
        Mail::assertSent(NewLeadAlert::class, fn ($m) => str_contains($m->envelope()->subject, 'رسالة تواصل'));
    }

    public function test_without_recipients_the_site_contact_email_is_used(): void
    {
        Mail::fake();
        Setting::create(['key' => 'contact_email', 'value' => 'info@site.test', 'group' => 'contact']);

        $this->post(route('consultation.store'), $this->consultation());

        Mail::assertSent(NewLeadAlert::class, fn ($m) => $m->hasTo('info@site.test'));
    }

    public function test_the_honeypot_sends_nothing(): void
    {
        Mail::fake();
        NotificationRecipient::factory()->create();

        $this->post(route('consultation.store'), $this->consultation(['website_url' => 'http://spam.test']));

        Mail::assertNothingSent();
        $this->assertSame(0, Lead::count());
    }

    public function test_a_mail_failure_never_loses_the_lead(): void
    {
        NotificationRecipient::factory()->create(['email' => 'a@team.test']);
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

        $this->post(route('consultation.store'), $this->consultation())
            ->assertRedirect()
            ->assertSessionHas('status');

        $lead = Lead::first();
        $this->assertNotNull($lead);
        $this->assertNull($lead->team_notified_at);
        $this->assertStringContainsString('SMTP down', $lead->mail_error);
    }

    public function test_client_confirmation_can_be_switched_off(): void
    {
        Mail::fake();
        config(['notifications.confirm_to_client' => false]);

        $this->post(route('consultation.store'), $this->consultation());

        Mail::assertNotSent(LeadConfirmation::class);
    }

    public function test_a_line_break_in_the_name_cannot_reach_the_subject(): void
    {
        Mail::fake();
        NotificationRecipient::factory()->create();

        $this->post(route('consultation.store'), $this->consultation(['full_name' => "Ahmed\r\nBcc: x@evil.test"]));

        Mail::assertSent(NewLeadAlert::class, fn ($m) => ! str_contains($m->envelope()->subject, "\n"));
    }

    public function test_the_test_mail_command_sends_a_branded_email(): void
    {
        Mail::fake();

        $this->artisan('mail:test', ['email' => 'owner@example.com'])->assertSuccessful();
        $this->artisan('mail:test', ['email' => 'not-an-email'])->assertFailed();

        Mail::assertSent(TestMail::class, fn ($m) => $m->hasTo('owner@example.com'));
    }
}
