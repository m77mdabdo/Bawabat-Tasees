<?php

namespace App\Filament\Resources\Campaigns;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Resources\Campaigns\RelationManagers\LeadsRelationManager;
use App\Models\Campaign;
use App\Models\ConversionEvent;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Paid campaigns, linked to the leads they produced (CampaignService) for return-on-spend reporting. */
class CampaignResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Campaign::class;

    protected static string $viewPermission = 'marketing.manage';

    protected static string $managePermission = 'marketing.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Marketing & analytics');
    }

    public static function getModelLabel(): string
    {
        return __('Campaign');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.campaigns.title');
    }

    /** @return array<string, string> */
    public static function platformOptions(): array
    {
        return collect(Campaign::PLATFORMS)->mapWithKeys(fn ($p) => [$p => __("dashboard.campaigns.platforms.$p")])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label(__('dashboard.campaigns.name'))->required()->maxLength(255),
                Select::make('platform')->label(__('dashboard.campaigns.platform'))->options(self::platformOptions())->native(false),
                TextInput::make('external_campaign_id')->label(__('dashboard.campaigns.external_campaign_id'))
                    ->helperText(__('dashboard.campaigns.external_campaign_id_hint'))->maxLength(255)
                    ->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr'])->columnSpanFull(),
                TextInput::make('budget')->label(__('dashboard.campaigns.budget'))->numeric()->minValue(0)->maxValue(9999999999.99),
                TextInput::make('spend')->label(__('dashboard.campaigns.spend'))->numeric()->minValue(0)->maxValue(9999999999.99),
                TextInput::make('currency')->label(__('dashboard.campaigns.currency'))->default('SAR')->length(3)->alpha(),
                Toggle::make('is_active')->label(__('dashboard.campaigns.active_label'))->default(true)->inline(false),
                DatePicker::make('starts_on')->label(__('dashboard.campaigns.starts_on')),
                DatePicker::make('ends_on')->label(__('dashboard.campaigns.ends_on'))->afterOrEqual('starts_on'),
                Textarea::make('notes')->label(__('dashboard.campaigns.notes'))->maxLength(2000)->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('leads')
                ->withSum(['conversionEvents as won_value' => fn ($q) => $q->whereIn('event_type', ConversionEvent::WON_TYPES)], 'value'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('dashboard.campaigns.name'))->weight('semibold')->searchable()
                    ->description(fn (Campaign $c) => $c->platform ? self::platformOptions()[$c->platform] ?? $c->platform : null),
                TextColumn::make('leads_count')->label(__('dashboard.campaigns.leads_count'))->badge()->color('info'),
                TextColumn::make('spend')->label(__('dashboard.campaigns.spend'))->money(fn (Campaign $c) => $c->currency ?: 'SAR')->placeholder('—'),
                TextColumn::make('won_value')->label(__('dashboard.campaigns.conversion_value'))->money(fn (Campaign $c) => $c->currency ?: 'SAR')->placeholder('—'),
                TextColumn::make('roi')->label(__('dashboard.campaigns.roi'))->state(fn (Campaign $c) => (float) $c->spend > 0
                    ? round(((float) $c->won_value - (float) $c->spend) / (float) $c->spend * 100).'%' : '—')
                    ->color(fn ($state) => str_starts_with((string) $state, '-') ? 'danger' : 'success'),
                ToggleColumn::make('is_active')->label(__('Active')),
            ])
            ->filters([SelectFilter::make('platform')->label(__('dashboard.campaigns.platform'))->options(self::platformOptions())])
            ->recordActions([EditAction::make(), DeleteAction::make()->modalDescription(__('dashboard.campaigns.confirm_delete'))]);
    }

    public static function getRelations(): array
    {
        return [LeadsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }
}
