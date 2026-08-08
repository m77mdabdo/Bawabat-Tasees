<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

class SeoMeta extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'seo_meta';

    protected $fillable = [
        'seo_metable_type',
        'seo_metable_id',
        'meta_title',
        'meta_description',
        'og_image',
        'canonical_url',
    ];

    public $translatable = [
        'meta_title',
        'meta_description',
    ];

    public function seoMetable(): MorphTo
    {
        return $this->morphTo();
    }
}
