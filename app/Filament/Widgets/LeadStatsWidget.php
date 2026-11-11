<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\ConversionEvent;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Enquiry figures with 14-day sparklines, for staff who can see enquiries. */
class LeadStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('leads.view');
    }

    protected function getColumns(): int|array
    {
        return ['default' => 2, 'lg' => 4];
    }

    protected function getStats(): array
    {
        $tz = 'Asia/Riyadh';
        $since = now($tz)->subDays(13)->startOfDay();
        $perDay = Lead::query()->where('created_at', '>=', $since->copy()->utc())->get(['created_at'])
            ->countBy(fn (Lead $l) => $l->created_at->timezone($tz)->toDateString());
        $spark = collect(range(13, 0))->map(fn ($d) => $perDay[now($tz)->subDays($d)->toDateString()] ?? 0)->all();

        $thisMonth = Lead::query()->where('created_at', '>=', now($tz)->startOfMonth()->utc())->count();
        $lastMonth = Lead::query()->whereBetween('created_at', [now($tz)->subMonthNoOverflow()->startOfMonth()->utc(), now($tz)->subMonthNoOverflow()->endOfMonth()->utc()])->count();
        $change = $lastMonth ? (int) round(($thisMonth - $lastMonth) / $lastMonth * 100) : null;

        $total = Lead::query()->count();
        $converted = Lead::query()->converted()->count();
        $value = (float) ConversionEvent::query()->whereIn('event_type', ConversionEvent::WON_TYPES)->sum('value');

        return [
            Stat::make(__('Not handled'), Lead::query()->unhandled()->count())
                ->description(__('Waiting for the team'))->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->chart($spark)->color('warning')
                ->url(LeadResource::getUrl('index', ['tab' => 'unhandled'])),
            Stat::make(__('This month'), $thisMonth)
                ->description($change === null ? __('No data for last month') : __(':change% vs last month', ['change' => ($change > 0 ? '+' : '').$change]))
                ->descriptionIcon($change !== null && $change < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($change !== null && $change < 0 ? 'danger' : 'success'),
            Stat::make(__('Conversion rate'), $total ? round($converted / $total * 100).'%' : '—')
                ->description(__(':count converted of :total', ['count' => $converted, 'total' => $total]))
                ->descriptionIcon('heroicon-m-check-badge')->color('primary'),
            Stat::make(__('Won value'), number_format($value).' '.__('SAR'))
                ->description(__('Signed contracts and payments'))->descriptionIcon('heroicon-m-banknotes')->color('success'),
        ];
    }
}
