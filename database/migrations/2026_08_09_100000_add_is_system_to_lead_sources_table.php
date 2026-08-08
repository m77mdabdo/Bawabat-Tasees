<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marks the eleven seeded lead sources so the new CRUD can guard their
 * deletion, mirroring menu_items.is_system.
 *
 * These are not merely "defaults": leads.source_platform stores the
 * source's KEY as a plain string (see AttributionService), so deleting a
 * source that leads already point at silently strands those leads with a
 * value that no longer resolves to anything in Reports.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_sources', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_active');
        });

        // Everything that exists at this point came from LeadSourceSeeder.
        DB::table('lead_sources')->update(['is_system' => true]);
    }

    public function down(): void
    {
        Schema::table('lead_sources', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
