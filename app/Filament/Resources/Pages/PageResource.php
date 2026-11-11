<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\RelationManagers\SectionsRelationManager;
use App\Filament\Support\Bilingual;
use App\Filament\Support\SeoFields;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/** The fixed site pages (about, why invest, formation steps, documents, legal) and their content blocks. */
class PageResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Page::class;

    protected static string $viewPermission = 'content.manage';

    protected static string $managePermission = 'content.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return __('Website content');
    }

    public static function getModelLabel(): string
    {
        return __('Page');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Pages');
    }

    public static function canCreate(): bool
    {
        return false; // pages are fixed routes; only their content is editable
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make(__('Content'))->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Bilingual::tabs(fn (string $l, bool $master) => [
                    TextInput::make("title.$l")->label(__('Title'))->required($master)->maxLength(255)->extraInputAttributes(Bilingual::dir($l)),
                    ArticleResource::editor("body.$l")->label(__('Body'))->required($master)->maxLength(20000)->extraInputAttributes(Bilingual::dir($l)),
                    TextInput::make("meta_title.$l")->label(__('Meta title'))->required($master)->maxLength(255)->extraInputAttributes(Bilingual::dir($l)),
                    Textarea::make("meta_description.$l")->label(__('Meta description'))->required($master)->maxLength(500)->rows(2)->extraInputAttributes(Bilingual::dir($l)),
                ]),
            ]),
            Section::make(__('Publishing'))->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Toggle::make('is_published')->label(__('Published')),
                TextInput::make('slug')->label(__('Link (slug)'))->disabled()->dehydrated(false)->extraInputAttributes(['dir' => 'ltr']),
            ]),
            SeoFields::section(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->paginated(false)
            ->columns([
                TextColumn::make('title')->label(__('Title'))->weight('semibold')->description(fn (Page $p) => '/'.$p->slug),
                TextColumn::make('sections_count')->label(__('Sections'))->counts('sections')->badge()->color('gray'),
                ToggleColumn::make('is_published')->label(__('Published')),
                TextColumn::make('updated_at')->label(__('Updated'))->since(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [SectionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
