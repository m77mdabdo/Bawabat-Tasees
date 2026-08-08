@php
    $isEdit = isset($country);
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="slug" :value="__('dashboard.countries.slug_hint')" />
        <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug', $isEdit ? $country->slug : '')" />
        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="flag_image" :value="__('dashboard.countries.flag_image')" />
        @if ($isEdit && $country->flag_image)
            <img src="{{ Illuminate\Support\Facades\Storage::url($country->flag_image) }}" alt="" class="h-12 mb-2 rounded">
        @endif
        <input id="flag_image" name="flag_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-700">
        <x-input-error :messages="$errors->get('flag_image')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="name_ar" :value="__('dashboard.common.name_ar')" />
            <x-text-input id="name_ar" name="name[ar]" type="text" dir="rtl" class="mt-1 block w-full" :value="old('name.ar', $isEdit ? $country->getTranslation('name', 'ar') : '')" required />
            <x-input-error :messages="$errors->get('name.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="name_en" :value="__('dashboard.common.name_en')" />
            <x-text-input id="name_en" name="name[en]" type="text" class="mt-1 block w-full" :value="old('name.en', $isEdit ? $country->getTranslation('name', 'en') : '')" />
            <x-input-error :messages="$errors->get('name.en')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="notes_ar" :value="__('dashboard.countries.notes_ar')" />
            <textarea id="notes_ar" name="notes[ar]" dir="rtl" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes.ar', $isEdit ? $country->getTranslation('notes', 'ar') : '') }}</textarea>
            <x-input-error :messages="$errors->get('notes.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="notes_en" :value="__('dashboard.countries.notes_en')" />
            <textarea id="notes_en" name="notes[en]" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes.en', $isEdit ? $country->getTranslation('notes', 'en') : '') }}</textarea>
            <x-input-error :messages="$errors->get('notes.en')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="sort_order" :value="__('dashboard.common.sort_order')" />
        <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full md:w-32" :value="old('sort_order', $isEdit ? $country->sort_order : 0)" />
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="flex items-center gap-6">
        <label class="inline-flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" {{ old('is_active', $isEdit ? $country->is_active : true) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-gray-600">{{ __('dashboard.common.active') }}</span>
        </label>
    </div>

    @include('dashboard._seo-fields', ['seoOwner' => $isEdit ? $country : null])
</div>
