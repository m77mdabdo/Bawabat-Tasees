<?php

namespace App\Filament\Resources\Pages\RelationManagers;

use App\Filament\Support\Bilingual;
use App\Models\PageSection;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The content blocks of a page (cards on "why invest", steps on "formation process"...).
 * Each block stores {"title": {ar, en}, "description": {ar, en}, "icon": "..."} in its `content` column.
 */
class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Page sections');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Bilingual::tabs(fn (string $l, bool $master) => [
                TextInput::make("content.title.$l")->label(__('Title'))->required($master)->maxLength(255)->extraInputAttributes(Bilingual::dir($l)),
                Textarea::make("content.description.$l")->label(__('Description'))->maxLength(1000)->rows(3)->extraInputAttributes(Bilingual::dir($l)),
            ]),
            TextInput::make('key')->label(__('Key'))->required()->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('content.icon')->label(__('Icon name'))->maxLength(100)->extraInputAttributes(['dir' => 'ltr']),
            Toggle::make('is_active')->label(__('Shown on the website'))->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label(__('Title'))->weight('semibold')->wrap()
                    ->state(fn (PageSection $s) => $s->content['title'][app()->getLocale()] ?? $s->content['title']['ar'] ?? $s->key)
                    ->description(fn (PageSection $s) => $s->key),
                ToggleColumn::make('is_active')->label(__('Shown')),
            ])
            ->headerActions([
                CreateAction::make()->mutateDataUsing(fn (array $data) => [
                    ...self::clean($data),
                    'sort_order' => (int) $this->getOwnerRecord()->sections()->max('sort_order') + 1,
                ]),
            ])
            ->recordActions([EditAction::make()->mutateDataUsing(fn (array $data) => self::clean($data)), DeleteAction::make()]);
    }

    /** Blank English text is dropped so the site falls back to Arabic instead of an empty card. */
    private static function clean(array $data): array
    {
        $data['content'] = Bilingual::clean($data['content'] ?? [], ['title', 'description']);

        return $data;
    }
}
