<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use App\Services\Marketing\CampaignService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    /** Through the service, so a changed campaign ID re-links its leads. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(CampaignService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
