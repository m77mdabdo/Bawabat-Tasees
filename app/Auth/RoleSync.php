<?php

namespace App\Auth;

use App\Enums\Role as RoleEnum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Makes spatie's roles and permissions tables match PermissionMatrix exactly. Safe to run any number of times. */
final class RoleSync
{
    public static function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = array_keys(PermissionMatrix::all());
        foreach ($names as $name) {
            Permission::findOrCreate($name, 'web');
        }
        Permission::query()->whereNotIn('name', $names)->delete();

        foreach (RoleEnum::cases() as $case) {
            Role::findOrCreate($case->value, 'web')->syncPermissions(PermissionMatrix::permissionsFor($case));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
