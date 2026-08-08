<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('dashboard.reports.title') }}</h2>
            <p class="mt-1 text-sm text-text-secondary">{{ __('dashboard.reports.subtitle') }}</p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Range picker. GET so the selected period is shareable/bookmarkable. --}}
            <form method="GET" action="{{ route('dashboard.reports.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="days" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.reports.range_label') }}</label>
                    <select name="days" id="days" class="mt-1 rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        @foreach ($ranges as $range)
                            <option value="{{ $range }}" @selected($days === $range)>{{ __('dashboard.reports.range_'.$range) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-md bg-primary-green px-4 py-2 text-sm font-semibold text-white hover:bg-dark-green">
                    {{ __('dashboard.reports.apply') }}
                </button>
            </form>

            {{-- Funnel headline figures. --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                @foreach ([
                    ['label' => __('dashboard.reports.total_leads'), 'value' => number_format($funnel['total'])],
                    ['label' => __('dashboard.reports.converted_leads'), 'value' => number_format($funnel['converted'])],
                    ['label' => __('dashboard.reports.conversion_rate'), 'value' => $funnel['rate'].'%'],
                    ['label' => __('dashboard.reports.total_value'), 'value' => number_format($funnel['value'], 2)],
                    ['label' => __('dashboard.reports.average_value'), 'value' => number_format($funnel['average'], 2)],
                ] as $tile)
                    <x-card>
                        <p class="text-xs text-text-secondary">{{ $tile['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold text-text-main" dir="ltr">{{ $tile['value'] }}</p>
                    </x-card>
                @endforeach
            </div>

            <x-card>
                <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.reports.leads_over_time') }}</h3>
                <div class="mt-4">
                    <x-dashboard.spark-chart :rows="$leadsOverTime" />
                </div>
            </x-card>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <x-card>
                    <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.reports.leads_by_type') }}</h3>
                    <div class="mt-4">
                        <x-dashboard.bar-chart :rows="$leadsByType" />
                    </div>
                </x-card>

                <x-card>
                    <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.reports.leads_by_source') }}</h3>
                    <div class="mt-4">
                        <x-dashboard.bar-chart :rows="$leadsBySource" />
                    </div>
                </x-card>

                <x-card>
                    <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.reports.leads_by_lead_source') }}</h3>
                    <div class="mt-4">
                        <x-dashboard.bar-chart :rows="$leadsByLeadSource" />
                    </div>
                </x-card>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Revenue vs spend per campaign — the ROI view. --}}
                <x-card>
                    <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.reports.revenue_by_campaign') }}</h3>

                    @if ($revenueByCampaign->isEmpty())
                        <p class="mt-4 rounded-md border border-dashed border-border-default px-4 py-6 text-center text-sm text-text-secondary">
                            {{ __('dashboard.reports.no_data') }}
                        </p>
                    @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-border-default text-sm">
                                <thead>
                                    <tr>
                                        <th scope="col" class="px-3 py-2 text-start text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.campaigns.name') }}</th>
                                        <th scope="col" class="px-3 py-2 text-end text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.campaigns.leads_count') }}</th>
                                        <th scope="col" class="px-3 py-2 text-end text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.reports.spend') }}</th>
                                        <th scope="col" class="px-3 py-2 text-end text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.reports.revenue') }}</th>
                                        <th scope="col" class="px-3 py-2 text-end text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.reports.roi') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border-default">
                                    @foreach ($revenueByCampaign as $row)
                                        <tr>
                                            <th scope="row" class="px-3 py-2 text-start font-medium text-text-main">{{ $row['label'] }}</th>
                                            <td class="px-3 py-2 text-end text-text-secondary">{{ number_format($row['leads']) }}</td>
                                            <td class="px-3 py-2 text-end text-text-secondary" dir="ltr">{{ $row['spend'] !== null ? number_format($row['spend'], 2) : '—' }}</td>
                                            <td class="px-3 py-2 text-end text-text-main" dir="ltr">{{ number_format($row['revenue'], 2) }}</td>
                                            <td class="px-3 py-2 text-end" dir="ltr">
                                                @if ($row['roi'] === null)
                                                    <span class="text-text-secondary">—</span>
                                                @else
                                                    <span class="font-semibold {{ $row['roi'] >= 1 ? 'text-primary-green' : 'text-red-600' }}">{{ number_format($row['roi'], 2) }}×</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>

                <x-card>
                    <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.reports.revenue_by_source') }}</h3>
                    <div class="mt-4">
                        <x-dashboard.bar-chart :rows="$revenueBySource" value-key="revenue" format="money" accent="gold" />
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
