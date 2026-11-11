<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;

/** Enquiries received per day over the last 30 days, consultations and contact messages apart. */
class LeadsTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('leads.view');
    }

    public function getHeading(): string
    {
        return __('Enquiries in the last 30 days');
    }

    protected function getData(): array
    {
        $tz = 'Asia/Riyadh';
        $days = collect(range(29, 0))->map(fn ($d) => now($tz)->subDays($d));
        $rows = Lead::query()->where('created_at', '>=', $days->first()->copy()->startOfDay()->utc())->get(['created_at', 'type']);
        $count = fn (string $type) => $rows->where('type', $type)->countBy(fn (Lead $l) => $l->created_at->timezone($tz)->toDateString());
        $consult = $count(Lead::TYPE_CONSULTATION);
        $contact = $count(Lead::TYPE_CONTACT);

        return [
            'datasets' => [
                [
                    'label' => __('Consultation requests'),
                    'data' => $days->map(fn ($d) => $consult[$d->toDateString()] ?? 0)->all(),
                    'borderColor' => '#0B5D3B', 'backgroundColor' => 'rgba(11, 93, 59, .15)', 'fill' => true, 'tension' => .35,
                ],
                [
                    'label' => __('Contact messages'),
                    'data' => $days->map(fn ($d) => $contact[$d->toDateString()] ?? 0)->all(),
                    'borderColor' => '#B8903A', 'backgroundColor' => 'rgba(184, 144, 58, .12)', 'fill' => true, 'tension' => .35,
                ],
            ],
            'labels' => $days->map(fn ($d) => $d->locale(app()->getLocale())->translatedFormat('j M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return ['scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]];
    }
}
