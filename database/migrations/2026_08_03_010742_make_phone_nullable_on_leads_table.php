<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original leads schema made phone required for every lead type,
     * but the contact form (unlike the consultation form) treats phone as
     * optional per the task spec — nothing else in the schema needs to
     * change for that.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
        });
    }
};
