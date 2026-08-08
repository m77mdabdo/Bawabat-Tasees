@php
    // $seoOwner is the Article/Page/Country/Service being edited, or null on
    // a create form. Reading the relation directly keeps this partial usable
    // from any of the four forms without each controller passing extra data.
    $seo = isset($seoOwner) && $seoOwner?->exists ? $seoOwner->seoMeta : null;
@endphp

<div class="rounded-lg border border-border-default bg-bg-soft p-4">
    <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.seo.section_heading') }}</h3>
    <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.seo.section_hint') }}</p>

    <div class="mt-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="seo_meta_title_ar" :value="__('dashboard.seo.meta_title_ar')" />
                <x-text-input id="seo_meta_title_ar" name="seo[meta_title][ar]" type="text" dir="rtl" class="mt-1 block w-full"
                    :value="old('seo.meta_title.ar', $seo?->getTranslation('meta_title', 'ar', false))" />
                <x-input-error :messages="$errors->get('seo.meta_title.ar')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="seo_meta_title_en" :value="__('dashboard.seo.meta_title_en')" />
                <x-text-input id="seo_meta_title_en" name="seo[meta_title][en]" type="text" class="mt-1 block w-full"
                    :value="old('seo.meta_title.en', $seo?->getTranslation('meta_title', 'en', false))" />
                <x-input-error :messages="$errors->get('seo.meta_title.en')" class="mt-2" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="seo_meta_description_ar" :value="__('dashboard.seo.meta_description_ar')" />
                <textarea id="seo_meta_description_ar" name="seo[meta_description][ar]" dir="rtl" rows="3"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('seo.meta_description.ar', $seo?->getTranslation('meta_description', 'ar', false)) }}</textarea>
                <x-input-error :messages="$errors->get('seo.meta_description.ar')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="seo_meta_description_en" :value="__('dashboard.seo.meta_description_en')" />
                <textarea id="seo_meta_description_en" name="seo[meta_description][en]" rows="3"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('seo.meta_description.en', $seo?->getTranslation('meta_description', 'en', false)) }}</textarea>
                <x-input-error :messages="$errors->get('seo.meta_description.en')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="seo_og_image" :value="__('dashboard.seo.og_image')" />
            @if ($seo?->og_image)
                <img src="{{ Illuminate\Support\Facades\Storage::url($seo->og_image) }}" alt="" class="h-20 mb-2 rounded">
            @endif
            <input id="seo_og_image" name="seo_og_image" type="file" accept="image/jpeg,image/png,image/webp"
                class="mt-1 block w-full text-sm text-gray-700">
            <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.seo.og_image_hint') }}</p>
            <x-input-error :messages="$errors->get('seo_og_image')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="seo_canonical_url" :value="__('dashboard.seo.canonical_url')" />
            <x-text-input id="seo_canonical_url" name="seo[canonical_url]" type="url" dir="ltr" class="mt-1 block w-full"
                :value="old('seo.canonical_url', $seo?->canonical_url)" />
            <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.seo.canonical_url_hint') }}</p>
            <x-input-error :messages="$errors->get('seo.canonical_url')" class="mt-2" />
        </div>
    </div>
</div>
