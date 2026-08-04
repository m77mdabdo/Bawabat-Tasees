<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    protected $fillable = [
        'page_id',
        'key',
        'sort_order',
        'content',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * content is a single JSON blob (not spatie/laravel-translatable),
     * so title/description need their own locale-aware accessors rather
     * than getTranslation(). Falls back to 'ar' since every section is
     * required to have an Arabic value.
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->content['title'][app()->getLocale()] ?? $this->content['title']['ar'] ?? null,
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->content['description'][app()->getLocale()] ?? $this->content['description']['ar'] ?? null,
        );
    }

    protected function icon(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->content['icon'] ?? null,
        );
    }
}
