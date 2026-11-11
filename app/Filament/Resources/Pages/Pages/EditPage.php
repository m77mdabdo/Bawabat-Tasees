<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    use EditsTranslations;

    protected static string $resource = PageResource::class;
}
