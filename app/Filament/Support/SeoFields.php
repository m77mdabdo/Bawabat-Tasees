<?php

namespace App\Filament\Support;

use App\Models\SeoMeta;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;

/**
 * The search-engine fields shared by services, articles, countries and pages (the seoMeta morph).
 * Empty means "use the content itself", so nothing here is required.
 */
final class SeoFields
{
    public static function section(): Section
    {
        return Section::make(__('Search engines (SEO)'))
            ->description(__('Optional. Leave empty to use the title and summary of the content.'))
            ->icon('heroicon-o-magnifying-glass')
            ->collapsible()
            ->collapsed()
            ->columnSpanFull()
            ->relationship('seoMeta', condition: fn (?array $state) => self::hasContent($state))
            ->mutateRelationshipDataBeforeFillUsing(function (array $data, ?Model $record): array {
                $meta = $record instanceof Model ? $record->seoMeta : null;
                if ($meta instanceof SeoMeta) {
                    $data['meta_title'] = $meta->getTranslations('meta_title');
                    $data['meta_description'] = $meta->getTranslations('meta_description');
                }

                return $data;
            })
            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data) => Bilingual::clean($data, ['meta_title', 'meta_description']))
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => Bilingual::clean($data, ['meta_title', 'meta_description']))
            ->schema([
                Bilingual::tabs(fn (string $locale) => [
                    TextInput::make("meta_title.$locale")->label(__('Meta title'))->maxLength(255)->extraInputAttributes(Bilingual::dir($locale)),
                    Textarea::make("meta_description.$locale")->label(__('Meta description'))->maxLength(320)->rows(2)->extraInputAttributes(Bilingual::dir($locale)),
                ], 'seo'),
                TextInput::make('canonical_url')->label(__('Canonical URL'))->url()->maxLength(2048)->extraInputAttributes(['dir' => 'ltr']),
                FileUpload::make('og_image')->label(__('Share image (Open Graph)'))->image()->disk('public')->directory('seo')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(2048),
            ]);
    }

    /** @param  array<string, mixed>|null  $state */
    private static function hasContent(?array $state): bool
    {
        foreach (['meta_title', 'meta_description'] as $key) {
            foreach ((array) ($state[$key] ?? []) as $value) {
                if (is_string($value) && trim($value) !== '') {
                    return true;
                }
            }
        }

        return filled($state['canonical_url'] ?? null) || filled($state['og_image'] ?? null);
    }
}
