@php
    $customerFields = [
        ['label' => __('الاسم الكامل'), 'value' => $lead->full_name],
        ['label' => __('الهاتف'), 'value' => $lead->phone],
        ['label' => __('رقم واتساب'), 'value' => $lead->whatsapp_number],
        ['label' => __('البريد الإلكتروني'), 'value' => $lead->email],
        ['label' => __('الجنسية'), 'value' => $lead->nationality],
        ['label' => __('بلد الإقامة'), 'value' => $lead->country_of_residence],
        ['label' => __('الخدمة المطلوبة'), 'value' => $lead->requestedService?->name],
        ['label' => __('النشاط التجاري المطلوب'), 'value' => $lead->requested_activity],
        ['label' => __('يملك شركة خارج المملكة'), 'value' => $lead->owns_external_company ? __('نعم') : __('لا')],
        ['label' => __('نوع الطلب'), 'value' => $lead->type === 'consultation' ? __('طلب استشارة') : __('تواصل معنا')],
        ['label' => __('الموافقة على التواصل'), 'value' => $lead->consent_given ? __('نعم') . ' — ' . $lead->consented_at?->format('Y-m-d H:i') : __('لا')],
        ['label' => __('تاريخ الاستلام'), 'value' => $lead->created_at->format('Y-m-d H:i')],
    ];

    $sourceFields = [
        ['label' => __('المصدر'), 'value' => $lead->source_platform],
        ['label' => __('اسم الحملة'), 'value' => $lead->campaign_name],
        ['label' => __('معرف الحملة'), 'value' => $lead->campaign_id],
        ['label' => __('اسم المجموعة الإعلانية'), 'value' => $lead->adset_name],
        ['label' => __('معرف المجموعة الإعلانية'), 'value' => $lead->adset_id],
        ['label' => __('اسم الإعلان'), 'value' => $lead->ad_name],
        ['label' => __('معرف الإعلان'), 'value' => $lead->ad_id],
        ['label' => 'UTM Source', 'value' => $lead->utm_source],
        ['label' => 'UTM Medium', 'value' => $lead->utm_medium],
        ['label' => 'UTM Campaign', 'value' => $lead->utm_campaign],
        ['label' => 'UTM Content', 'value' => $lead->utm_content],
        ['label' => 'UTM Term', 'value' => $lead->utm_term],
        ['label' => __('رابط صفحة الهبوط'), 'value' => $lead->landing_page_url],
        ['label' => __('الرابط المُحيل'), 'value' => $lead->referrer_url],
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
                {{ __('العودة إلى القائمة') }}
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card class="p-6">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-text-secondary">
                {{ __('بيانات العميل') }}
            </h3>
            <dl class="space-y-3">
                @foreach ($customerFields as $field)
                    <div class="flex items-start justify-between gap-4 text-sm">
                        <dt class="text-text-secondary">{{ $field['label'] }}</dt>
                        <dd class="text-text-main text-right">{{ $field['value'] ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($lead->message)
                <div class="mt-4 border-t border-border-default pt-4">
                    <dt class="text-sm text-text-secondary">{{ __('الرسالة') }}</dt>
                    <dd class="mt-1 text-sm text-text-main whitespace-pre-line">{{ $lead->message }}</dd>
                </div>
            @endif
        </x-card>

        <x-card class="p-6">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-text-secondary">
                {{ __('من أين جاء العميل') }}
            </h3>
            <dl class="space-y-3">
                @foreach ($sourceFields as $field)
                    <div class="flex items-start justify-between gap-4 text-sm">
                        <dt class="text-text-secondary">{{ $field['label'] }}</dt>
                        <dd class="text-text-main text-right break-all">{{ $field['value'] ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-card>
    </div>

    <div class="mt-6">
        <form action="{{ route('dashboard.leads.destroy', $lead) }}" method="POST" onsubmit="return confirm('{{ __('أرشفة هذا العميل المحتمل؟') }}');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">
                {{ __('أرشفة العميل المحتمل') }}
            </button>
        </form>
    </div>
</x-app-layout>
