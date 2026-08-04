<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone');
            $table->string('whatsapp_number')->nullable();
            $table->string('email')->nullable();
            $table->string('nationality')->nullable();
            $table->string('country_of_residence')->nullable();
            $table->foreignId('requested_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('requested_activity')->nullable();
            $table->boolean('owns_external_company')->default(false);
            $table->text('message')->nullable();
            $table->string('type')->default('consultation');
            $table->string('source_platform')->nullable();
            $table->string('campaign_name')->nullable();
            $table->string('campaign_id')->nullable();
            $table->string('adset_name')->nullable();
            $table->string('adset_id')->nullable();
            $table->string('ad_name')->nullable();
            $table->string('ad_id')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('landing_page_url')->nullable();
            $table->string('referrer_url')->nullable();
            $table->string('gclid')->nullable();
            $table->string('fbclid')->nullable();
            $table->string('ttclid')->nullable();
            $table->json('first_touch')->nullable();
            $table->json('latest_touch')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
