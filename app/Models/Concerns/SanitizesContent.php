<?php

namespace App\Models\Concerns;

use App\Services\Cms\HtmlSanitizerService;

/**
 * Cleans translatable text before it is saved, whatever wrote it (dashboard, seeder, console).
 *
 * A model lists its rich-text attributes (rendered with {!! !!} on the public site, so they go
 * through the "article" HTMLPurifier profile) and its plain attributes (tags stripped):
 *
 *   protected array $richTextAttributes = ['body'];
 *   protected array $plainTextAttributes = ['title', 'excerpt'];
 */
trait SanitizesContent
{
    protected static function bootSanitizesContent(): void
    {
        static::saving(function (self $model) {
            $sanitizer = app(HtmlSanitizerService::class);

            foreach ($model->richTextAttributes ?? [] as $attribute) {
                $model->cleanTranslations($attribute, fn (string $v) => $sanitizer->sanitizeArticleBody($v));
            }
            foreach ($model->plainTextAttributes ?? [] as $attribute) {
                $model->cleanTranslations($attribute, fn (string $v) => $sanitizer->stripAllTags($v));
            }
        });
    }

    private function cleanTranslations(string $attribute, callable $clean): void
    {
        if (! $this->isDirty($attribute)) {
            return;
        }

        foreach ($this->getTranslations($attribute) as $locale => $value) {
            if (is_string($value) && $value !== '') {
                $this->setTranslation($attribute, $locale, $clean($value));
            }
        }
    }
}
