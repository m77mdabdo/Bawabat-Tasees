<?php

namespace Tests\Feature\Dashboard;

use App\Enums\Role;
use App\Filament\Resources\NotificationRecipients\Pages\ManageNotificationRecipients;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Mail\RecipientAdded;
use App\Mail\StaffAccessLink;
use App\Mail\TestMail;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/** Staff invitations and roles, and the enquiry-alert recipient list. */
class StaffAndRecipientsTest extends DashboardTestCase
{
    public function test_inviting_staff_creates_the_account_with_its_role_and_emails_a_link(): void
    {
        Mail::fake();
        $this->actingAs($this->makeAdmin());

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Sara Ali', 'email' => 'sara@company.test', 'role' => Role::Sales->value, 'locale' => 'ar', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $sara = User::where('email', 'sara@company.test')->firstOrFail();
        $this->assertTrue($sara->hasRole(Role::Sales->value));
        $this->assertTrue($sara->can('leads.manage'));
        $this->assertFalse($sara->can('content.manage'));

        Mail::assertSent(StaffAccessLink::class, function (StaffAccessLink $m) {
            $html = $m->render();

            return $m->hasTo('sara@company.test') && $m->invitation
                && str_contains($html, 'المبيعات') && str_contains($html, 'password-reset/reset');
        });
    }

    public function test_nobody_can_change_their_own_role_or_switch_themselves_off(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getKey()])
            ->fillForm(['name' => 'Still Admin', 'role' => Role::Viewer->value, 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $admin->refresh();
        $this->assertSame('Still Admin', $admin->name);
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue($admin->is_active);
    }

    public function test_a_super_admin_can_change_another_staff_members_role(): void
    {
        $this->actingAs($this->makeAdmin());
        $editor = $this->makeStaff(Role::Editor, ['email' => 'ed@company.test']);

        Livewire::test(EditUser::class, ['record' => $editor->getKey()])
            ->fillForm(['role' => Role::Manager->value])->call('save')->assertHasNoFormErrors();

        $this->assertTrue($editor->fresh()->hasRole(Role::Manager->value));
    }

    public function test_the_roles_window_lists_every_role(): void
    {
        $this->actingAs($this->makeAdmin());

        Livewire::test(ListUsers::class)->mountAction('matrix')->assertSee(['مدير عام', 'محرر محتوى', 'المبيعات', 'مُطّلع']);
    }

    public function test_a_new_alert_recipient_is_welcomed_by_email(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();
        $admin->forceFill(['name' => 'Mohamed'])->save();
        $this->actingAs($admin);

        Livewire::test(ManageNotificationRecipients::class)
            ->callAction('create', ['name' => 'Sales Inbox', 'email' => 'sales@company.test', 'receives_consultations' => true, 'receives_contact_messages' => false, 'is_active' => true])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('notification_recipients', ['email' => 'sales@company.test', 'receives_contact_messages' => false]);
        Mail::assertSent(RecipientAdded::class, fn (RecipientAdded $m) => $m->hasTo('sales@company.test') && str_contains($m->render(), 'Mohamed'));
    }

    public function test_a_recipient_can_be_sent_a_test_email(): void
    {
        Mail::fake();
        $this->actingAs($this->makeAdmin());
        $recipient = NotificationRecipient::factory()->create(['email' => 'check@company.test']);

        Livewire::test(ManageNotificationRecipients::class)->callTableAction('test', $recipient);

        Mail::assertSent(TestMail::class, fn ($m) => $m->hasTo('check@company.test'));
    }
}
