<?php

namespace App\Filament\Resources\Faqs;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Resources\Faqs\Pages\EditFaq;
use App\Filament\Resources\Faqs\Pages\ListFaqs;
use App\Filament\Support\Bilingual;
use App\Filament\Support\Fields;
use App\Models\Faq;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Faq::class;

    protected static string $viewPermission = 'content.manage';

    protected static string $managePermission = 'content.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('Website content');
    }

    public static function getModelLabel(): string
    {
        return __('Question');
    }

    public static function getPluralModelLabel(): string
    {
        return __('FAQs');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->schema([
                Bilingual::tabs(fn (string $l, bool $master) => [
                    TextInput::make("question.$l")->label(__('Question'))->required($master)->maxLength(500)->extraInputAttributes(Bilingual::dir($l)),
                    Textarea::make("answer.$l")->label(__('Answer'))->required($master)->maxLength(5000)->rows(5)->extraInputAttributes(Bilingual::dir($l)),
                ]),
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
                TextColumn::make('question')->label(__('Question'))->weight('semibold')->wrap()
                    ->searchable(query: fn ($query, string $search) => $query->where('question', 'like', "%{$search}%")),
                ToggleColumn::make('is_active')->label(__('Shown')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFaqs::route('/'),
            'create' => CreateFaq::route('/create'),
            'edit' => EditFaq::route('/{record}/edit'),
        ];
    }
}
