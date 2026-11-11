<?php

namespace App\Filament\Resources\Media;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\Media\Pages\ManageMedia;
use App\Models\Media;
use App\Services\Cms\MediaLibraryService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/** Uploaded images and videos, with a copyable public link for use anywhere on the site. */
class MediaResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Media::class;

    protected static string $viewPermission = 'content.manage';

    protected static string $managePermission = 'content.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 9;

    public static function getNavigationGroup(): ?string
    {
        return __('Website content');
    }

    public static function getModelLabel(): string
    {
        return __('File');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Media library');
    }

    public static function url(Media $media): string
    {
        return Storage::disk($media->disk)->url($media->path);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('alt_text')->label(__('Description (alt text)'))->maxLength(255)
                ->helperText(__('What the image shows, for screen readers and search engines.'))->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->contentGrid(['default' => 2, 'md' => 3, 'xl' => 5])
            ->columns([
                Stack::make([
                    ImageColumn::make('path')->label('')->disk('public')->height(140)->width('100%')
                        ->extraImgAttributes(['style' => 'object-fit:cover;border-radius:.75rem;width:100%'])
                        ->visible(fn (?Media $record) => $record?->type !== 'video'),
                    TextColumn::make('alt_text')->placeholder(__('No description'))->limit(40)->size('sm'),
                    TextColumn::make('meta')->state(fn (Media $m) => strtoupper($m->type).' · '.Number::fileSize((int) $m->size))->size('xs')->color('gray'),
                ])->space(2),
            ])
            ->filters([SelectFilter::make('type')->label(__('Type'))->options(['image' => __('Images'), 'video' => __('Videos')])])
            ->recordActions([
                Action::make('copy')->label(__('Link'))->icon('heroicon-o-link')->color('gray')
                    ->url(fn (Media $m) => self::url($m), true),
                EditAction::make(),
                DeleteAction::make()->using(fn (Media $record) => app(MediaLibraryService::class)->delete($record) ?? true),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMedia::route('/')];
    }
}
