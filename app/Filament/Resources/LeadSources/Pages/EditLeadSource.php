<?php

namespace App\Filament\Resources\LeadSources\Pages;

use App\Filament\Resources\LeadSources\LeadSourceResource;
use App\Services\Marketing\LeadSourceService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLeadSource extends EditRecord
{
    protected static string $resource = LeadSourceResource::class;

    /** Through the service: renaming a key re-points the leads that used it. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(LeadSourceService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->visible(fn () => ! $this->getRecord()->is_system)];
    }
}
