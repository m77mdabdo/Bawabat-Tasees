<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** The five most recent enquiries, one click from the full record. */
class LatestLeadsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected ?string $pollingInterval = '15s';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('leads.view');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Latest enquiries'))
            ->query(Lead::query()->latest()->limit(5))
            ->paginated(false)
            ->recordUrl(fn (Lead $lead) => LeadResource::getUrl('view', ['record' => $lead]))
            ->columns([
                TextColumn::make('full_name')->label(__('Name'))->weight('semibold')
                    ->description(fn (Lead $l) => LeadResource::typeOptions()[$l->type] ?? $l->type),
                IconColumn::make('is_handled')->label(__('Handled'))->boolean(),
                TextColumn::make('created_at')->label(__('Received'))->since(),
            ]);
    }
}
