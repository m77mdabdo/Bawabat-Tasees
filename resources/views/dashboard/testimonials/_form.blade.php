@php
    $isEdit = isset($testimonial);
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <x-input-label for="client_name" :value="__('dashboard.testimonials.client_name')" />
            <x-text-input id="client_name" name="client_name" type="text" class="mt-1 block w-full" :value="old('client_name', $isEdit ? $testimonial->client_name : '')" required />
            <x-input-error :messages="$errors->get('client_name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="client_title" :value="__('dashboard.testimonials.client_title')" />
            <x-text-input id="client_title" name="client_title" type="text" class="mt-1 block w-full" :value="old('client_title', $isEdit ? $testimonial->client_title : '')" />
            <x-input-error :messages="$errors->get('client_title')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="client_country" :value="__('dashboard.testimonials.client_country')" />
            <x-text-input id="client_country" name="client_country" type="text" class="mt-1 block w-full" :value="old('client_country', $isEdit ? $testimonial->client_country : '')" />
            <x-input-error :messages="$errors->get('client_country')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="avatar" :value="__('dashboard.testimonials.avatar')" />
        @if ($isEdit && $testimonial->avatar)
            <img src="{{ Illuminate\Support\Facades\Storage::url($testimonial->avatar) }}" alt="" class="h-12 w-12 rounded-full mb-2 object-cover">
        @endif
        <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-700">
        <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="quote_ar" :value="__('dashboard.testimonials.quote_ar')" />
            <textarea id="quote_ar" name="quote[ar]" dir="rtl" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('quote.ar', $isEdit ? $testimonial->getTranslation('quote', 'ar') : '') }}</textarea>
            <x-input-error :messages="$errors->get('quote.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="quote_en" :value="__('dashboard.testimonials.quote_en')" />
            <textarea id="quote_en" name="quote[en]" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('quote.en', $isEdit ? $testimonial->getTranslation('quote', 'en') : '') }}</textarea>
            <x-input-error :messages="$errors->get('quote.en')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="sort_order" :value="__('dashboard.common.sort_order')" />
        <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full md:w-32" :value="old('sort_order', $isEdit ? $testimonial->sort_order : 0)" />
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="flex items-center gap-6">
        <label class="inline-flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" {{ old('is_active', $isEdit ? $testimonial->is_active : true) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-gray-600">{{ __('dashboard.common.active') }}</span>
        </label>
    </div>
</div>
