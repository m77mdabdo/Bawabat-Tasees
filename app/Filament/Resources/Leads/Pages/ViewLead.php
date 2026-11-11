<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->full_name.' · '.$this->getRecord()->reference();
    }

    protected function getHeaderActions(): array
    {
        return [
            LeadResource::toggleHandledAction(),
            LeadResource::whatsappAction(),
            DeleteAction::make(),
        ];
    }
}
