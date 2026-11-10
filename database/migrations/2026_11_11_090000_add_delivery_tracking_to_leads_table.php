<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets every enquiry remember the language it was written in (so the client's
 * confirmation email goes out in that language) and whether the team was told
 * about it. A delivery failure is stored on the lead, never instead of the lead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('locale', 5)->default('ar')->after('type');
            $table->timestamp('team_notified_at')->nullable()->after('consented_at');
            $table->timestamp('client_confirmed_at')->nullable()->after('team_notified_at');
            $table->string('mail_error', 500)->nullable()->after('client_confirmed_at');
        });

        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            // Which enquiries this address hears about: consultation, contact, or both.
            $table->boolean('receives_consultations')->default(true);
            $table->boolean('receives_contact_messages')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['locale', 'team_notified_at', 'client_confirmed_at', 'mail_error']);
        });
    }
};
