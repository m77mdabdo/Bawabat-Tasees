<?php

namespace Tests\Concerns;

use App\Models\User;

trait CreatesAdminUsers
{
    /**
     * is_admin is intentionally excluded from User::$fillable, so it can't
     * be set via the factory's mass-assigned attributes — set it directly.
     */
    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin->fresh();
    }
}
