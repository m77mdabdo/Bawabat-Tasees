@php
    $isEdit = isset($section);
    $sectionContent = $isEdit ? $section->content : [];
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="key" :value="__('dashboard.sections.key_hint')" />
        <x-text-input id="key" name="key" type="text" class="mt-1 block w-full" :value="old('key', $isEdit ? $section->key : '')" required />
        <x-input-error :messages="$errors->get('key')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="title_ar" :value="__('dashboard.common.title_ar')" />
            <x-text-input id="title_ar" name="title[ar]" type="text" dir="rtl" class="mt-1 block w-full" :value="old('title.ar', $sectionContent['title']['ar'] ?? '')" required />
            <x-input-error :messages="$errors->get('title.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="title_en" :value="__('dashboard.common.title_en')" />
            <x-text-input id="title_en" name="title[en]" type="text" class="mt-1 block w-full" :value="old('title.en', $sectionContent['title']['en'] ?? '')" />
            <x-input-error :messages="$errors->get('title.en')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="description_ar" :value="__('dashboard.sections.description_ar_hint')" />
            <textarea id="description_ar" name="description[ar]" dir="rtl" rows="3" class="mt-1 block w-full border-border-default focus:border-primary-green focus:ring-primary-green rounded-md shadow-sm">{{ old('description.ar', $sectionContent['description']['ar'] ?? '') }}</textarea>
            <x-input-error :messages="$errors->get('description.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="description_en" :value="__('dashboard.sections.description_en')" />
            <textarea id="description_en" name="description[en]" rows="3" class="mt-1 block w-full border-border-default focus:border-primary-green focus:ring-primary-green rounded-md shadow-sm">{{ old('description.en', $sectionContent['description']['en'] ?? '') }}</textarea>
            <x-input-error :messages="$errors->get('description.en')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="icon" :value="__('dashboard.sections.icon_hint')" />
        <x-text-input id="icon" name="icon" type="text" class="mt-1 block w-full md:w-64" :value="old('icon', $sectionContent['icon'] ?? '')" />
        <x-input-error :messages="$errors->get('icon')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sort_order" :value="__('dashboard.common.sort_order')" />
        <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full md:w-32" :value="old('sort_order', $isEdit ? $section->sort_order : 0)" />
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
        <label class="inline-flex min-h-[44px] cursor-pointer items-center py-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded border-border-default text-primary-green shadow-sm" {{ old('is_active', $isEdit ? $section->is_active : true) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-text-secondary">{{ __('dashboard.common.active') }}</span>
        </label>
    </div>
</div>
