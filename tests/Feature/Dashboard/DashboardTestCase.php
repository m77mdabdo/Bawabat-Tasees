<?php

namespace Tests\Feature\Dashboard;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

/** Base for dashboard tests: fresh database, roles synced, the dashboard panel current. */
abstract class DashboardTestCase extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    }
}
