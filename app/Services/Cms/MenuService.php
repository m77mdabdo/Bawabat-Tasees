<?php

namespace App\Services\Cms;

use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;

/**
 * Owns every write to menu_items, so the normalisation rules and the
 * ordering/nesting invariants live in one place rather than in the
 * controller.
 */
class MenuService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MenuItem
    {
        $data = $this->normalise($data);

        // New items land at the end of their own level.
        $data['sort_order'] = $this->nextSortOrder($data['parent_id'] ?? null);

        return MenuItem::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MenuItem $item, array $data): MenuItem
    {
        $data = $this->normalise($data);

        // Moving to a different level puts the item at the end of the new
        // one, rather than colliding with an existing sort_order there.
        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== $item->parent_id) {
            $data['sort_order'] = $this->nextSortOrder($data['parent_id']);
        }

        $item->update($data);

        return $item;
    }

    /**
     * Children are reparented to top level, never deleted — the same
     * no-orphan rule the schema enforces via nullOnDelete, made explicit
     * here so the intent is visible at the application layer too.
     */
    public function delete(MenuItem $item): void
    {
        DB::transaction(function () use ($item) {
            $item->children()->update([
                'parent_id' => null,
                'sort_order' => 0,
            ]);

            $item->delete();
        });
    }

    public function toggleVisibility(MenuItem $item): MenuItem
    {
        $item->update(['is_visible' => ! $item->is_visible]);

        return $item;
    }

    /**
     * Persists a whole tree in one transaction.
     *
     * @param  array<int, array{id: int|string, parent_id?: int|string|null}>  $items
     * @return int number of rows written
     */
    public function reorder(array $items): int
    {
        return DB::transaction(function () use ($items) {
            $written = 0;

            foreach (array_values($items) as $position => $row) {
                $parentId = $row['parent_id'] ?? null;
                $parentId = ($parentId === '' || $parentId === 'null') ? null : $parentId;

                // Nesting stays capped at one level: an item may only be
                // parented to something that is itself top-level.
                if ($parentId !== null && MenuItem::whereKey($parentId)->whereNotNull('parent_id')->exists()) {
                    $parentId = null;
                }

                // An item cannot be its own parent.
                if ($parentId !== null && (int) $parentId === (int) $row['id']) {
                    $parentId = null;
                }

                $written += MenuItem::whereKey($row['id'])->update([
                    'parent_id' => $parentId,
                    'sort_order' => $position,
                ]);
            }

            // A moved-away parent must not leave grandchildren behind: any
            // item whose parent is itself nested gets pulled back up.
            MenuItem::whereIn('parent_id', MenuItem::whereNotNull('parent_id')->select('id'))
                ->update(['parent_id' => null]);

            return $written;
        });
    }

    private function nextSortOrder(?int $parentId): int
    {
        return (int) MenuItem::where('parent_id', $parentId)->max('sort_order') + 1;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalise(array $data): array
    {
        // Every read is null-coalesced: nullable fields are absent from
        // validated() entirely when the client omits them, and an
        // undefined-key warning is escalated to an ErrorException (and so
        // a 500) by Laravel's error handler.
        $data['link_type'] = $data['link_type'] ?? 'route';
        $data['target'] = $data['target'] ?? '_self';
        $data['is_visible'] = (bool) ($data['is_visible'] ?? false);
        $data['parent_id'] = ($data['parent_id'] ?? null) ?: null;
        $data['route_name'] = $data['route_name'] ?? null;
        $data['url'] = $data['url'] ?? null;

        // Clear the field the chosen link type does not use, so a stale
        // value can never resurface if the type is switched back.
        if ($data['link_type'] !== 'route') {
            $data['route_name'] = null;
        }

        if ($data['link_type'] !== 'url') {
            $data['url'] = null;
        }

        // A dropdown parent has no destination of its own.
        if ($data['link_type'] === 'none') {
            $data['target'] = '_self';
        }

        // Drop blank locales so label stores {"ar": "..."} rather than
        // {"ar": "...", "en": ""} — an empty string would render as a
        // blank nav item in English instead of falling back to Arabic.
        if (isset($data['label']) && is_array($data['label'])) {
            $data['label'] = array_filter(
                array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data['label']),
                fn ($v) => is_string($v) && $v !== ''
            );
        }

        return $data;
    }
}
