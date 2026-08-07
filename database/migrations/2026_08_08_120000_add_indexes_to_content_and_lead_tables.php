<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds indexes to the columns this app actually filters and sorts on.
 *
 * These are composites rather than single-column indexes because every
 * one of them is queried as a filter+sort pair, not in isolation — e.g.
 * the global nav view composer runs
 * `where('is_active', true)->orderBy('sort_order')` on services for
 * EVERY page render, and the blog index does
 * `where('is_published', true)->where('published_at','<=',now())
 *  ->orderByDesc('published_at')`.
 *
 * Column order within each composite matters: the equality-filtered
 * column comes first, the range/sort column second, so the index can
 * satisfy both the WHERE and the ORDER BY without a filesort.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'services_is_active_sort_order_index');
        });

        Schema::table('page_sections', function (Blueprint $table) {
            $table->index(['page_id', 'is_active', 'sort_order'], 'page_sections_page_active_sort_index');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->index(['is_published', 'published_at'], 'articles_is_published_published_at_index');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->index('source_platform', 'leads_source_platform_index');
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'countries_is_active_sort_order_index');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'faqs_is_active_sort_order_index');
        });

        Schema::table('testimonials', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order'], 'testimonials_is_active_sort_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('services_is_active_sort_order_index');
        });

        // The composite below leads with page_id, so MySQL adopts it as the
        // index backing the page_id foreign key and drops the redundant
        // auto-created one. Dropping it directly therefore fails with
        // errno 1553 ("needed in a foreign key constraint") — the plain
        // index has to be put back first.
        Schema::table('page_sections', function (Blueprint $table) {
            $table->index('page_id', 'page_sections_page_id_foreign');
        });

        Schema::table('page_sections', function (Blueprint $table) {
            $table->dropIndex('page_sections_page_active_sort_index');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('articles_is_published_published_at_index');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_source_platform_index');
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->dropIndex('countries_is_active_sort_order_index');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex('faqs_is_active_sort_order_index');
        });

        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropIndex('testimonials_is_active_sort_order_index');
        });
    }
};
