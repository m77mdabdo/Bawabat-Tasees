<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\SanitizesContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory, HasSlug, HasTranslations, SanitizesContent;

    /** @var list<string> Rendered as HTML on the public site, so purified on every save. */
    protected array $richTextAttributes = ['body'];

    /** @var list<string> Tags stripped on every save. */
    protected array $plainTextAttributes = ['title', 'meta_title', 'meta_description'];

    protected $fillable = [
        'slug',
        'is_published',
        'title',
        'body',
        'meta_title',
        'meta_description',
    ];

    public $translatable = [
        'title',
        'body',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
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

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class);
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seo_metable');
    }
}
