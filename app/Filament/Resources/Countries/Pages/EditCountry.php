<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\Countries\CountryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCountry extends EditRecord
{
    use EditsTranslations;

    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
