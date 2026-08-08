@php
    $isEdit = isset($campaign);
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('dashboard.campaigns.name')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $isEdit ? $campaign->name : '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="platform" :value="__('dashboard.campaigns.platform')" />
            <select id="platform" name="platform"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('dashboard.campaigns.platform_none') }}</option>
                @foreach (App\Models\Campaign::PLATFORMS as $platform)
                    <option value="{{ $platform }}" @selected(old('platform', $isEdit ? $campaign->platform : '') === $platform)>
                        {{ __('dashboard.campaigns.platforms.'.$platform) }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('platform')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="external_campaign_id" :value="__('dashboard.campaigns.external_campaign_id')" />
            <x-text-input id="external_campaign_id" name="external_campaign_id" type="text" dir="ltr" class="mt-1 block w-full"
                :value="old('external_campaign_id', $isEdit ? $campaign->external_campaign_id : '')" />
            <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.campaigns.external_campaign_id_hint') }}</p>
            <x-input-error :messages="$errors->get('external_campaign_id')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <x-input-label for="budget" :value="__('dashboard.campaigns.budget')" />
            <x-text-input id="budget" name="budget" type="number" step="0.01" min="0" dir="ltr" class="mt-1 block w-full"
                :value="old('budget', $isEdit ? $campaign->budget : '')" />
            <x-input-error :messages="$errors->get('budget')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="spend" :value="__('dashboard.campaigns.spend')" />
            <x-text-input id="spend" name="spend" type="number" step="0.01" min="0" dir="ltr" class="mt-1 block w-full"
                :value="old('spend', $isEdit ? $campaign->spend : '')" />
            <x-input-error :messages="$errors->get('spend')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="currency" :value="__('dashboard.campaigns.currency')" />
            <x-text-input id="currency" name="currency" type="text" maxlength="3" dir="ltr" class="mt-1 block w-full"
                :value="old('currency', $isEdit ? $campaign->currency : 'SAR')" />
            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="starts_on" :value="__('dashboard.campaigns.starts_on')" />
            <x-text-input id="starts_on" name="starts_on" type="date" class="mt-1 block w-full"
                :value="old('starts_on', $isEdit && $campaign->starts_on ? $campaign->starts_on->format('Y-m-d') : '')" />
            <x-input-error :messages="$errors->get('starts_on')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="ends_on" :value="__('dashboard.campaigns.ends_on')" />
            <x-text-input id="ends_on" name="ends_on" type="date" class="mt-1 block w-full"
                :value="old('ends_on', $isEdit && $campaign->ends_on ? $campaign->ends_on->format('Y-m-d') : '')" />
            <x-input-error :messages="$errors->get('ends_on')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="notes" :value="__('dashboard.campaigns.notes')" />
        <textarea id="notes" name="notes" rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $isEdit ? $campaign->notes : '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="flex items-center gap-6">
        <label class="inline-flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm"
                {{ old('is_active', $isEdit ? $campaign->is_active : true) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-gray-600">{{ __('dashboard.campaigns.active_label') }}</span>
        </label>
    </div>
</div>
