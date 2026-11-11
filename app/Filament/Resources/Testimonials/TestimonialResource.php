<?php

namespace App\Filament\Resources\Testimonials;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use App\Filament\Resources\Testimonials\Pages\EditTestimonial;
use App\Filament\Resources\Testimonials\Pages\ListTestimonials;
use App\Filament\Support\Bilingual;
use App\Filament\Support\Fields;
use App\Models\Testimonial;
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

/** Client testimonials. Publish only real quotes the client agreed to share. */
class TestimonialResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Testimonial::class;

    protected static string $viewPermission = 'content.manage';

    protected static string $managePermission = 'content.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('Website content');
    }

    public static function getModelLabel(): string
    {
        return __('Testimonial');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Testimonials');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make(__('Quote'))->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Bilingual::tabs(fn (string $l, bool $master) => [
                    Textarea::make("quote.$l")->label(__('Quote'))->required($master)->maxLength(2000)->rows(5)->extraInputAttributes(Bilingual::dir($l)),
                ]),
            ]),
            Section::make(__('Client'))->columnSpan(['default' => 3, 'lg' => 1])->schema([
                TextInput::make('client_name')->label(__('Name'))->required()->maxLength(255),
                TextInput::make('client_title')->label(__('Title or company'))->maxLength(255),
                TextInput::make('client_country')->label(__('Country'))->maxLength(255),
                Fields::image('avatar', 'testimonials', __('Photo')),
                Fields::active(),
                Fields::sortOrder(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('avatar')->label('')->disk('public')->circular()->size(36),
                TextColumn::make('client_name')->label(__('Name'))->weight('semibold')->searchable()
                    ->description(fn (Testimonial $t) => $t->client_title),
                TextColumn::make('quote')->label(__('Quote'))->limit(70)->toggleable(),
                ToggleColumn::make('is_active')->label(__('Shown')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTestimonials::route('/'),
            'create' => CreateTestimonial::route('/create'),
            'edit' => EditTestimonial::route('/{record}/edit'),
        ];
    }
}
