<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_can_access_dashboard(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        // The dashboard home shows the admin's name (header + top bar),
        // not their email, since the Dashboard Shell Redesign task
        // replaced the old placeholder view's "logged in as :email" text.
        $response->assertSee($admin->name);
    }

    public function test_authenticated_non_admin_gets_403_on_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertForbidden();
    }

    public function test_invalid_login_credentials_do_not_authenticate(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->from('/login')->post('/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout_ends_session_and_dashboard_redirects_to_login_again(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/dashboard')->assertOk();

        $response = $this->actingAs($admin)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();

        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_registration_route_is_unreachable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    /**
     * is_admin is intentionally excluded from User::$fillable, so it can't
     * be set via the factory's mass-assigned attributes — set it directly.
     */
    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin->fresh();
    }
}
