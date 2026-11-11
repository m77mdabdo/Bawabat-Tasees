<?php

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Arabic / English tabs for translatable fields (spatie/laravel-translatable).
 *
 * Fields are named "{attribute}.{locale}". Arabic is the site's master language, so it is
 * required where the old dashboard required it; English is optional and falls back to Arabic.
 *
 *   Bilingual::tabs(fn (string $locale, bool $master) => [
 *       TextInput::make("name.$locale")->required($master),
 *   ])
 */
final class Bilingual
{
    public const LOCALES = ['ar' => 'العربية', 'en' => 'English'];

    /** @param  Closure(string $locale, bool $master): list<Field>  $fields */
    public static function tabs(Closure $fields, ?string $key = null): Tabs
    {
        return Tabs::make($key ?? 'languages')
            ->columnSpanFull()
            ->tabs(collect(self::LOCALES)->map(
                fn (string $label, string $locale) => Tab::make($label)
                    ->key(($key ?? 'languages').'-'.$locale)
                    ->extraAttributes(['dir' => $locale === 'ar' ? 'rtl' : 'ltr'])
                    ->schema($fields($locale, $locale === 'ar'))
            )->values()->all());
    }

    /**
     * Text direction for an input whose content is in the given language, whatever the dashboard language is.
     *
     * @return array<string, string>
     */
    public static function dir(string $locale): array
    {
        return ['dir' => $locale === 'ar' ? 'rtl' : 'ltr'];
    }

    /**
     * Drops empty translations so English falls back to Arabic on the public site instead of showing blank.
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>  $attributes
     * @return array<string, mixed>
     */
    public static function clean(array $data, array $attributes): array
    {
        foreach ($attributes as $attribute) {
            if (is_array($data[$attribute] ?? null)) {
                $data[$attribute] = array_map('trim', array_filter(
                    $data[$attribute],
                    fn ($value) => is_string($value) && trim($value) !== '',
                ));
            }
        }

        return $data;
    }
}
