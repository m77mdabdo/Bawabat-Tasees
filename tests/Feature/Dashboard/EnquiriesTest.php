<?php

namespace Tests\Feature\Dashboard;

use App\Enums\Role;
use App\Events\LeadSubmitted;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\RelationManagers\ConversionEventsRelationManager;
use App\Models\Lead;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/** The enquiries screen, the conversions recorded on it, and the dashboard bell. */
class EnquiriesTest extends DashboardTestCase
{
    private function lead(array $attributes = []): Lead
    {
        return Lead::factory()->create($attributes);
    }

    public function test_the_list_shows_enquiries_and_the_tabs_filter_them(): void
    {
        $this->actingAs($this->makeStaff(Role::Sales));
        $consult = $this->lead(['type' => Lead::TYPE_CONSULTATION, 'full_name' => 'Consult Person']);
        $contact = $this->lead(['type' => Lead::TYPE_CONTACT, 'full_name' => 'Contact Person']);
        $done = $this->lead(['full_name' => 'Done Person']);
        $done->forceFill(['is_handled' => true])->save();

        Livewire::test(ListLeads::class)->assertCanSeeTableRecords([$consult, $contact, $done]);
        Livewire::test(ListLeads::class, ['activeTab' => 'contact'])->assertCanSeeTableRecords([$contact])->assertCanNotSeeTableRecords([$consult]);
        Livewire::test(ListLeads::class, ['activeTab' => 'unhandled'])->assertCanNotSeeTableRecords([$done]);
    }

    public function test_sales_can_mark_an_enquiry_handled_and_record_a_conversion(): void
    {
        $this->actingAs($this->makeStaff(Role::Sales));
        $lead = $this->lead();

        Livewire::test(ViewLead::class, ['record' => $lead->getKey()])->callAction('toggleHandled');
        $this->assertTrue($lead->fresh()->is_handled);

        Livewire::test(ConversionEventsRelationManager::class, ['ownerRecord' => $lead, 'pageClass' => ViewLead::class])
            ->callTableAction('create', data: ['event_type' => 'contract_signed', 'value' => 15000, 'currency' => 'SAR'])
            ->assertHasNoTableActionErrors();

        $this->assertTrue($lead->fresh()->isConverted());
    }

    public function test_a_viewer_can_read_but_not_change_or_delete(): void
    {
        $this->actingAs($this->makeStaff(Role::Viewer));
        $lead = $this->lead();

        $this->get('/dashboard/leads/'.$lead->getKey())->assertOk();
        Livewire::test(ViewLead::class, ['record' => $lead->getKey()])
            ->assertActionHidden('toggleHandled')
            ->assertActionHidden('delete');
    }

    public function test_only_managers_and_super_admins_delete_enquiries(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->makeStaff(Role::Sales));
        Livewire::test(ViewLead::class, ['record' => $lead->getKey()])->assertActionHidden('delete');

        $this->actingAs($this->makeStaff(Role::Manager, ['email' => 'm@company.test']));
        Livewire::test(ViewLead::class, ['record' => $lead->getKey()])->callAction('delete');
        $this->assertSoftDeleted($lead);
    }

    public function test_a_new_enquiry_rings_the_bell_of_staff_who_handle_enquiries_only(): void
    {
        Mail::fake();
        $sales = $this->makeStaff(Role::Sales);
        $editor = $this->makeStaff(Role::Editor, ['email' => 'editor@company.test']);
        $off = $this->makeStaff(Role::Manager, ['email' => 'off@company.test']);
        $off->forceFill(['is_active' => false])->save();
        $english = $this->makeStaff(Role::Viewer, ['email' => 'viewer@company.test', 'locale' => 'en']);

        $lead = $this->lead(['full_name' => 'Bell Test']);
        LeadSubmitted::dispatch($lead);

        $this->assertCount(1, $sales->notifications);
        $this->assertCount(0, $editor->notifications);
        $this->assertCount(0, $off->notifications);
        $this->assertStringContainsString('طلب استشارة '.$lead->reference(), $sales->notifications->first()->data['title']);
        $this->assertStringContainsString('Consultation request '.$lead->reference(), $english->notifications->first()->data['title']);
    }

    public function test_a_visitor_name_is_escaped_in_the_dashboard_bell(): void
    {
        Mail::fake();
        $sales = $this->makeStaff(Role::Sales);

        LeadSubmitted::dispatch($this->lead(['full_name' => '<b>x</b><img src=x>']));

        $body = $sales->notifications->first()->data['body'];
        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $body);
        $this->assertStringNotContainsString('<b>', $body);
        $this->assertStringNotContainsString('<img', $body);
    }
}
