<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Page;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Gives every seeded Page and Article a SeoMeta row so a fresh install
 * demonstrates the feature rather than relying purely on the fallback
 * chain.
 *
 * Deliberately derives the values from the record's own content instead
 * of hand-writing 10 more pairs of strings: that keeps the seeder in step
 * with PageContentSeeder/ArticleSeeder automatically, and a meta
 * description genuinely *should* read like a trimmed version of the page
 * it describes.
 *
 * Runs after those seeders (see DatabaseSeeder) and is idempotent —
 * updateOrCreate keyed on the morph pair.
 */
class SeoMetaSeeder extends Seeder
{
    public function run(): void
    {
        Page::all()->each(fn (Page $page) => $this->seedFor(
            $page,
            titles: ['ar' => $this->text($page, 'title', 'ar'), 'en' => $this->text($page, 'title', 'en')],
            descriptions: [
                'ar' => $this->text($page, 'meta_description', 'ar') ?: $this->summarise($page, 'body', 'ar'),
                'en' => $this->text($page, 'meta_description', 'en') ?: $this->summarise($page, 'body', 'en'),
            ],
        ));

        Article::all()->each(fn (Article $article) => $this->seedFor(
            $article,
            titles: ['ar' => $this->text($article, 'title', 'ar'), 'en' => $this->text($article, 'title', 'en')],
            descriptions: [
                'ar' => $this->text($article, 'excerpt', 'ar') ?: $this->summarise($article, 'body', 'ar'),
                'en' => $this->text($article, 'excerpt', 'en') ?: $this->summarise($article, 'body', 'en'),
            ],
        ));
    }

    /**
     * @param  array<string, ?string>  $titles
     * @param  array<string, ?string>  $descriptions
     */
    private function seedFor(Model $model, array $titles, array $descriptions): void
    {
        $brand = 'بوابة تأسيس الشركات';
        $brandEn = 'Bawabat Taasees Al Sharikat';

        $metaTitle = array_filter([
            'ar' => $titles['ar'] ? Str::limit($titles['ar'].' — '.$brand, 120, '') : null,
            'en' => $titles['en'] ? Str::limit($titles['en'].' — '.$brandEn, 120, '') : null,
        ]);

        $metaDescription = array_filter([
            'ar' => $descriptions['ar'] ? Str::limit($descriptions['ar'], 160, '') : null,
            'en' => $descriptions['en'] ? Str::limit($descriptions['en'], 160, '') : null,
        ]);

        if ($metaTitle === [] && $metaDescription === []) {
            return;
        }

        SeoMeta::updateOrCreate(
            [
                'seo_metable_type' => $model->getMorphClass(),
                'seo_metable_id' => $model->getKey(),
            ],
            [
                'meta_title' => $metaTitle ?: null,
                'meta_description' => $metaDescription ?: null,
            ]
        );
    }

    /**
     * Raw per-locale value with no fallback — an Arabic string must not
     * leak into the English meta tag.
     */
    private function text(Model $model, string $field, string $locale): ?string
    {
        $value = $model->getTranslation($field, $locale, false);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function summarise(Model $model, string $field, string $locale): ?string
    {
        $value = $this->text($model, $field, $locale);

        if (! $value) {
            return null;
        }

        return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
    }
}
