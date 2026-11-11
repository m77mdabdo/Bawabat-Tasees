<?php

namespace App\Filament\Resources\MenuItems;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\MenuItems\Pages\CreateMenuItem;
use App\Filament\Resources\MenuItems\Pages\EditMenuItem;
use App\Filament\Resources\MenuItems\Pages\ListMenuItems;
use App\Filament\Support\Bilingual;
use App\Models\MenuItem;
use App\Services\Cms\MenuService;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/** The public site's top navigation. Writes go through MenuService (ordering, cleanup, safe deletes). */
class MenuItemResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = MenuItem::class;

    protected static string $viewPermission = 'content.manage';

    protected static string $managePermission = 'content.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): ?string
    {
        return __('Website content');
    }

    public static function getModelLabel(): string
    {
        return __('Menu item');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.menu.title');
    }

    /** @return array<string, string> */
    private static function translatedOptions(array $keys, string $group): array
    {
        return collect($keys)->mapWithKeys(fn ($k) => [$k => __("dashboard.menu.{$group}.{$k}")])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                Bilingual::tabs(fn (string $l, bool $master) => [
                    TextInput::make("label.$l")->label(__('Label'))->required($master)->maxLength(120)->extraInputAttributes(Bilingual::dir($l)),
                ]),
                Select::make('link_type')->label(__('dashboard.menu.link_type'))->required()->live()->native(false)->default('route')
                    ->options(self::translatedOptions(MenuItem::LINK_TYPES, 'link_types')),
                Select::make('route_name')->label(__('dashboard.menu.route_name'))->native(false)
                    ->options(collect(MenuItem::ROUTE_WHITELIST)->map(fn ($key) => __($key))->all())
                    ->required(fn (Get $get) => $get('link_type') === 'route')
                    ->visible(fn (Get $get) => $get('link_type') === 'route'),
                TextInput::make('url')->label(__('dashboard.menu.url'))->helperText(__('dashboard.menu.url_hint'))->maxLength(255)
                    ->regex('/^(https?:\/\/|\/)/')->extraInputAttributes(['dir' => 'ltr'])
                    ->required(fn (Get $get) => $get('link_type') === 'url')
                    ->visible(fn (Get $get) => $get('link_type') === 'url'),
                Select::make('target')->label(__('dashboard.menu.target'))->native(false)->default('_self')
                    ->options(self::translatedOptions(MenuItem::TARGETS, 'targets'))
                    ->visible(fn (Get $get) => $get('link_type') !== 'none'),
                Select::make('parent_id')->label(__('dashboard.menu.parent'))->native(false)->placeholder(__('dashboard.menu.parent_none'))
                    ->options(fn (?MenuItem $record) => MenuItem::query()->topLevel()->ordered()
                        ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey()))
                        ->get()->mapWithKeys(fn (MenuItem $m) => [$m->id => $m->label])->all())
                    ->rule(Rule::exists('menu_items', 'id')->whereNull('parent_id')),
                Toggle::make('is_visible')->label(__('dashboard.menu.visible_label'))->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent'))
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('COALESCE(parent_id, id)')->orderByRaw('parent_id IS NOT NULL')->orderBy('sort_order'))
            ->reorderable('sort_order')
            ->paginated(false)
            ->description(__('dashboard.menu.subtitle'))
            ->columns([
                TextColumn::make('label')->label(__('Label'))->weight('semibold')
                    ->formatStateUsing(fn (MenuItem $m, $state) => $m->parent_id ? '↳ '.$state : $state)
                    ->description(fn (MenuItem $m) => $m->is_system ? __('dashboard.menu.system_badge') : null),
                TextColumn::make('link_type')->label(__('dashboard.menu.link_type'))->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => __("dashboard.menu.link_types.$state")),
                TextColumn::make('destination')->label(__('Destination'))->state(fn (MenuItem $m) => match ($m->link_type) {
                    'route' => __(MenuItem::ROUTE_WHITELIST[$m->route_name] ?? (string) $m->route_name),
                    'url' => $m->url,
                    default => '—',
                }),
                ToggleColumn::make('is_visible')->label(__('Shown')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(fn (MenuItem $m) => ($m->is_system ? __('dashboard.menu.confirm_delete_system') : __('dashboard.menu.confirm_delete')).' '.__('dashboard.menu.reparent_note'))
                    ->using(fn (MenuItem $record) => app(MenuService::class)->delete($record) ?? true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenuItems::route('/'),
            'create' => CreateMenuItem::route('/create'),
            'edit' => EditMenuItem::route('/{record}/edit'),
        ];
    }
}
