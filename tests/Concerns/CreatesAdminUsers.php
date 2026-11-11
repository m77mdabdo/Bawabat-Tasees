<?php

namespace Tests\Concerns;

use App\Auth\RoleSync;
use App\Enums\Role;
use App\Models\User;

trait CreatesAdminUsers
{
    /** A Super Admin: roles are never mass-assigned, so they are given through spatie. */
    protected function makeAdmin(): User
    {
        return $this->makeStaff(Role::SuperAdmin);
    }

    protected function makeStaff(Role $role, array $attributes = []): User
    {
        RoleSync::run();
        $user = User::factory()->create($attributes);
        $user->assignRole($role->value);

        return $user->fresh();
    }
}
