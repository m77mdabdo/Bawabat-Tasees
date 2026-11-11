{{-- Reports: plain bars (no chart library), readable in both directions and on phones. --}}
<x-filament-panels::page>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem">
        @foreach ($ranges as $range)
            <x-filament::button size="sm" :color="$range === $days ? 'primary' : 'gray'" wire:click="setRange({{ $range }})">
                {{ __('dashboard.reports.range_'.$range) }}
            </x-filament::button>
        @endforeach
    </div>

    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
        @foreach ([
            ['dashboard.reports.total_leads', number_format($funnel['total'])],
            ['dashboard.reports.converted_leads', number_format($funnel['converted'])],
            ['dashboard.reports.conversion_rate', $funnel['rate'].'%'],
            ['dashboard.reports.total_value', number_format($funnel['value']).' '.__('SAR')],
            ['dashboard.reports.average_value', number_format($funnel['average']).' '.__('SAR')],
        ] as [$label, $value])
            <x-filament::section compact>
                <div style="font-size:.8rem;color:#66706A">{{ __($label) }}</div>
                <div style="font-size:1.6rem;font-weight:700;margin-top:.25rem" dir="ltr">{{ $value }}</div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section :heading="__('dashboard.reports.leads_over_time')">
        @php($max = max(1, $overTime->max('value')))
        <div style="display:flex;align-items:flex-end;gap:2px;height:160px" role="img" aria-label="{{ __('dashboard.reports.leads_over_time') }}">
            @foreach ($overTime as $point)
                <div title="{{ $point['label'] }}: {{ $point['value'] }}"
                     style="flex:1;min-width:2px;border-radius:3px 3px 0 0;background:{{ $point['value'] ? '#0B5D3B' : 'rgba(11,93,59,.12)' }};height:{{ max(3, round($point['value'] / $max * 100)) }}%"></div>
            @endforeach
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.75rem;color:#66706A;margin-top:.4rem" dir="ltr">
            <span>{{ $overTime->first()['label'] ?? '' }}</span><span>{{ $overTime->last()['label'] ?? '' }}</span>
        </div>
    </x-filament::section>

    <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(280px,1fr))">
        @foreach ($groups as $heading => $rows)
            <x-filament::section :heading="$heading">
                @php($total = max(1, $rows->sum('value')))
                @forelse ($rows as $row)
                    <div style="margin:.5rem 0">
                        <div style="display:flex;justify-content:space-between;font-size:.85rem"><span>{{ $row['label'] }}</span><strong>{{ $row['value'] }}</strong></div>
                        <div style="height:8px;border-radius:999px;background:rgba(11,93,59,.10);overflow:hidden;margin-top:.25rem">
                            <div style="height:100%;width:{{ round($row['value'] / $total * 100) }}%;background:linear-gradient(90deg,#0B5D3B,#B8903A)"></div>
                        </div>
                    </div>
                @empty
                    <p style="color:#66706A;font-size:.85rem">{{ __('dashboard.reports.no_data') }}</p>
                @endforelse
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section :heading="__('dashboard.reports.revenue_by_campaign')">
        @if ($revenueByCampaign->isEmpty())
            <p style="color:#66706A;font-size:.85rem">{{ __('dashboard.reports.no_data') }}</p>
        @else
            <div style="overflow-x:auto">
                <table style="width:100%;font-size:.85rem;border-collapse:collapse">
                    <thead><tr style="text-align:start;color:#66706A">
                        <th style="text-align:start;padding:.5rem">{{ __('dashboard.campaigns.name') }}</th>
                        <th style="padding:.5rem">{{ __('dashboard.campaigns.leads_count') }}</th>
                        <th style="padding:.5rem">{{ __('dashboard.reports.spend') }}</th>
                        <th style="padding:.5rem">{{ __('dashboard.reports.revenue') }}</th>
                        <th style="padding:.5rem">{{ __('dashboard.reports.roi') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($revenueByCampaign as $row)
                            <tr style="border-top:1px solid rgba(0,0,0,.06)">
                                <td style="padding:.5rem">{{ $row['label'] }}</td>
                                <td style="padding:.5rem;text-align:center">{{ $row['leads'] }}</td>
                                <td style="padding:.5rem;text-align:center" dir="ltr">{{ $row['spend'] === null ? '—' : number_format($row['spend']) }}</td>
                                <td style="padding:.5rem;text-align:center" dir="ltr">{{ number_format($row['revenue']) }}</td>
                                <td style="padding:.5rem;text-align:center" dir="ltr">{{ $row['roi'] === null ? '—' : $row['roi'].'×' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section :heading="__('dashboard.reports.revenue_by_source')">
        @forelse ($revenueBySource as $row)
            <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-top:1px solid rgba(0,0,0,.05);font-size:.85rem">
                <span>{{ $row['label'] }}</span><strong dir="ltr">{{ number_format($row['revenue']) }} {{ __('SAR') }}</strong>
            </div>
        @empty
            <p style="color:#66706A;font-size:.85rem">{{ __('dashboard.reports.no_data') }}</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
