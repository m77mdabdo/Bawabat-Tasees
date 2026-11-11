<?php

use App\Auth\RoleSync;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the yes/no `is_admin` flag with roles. Every existing admin becomes a Super Admin, so
 * nobody loses access; accounts can then be switched off (is_active) without being deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('locale');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        RoleSync::run();

        $adminIds = DB::table('users')->where('is_admin', true)->pluck('id');
        foreach (User::query()->whereIn('id', $adminIds)->get() as $user) {
            $user->assignRole(Role::SuperAdmin->value);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });

        foreach (User::role(Role::SuperAdmin->value)->get() as $user) {
            DB::table('users')->where('id', $user->id)->update(['is_admin' => true]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'last_login_at']);
        });
    }
};
