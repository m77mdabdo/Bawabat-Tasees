<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes campaigns usable for ROI reporting, and links leads to them.
 *
 * THE LINKING PROBLEM
 * -------------------
 * leads.campaign_id is a varchar holding the EXTERNAL ad-platform campaign
 * id captured from the landing URL by resources/js/attribution.js. It is
 * not, and must not become, a foreign key to campaigns.id — the two are
 * different identifier spaces, and a lead can legitimately carry an
 * external id for a campaign nobody has created a record for yet.
 *
 * So rather than repurposing that column (which would break the
 * attribution flow), this adds a SEPARATE nullable foreign key,
 * leads.linked_campaign_id. The two coexist:
 *
 *   campaign_id         - raw external string, written by attribution,
 *                         never modified, still the source of truth for
 *                         "what did the ad platform tell us"
 *   linked_campaign_id  - resolved internal FK, set when the external id
 *                         matches a Campaign the admin has created
 *
 * Resolution happens in AttributionService (the same place source_platform
 * is already matched against lead_sources), so new leads self-link; the
 * FK is nullable so leads for unknown campaigns are still recorded
 * exactly as before. nullOnDelete keeps historical leads intact if a
 * campaign record is later removed.
 *
 * campaigns.external_campaign_id gets a unique index because it is the
 * lookup key for that resolution, and two campaigns sharing one external
 * id would make the match ambiguous.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->decimal('budget', 12, 2)->nullable()->after('external_campaign_id');
            $table->decimal('spend', 12, 2)->nullable()->after('budget');
            $table->string('currency', 3)->default('SAR')->after('spend');
            $table->date('starts_on')->nullable()->after('currency');
            $table->date('ends_on')->nullable()->after('starts_on');

            $table->unique('external_campaign_id', 'campaigns_external_campaign_id_unique');
            $table->index(['is_active', 'name'], 'campaigns_is_active_name_index');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('linked_campaign_id')
                ->nullable()
                ->after('campaign_id')
                ->constrained('campaigns')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // dropForeign + dropColumn rather than dropConstrainedForeignKey:
            // that helper does not exist on Blueprint in this Laravel
            // version, and calling it aborts down() midway.
            $table->dropForeign(['linked_campaign_id']);
            $table->dropColumn('linked_campaign_id');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropUnique('campaigns_external_campaign_id_unique');
            $table->dropIndex('campaigns_is_active_name_index');
            $table->dropColumn(['budget', 'spend', 'currency', 'starts_on', 'ends_on']);
        });
    }
};
