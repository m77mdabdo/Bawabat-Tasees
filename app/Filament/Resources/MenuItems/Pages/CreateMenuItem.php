<?php

namespace App\Filament\Resources\MenuItems\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Models\MenuItem;
use App\Services\Cms\MenuService;
use Filament\Resources\Pages\CreateRecord;

class CreateMenuItem extends CreateRecord
{
    use EditsTranslations;

    protected static string $resource = MenuItemResource::class;

    protected function handleRecordCreation(array $data): MenuItem
    {
        return app(MenuService::class)->create($data);
    }
}
