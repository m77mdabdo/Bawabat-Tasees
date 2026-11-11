<?php

namespace App\Filament\Concerns;

use App\Filament\Support\Bilingual;

/**
 * For Create/Edit pages of models with spatie translations: the form sees every language
 * ("name" => ["ar" => ..., "en" => ...]) instead of only the current one, and blank
 * translations are dropped on save. Add to a page; nothing else to configure.
 */
trait EditsTranslations
{
    /** @return list<string> */
    protected function translatableAttributes(): array
    {
        $model = static::getResource()::getModel();

        return (new $model)->getTranslatableAttributes();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach ($this->translatableAttributes() as $attribute) {
            $data[$attribute] = $this->getRecord()->getTranslations($attribute);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return Bilingual::clean($data, $this->translatableAttributes());
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return Bilingual::clean($data, $this->translatableAttributes());
    }
}
