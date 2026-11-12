<?php

namespace Tests\Feature\Dashboard;

use App\Auth\PermissionMatrix;
use App\Enums\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;

/** Who may open which dashboard screen (App\Auth\PermissionMatrix), checked over HTTP. */
class AccessAndRolesTest extends DashboardTestCase
{
    public function test_a_guest_is_sent_to_the_sign_in_page(): void
    {
        $this->get('/dashboard')->assertRedirect(route('filament.dashboard.auth.login'));
        $this->get('/login')->assertRedirect('/dashboard/login');
    }

    public function test_an_account_without_a_role_is_refused(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertForbidden();
    }

    public function test_a_switched_off_account_is_refused_even_as_super_admin(): void
    {
        $admin = $this->makeAdmin();
        $admin->forceFill(['is_active' => false])->save();

        $this->actingAs($admin)->get('/dashboard')->assertForbidden();
    }

    /** @return array<string, array{Role, string, int}> */
    public static function screens(): array
    {
        return [
            'super admin → staff' => [Role::SuperAdmin, '/dashboard/users', 200],
            'manager → staff' => [Role::Manager, '/dashboard/users', 403],
            'manager → enquiries' => [Role::Manager, '/dashboard/leads', 200],
            'manager → settings' => [Role::Manager, '/dashboard/site-settings', 200],
            'editor → services' => [Role::Editor, '/dashboard/services', 200],
            'editor → enquiries' => [Role::Editor, '/dashboard/leads', 403],
            'editor → reports' => [Role::Editor, '/dashboard/reports', 403],
            'sales → enquiries' => [Role::Sales, '/dashboard/leads', 200],
            'sales → services' => [Role::Sales, '/dashboard/services', 403],
            'sales → recipients' => [Role::Sales, '/dashboard/notification-recipients', 403],
            'viewer → reports' => [Role::Viewer, '/dashboard/reports', 200],
            'viewer → visitor sessions' => [Role::Viewer, '/dashboard/visitor-sessions', 200],
            'viewer → enquiries' => [Role::Viewer, '/dashboard/leads', 200],
            'viewer → campaigns' => [Role::Viewer, '/dashboard/campaigns', 403],
            'viewer → tracking' => [Role::Viewer, '/dashboard/tracking-settings', 403],
            'manager → tracking' => [Role::Manager, '/dashboard/tracking-settings', 403],
            'super admin → tracking' => [Role::SuperAdmin, '/dashboard/tracking-settings', 200],
        ];
    }

    #[DataProvider('screens')]
    public function test_each_role_reaches_only_its_screens(Role $role, string $uri, int $status): void
    {
        $this->actingAs($this->makeStaff($role))->get($uri)->assertStatus($status);
    }

    public function test_every_role_can_open_the_overview(): void
    {
        foreach (Role::cases() as $role) {
            $this->actingAs($this->makeStaff($role, ['email' => $role->value.'@staff.test']))->get('/dashboard')->assertOk();
        }
    }

    public function test_the_matrix_denies_by_default_and_only_super_admins_manage_staff(): void
    {
        foreach (PermissionMatrix::all() as $permission => $roles) {
            $this->assertContains(Role::SuperAdmin, $roles, "Super Admin lacks {$permission}");
        }
        $this->assertSame([Role::SuperAdmin], PermissionMatrix::all()['users.manage']);
        $this->assertSame([Role::SuperAdmin], PermissionMatrix::all()['tracking.manage']);
        $this->assertFalse($this->makeStaff(Role::Viewer)->can('leads.manage'));
    }

    public function test_the_dashboard_follows_each_persons_language(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('dir="rtl"', false);

        $this->actingAs($admin)->post('/dashboard/locale')->assertRedirect();
        $this->assertSame('en', $admin->fresh()->locale);
        $this->actingAs($admin->fresh())->get('/dashboard')->assertOk()->assertSee('dir="ltr"', false);
    }
}
