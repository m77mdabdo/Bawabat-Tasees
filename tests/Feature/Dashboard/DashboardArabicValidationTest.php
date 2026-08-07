<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class DashboardArabicValidationTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    public function test_service_store_validation_errors_are_in_arabic(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('dashboard.services.store'), []);

        $response->assertSessionHasErrors('name.ar');
        $message = session('errors')->first('name.ar');

        $this->assertMatchesRegularExpression('/\p{Arabic}/u', $message);
        $this->assertStringNotContainsString('field is required', $message);
    }

    public function test_country_store_validation_errors_are_in_arabic(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('dashboard.countries.store'), []);

        $response->assertSessionHasErrors('name.ar');
        $message = session('errors')->first('name.ar');

        $this->assertMatchesRegularExpression('/\p{Arabic}/u', $message);
        $this->assertStringNotContainsString('field is required', $message);
    }
}
