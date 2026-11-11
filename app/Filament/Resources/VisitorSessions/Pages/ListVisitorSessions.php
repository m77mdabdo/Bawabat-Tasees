<?php

namespace App\Filament\Resources\VisitorSessions\Pages;

use App\Filament\Resources\VisitorSessions\VisitorSessionResource;
use Filament\Resources\Pages\ListRecords;

class ListVisitorSessions extends ListRecords
{
    protected static string $resource = VisitorSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [VisitorSessionResource::exportAction()];
    }
}
