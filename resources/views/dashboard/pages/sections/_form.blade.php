@php
    $isEdit = isset($section);
    $sectionContent = $isEdit ? $section->content : [];
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="key" :value="__('Key (a short stable identifier, e.g. \"vision-2030\" or \"step-1\")')" />
        <x-text-input id="key" name="key" type="text" class="mt-1 block w-full" :value="old('key', $isEdit ? $section->key : '')" required />
        <x-input-error :messages="$errors->get('key')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="title_ar" :value="__('Title (Arabic)')" />
            <x-text-input id="title_ar" name="title[ar]" type="text" dir="rtl" class="mt-1 block w-full" :value="old('title.ar', $sectionContent['title']['ar'] ?? '')" required />
            <x-input-error :messages="$errors->get('title.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="title_en" :value="__('Title (English)')" />
            <x-text-input id="title_en" name="title[en]" type="text" class="mt-1 block w-full" :value="old('title.en', $sectionContent['title']['en'] ?? '')" />
            <x-input-error :messages="$errors->get('title.en')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="description_ar" :value="__('Description (Arabic) — plain text, no HTML')" />
            <textarea id="description_ar" name="description[ar]" dir="rtl" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description.ar', $sectionContent['description']['ar'] ?? '') }}</textarea>
            <x-input-error :messages="$errors->get('description.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="description_en" :value="__('Description (English)')" />
            <textarea id="description_en" name="description[en]" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description.en', $sectionContent['description']['en'] ?? '') }}</textarea>
            <x-input-error :messages="$errors->get('description.en')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="icon" :value="__('Icon (a simple keyword identifier, e.g. \"check-circle\" — not an uploaded image)')" />
        <x-text-input id="icon" name="icon" type="text" class="mt-1 block w-full md:w-64" :value="old('icon', $sectionContent['icon'] ?? '')" />
        <x-input-error :messages="$errors->get('icon')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="sort_order" :value="__('Sort Order')" />
        <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full md:w-32" :value="old('sort_order', $isEdit ? $section->sort_order : 0)" />
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="flex items-center gap-6">
        <label class="inline-flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" {{ old('is_active', $isEdit ? $section->is_active : true) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-gray-600">{{ __('Active') }}</span>
        </label>
    </div>
</div>
