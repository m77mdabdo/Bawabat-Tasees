<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\RelationManagers\ConversionEventsRelationManager;
use App\Models\Lead;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Consultation requests and contact messages from the website (both are `leads` rows).
 * Sales staff follow them up, mark them handled and record what they turned into.
 */
class LeadResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Lead::class;

    protected static string $viewPermission = 'leads.view';

    protected static string $managePermission = 'leads.manage';

    protected static ?string $deletePermission = 'leads.delete';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getNavigationGroup(): ?string
    {
        return __('Enquiries');
    }

    public static function getModelLabel(): string
    {
        return __('Enquiry');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Enquiries');
    }

    /** Unhandled enquiries, so the sidebar shows at a glance what is waiting. */
    public static function getNavigationBadge(): ?string
    {
        $count = Lead::query()->unhandled()->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('Not handled yet');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'email', 'phone'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['requestedService', 'campaign'])->withCount('conversionEvents');
    }

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return [Lead::TYPE_CONSULTATION => __('Consultation request'), Lead::TYPE_CONTACT => __('Contact message')];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordClasses(fn (Lead $lead) => $lead->is_handled ? null : 'bt-row-unhandled')
            ->columns([
                TextColumn::make('id')->label(__('Ref.'))->formatStateUsing(fn (Lead $lead) => $lead->reference())
                    ->fontFamily('mono')->sortable()->toggleable(),
                TextColumn::make('full_name')->label(__('Name'))->searchable()->sortable()->weight('semibold')
                    ->description(fn (Lead $lead) => $lead->email),
                TextColumn::make('phone')->label(__('Phone'))->searchable()->copyable()
                    ->extraAttributes(['dir' => 'ltr'])->toggleable(),
                TextColumn::make('type')->label(__('Type'))->badge()
                    ->formatStateUsing(fn (string $state) => self::typeOptions()[$state] ?? $state)
                    ->color(fn (string $state) => $state === Lead::TYPE_CONTACT ? 'info' : 'success'),
                TextColumn::make('requestedService.name')->label(__('Service'))->placeholder('—')->toggleable()->wrap(),
                TextColumn::make('source_platform')->label(__('Source'))->placeholder(__('Direct'))->badge()->color('gray')->toggleable(),
                IconColumn::make('is_handled')->label(__('Handled'))->boolean()->sortable(),
                IconColumn::make('team_notified_at')->label(__('Team emailed'))->boolean()
                    ->state(fn (Lead $lead) => $lead->team_notified_at !== null)
                    ->tooltip(fn (Lead $lead) => $lead->mail_error ?: ($lead->team_notified_at ? $lead->team_notified_at->diffForHumans() : __('Not sent')))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('conversion_events_count')->label(__('Conversions'))->badge()->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label(__('Received'))->since()->sortable()
                    ->dateTimeTooltip('j M Y, H:i', 'Asia/Riyadh'),
            ])
            ->filters([
                SelectFilter::make('type')->label(__('Type'))->options(self::typeOptions()),
                TernaryFilter::make('is_handled')->label(__('Handled')),
                SelectFilter::make('requested_service_id')->label(__('Service'))
                    ->options(fn () => Service::query()->orderBy('sort_order')->get()->mapWithKeys(fn (Service $s) => [$s->id => $s->name])->all()),
                SelectFilter::make('source_platform')->label(__('Source'))
                    ->options(fn () => Lead::query()->whereNotNull('source_platform')->distinct()->orderBy('source_platform')->pluck('source_platform', 'source_platform')->all()),
                TernaryFilter::make('converted')->label(__('Converted'))
                    ->queries(true: fn (Builder $query) => $query->converted(), false: fn (Builder $query) => $query->notConverted()),
                Filter::make('received')->label(__('Received'))
                    ->schema([
                        DatePicker::make('from')->label(__('From')),
                        DatePicker::make('until')->label(__('Until')),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    self::toggleHandledAction(),
                    self::whatsappAction(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markHandled')->label(__('Mark as handled'))->icon('heroicon-o-check-circle')
                        ->visible(fn () => self::canEdit(new Lead))
                        ->action(fn (Collection $records) => $records->each(fn (Lead $lead) => $lead->forceFill(['is_handled' => true])->save()))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function toggleHandledAction(): Action
    {
        return Action::make('toggleHandled')
            ->label(fn (Lead $record) => $record->is_handled ? __('Mark as not handled') : __('Mark as handled'))
            ->icon(fn (Lead $record) => $record->is_handled ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-check-circle')
            ->color(fn (Lead $record) => $record->is_handled ? 'gray' : 'success')
            ->visible(fn (Lead $record) => self::canEdit($record))
            ->action(fn (Lead $record) => $record->forceFill(['is_handled' => ! $record->is_handled])->save());
    }

    public static function whatsappAction(): Action
    {
        return Action::make('whatsapp')->label(__('WhatsApp'))->icon('heroicon-o-chat-bubble-left-right')->color('success')
            ->visible(fn (Lead $record) => filled($record->whatsapp_number ?: $record->phone))
            ->url(fn (Lead $record) => 'https://wa.me/'.preg_replace('/\D+/', '', (string) ($record->whatsapp_number ?: $record->phone)), true);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 2])->schema([
                Section::make(__('Client'))->icon('heroicon-o-user')->columns(2)->schema([
                    TextEntry::make('full_name')->label(__('Name'))->weight('bold'),
                    TextEntry::make('email')->label(__('Email'))->copyable()->placeholder('—')
                        ->url(fn (Lead $l) => $l->email ? 'mailto:'.$l->email : null),
                    TextEntry::make('phone')->label(__('Phone'))->copyable()->extraAttributes(['dir' => 'ltr'])
                        ->url(fn (Lead $l) => 'tel:'.preg_replace('/[^\d+]/', '', $l->phone)),
                    TextEntry::make('whatsapp_number')->label(__('WhatsApp'))->placeholder('—')->extraAttributes(['dir' => 'ltr']),
                    TextEntry::make('nationality')->label(__('Nationality'))->placeholder('—'),
                    TextEntry::make('country_of_residence')->label(__('Country of residence'))->placeholder('—'),
                ]),
                Section::make(__('Request'))->icon('heroicon-o-clipboard-document-list')->columns(2)->schema([
                    TextEntry::make('type')->label(__('Type'))->badge()->formatStateUsing(fn (string $state) => self::typeOptions()[$state] ?? $state),
                    TextEntry::make('requestedService.name')->label(__('Service'))->placeholder('—'),
                    TextEntry::make('requested_activity')->label(__('Business activity'))->placeholder('—'),
                    IconEntry::make('owns_external_company')->label(__('Owns a company outside Saudi Arabia'))->boolean()
                        ->visible(fn (Lead $l) => $l->type === Lead::TYPE_CONSULTATION),
                    TextEntry::make('message')->label(__('Message'))->placeholder('—')->columnSpanFull()
                        ->extraAttributes(['style' => 'white-space: pre-line', 'dir' => 'auto']),
                ]),
            ]),
            Grid::make(1)->columnSpan(['default' => 3, 'lg' => 1])->schema([
                Section::make(__('Status'))->icon('heroicon-o-flag')->schema([
                    TextEntry::make('id')->label(__('Reference'))->formatStateUsing(fn (Lead $l) => $l->reference())->fontFamily('mono'),
                    IconEntry::make('is_handled')->label(__('Handled'))->boolean(),
                    TextEntry::make('created_at')->label(__('Received'))->dateTime('j M Y, H:i', 'Asia/Riyadh'),
                    TextEntry::make('locale')->label(__('Language'))->formatStateUsing(fn (string $state) => $state === 'en' ? 'English' : 'العربية'),
                    TextEntry::make('team_notified_at')->label(__('Team emailed'))->dateTime('j M Y, H:i', 'Asia/Riyadh')->placeholder(__('Not sent')),
                    TextEntry::make('client_confirmed_at')->label(__('Client confirmation'))->dateTime('j M Y, H:i', 'Asia/Riyadh')->placeholder(__('Not sent')),
                    TextEntry::make('mail_error')->label(__('Email problem'))->color('danger')->visible(fn (Lead $l) => filled($l->mail_error)),
                ]),
                Section::make(__('Where they came from'))->icon('heroicon-o-megaphone')->collapsible()->schema([
                    TextEntry::make('source_platform')->label(__('Source'))->placeholder(__('Direct')),
                    TextEntry::make('campaign.name')->label(__('Campaign'))->placeholder(fn (Lead $l) => $l->campaign_name ?: '—'),
                    TextEntry::make('utm_source')->label('utm_source')->placeholder('—'),
                    TextEntry::make('utm_medium')->label('utm_medium')->placeholder('—'),
                    TextEntry::make('utm_campaign')->label('utm_campaign')->placeholder('—'),
                    TextEntry::make('landing_page_url')->label(__('Landing page'))->placeholder('—')->limit(60)->extraAttributes(['dir' => 'ltr']),
                    TextEntry::make('referrer_url')->label(__('Referrer'))->placeholder('—')->limit(60)->extraAttributes(['dir' => 'ltr']),
                ]),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [ConversionEventsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'view' => ViewLead::route('/{record}'),
        ];
    }
}
