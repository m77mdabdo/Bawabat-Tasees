<?php

namespace App\Filament\Resources\MenuItems\Pages;

use App\Filament\Concerns\EditsTranslations;
use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Services\Cms\MenuService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMenuItem extends EditRecord
{
    use EditsTranslations;

    protected static string $resource = MenuItemResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(MenuService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->using(fn (Model $record) => app(MenuService::class)->delete($record) ?? true)];
    }
}
