<?php

namespace App\Auth;

use App\Enums\Role;

/**
 * Who may do what in the dashboard. Deny by default: a role has only the permissions listed here.
 * RolesAndPermissionsSeeder (and the roles migration) sync spatie's tables from this one table,
 * so adding a permission or changing a role is an edit here plus `php artisan db:seed --class=RolesAndPermissionsSeeder`.
 */
final class PermissionMatrix
{
    /** @return array<string, list<Role>> */
    public static function all(): array
    {
        $sa = Role::SuperAdmin;
        $mg = Role::Manager;
        $ed = Role::Editor;
        $sl = Role::Sales;
        $vw = Role::Viewer;

        return [
            'dashboard.access' => [$sa, $mg, $ed, $sl, $vw],
            'content.manage' => [$sa, $mg, $ed],
            'comments.moderate' => [$sa, $mg, $ed],
            'leads.view' => [$sa, $mg, $sl, $vw],
            'leads.manage' => [$sa, $mg, $sl],
            'leads.delete' => [$sa, $mg],
            'marketing.manage' => [$sa, $mg],
            'analytics.view' => [$sa, $mg, $vw],
            'settings.manage' => [$sa, $mg],
            'recipients.manage' => [$sa, $mg],
            // A GTM container runs any script its owner publishes on every public page, so only Super Admins.
            'tracking.manage' => [$sa],
            'users.manage' => [$sa],
        ];
    }

    /** @return list<string> */
    public static function permissionsFor(Role $role): array
    {
        return array_keys(array_filter(self::all(), fn (array $roles) => in_array($role, $roles, true)));
    }
}
