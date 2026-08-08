<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Read/handled state for the Contact Messages inbox.
 *
 * The column lives on `leads` rather than a new table because contact
 * submissions ARE leads — ContactController@store writes them with
 * type = 'contact' (app/Http/Controllers/Public/ContactController.php:51).
 * Splitting them out would fork attribution and the Leads report.
 *
 * Indexed together with `type` because the only two reads are "the
 * contact inbox" and "the unread badge", and the badge runs on EVERY
 * dashboard page render.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('is_handled')->default(false)->after('type');
            $table->index(['type', 'is_handled'], 'leads_type_is_handled_index');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_type_is_handled_index');
            $table->dropColumn('is_handled');
        });
    }
};
