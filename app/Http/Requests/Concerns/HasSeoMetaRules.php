<?php

namespace App\Http\Requests\Concerns;

/**
 * Shared SEO-meta validation for the four seoMetable resources (Article,
 * Page, Country, Service), so the rules live in one place instead of
 * being repeated across seven Store/Update request classes.
 *
 * Unlike the content fields, `.ar` is NOT unconditionally required here:
 * SEO meta is supplemental, and an admin must be able to save an article
 * without filling it in. The site falls back to the record's own content
 * when no SeoMeta row exists. What IS enforced is the project's
 * Arabic-primary convention — an English value may not be supplied
 * without its Arabic counterpart.
 */
trait HasSeoMetaRules
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function seoMetaRules(): array
    {
        return [
            'seo' => ['nullable', 'array'],

            'seo.meta_title' => ['nullable', 'array'],
            'seo.meta_title.ar' => ['nullable', 'required_with:seo.meta_title.en', 'string', 'max:255'],
            'seo.meta_title.en' => ['nullable', 'string', 'max:255'],

            'seo.meta_description' => ['nullable', 'array'],
            'seo.meta_description.ar' => ['nullable', 'required_with:seo.meta_description.en', 'string', 'max:320'],
            'seo.meta_description.en' => ['nullable', 'string', 'max:320'],

            'seo.canonical_url' => ['nullable', 'url', 'max:2048'],

            'seo_og_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];
    }
}
