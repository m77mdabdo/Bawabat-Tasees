@php
    $isEdit = isset($service);
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="slug" :value="__('Slug (optional — auto-generated from the Arabic name if left blank)')" />
        <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug', $isEdit ? $service->slug : '')" />
        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="icon" :value="__('Icon')" />
        <x-text-input id="icon" name="icon" type="text" class="mt-1 block w-full" :value="old('icon', $isEdit ? $service->icon : '')" />
        <x-input-error :messages="$errors->get('icon')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="cover_image" :value="__('Cover Image')" />
        @if ($isEdit && $service->cover_image)
            <img src="{{ Illuminate\Support\Facades\Storage::url($service->cover_image) }}" alt="" class="h-20 mb-2 rounded">
        @endif
        <input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-700">
        <x-input-error :messages="$errors->get('cover_image')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="name_ar" :value="__('Name (Arabic)')" />
            <x-text-input id="name_ar" name="name[ar]" type="text" dir="rtl" class="mt-1 block w-full" :value="old('name.ar', $isEdit ? $service->getTranslation('name', 'ar') : '')" required />
            <x-input-error :messages="$errors->get('name.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="name_en" :value="__('Name (English)')" />
            <x-text-input id="name_en" name="name[en]" type="text" class="mt-1 block w-full" :value="old('name.en', $isEdit ? $service->getTranslation('name', 'en') : '')" />
            <x-input-error :messages="$errors->get('name.en')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="summary_ar" :value="__('Summary (Arabic)')" />
            <textarea id="summary_ar" name="summary[ar]" dir="rtl" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('summary.ar', $isEdit ? $service->getTranslation('summary', 'ar') : '') }}</textarea>
            <x-input-error :messages="$errors->get('summary.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="summary_en" :value="__('Summary (English)')" />
            <textarea id="summary_en" name="summary[en]" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('summary.en', $isEdit ? $service->getTranslation('summary', 'en') : '') }}</textarea>
            <x-input-error :messages="$errors->get('summary.en')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="body_ar" :value="__('Body (Arabic)')" />
            <textarea id="body_ar" name="body[ar]" dir="rtl" rows="5" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body.ar', $isEdit ? $service->getTranslation('body', 'ar') : '') }}</textarea>
            <x-input-error :messages="$errors->get('body.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="body_en" :value="__('Body (English)')" />
            <textarea id="body_en" name="body[en]" rows="5" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body.en', $isEdit ? $service->getTranslation('body', 'en') : '') }}</textarea>
            <x-input-error :messages="$errors->get('body.en')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="requirements_ar" :value="__('Requirements (Arabic)')" />
            <textarea id="requirements_ar" name="requirements[ar]" dir="rtl" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('requirements.ar', $isEdit ? $service->getTranslation('requirements', 'ar') : '') }}</textarea>
            <x-input-error :messages="$errors->get('requirements.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="requirements_en" :value="__('Requirements (English)')" />
            <textarea id="requirements_en" name="requirements[en]" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('requirements.en', $isEdit ? $service->getTranslation('requirements', 'en') : '') }}</textarea>
            <x-input-error :messages="$errors->get('requirements.en')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="process_ar" :value="__('Process (Arabic)')" />
            <textarea id="process_ar" name="process[ar]" dir="rtl" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('process.ar', $isEdit ? $service->getTranslation('process', 'ar') : '') }}</textarea>
            <x-input-error :messages="$errors->get('process.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="process_en" :value="__('Process (English)')" />
            <textarea id="process_en" name="process[en]" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('process.en', $isEdit ? $service->getTranslation('process', 'en') : '') }}</textarea>
            <x-input-error :messages="$errors->get('process.en')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="sort_order" :value="__('Sort Order')" />
        <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full md:w-32" :value="old('sort_order', $isEdit ? $service->sort_order : 0)" />
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="flex items-center gap-6">
        <label class="inline-flex items-center">
            <input type="hidden" name="is_flagship" value="0">
            <input type="checkbox" name="is_flagship" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" {{ old('is_flagship', $isEdit ? $service->is_flagship : false) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-gray-600">{{ __('Flagship') }}</span>
        </label>
        <label class="inline-flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" {{ old('is_active', $isEdit ? $service->is_active : true) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-gray-600">{{ __('Active') }}</span>
        </label>
    </div>
</div>
