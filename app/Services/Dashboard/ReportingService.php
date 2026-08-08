<?php

namespace App\Services\Dashboard;

use App\Models\Campaign;
use App\Models\ConversionEvent;
use App\Models\Lead;
use App\Models\LeadSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Every figure on the Reports screen.
 *
 * All of these are GROUP BY aggregates executed in the database — none of
 * them load rows into PHP to count or sum them, so the cost does not grow
 * with the size of the leads table. They lean on the indexes added
 * earlier: leads(created_at), leads(type), leads(source_platform),
 * conversion_events(event_type), conversion_events(occurred_at).
 */
class ReportingService
{
    /**
     * Periods the UI offers, in days.
     */
    public const RANGES = [30, 90, 365];

    public function __construct(
        private readonly CarbonImmutable $now = new CarbonImmutable,
    ) {}

    public function since(int $days): CarbonImmutable
    {
        return $this->now->subDays($days)->startOfDay();
    }

    /**
     * @return array{total: int, converted: int, rate: float, value: float, average: float}
     */
    public function funnel(int $days): array
    {
        $since = $this->since($days);

        $total = Lead::where('created_at', '>=', $since)->count();

        $converted = Lead::where('created_at', '>=', $since)->converted()->count();

        // Value comes from the events themselves (not the leads) so a lead
        // with two payments contributes both.
        $row = ConversionEvent::query()
            ->where('occurred_at', '>=', $since)
            ->whereIn('event_type', ConversionEvent::WON_TYPES)
            ->selectRaw('COALESCE(SUM(value), 0) as total_value, COUNT(*) as event_count')
            ->first();

        $value = (float) ($row->total_value ?? 0);
        $eventCount = (int) ($row->event_count ?? 0);

        return [
            'total' => $total,
            'converted' => $converted,
            'rate' => $total > 0 ? round($converted / $total * 100, 1) : 0.0,
            'value' => $value,
            'average' => $eventCount > 0 ? round($value / $eventCount, 2) : 0.0,
        ];
    }

    /**
     * Daily lead counts, zero-filled so the chart has no gaps.
     *
     * @return Collection<int, array{label: string, value: int}>
     */
    public function leadsOverTime(int $days): Collection
    {
        $since = $this->since($days);

        $counts = Lead::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        // Long ranges get bucketed by month so the chart stays readable.
        if ($days > 90) {
            return $this->bucketByMonth($counts, $days);
        }

        return collect(range($days - 1, 0))->map(function (int $offset) use ($counts) {
            $date = $this->now->subDays($offset)->toDateString();

            return ['label' => $date, 'value' => (int) ($counts[$date] ?? 0)];
        })->values();
    }

    /**
     * @return Collection<int, array{label: string, value: int}>
     */
    public function leadsByType(int $days): Collection
    {
        return $this->groupedCount('type', $days);
    }

    /**
     * @return Collection<int, array{label: string, value: int}>
     */
    public function leadsBySourcePlatform(int $days): Collection
    {
        return $this->groupedCount('source_platform', $days);
    }

    /**
     * leads.source_platform holds a lead_sources.key, so this joins to
     * pick up the human-readable label rather than the raw slug.
     *
     * @return Collection<int, array{label: string, value: int}>
     */
    public function leadsByLeadSource(int $days): Collection
    {
        $counts = $this->groupedCount('source_platform', $days);
        $labels = LeadSource::pluck('label', 'key');

        return $counts->map(fn (array $row) => [
            'label' => $labels[$row['label']] ?? $row['label'],
            'value' => $row['value'],
        ]);
    }

    /**
     * Revenue per campaign, joined through leads.linked_campaign_id, with
     * each campaign's recorded spend so ROI is computable.
     *
     * @return Collection<int, array{label: string, revenue: float, spend: ?float, roi: ?float, leads: int}>
     */
    public function revenueByCampaign(int $days): Collection
    {
        $since = $this->since($days);

        $rows = Campaign::query()
            ->leftJoin('leads', 'leads.linked_campaign_id', '=', 'campaigns.id')
            ->leftJoin('conversion_events', function ($join) use ($since) {
                $join->on('conversion_events.lead_id', '=', 'leads.id')
                    ->whereIn('conversion_events.event_type', ConversionEvent::WON_TYPES)
                    ->where('conversion_events.occurred_at', '>=', $since);
            })
            ->groupBy('campaigns.id', 'campaigns.name', 'campaigns.spend')
            ->selectRaw('campaigns.name as name, campaigns.spend as spend')
            ->selectRaw('COALESCE(SUM(conversion_events.value), 0) as revenue')
            ->selectRaw('COUNT(DISTINCT leads.id) as leads_count')
            ->orderByDesc('revenue')
            ->get();

        return $rows->map(function ($row) {
            $spend = $row->spend === null ? null : (float) $row->spend;
            $revenue = (float) $row->revenue;

            return [
                'label' => $row->name,
                'revenue' => $revenue,
                'spend' => $spend,
                'roi' => $spend > 0 ? round($revenue / $spend, 2) : null,
                'leads' => (int) $row->leads_count,
            ];
        });
    }

    /**
     * Revenue grouped by the lead's acquisition platform.
     *
     * @return Collection<int, array{label: string, revenue: float}>
     */
    public function revenueBySource(int $days): Collection
    {
        $since = $this->since($days);

        return ConversionEvent::query()
            ->join('leads', 'leads.id', '=', 'conversion_events.lead_id')
            ->where('conversion_events.occurred_at', '>=', $since)
            ->whereIn('conversion_events.event_type', ConversionEvent::WON_TYPES)
            ->groupBy('leads.source_platform')
            ->selectRaw('leads.source_platform as source')
            ->selectRaw('COALESCE(SUM(conversion_events.value), 0) as revenue')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->source ?: __('dashboard.reports.unattributed'),
                'revenue' => (float) $row->revenue,
            ]);
    }

    /**
     * @return Collection<int, array{label: string, value: int}>
     */
    private function groupedCount(string $column, int $days): Collection
    {
        return Lead::query()
            ->where('created_at', '>=', $this->since($days))
            ->groupBy($column)
            ->select($column)
            ->selectRaw('COUNT(*) as total')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) ($row->{$column} ?: __('dashboard.reports.unattributed')),
                'value' => (int) $row->total,
            ]);
    }

    /**
     * @param  Collection<string, int>  $dailyCounts
     * @return Collection<int, array{label: string, value: int}>
     */
    private function bucketByMonth(Collection $dailyCounts, int $days): Collection
    {
        $months = (int) ceil($days / 30);

        return collect(range($months - 1, 0))->map(function (int $offset) use ($dailyCounts) {
            $month = $this->now->subMonths($offset)->format('Y-m');

            $total = $dailyCounts
                ->filter(fn ($value, $day) => str_starts_with((string) $day, $month))
                ->sum();

            return ['label' => $month, 'value' => (int) $total];
        })->values();
    }

    /**
     * Guards the range parameter — an arbitrary ?days= value would let a
     * URL drive an unbounded date scan.
     */
    public static function normaliseRange(mixed $days): int
    {
        $days = (int) $days;

        return in_array($days, self::RANGES, true) ? $days : self::RANGES[0];
    }
}
