<?php

namespace App\Filament\Resources\Articles;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Support\Bilingual;
use App\Filament\Support\Fields;
use App\Filament\Support\SeoFields;
use App\Models\Article;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
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

/** Blog articles. The body is rich text, purified on save by Article's SanitizesContent. */
class ArticleResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Article::class;

    protected static string $viewPermission = 'content.manage';

    protected static string $managePermission = 'content.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('Website content');
    }

    public static function getModelLabel(): string
    {
        return __('Article');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Articles');
    }

    /** Toolbar limited to what the "article" HTMLPurifier profile keeps. */
    public static function editor(string $name): RichEditor
    {
        return RichEditor::make($name)->toolbarButtons([
            ['bold', 'italic', 'underline', 'link'],
            ['h2', 'h3', 'blockquote'],
            ['bulletList', 'orderedList'],
            ['undo', 'redo'],
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make(__('Content'))->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Bilingual::tabs(fn (string $l, bool $master) => [
                    TextInput::make("title.$l")->label(__('Title'))->required($master)->maxLength(255)->extraInputAttributes(Bilingual::dir($l)),
                    Textarea::make("excerpt.$l")->label(__('Excerpt'))->maxLength(500)->rows(2)->extraInputAttributes(Bilingual::dir($l)),
                    self::editor("body.$l")->label(__('Body'))->required($master)->maxLength(50000)->extraInputAttributes(Bilingual::dir($l)),
                ]),
            ]),
            Section::make(__('Publishing'))->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Toggle::make('is_published')->label(__('Published'))->default(false),
                DateTimePicker::make('published_at')->label(__('Publish date'))->seconds(false),
                Fields::slug('articles'),
                Fields::image('cover_image', 'covers', __('Cover image')),
            ]),
            SeoFields::section(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('cover_image')->label('')->disk('public')->size(44),
                TextColumn::make('title')->label(__('Title'))->weight('semibold')->wrap()
                    ->searchable(query: fn ($query, string $search) => $query->where('title', 'like', "%{$search}%")),
                TextColumn::make('comments_count')->label(__('Comments'))->counts('comments')->badge()->color('gray'),
                ToggleColumn::make('is_published')->label(__('Published')),
                TextColumn::make('published_at')->label(__('Publish date'))->date('j M Y')->sortable()->placeholder('—'),
            ])
            ->filters([TernaryFilter::make('is_published')->label(__('Published')), TrashedFilter::make()])
            ->recordActions([
                Action::make('open')->label(__('View'))->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->visible(fn (Article $a) => $a->is_published && $a->slug)
                    ->url(fn (Article $a) => route('articles.show', $a), true),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
