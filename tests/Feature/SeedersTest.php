<?php

namespace Tests\Feature;

use App\Models\LeadSource;
use App\Models\Setting;
use App\Models\TrackingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_expected_baseline_data(): void
    {
        $this->seed();

        $this->assertSame(1, User::where('is_admin', true)->count());
        $this->assertSame(11, LeadSource::count());
        $this->assertSame(10, Setting::count());
        $this->assertSame(6, TrackingSetting::count());
        $this->assertSame(0, TrackingSetting::where('is_active', true)->count());
    }

    public function test_seeding_twice_does_not_create_duplicates(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, User::where('is_admin', true)->count());
        $this->assertSame(11, LeadSource::count());
        $this->assertSame(10, Setting::count());
        $this->assertSame(6, TrackingSetting::count());
    }

    public function test_admin_user_has_hashed_password_and_is_admin_flag(): void
    {
        $this->seed();

        $admin = User::where('is_admin', true)->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_admin);
        $this->assertNotSame('', $admin->password);
        $this->assertStringStartsWith('$2y$', $admin->password);
    }
}
