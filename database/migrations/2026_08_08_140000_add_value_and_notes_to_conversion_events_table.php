<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original conversion_events table records THAT something happened
 * (event_type, lead_id, url, utm_snapshot, occurred_at) but carries no
 * monetary figure and nowhere to explain the entry — so it cannot answer
 * the question the table exists for: what did this lead actually earn.
 *
 * `value` is nullable because not every conversion is monetary — a
 * "qualified" or "contract_signed" milestone is worth logging on its own.
 *
 * `occurred_at` is deliberately left NOT NULL and NOT given a default.
 * MySQL silently supplies current_timestamp() for the first TIMESTAMP
 * column, but SQLite (used by the test suite) does not, so a write that
 * omits it fails there. An event always happened at a knowable time, so
 * the honest fix is that every writer sets it explicitly — see
 * ConversionEventService::log() — rather than papering over it with a
 * default that would silently record "now" for a backdated entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversion_events', function (Blueprint $table) {
            $table->decimal('value', 12, 2)->nullable()->after('event_type');
            $table->string('currency', 3)->default('SAR')->after('value');
            $table->text('notes')->nullable()->after('utm_snapshot');

            // Reporting reads this table by type and by date range.
            $table->index('event_type', 'conversion_events_event_type_index');
            $table->index('occurred_at', 'conversion_events_occurred_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('conversion_events', function (Blueprint $table) {
            $table->dropIndex('conversion_events_event_type_index');
            $table->dropIndex('conversion_events_occurred_at_index');
            $table->dropColumn(['value', 'currency', 'notes']);
        });
    }
};
