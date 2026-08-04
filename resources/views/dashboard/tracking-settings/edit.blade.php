@php
    $fields = [
        'meta_pixel_id' => [
            'label' => __('معرّف بكسل ميتا (Meta / Facebook Pixel ID)'),
            'placeholder' => 'مثال: 123456789012345',
        ],
        'gtm_container_id' => [
            'label' => __('معرّف حاوية Google Tag Manager'),
            'placeholder' => 'مثال: GTM-XXXXXXX',
        ],
        'ga4_measurement_id' => [
            'label' => __('معرّف قياس Google Analytics 4'),
            'placeholder' => 'مثال: G-XXXXXXXXXX',
        ],
        'google_ads_conversion_id' => [
            'label' => __('معرّف تحويل Google Ads'),
            'placeholder' => 'مثال: AW-XXXXXXXXX',
        ],
        'google_ads_conversion_label' => [
            'label' => __('تسمية تحويل Google Ads (Conversion Label)'),
            'placeholder' => 'مثال: AbCdEfGhIjKlMnOp',
        ],
        'tiktok_pixel_id' => [
            'label' => __('معرّف بكسل TikTok'),
            'placeholder' => 'مثال: XXXXXXXXXXXXXXXXXX',
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-text-main leading-tight">
            {{ __('إعدادات التتبع') }}
        </h2>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        @if (session('status'))
            <div class="rounded-md bg-primary-green/5 border border-primary-green/30 p-4 text-primary-green">
                {{ session('status') }}
            </div>
        @endif

        <x-card class="p-6">
            <p class="text-sm text-text-secondary">
                {{ __('هذه المعرّفات ليست بيانات سرية (تُعرض في كود الصفحة العام)، لكنها لا تُدرج إلا من هنا — لن يعمل أي كود تتبع إلا بعد إدخال قيمته هنا وتفعيله.') }}
            </p>
        </x-card>

        <form method="POST" action="{{ route('dashboard.tracking-settings.update') }}">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                @foreach ($fields as $key => $field)
                    <x-card class="p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex-1">
                                <label for="settings_{{ $key }}_value" class="block text-sm font-medium text-text-main">
                                    {{ $field['label'] }}
                                </label>
                                <input
                                    type="text"
                                    name="settings[{{ $key }}][value]"
                                    id="settings_{{ $key }}_value"
                                    value="{{ old("settings.{$key}.value", $settings[$key]?->value) }}"
                                    placeholder="{{ $field['placeholder'] }}"
                                    dir="ltr"
                                    class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green"
                                >
                                @error("settings.{$key}.value")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <label class="flex shrink-0 items-center gap-2 sm:mt-7">
                                <input
                                    type="checkbox"
                                    name="settings[{{ $key }}][is_active]"
                                    value="1"
                                    @checked(old("settings.{$key}.is_active", $settings[$key]?->is_active))
                                    class="rounded border-border-default text-primary-green shadow-sm"
                                >
                                <span class="text-sm text-text-main">{{ __('مفعّل') }}</span>
                            </label>
                        </div>
                    </x-card>
                @endforeach
            </div>

            <button type="submit" class="mt-6 rounded-md bg-primary-green px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-green/90">
                {{ __('حفظ الإعدادات') }}
            </button>
        </form>
    </div>
</x-app-layout>
