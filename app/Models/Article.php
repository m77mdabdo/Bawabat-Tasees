<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Article extends Model
{
    use HasFactory, HasSlug, HasTranslations, SoftDeletes;

    protected $fillable = [
        'slug',
        'cover_image',
        'published_at',
        'is_published',
        'title',
        'excerpt',
        'body',
    ];

    public $translatable = [
        'title',
        'excerpt',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function slugSourceField(): string
    {
        return 'title';
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seo_metable');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
