<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\SeoMeta;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resolves the final set of SEO/social tag values for a public page.
 *
 * Everything funnels through here rather than being assembled per-view so
 * the precedence rules live in exactly one place:
 *
 *   1. the record's own SeoMeta row (admin-authored, locale-aware)
 *   2. a fallback derived from the record's content (title/name, excerpt/…)
 *   3. the site-wide seo_defaults settings
 *   4. the brand name / a non-empty last resort
 *
 * A tag is therefore never emitted empty, which is the whole point — an
 * empty <title> or description is worse than an imperfect one.
 */
class SeoTagService
{
    /**
     * Content fields, in priority order, that stand in for a missing
     * meta_title on each supported model. Keyed by nothing — the first
     * attribute that exists and is non-empty wins.
     */
    private const TITLE_SOURCES = ['meta_title', 'title', 'name'];

    private const DESCRIPTION_SOURCES = ['excerpt', 'summary', 'notes', 'meta_description'];

    private const IMAGE_SOURCES = ['og_image', 'cover_image', 'flag_image', 'avatar'];

    /**
     * @param  array{title?: ?string, description?: ?string, type?: ?string}  $overrides
     *                                                                                    Explicit values from the view (used by list pages, which have
     *                                                                                    no single backing record). Lower priority than SeoMeta.
     * @return array<string, ?string>
     */
    public function resolve(?Model $model = null, array $overrides = []): array
    {
        $seoMeta = $this->seoMetaFor($model);
        $locale = app()->getLocale();

        $title = $this->firstFilled([
            $seoMeta?->meta_title,
            $overrides['title'] ?? null,
            $this->fromModel($model, self::TITLE_SOURCES),
            $this->setting('default_meta_title'),
            __('site.brand.name'),
        ]);

        $description = $this->firstFilled([
            $seoMeta?->meta_description,
            $overrides['description'] ?? null,
            $this->fromModel($model, self::DESCRIPTION_SOURCES),
            $this->setting('default_meta_description'),
            __('site.brand.name'),
        ]);

        return [
            'title' => $this->trimTo($title, 120),
            'description' => $this->trimTo($description, 160),
            'canonical' => $this->firstFilled([
                $seoMeta?->canonical_url,
                url()->current(),
            ]),
            'og_type' => $overrides['type'] ?? ($model ? $this->ogTypeFor($model) : 'website'),
            'og_url' => url()->current(),
            'og_image' => $this->imageUrl($seoMeta, $model),
            'og_locale' => $locale === 'ar' ? 'ar_SA' : 'en_US',
            'og_locale_alternate' => $locale === 'ar' ? 'en_US' : 'ar_SA',
            'site_name' => __('site.brand.name'),
        ];
    }

    /**
     * Loads the morph relation without assuming it exists — Faq and
     * Testimonial deliberately have no seoMeta(), and list pages pass no
     * model at all.
     */
    private function seoMetaFor(?Model $model): ?SeoMeta
    {
        if (! $model || ! method_exists($model, 'seoMeta')) {
            return null;
        }

        /** @var SeoMeta|null $seoMeta */
        $seoMeta = $model->relationLoaded('seoMeta') ? $model->getRelation('seoMeta') : $model->seoMeta;

        return $seoMeta;
    }

    private function fromModel(?Model $model, array $attributes): ?string
    {
        if (! $model) {
            return null;
        }

        foreach ($attributes as $attribute) {
            $value = $model->{$attribute} ?? null;

            if (is_string($value) && trim($value) !== '') {
                // Content bodies are HTML; meta tags must be plain text.
                return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
            }
        }

        return null;
    }

    /**
     * Articles are the only content type that maps to a non-"website"
     * Open Graph type here; everything else is a marketing page.
     */
    private function ogTypeFor(Model $model): string
    {
        return $model instanceof Article ? 'article' : 'website';
    }

    private function imageUrl(?SeoMeta $seoMeta, ?Model $model): ?string
    {
        $path = $seoMeta?->og_image;

        if (! $path && $model) {
            foreach (self::IMAGE_SOURCES as $attribute) {
                if (! empty($model->{$attribute}) && is_string($model->{$attribute})) {
                    $path = $model->{$attribute};
                    break;
                }
            }
        }

        if ($path) {
            return Str::startsWith($path, ['http://', 'https://'])
                ? $path
                : Storage::url($path);
        }

        // Brand logo as the last resort so a shared link is never imageless.
        return asset('images/brand/logo-full-color-1024.png');
    }

    private function setting(string $key): ?string
    {
        $value = Setting::where('key', $key)->value('value');

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * @param  array<int, ?string>  $candidates
     */
    private function firstFilled(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    private function trimTo(?string $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::limit($value, $limit, '');
    }
}
