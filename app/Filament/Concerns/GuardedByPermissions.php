<?php

namespace App\Filament\Concerns;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Resource authorization from App\Auth\PermissionMatrix instead of one policy class per model.
 *
 * A resource declares which permission lets staff see it and which lets them change it:
 *
 *   protected static string $viewPermission = 'leads.view';
 *   protected static string $managePermission = 'leads.manage';
 *   protected static ?string $deletePermission = 'leads.delete';   // optional, defaults to manage
 *
 * Every Filament check (list, view, create, edit, delete, reorder, bulk actions...) then
 * resolves to one of those three names. Deny by default for anything not recognised.
 */
trait GuardedByPermissions
{
    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $action = $action instanceof UnitEnum ? $action->name : $action;

        $permission = match ($action) {
            'viewAny', 'view' => static::$viewPermission,
            'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny' => static::$deletePermission ?? static::$managePermission,
            'create', 'update', 'reorder', 'replicate', 'restore', 'restoreAny' => static::$managePermission,
            default => null,
        };

        return $permission && auth()->user()?->can($permission) ? Response::allow() : Response::deny();
    }
}
