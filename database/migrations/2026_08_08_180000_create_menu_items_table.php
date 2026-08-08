<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the public navbar data-driven instead of hardcoded markup.
 *
 * parent_id is self-referencing with nullOnDelete, which encodes the
 * no-orphan rule at the DB level: deleting a parent REPARENTS its
 * children to top level rather than deleting them or leaving them
 * pointing at a row that no longer exists. That is the deliberate choice
 * between the brief's two options — it is non-destructive, and an admin
 * who deletes a dropdown parent keeps every child as a normal top-level
 * item they can re-nest.
 *
 * route_name is not a foreign key to anything — it holds a Laravel route
 * name, validated against MenuItem::ROUTE_WHITELIST (parameter-free
 * public routes only) so a menu entry can never resolve to a route that
 * needs a bound model and 500 the whole site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('menu_items')
                ->nullOnDelete();

            // Translatable JSON, like every other bilingual field here.
            $table->json('label');

            // route | url | none  ('none' = a parent that only opens a
            // dropdown and has no destination of its own).
            $table->string('link_type', 16)->default('route');
            $table->string('route_name')->nullable();
            $table->string('url')->nullable();

            $table->string('target', 8)->default('_self');

            $table->boolean('is_visible')->default(true);

            // Marks the seeded core nav. Renaming/hiding/reordering these
            // is always allowed; deletion is guarded so an admin cannot
            // accidentally leave the site with no navigation.
            $table->boolean('is_system')->default(false);

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // The public navbar reads exactly this: visible items, by
            // parent, in order.
            $table->index(['parent_id', 'is_visible', 'sort_order'], 'menu_items_tree_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
