<?php

namespace App\Filament\Resources\LeadSources\Pages;

use App\Filament\Resources\LeadSources\LeadSourceResource;
use App\Models\LeadSource;
use App\Services\Marketing\LeadSourceService;
use Filament\Resources\Pages\CreateRecord;

class CreateLeadSource extends CreateRecord
{
    protected static string $resource = LeadSourceResource::class;

    protected function handleRecordCreation(array $data): LeadSource
    {
        return app(LeadSourceService::class)->create($data);
    }
}
