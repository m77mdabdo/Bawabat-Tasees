<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Not part of the original leads schema — added here as good
     * practice for any public form collecting phone/email, so consent
     * can be proven per lead.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('consent_given')->default(false)->after('latest_touch');
            $table->timestamp('consented_at')->nullable()->after('consent_given');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['consent_given', 'consented_at']);
        });
    }
};
