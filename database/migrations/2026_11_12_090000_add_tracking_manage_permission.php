<?php

use App\Auth\RoleSync;
use Illuminate\Database\Migrations\Migration;

/** Adds `tracking.manage` (Super Admins only) by syncing the roles tables with PermissionMatrix. */
return new class extends Migration
{
    public function up(): void
    {
        RoleSync::run();
    }

    public function down(): void
    {
        // Nothing to undo: the matrix in code is the source of truth and RoleSync follows it.
    }
};
