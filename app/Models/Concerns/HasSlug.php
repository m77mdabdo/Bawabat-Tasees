<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = $model->generateUniqueSlug();
            }
        });
    }

    protected function generateUniqueSlug(): string
    {
        $source = collect($this->getTranslations($this->slugSourceField()))->first() ?? '';
        $base = Str::slug($source);
        $slug = $base;
        $suffix = 2;

        // withTrashed() where the model soft-deletes: the DB unique index
        // on `slug` counts soft-deleted rows, but the default global scope
        // hides them — so without this the loop declares a slug free while
        // a trashed row still holds it, and the insert dies with a
        // UniqueConstraintViolationException.
        while ($this->newSlugQuery()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * A query that sees every row competing for a slug — including
     * soft-deleted ones on models that use SoftDeletes, since the unique
     * index does not ignore them. Models without SoftDeletes get a plain
     * query (calling withTrashed() on them would throw).
     */
    protected function newSlugQuery(): Builder
    {
        $query = static::query();

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $query->withTrashed();
        }

        return $query;
    }
}
