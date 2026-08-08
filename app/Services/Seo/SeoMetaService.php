<?php

namespace App\Services\Seo;

use App\Models\SeoMeta;
use App\Services\Cms\ContentPublishingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Write side of the SeoMeta morph relation, shared by the Article, Page,
 * Country and Service dashboard controllers so the persist logic is not
 * copy-pasted four times.
 */
class SeoMetaService
{
    public function __construct(
        private readonly ContentPublishingService $contentPublishingService,
    ) {}

    /**
     * Creates, updates, or leaves alone the record's SeoMeta row.
     *
     * @param  array<string, mixed>  $data  The validated `seo` sub-array.
     */
    public function persist(Model $model, array $data, ?UploadedFile $ogImage = null): ?SeoMeta
    {
        /** @var SeoMeta|null $existing */
        $existing = $model->seoMeta()->first();

        $attributes = [
            'meta_title' => $this->translatable($data, 'meta_title'),
            'meta_description' => $this->translatable($data, 'meta_description'),
            'canonical_url' => $data['canonical_url'] ?? null,
        ];

        if ($ogImage) {
            $attributes['og_image'] = $this->contentPublishingService->replaceImage(
                $ogImage,
                'seo',
                $existing?->og_image
            );
        }

        // Nothing filled in and no row yet: don't create an empty record.
        // The public side already falls back to the content itself, so a
        // blank SeoMeta row would add a query for no benefit.
        if (! $existing && ! $this->hasContent($attributes)) {
            return null;
        }

        if ($existing) {
            $existing->update($attributes);

            return $existing;
        }

        return $model->seoMeta()->create($attributes);
    }

    /**
     * Deletes the row and its uploaded image — called when the owning
     * record is hard-deleted, since morphOne has no DB-level cascade.
     */
    public function purge(Model $model): void
    {
        $seoMeta = $model->seoMeta()->first();

        if (! $seoMeta) {
            return;
        }

        $this->contentPublishingService->deleteImage($seoMeta->og_image);
        $seoMeta->delete();
    }

    /**
     * Drops locales the admin left blank so a translatable field stores
     * {"ar": "..."} rather than {"ar": "...", "en": ""} — an empty string
     * would otherwise defeat the fallback chain on the public side.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>|null
     */
    private function translatable(array $data, string $key): ?array
    {
        $values = array_filter(
            $data[$key] ?? [],
            fn ($value) => is_string($value) && trim($value) !== ''
        );

        return $values === [] ? null : array_map('trim', $values);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function hasContent(array $attributes): bool
    {
        foreach ($attributes as $value) {
            if (is_array($value) ? $value !== [] : (is_string($value) && trim($value) !== '')) {
                return true;
            }
        }

        return false;
    }
}
