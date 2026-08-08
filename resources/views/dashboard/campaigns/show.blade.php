@php
    $spend = (float) $campaign->spend;
    // ROI as a multiple of spend, only meaningful once spend is recorded.
    $roi = $spend > 0 ? $conversionValue / $spend : null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $campaign->name }}</h2>
            <a href="{{ route('dashboard.campaigns.edit', $campaign) }}" class="text-sm text-indigo-600 hover:underline">{{ __('dashboard.common.edit') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                @foreach ([
                    ['label' => __('dashboard.campaigns.leads_count'), 'value' => number_format($leadsCount)],
                    ['label' => __('dashboard.campaigns.conversions_count'), 'value' => number_format($conversionsCount)],
                    ['label' => __('dashboard.campaigns.conversion_value'), 'value' => number_format($conversionValue, 2)],
                    ['label' => __('dashboard.campaigns.spend'), 'value' => $campaign->spend !== null ? number_format($spend, 2) : '—'],
                    ['label' => __('dashboard.campaigns.roi'), 'value' => $roi !== null ? number_format($roi, 2).'×' : '—'],
                ] as $tile)
                    <x-card>
                        <p class="text-xs text-text-secondary">{{ $tile['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold text-text-main" dir="ltr">{{ $tile['value'] }}</p>
                    </x-card>
                @endforeach
            </div>

            <x-card>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ([
                        ['label' => __('dashboard.campaigns.platform'), 'value' => $campaign->platform ? __('dashboard.campaigns.platforms.'.$campaign->platform) : null],
                        ['label' => __('dashboard.campaigns.external_campaign_id'), 'value' => $campaign->external_campaign_id],
                        ['label' => __('dashboard.campaigns.budget'), 'value' => $campaign->budget !== null ? number_format((float) $campaign->budget, 2).' '.$campaign->currency : null],
                        ['label' => __('dashboard.campaigns.starts_on'), 'value' => $campaign->starts_on?->format('Y-m-d')],
                        ['label' => __('dashboard.campaigns.ends_on'), 'value' => $campaign->ends_on?->format('Y-m-d')],
                        ['label' => __('dashboard.common.active'), 'value' => $campaign->is_active ? __('dashboard.common.yes') : __('dashboard.common.no')],
                    ] as $field)
                        <div class="flex items-start justify-between gap-4 text-sm">
                            <dt class="shrink-0 text-text-secondary">{{ $field['label'] }}</dt>
                            <dd class="min-w-0 break-all text-end text-text-main">{{ $field['value'] ?? '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($campaign->notes)
                    <p class="mt-4 whitespace-pre-line border-t border-border-default pt-4 text-sm text-text-secondary">{{ $campaign->notes }}</p>
                @endif
            </x-card>

            <x-card>
                <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.campaigns.linked_leads') }}</h3>

                @if ($campaign->leads->isEmpty())
                    <p class="mt-4 rounded-md border border-dashed border-border-default px-4 py-6 text-center text-sm text-text-secondary">
                        {{ __('dashboard.campaigns.no_linked_leads') }}
                    </p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-border-default">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-start text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.leads.name') }}</th>
                                    <th class="px-4 py-2 text-start text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.leads.source') }}</th>
                                    <th class="px-4 py-2 text-start text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.leads.received_at') }}</th>
                                    <th class="px-4 py-2 text-end text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-default">
                                @foreach ($campaign->leads as $lead)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-text-main">
                                            {{ $lead->full_name }}
                                            @if ($lead->isConverted())
                                                <span class="ms-2 rounded-full bg-primary-green/10 px-2 py-0.5 text-xs font-semibold text-primary-green">{{ __('dashboard.conversions.converted_badge') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm text-text-secondary">{{ $lead->source_platform ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm text-text-secondary">{{ $lead->created_at->format('Y-m-d') }}</td>
                                        <td class="px-4 py-2 text-end text-sm">
                                            <a href="{{ route('dashboard.leads.show', $lead) }}" class="text-indigo-600 hover:underline">{{ __('dashboard.leads.view') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
