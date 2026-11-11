<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('All')),
            'unhandled' => Tab::make(__('Not handled'))->icon('heroicon-o-bell-alert')
                ->badge(fn () => Lead::query()->unhandled()->count() ?: null)->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->unhandled()),
            'consultation' => Tab::make(__('Consultation requests'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', Lead::TYPE_CONSULTATION)),
            'contact' => Tab::make(__('Contact messages'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', Lead::TYPE_CONTACT)),
        ];
    }
}
