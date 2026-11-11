<?php

namespace App\Filament\Resources\LeadSources;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\LeadSources\Pages\CreateLeadSource;
use App\Filament\Resources\LeadSources\Pages\EditLeadSource;
use App\Filament\Resources\LeadSources\Pages\ListLeadSources;
use App\Models\LeadSource;
use App\Services\Marketing\LeadSourceService;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/** The names that "source" values on leads map to (google, meta, ...). Built-in sources keep their key. */
class LeadSourceResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = LeadSource::class;

    protected static string $viewPermission = 'marketing.manage';

    protected static string $managePermission = 'marketing.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Marketing & analytics');
    }

    public static function getModelLabel(): string
    {
        return __('Lead source');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.sidebar.lead_sources');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextInput::make('key')->label(__('Key'))->required()->maxLength(60)->regex('/^[a-z0-9_-]+$/i')
                    ->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr'])
                    ->disabled(fn (?LeadSource $record) => (bool) $record?->is_system)
                    ->dehydrated(fn (?LeadSource $record) => ! $record?->is_system),
                TextInput::make('label')->label(__('Label'))->required()->maxLength(120),
                TextInput::make('sort_order')->label(__('Order'))->numeric()->minValue(0)->maxValue(999)->default(0),
                Toggle::make('is_active')->label(__('Active'))->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('label')->label(__('Label'))->weight('semibold')
                    ->description(fn (LeadSource $s) => $s->is_system ? __('Built in') : null),
                TextColumn::make('key')->label(__('Key'))->fontFamily('mono'),
                TextColumn::make('leads')->label(__('Enquiries'))->state(fn (LeadSource $s) => $s->leadsCount())->badge()->color('gray'),
                ToggleColumn::make('is_active')->label(__('Active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (LeadSource $s) => ! $s->is_system)
                    ->using(fn (LeadSource $record) => app(LeadSourceService::class)->delete($record) ?? true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeadSources::route('/'),
            'create' => CreateLeadSource::route('/create'),
            'edit' => EditLeadSource::route('/{record}/edit'),
        ];
    }
}
