<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Models\ConversionEvent;
use App\Models\Lead;
use App\Services\Marketing\ConversionEventService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** What an enquiry turned into: qualified, meeting, contract, payment. Feeds the revenue reports. */
class ConversionEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'conversionEvents';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Conversions');
    }

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return collect(ConversionEvent::TYPES)->mapWithKeys(fn (string $t) => [$t => __('dashboard.conversions.types.'.$t)])->all();
    }

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->can('leads.manage');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('event_type')->label(__('What happened'))->options(self::typeOptions())->required()->native(false),
            DateTimePicker::make('occurred_at')->label(__('When'))->default(now())->maxDate(now())->seconds(false),
            TextInput::make('value')->label(__('Value'))->numeric()->minValue(0)->maxValue(9999999999.99),
            TextInput::make('currency')->label(__('Currency'))->default('SAR')->length(3)->alpha(),
            Textarea::make('notes')->label(__('Notes'))->maxLength(2000)->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('event_type')->label(__('What happened'))->badge()
                    ->formatStateUsing(fn (string $state) => self::typeOptions()[$state] ?? $state)
                    ->color(fn (string $state) => in_array($state, ConversionEvent::WON_TYPES, true) ? 'success' : 'info'),
                TextColumn::make('value')->label(__('Value'))->money(fn (ConversionEvent $e) => $e->currency ?: 'SAR')->placeholder('—'),
                TextColumn::make('occurred_at')->label(__('When'))->dateTime('j M Y, H:i', 'Asia/Riyadh'),
                TextColumn::make('notes')->label(__('Notes'))->limit(60)->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->label(__('Record a conversion'))
                    ->using(fn (array $data) => app(ConversionEventService::class)->log($this->getOwnerRecordAsLead(), $data)),
            ])
            ->recordActions([DeleteAction::make()]);
    }

    private function getOwnerRecordAsLead(): Lead
    {
        /** @var Lead */
        return $this->getOwnerRecord();
    }
}
