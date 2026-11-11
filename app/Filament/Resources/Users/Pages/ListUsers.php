<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('matrix')->label(__('Roles and permissions'))->icon('heroicon-o-shield-check')->color('gray')
                ->modalHeading(__('Roles and permissions'))->modalSubmitAction(false)->modalCancelActionLabel(__('Close'))
                ->modalWidth('5xl')
                ->modalContent(fn () => view('filament.roles-matrix', UserResource::matrix())),
            CreateAction::make()->label(__('Invite staff member')),
        ];
    }
}
