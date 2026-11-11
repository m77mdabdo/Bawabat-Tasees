<?php

namespace App\Filament\Resources\Services;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Support\Bilingual;
use App\Filament\Support\Fields;
use App\Filament\Support\SeoFields;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Service::class;

    protected static string $viewPermission = 'content.manage';

    protected static string $managePermission = 'content.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Website content');
    }

    public static function getModelLabel(): string
    {
        return __('Service');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Services');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make(__('Content'))->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Bilingual::tabs(fn (string $l, bool $master) => [
                    TextInput::make("name.$l")->label(__('Name'))->required($master)->maxLength(255)->extraInputAttributes(Bilingual::dir($l)),
                    Textarea::make("summary.$l")->label(__('Summary'))->required($master)->maxLength(1000)->rows(2)->extraInputAttributes(Bilingual::dir($l)),
                    ArticleResource::editor("body.$l")->label(__('Description'))->required($master)->maxLength(20000)->extraInputAttributes(Bilingual::dir($l)),
                    Textarea::make("requirements.$l")->label(__('Requirements'))->required($master)->maxLength(20000)->rows(5)->extraInputAttributes(Bilingual::dir($l)),
                    Textarea::make("process.$l")->label(__('Steps'))->required($master)->maxLength(20000)->rows(5)->extraInputAttributes(Bilingual::dir($l)),
                ]),
            ]),
            Section::make(__('Publishing'))->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Fields::active(),
                Toggle::make('is_flagship')->label(__('Flagship service'))->helperText(__('Shown first, with a badge.')),
                Fields::sortOrder(),
                Fields::slug('services'),
                TextInput::make('icon')->label(__('Icon name'))->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
                Fields::image('cover_image', 'services', __('Cover image')),
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
                ImageColumn::make('cover_image')->label('')->disk('public')->square()->size(44),
                TextColumn::make('name')->label(__('Name'))->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
                    ->weight('semibold')->description(fn (Service $s) => $s->is_flagship ? __('Flagship service') : null),
                TextColumn::make('leads_count')->label(__('Enquiries'))->counts('leads')->badge()->color('gray'),
                ToggleColumn::make('is_active')->label(__('Shown')),
                TextColumn::make('updated_at')->label(__('Updated'))->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([TernaryFilter::make('is_active')->label(__('Shown on the website')), TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
