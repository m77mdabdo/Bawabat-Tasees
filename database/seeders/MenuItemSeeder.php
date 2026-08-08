<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Seeds the navbar that was previously hardcoded in
 * layouts/public.blade.php, so making it data-driven changes nothing a
 * visitor can see.
 *
 * Six top-level items, no children — in particular "خدماتنا" points
 * straight at services.index with no dropdown, which is the behaviour the
 * navbar task established and which this must not silently undo.
 *
 * All six are marked is_system: the admin may rename, hide or reorder
 * them freely, but deleting them is guarded so the site cannot end up
 * with no navigation at all.
 *
 * Idempotent via updateOrCreate on (is_system, route_name). Every system
 * item is a route item with a distinct route, so that pair is a stable
 * natural key — and admin-created items are is_system = false, so they
 * can never collide with it. Re-seeding therefore restores the core nav
 * without touching anything the admin added.
 *
 * NOTE: labels are re-applied on every run. That is intentional for the
 * system items (they should track the site's own translations), and it
 * means an admin who renames one will see it revert on a re-seed —
 * re-seeding production is not a routine operation.
 */
class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->items() as $sortOrder => $item) {
            MenuItem::updateOrCreate(
                [
                    'is_system' => true,
                    'route_name' => $item['route'],
                ],
                [
                    'label' => $item['label'],
                    'link_type' => 'route',
                    'url' => null,
                    'target' => '_self',
                    'parent_id' => null,
                    'sort_order' => $sortOrder,
                    // is_visible is NOT re-applied: hiding a core item is a
                    // legitimate admin choice a re-seed should not undo.
                ]
            );
        }
    }

    /**
     * Mirrors the old hardcoded navbar, in the same order, using the same
     * translation strings it rendered.
     *
     * @return array<int, array{route: string, label: array<string, string>}>
     */
    private function items(): array
    {
        return [
            ['route' => 'home', 'label' => $this->label('home')],
            ['route' => 'services.index', 'label' => $this->label('services')],
            ['route' => 'countries.index', 'label' => $this->label('countries')],
            ['route' => 'articles.index', 'label' => $this->label('blog')],
            ['route' => 'pages.about', 'label' => $this->label('about')],
            ['route' => 'contact', 'label' => $this->label('contact')],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function label(string $key): array
    {
        return [
            'ar' => __('site.nav.'.$key, [], 'ar'),
            'en' => __('site.nav.'.$key, [], 'en'),
        ];
    }
}
