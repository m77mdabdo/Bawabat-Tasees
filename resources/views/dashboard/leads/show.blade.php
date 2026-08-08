@php
    $customerFields = [
        ['label' => __('dashboard.leads.full_name'), 'value' => $lead->full_name],
        ['label' => __('dashboard.leads.phone'), 'value' => $lead->phone],
        ['label' => __('dashboard.leads.whatsapp_number'), 'value' => $lead->whatsapp_number],
        ['label' => __('dashboard.leads.email'), 'value' => $lead->email],
        ['label' => __('dashboard.leads.nationality'), 'value' => $lead->nationality],
        ['label' => __('dashboard.leads.country_of_residence'), 'value' => $lead->country_of_residence],
        ['label' => __('dashboard.leads.requested_service'), 'value' => $lead->requestedService?->name],
        ['label' => __('dashboard.leads.requested_activity'), 'value' => $lead->requested_activity],
        ['label' => __('dashboard.leads.owns_external_company'), 'value' => $lead->owns_external_company ? __('dashboard.leads.yes') : __('dashboard.leads.no')],
        ['label' => __('dashboard.leads.request_type'), 'value' => $lead->type === 'consultation' ? __('dashboard.leads.type_consultation') : __('dashboard.leads.type_contact')],
        ['label' => __('dashboard.leads.consent_given'), 'value' => $lead->consent_given ? __('dashboard.leads.yes') . ' — ' . $lead->consented_at?->format('Y-m-d H:i') : __('dashboard.leads.no')],
        ['label' => __('dashboard.leads.received_at'), 'value' => $lead->created_at->format('Y-m-d H:i')],
    ];

    $sourceFields = [
        ['label' => __('dashboard.leads.source'), 'value' => $lead->source_platform],
        ['label' => __('dashboard.leads.campaign_name'), 'value' => $lead->campaign_name],
        ['label' => __('dashboard.leads.campaign_id'), 'value' => $lead->campaign_id],
        ['label' => __('dashboard.leads.adset_name'), 'value' => $lead->adset_name],
        ['label' => __('dashboard.leads.adset_id'), 'value' => $lead->adset_id],
        ['label' => __('dashboard.leads.ad_name'), 'value' => $lead->ad_name],
        ['label' => __('dashboard.leads.ad_id'), 'value' => $lead->ad_id],
        ['label' => 'UTM Source', 'value' => $lead->utm_source],
        ['label' => 'UTM Medium', 'value' => $lead->utm_medium],
        ['label' => 'UTM Campaign', 'value' => $lead->utm_campaign],
        ['label' => 'UTM Content', 'value' => $lead->utm_content],
        ['label' => 'UTM Term', 'value' => $lead->utm_term],
        ['label' => __('dashboard.leads.landing_page_url'), 'value' => $lead->landing_page_url],
        ['label' => __('dashboard.leads.referrer_url'), 'value' => $lead->referrer_url],
        ['label' => 'Google Click ID', 'value' => $lead->gclid],
        ['label' => 'Facebook Click ID', 'value' => $lead->fbclid],
        ['label' => 'TikTok Click ID', 'value' => $lead->ttclid],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-text-main leading-tight">
                {{ $lead->full_name }}
            </h2>
            <a href="{{ route('dashboard.leads.index') }}" class="text-sm text-primary-green hover:text-primary-green/70">
                {{ __('dashboard.leads.back_to_list') }}
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card class="p-6">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-text-secondary">
                {{ __('dashboard.leads.customer_data') }}
            </h3>
            <dl class="space-y-3">
                @foreach ($customerFields as $field)
                    <div class="flex items-start justify-between gap-4 text-sm">
                        <dt class="shrink-0 text-text-secondary">{{ $field['label'] }}</dt>
                        <dd class="min-w-0 break-words text-end text-text-main">{{ $field['value'] ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($lead->message)
                <div class="mt-4 border-t border-border-default pt-4">
                    <dt class="text-sm text-text-secondary">{{ __('dashboard.leads.message') }}</dt>
                    <dd class="mt-1 text-sm text-text-main whitespace-pre-line">{{ $lead->message }}</dd>
                </div>
            @endif
        </x-card>

        <x-card class="p-6">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-text-secondary">
                {{ __('dashboard.leads.lead_source_data') }}
            </h3>
            <dl class="space-y-3">
                @foreach ($sourceFields as $field)
                    <div class="flex items-start justify-between gap-4 text-sm">
                        <dt class="shrink-0 text-text-secondary">{{ $field['label'] }}</dt>
                        <dd class="min-w-0 break-all text-end text-text-main">{{ $field['value'] ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-card>
    </div>

    @include('dashboard.leads._conversions', ['lead' => $lead])

    <div class="mt-6">
        <form action="{{ route('dashboard.leads.destroy', $lead) }}" method="POST" onsubmit="return confirm('{{ __('dashboard.leads.confirm_archive') }}');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">
                {{ __('dashboard.leads.archive_lead') }}
            </button>
        </form>
    </div>
</x-app-layout>
