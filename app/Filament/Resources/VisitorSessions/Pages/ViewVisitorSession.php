<?php

namespace App\Filament\Resources\VisitorSessions\Pages;

use App\Filament\Resources\VisitorSessions\VisitorSessionResource;
use App\Services\Analytics\VisitorSessionReportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewVisitorSession extends ViewRecord
{
    protected static string $resource = VisitorSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportOne')->label(__('dashboard.visitor_sessions.export_one'))->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(function () {
                    $report = app(VisitorSessionReportService::class);
                    $session = $this->getRecord()->load('lead');

                    return VisitorSessionResource::download($report->export(collect([$session])), $report->exportFilename($session));
                }),
        ];
    }
}
