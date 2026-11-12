<?php

namespace Tests\Feature\Dashboard;

use App\Content\SiteTexts;
use App\Enums\Role;
use App\Filament\Pages\Reports;
use App\Filament\Pages\SiteSettings;
use App\Filament\Pages\WebsiteTexts;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\RelationManagers\ConversionEventsRelationManager;
use App\Filament\Resources\NotificationRecipients\Pages\ManageNotificationRecipients;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\VisitorSessions\Pages\ListVisitorSessions;
use App\Filament\Resources\VisitorSessions\Pages\ViewVisitorSession;
use App\Mail\StaffAccessLink;
use App\Models\ConversionEvent;
use App\Models\Lead;
use App\Models\NotificationRecipient;
use App\Models\Setting;
use App\Models\User;
use App\Models\VisitorSession;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** Enquiries, staff, alert recipients, visitor sessions, settings and website texts: every button. */
class AdministrationCrudTest extends DashboardTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->admin = $this->makeAdmin());
    }

    // Enquiries --------------------------------------------------------------

    public function test_enquiries_bulk_mark_handled_and_bulk_delete(): void
    {
        $leads = Lead::factory()->count(3)->create();

        Livewire::test(ListLeads::class)->callTableBulkAction('markHandled', $leads->take(2));
        $this->assertSame(2, Lead::where('is_handled', true)->count());

        Livewire::test(ListLeads::class)->callTableBulkAction('delete', $leads);
        $this->assertSame(0, Lead::count());
        $this->assertSame(3, Lead::withTrashed()->count(), 'Enquiries are soft-deleted, never lost.');
    }

    public function test_an_enquiry_can_be_reopened_and_its_conversion_removed(): void
    {
        $lead = Lead::factory()->create();
        $lead->forceFill(['is_handled' => true])->save();
        $event = ConversionEvent::factory()->create(['lead_id' => $lead->id]);

        Livewire::test(ViewLead::class, ['record' => $lead->getKey()])->callAction('toggleHandled');
        $this->assertFalse($lead->fresh()->is_handled);

        Livewire::test(ConversionEventsRelationManager::class, ['ownerRecord' => $lead, 'pageClass' => ViewLead::class])
            ->callTableAction('delete', $event);
        $this->assertModelMissing($event);
    }

    public function test_a_conversion_cannot_be_in_the_future_or_negative(): void
    {
        $lead = Lead::factory()->create();

        Livewire::test(ConversionEventsRelationManager::class, ['ownerRecord' => $lead, 'pageClass' => ViewLead::class])
            ->callTableAction('create', data: ['event_type' => 'payment_received', 'value' => -5, 'occurred_at' => now()->addWeek()])
            ->assertHasTableActionErrors(['value', 'occurred_at']);
    }

    // Staff ------------------------------------------------------------------

    public function test_staff_can_be_deleted_and_sent_a_new_password_link(): void
    {
        Mail::fake();
        $editor = $this->makeStaff(Role::Editor, ['email' => 'editor@company.test']);

        Livewire::test(ListUsers::class)->callTableAction('sendLink', $editor);
        Mail::assertSent(StaffAccessLink::class, fn (StaffAccessLink $m) => $m->hasTo('editor@company.test') && ! $m->invitation);

        Livewire::test(ListUsers::class)->callTableAction('delete', $editor);
        $this->assertModelMissing($editor);
    }

    public function test_you_cannot_delete_yourself_or_the_last_super_admin(): void
    {
        Livewire::test(ListUsers::class)->assertTableActionHidden('delete', $this->admin);
        Livewire::test(EditUser::class, ['record' => $this->admin->getKey()])->assertActionHidden('delete');
    }

    public function test_a_switched_off_staff_member_is_locked_out(): void
    {
        $sales = $this->makeStaff(Role::Sales, ['email' => 'sales@company.test']);

        Livewire::test(EditUser::class, ['record' => $sales->getKey()])->fillForm(['is_active' => false])->call('save')->assertHasNoFormErrors();

        $this->actingAs($sales->fresh())->get('/dashboard')->assertForbidden();
    }

    public function test_emails_must_be_unique_across_staff(): void
    {
        $other = $this->makeStaff(Role::Viewer, ['email' => 'taken@company.test']);

        Livewire::test(EditUser::class, ['record' => $this->admin->getKey()])
            ->fillForm(['email' => $other->email])->call('save')->assertHasFormErrors(['email' => 'unique']);
    }

    // Alert recipients -------------------------------------------------------

    public function test_alert_recipient_edit_toggle_and_delete(): void
    {
        $recipient = NotificationRecipient::factory()->create();

        Livewire::test(ManageNotificationRecipients::class)
            ->callTableAction('edit', $recipient, data: ['name' => 'Riyadh office', 'receives_contact_messages' => false])
            ->assertHasNoTableActionErrors();
        $this->assertSame('Riyadh office', $recipient->fresh()->name);
        $this->assertFalse($recipient->fresh()->receives_contact_messages);

        Livewire::test(ManageNotificationRecipients::class)->call('updateTableColumnState', 'is_active', (string) $recipient->getKey(), false);
        $this->assertFalse($recipient->fresh()->is_active);

        Livewire::test(ManageNotificationRecipients::class)->callTableAction('delete', $recipient);
        $this->assertModelMissing($recipient);
    }

    public function test_the_same_address_cannot_be_added_twice(): void
    {
        NotificationRecipient::factory()->create(['email' => 'dup@company.test']);

        Livewire::test(ManageNotificationRecipients::class)
            ->callAction('create', ['email' => 'dup@company.test'])
            ->assertHasActionErrors(['email' => 'unique']);
    }

    // Visitor sessions and reports -------------------------------------------

    public function test_visitor_sessions_list_view_and_export(): void
    {
        $session = VisitorSession::factory()->create();

        Livewire::test(ListVisitorSessions::class)->assertCanSeeTableRecords([$session])->callAction('export')->assertFileDownloaded();
        Livewire::test(ViewVisitorSession::class, ['record' => $session->getKey()])->assertOk()->callAction('exportOne')->assertFileDownloaded();
        $this->get('/dashboard/visitor-sessions/'.$session->getKey())->assertOk()->assertSee('/services');
    }

    public function test_reports_switch_period(): void
    {
        Lead::factory()->create(['created_at' => now()->subDays(60)]);

        Livewire::test(Reports::class)->assertSet('days', 30)->call('setRange', 90)->assertSet('days', 90)
            ->call('setRange', 7)->assertSet('days', 30); // only the offered periods
    }

    // Settings and website texts ---------------------------------------------

    public function test_site_images_can_be_replaced_and_restored(): void
    {
        Storage::fake('public');
        $this->seed(SettingsSeeder::class);
        $valid = ['contact_phone' => '+966 11 000 0000', 'contact_whatsapp' => '+966 55 000 0000', 'contact_email' => 'info@company.sa'];

        Livewire::test(SiteSettings::class)
            ->fillForm([...$valid, 'hero_poster' => UploadedFile::fake()->image('hero.jpg', 1600, 900)])
            ->call('save')->assertHasNoFormErrors();

        $path = Setting::where('key', 'hero_poster')->value('value');
        $this->assertStringStartsWith('settings/', (string) $path);
        $this->get('/')->assertSee(Storage::disk('public')->url($path), false);

        Livewire::test(SiteSettings::class)->fillForm([...$valid, 'hero_poster' => null])->call('save')->assertHasNoFormErrors();
        $this->assertNull(Setting::where('key', 'hero_poster')->value('value'));
        Storage::disk('public')->assertMissing($path);
        $this->get('/')->assertSee('images/hero-poster.webp', false);
    }

    public function test_website_texts_change_the_site_and_clearing_restores_them(): void
    {
        Livewire::test(WebsiteTexts::class)
            ->fillForm(['home__hero_heading' => ['ar' => 'عنوان من اللوحة', 'en' => 'Heading from the dashboard']])
            ->call('save')->assertHasNoFormErrors();

        $this->get('/')->assertSee('عنوان من اللوحة');
        $this->get('/en')->assertSee('Heading from the dashboard');

        Livewire::test(WebsiteTexts::class)->fillForm(['home__hero_heading' => ['ar' => '', 'en' => '']])->call('save');
        $this->get('/')->assertSee(app(SiteTexts::class)->default('home.hero_heading', 'ar'));
    }

    public function test_website_texts_must_keep_their_placeholders(): void
    {
        // site.php has no ":name" texts today; the rule is checked through the placeholder reader.
        $this->assertSame([':name', ':count'], SiteTexts::placeholders('Hello :name, :count new'));
        $this->assertSame([], SiteTexts::placeholders('No placeholders here'));
    }

    public function test_the_profile_page_and_language_switch_work(): void
    {
        $this->get('/dashboard/profile')->assertOk();
        $this->post('/dashboard/locale')->assertRedirect();
        $this->assertSame('en', $this->admin->fresh()->locale);
    }

    public function test_changing_the_profile_email_needs_the_new_address_to_confirm_it(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $old = $this->admin->email;

        Livewire::test(\Filament\Auth\Pages\EditProfile::class)
            ->fillForm(['email' => 'new-address@company.test', 'currentPassword' => 'password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($old, $this->admin->fresh()->email, 'The address must not change before it is verified.');
        \Illuminate\Support\Facades\Notification::assertSentOnDemand(\Filament\Auth\Notifications\VerifyEmailChange::class);
        \Illuminate\Support\Facades\Notification::assertSentTo($this->admin, \Filament\Auth\Notifications\NoticeOfEmailChangeRequest::class);
    }
}
