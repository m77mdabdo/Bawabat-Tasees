<?php

namespace App\Filament\Resources\Campaigns\RelationManagers;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Leads linked to a campaign (read-only; links are made by CampaignService from the campaign ID). */
class LeadsRelationManager extends RelationManager
{
    protected static string $relationship = 'leads';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('dashboard.campaigns.linked_leads');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('dashboard.campaigns.no_linked_leads'))
            ->recordUrl(fn (Lead $lead) => auth()->user()?->can('leads.view') ? LeadResource::getUrl('view', ['record' => $lead]) : null)
            ->columns([
                TextColumn::make('full_name')->label(__('Name'))->weight('semibold'),
                TextColumn::make('type')->label(__('Type'))->badge()->formatStateUsing(fn (string $state) => LeadResource::typeOptions()[$state] ?? $state),
                IconColumn::make('converted')->label(__('Converted'))->boolean()->state(fn (Lead $l) => $l->isConverted()),
                TextColumn::make('created_at')->label(__('Received'))->since(),
            ]);
    }
}
