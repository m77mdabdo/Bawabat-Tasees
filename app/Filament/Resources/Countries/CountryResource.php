<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\Countries\Pages\CreateCountry;
use App\Filament\Resources\Countries\Pages\EditCountry;
use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Filament\Support\Bilingual;
use App\Filament\Support\Fields;
use App\Filament\Support\SeoFields;
use App\Models\Country;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/** The countries whose investors the company serves (public "Countries" page). */
class CountryResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Country::class;

    protected static string $viewPermission = 'content.manage';

    protected static string $managePermission = 'content.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeEuropeAfrica;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Website content');
    }

    public static function getModelLabel(): string
    {
        return __('Country');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Countries');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make(__('Content'))->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Bilingual::tabs(fn (string $l, bool $master) => [
                    TextInput::make("name.$l")->label(__('Name'))->required($master)->maxLength(255)->extraInputAttributes(Bilingual::dir($l)),
                    Textarea::make("notes.$l")->label(__('Notes'))->maxLength(2000)->rows(3)->extraInputAttributes(Bilingual::dir($l)),
                ]),
            ]),
            Section::make(__('Publishing'))->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Fields::active(),
                Fields::sortOrder(),
                Fields::slug('countries'),
                Fields::image('flag_image', 'countries', __('Flag')),
            ]),
            SeoFields::section(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('flag_image')->label('')->disk('public')->size(32),
                TextColumn::make('name')->label(__('Name'))->weight('semibold')->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")),
                ToggleColumn::make('is_active')->label(__('Shown')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountries::route('/'),
            'create' => CreateCountry::route('/create'),
            'edit' => EditCountry::route('/{record}/edit'),
        ];
    }
}
