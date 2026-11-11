<?php

namespace App\Filament\Resources\VisitorSessions;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\VisitorSessions\Pages\ListVisitorSessions;
use App\Filament\Resources\VisitorSessions\Pages\ViewVisitorSession;
use App\Models\VisitorSession;
use App\Services\Analytics\UserAgentParser;
use App\Services\Analytics\VisitorSessionReportService;
use App\Services\Cms\SettingsRepository;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Consent-gated visitor journeys (read-only): which pages someone saw before enquiring. */
class VisitorSessionResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = VisitorSession::class;

    protected static string $viewPermission = 'analytics.view';

    protected static string $managePermission = 'analytics.view';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCursorArrowRays;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('Marketing & analytics');
    }

    public static function getModelLabel(): string
    {
        return __('Visitor session');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.visitor_sessions.title');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    /** @return array<string, string> */
    public static function deviceOptions(): array
    {
        return [
            UserAgentParser::DEVICE_DESKTOP => __('dashboard.visitor_sessions.device_desktop'),
            UserAgentParser::DEVICE_MOBILE => __('dashboard.visitor_sessions.device_mobile'),
            UserAgentParser::DEVICE_TABLET => __('dashboard.visitor_sessions.device_tablet'),
        ];
    }

    public static function download(array $payload, string $filename): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print (json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('lead:id,type,full_name,created_at'))
            ->defaultSort('started_at', 'desc')
            ->description(__('dashboard.visitor_sessions.consent_notice', ['days' => (int) app(SettingsRepository::class)->integer('visitor_sessions_retention_days', 90)]))
            ->columns([
                TextColumn::make('started_at')->label(__('dashboard.visitor_sessions.started_at'))->dateTime('j M Y, H:i', 'Asia/Riyadh')->sortable(),
                TextColumn::make('entry_url')->label(__('dashboard.visitor_sessions.entry_page'))->limit(40)->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('page_count')->label(__('dashboard.visitor_sessions.pages'))->badge()->color('gray')->sortable(),
                TextColumn::make('duration')->label(__('dashboard.visitor_sessions.duration'))->state(fn (VisitorSession $s) => $s->durationForHumans()),
                TextColumn::make('device_type')->label(__('dashboard.visitor_sessions.device'))
                    ->formatStateUsing(fn (?string $state) => self::deviceOptions()[$state] ?? $state)->toggleable(),
                TextColumn::make('source')->label(__('dashboard.visitor_sessions.source'))->state(fn (VisitorSession $s) => $s->sourcePlatform())->placeholder(__('Direct'))->toggleable(),
                IconColumn::make('converted')->label(__('dashboard.visitor_sessions.converted'))->boolean()->state(fn (VisitorSession $s) => $s->hasConverted()),
            ])
            ->filters([
                TernaryFilter::make('converted')->label(__('dashboard.visitor_sessions.conversion_filter'))
                    ->queries(true: fn (Builder $query) => $query->converted(), false: fn (Builder $query) => $query->notConverted()),
                SelectFilter::make('device_type')->label(__('dashboard.visitor_sessions.device'))->options(self::deviceOptions()),
                Filter::make('started')->schema([
                    DatePicker::make('from')->label(__('From')),
                    DatePicker::make('until')->label(__('Until')),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('started_at', '>=', $d))
                    ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('started_at', '<=', $d))),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make(__('dashboard.visitor_sessions.journey'))->columnSpan(['default' => 3, 'lg' => 2])->schema([
                ViewEntry::make('events')->hiddenLabel()->view('filament.visitor-journey'),
            ]),
            Section::make(__('dashboard.visitor_sessions.session_details'))->columnSpan(['default' => 3, 'lg' => 1])->schema([
                TextEntry::make('started_at')->label(__('dashboard.visitor_sessions.started_at'))->dateTime('j M Y, H:i', 'Asia/Riyadh'),
                TextEntry::make('duration')->label(__('dashboard.visitor_sessions.duration'))->state(fn (VisitorSession $s) => $s->durationForHumans()),
                TextEntry::make('page_count')->label(__('dashboard.visitor_sessions.pages')),
                TextEntry::make('device_type')->label(__('dashboard.visitor_sessions.device'))->formatStateUsing(fn (?string $state) => self::deviceOptions()[$state] ?? $state),
                TextEntry::make('browser')->label(__('Browser'))->placeholder('—'),
                TextEntry::make('platform')->label(__('System'))->placeholder('—'),
                TextEntry::make('source')->label(__('dashboard.visitor_sessions.source'))->state(fn (VisitorSession $s) => $s->sourcePlatform())->placeholder(__('Direct')),
                TextEntry::make('campaign')->label(__('dashboard.visitor_sessions.campaign'))->state(fn (VisitorSession $s) => $s->campaignName())->placeholder('—'),
                TextEntry::make('referrer')->label(__('dashboard.visitor_sessions.referrer'))->placeholder('—')->limit(50)->extraAttributes(['dir' => 'ltr']),
                TextEntry::make('ip_prefix')->label(__('dashboard.visitor_sessions.ip_prefix'))->placeholder('—')->extraAttributes(['dir' => 'ltr']),
                TextEntry::make('lead.full_name')->label(__('dashboard.visitor_sessions.lead'))->placeholder(__('dashboard.visitor_sessions.no_lead'))
                    ->url(fn (VisitorSession $s) => $s->lead && auth()->user()?->can('leads.view') ? LeadResource::getUrl('view', ['record' => $s->lead]) : null),
            ]),
        ]);
    }

    public static function exportAction(): Action
    {
        return Action::make('export')->label(__('dashboard.visitor_sessions.export'))->icon('heroicon-o-arrow-down-tray')->color('gray')
            ->action(function () {
                $report = app(VisitorSessionReportService::class);
                $sessions = $report->query([])->limit(1000)->get();

                return self::download($report->export($sessions), $report->exportFilename());
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVisitorSessions::route('/'),
            'view' => ViewVisitorSession::route('/{record}'),
        ];
    }
}
