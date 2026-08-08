@php
    $isEdit = isset($source);
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="key" :value="__('dashboard.lead_sources.key')" />
            <x-text-input id="key" name="key" type="text" dir="ltr" class="mt-1 block w-full"
                :value="old('key', $isEdit ? $source->key : '')" required />
            <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.lead_sources.key_hint') }}</p>
            @if ($isEdit && ($leadsCount ?? 0) > 0)
                {{-- leads.source_platform stores this key as a string, so a
                     rename has to cascade — the service does it, but the
                     admin should know it is happening. --}}
                <p class="mt-1 text-xs font-medium text-gold-deep">{{ __('dashboard.lead_sources.rename_warning') }}</p>
                <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.lead_sources.linked_leads_note', ['count' => $leadsCount]) }}</p>
            @endif
            <x-input-error :messages="$errors->get('key')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="label" :value="__('dashboard.lead_sources.label')" />
            <x-text-input id="label" name="label" type="text" class="mt-1 block w-full"
                :value="old('label', $isEdit ? $source->label : '')" required />
            <x-input-error :messages="$errors->get('label')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="sort_order" :value="__('dashboard.common.sort_order')" />
            <x-text-input id="sort_order" name="sort_order" type="number" min="0" dir="ltr" class="mt-1 block w-full"
                :value="old('sort_order', $isEdit ? $source->sort_order : 0)" />
            <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
        </div>

        <div class="flex items-end">
            <label class="inline-flex items-center">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded border-border-default text-primary-green shadow-sm focus:ring-primary-green"
                    {{ old('is_active', $isEdit ? $source->is_active : true) ? 'checked' : '' }}>
                <span class="ms-2 text-sm text-text-secondary">{{ __('dashboard.common.active') }}</span>
            </label>
        </div>
    </div>
</div>
