<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardStatsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** What is on the website right now, and what is waiting to go live. For content staff. */
class ContentOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('content.manage');
    }

    protected function getHeading(): ?string
    {
        return __('Website content');
    }

    protected function getColumns(): int|array
    {
        return ['default' => 2, 'lg' => 4];
    }

    protected function getStats(): array
    {
        $s = app(DashboardStatsService::class);

        return [
            Stat::make(__('Published services'), $s->activeServicesCount())
                ->description(__(':count hidden', ['count' => $s->inactiveServicesCount()]))->icon('heroicon-o-squares-2x2'),
            Stat::make(__('Published articles'), $s->publishedArticlesCount())
                ->description(__(':count drafts', ['count' => $s->unpublishedArticlesCount()]))->icon('heroicon-o-newspaper'),
            Stat::make(__('Countries served'), $s->countriesCount())->icon('heroicon-o-globe-europe-africa'),
            Stat::make(__('Comments to review'), $s->pendingCommentsCount())
                ->color($s->pendingCommentsCount() ? 'warning' : 'gray')->icon('heroicon-o-chat-bubble-left-ellipsis'),
        ];
    }
}
