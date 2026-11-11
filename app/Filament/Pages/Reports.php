<?php

namespace App\Filament\Pages;

use App\Services\Dashboard\ReportingService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

/** Leads, conversions and return on ad spend over 30 / 90 / 365 days (ReportingService). */
class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.reports';

    #[Url]
    public int $days = 30;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('analytics.view');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Marketing & analytics');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.reports.title');
    }

    public function getTitle(): string
    {
        return __('dashboard.reports.title');
    }

    public function getSubheading(): ?string
    {
        return __('dashboard.reports.subtitle');
    }

    public function setRange(int $days): void
    {
        $this->days = ReportingService::normaliseRange($days);
    }

    protected function getViewData(): array
    {
        $r = app(ReportingService::class);
        $days = ReportingService::normaliseRange($this->days);
        $typeLabels = ['consultation' => __('Consultation requests'), 'contact' => __('Contact messages')];

        $byType = $r->leadsByType($days)->map(fn ($row) => [...$row, 'label' => $typeLabels[$row['label']] ?? $row['label']]);

        return [
            'days' => $days,
            'ranges' => ReportingService::RANGES,
            'funnel' => $r->funnel($days),
            'overTime' => $r->leadsOverTime($days),
            'groups' => [
                __('dashboard.reports.leads_by_type') => $byType,
                __('dashboard.reports.leads_by_source') => $r->leadsBySourcePlatform($days),
                __('dashboard.reports.leads_by_lead_source') => $r->leadsByLeadSource($days),
            ],
            'revenueByCampaign' => $r->revenueByCampaign($days),
            'revenueBySource' => $r->revenueBySource($days),
        ];
    }
}
