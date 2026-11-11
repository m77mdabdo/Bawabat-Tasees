<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard edits of the fixed website texts (lang/{ar,en}/site.php). Only changed texts are stored;
 * everything else keeps coming from the language files.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_texts', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g. "home.hero_heading" inside lang/*/site.php
            $table->json('value');            // {"ar": "...", "en": "..."}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_texts');
    }
};
