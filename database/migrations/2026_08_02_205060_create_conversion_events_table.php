<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversion_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('url')->nullable();
            $table->json('utm_snapshot')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_events');
    }
};
