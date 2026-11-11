<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use App\Models\Campaign;
use App\Services\Marketing\CampaignService;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    /** Through the service, so existing leads with this campaign ID are linked at once. */
    protected function handleRecordCreation(array $data): Campaign
    {
        return app(CampaignService::class)->create($data);
    }
}
