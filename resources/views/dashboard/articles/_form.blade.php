@php
    $isEdit = isset($article);
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="slug" :value="__('dashboard.articles.slug_hint')" />
        <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug', $isEdit ? $article->slug : '')" />
        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="cover_image" :value="__('dashboard.articles.cover_image')" />
        @if ($isEdit && $article->cover_image)
            <img src="{{ Illuminate\Support\Facades\Storage::url($article->cover_image) }}" alt="" class="h-20 mb-2 rounded">
        @endif
        <input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-700">
        <x-input-error :messages="$errors->get('cover_image')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="title_ar" :value="__('dashboard.common.title_ar')" />
            <x-text-input id="title_ar" name="title[ar]" type="text" dir="rtl" class="mt-1 block w-full" :value="old('title.ar', $isEdit ? $article->getTranslation('title', 'ar') : '')" required />
            <x-input-error :messages="$errors->get('title.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="title_en" :value="__('dashboard.common.title_en')" />
            <x-text-input id="title_en" name="title[en]" type="text" class="mt-1 block w-full" :value="old('title.en', $isEdit ? $article->getTranslation('title', 'en') : '')" />
            <x-input-error :messages="$errors->get('title.en')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="excerpt_ar" :value="__('dashboard.articles.excerpt_ar')" />
            <textarea id="excerpt_ar" name="excerpt[ar]" dir="rtl" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('excerpt.ar', $isEdit ? $article->getTranslation('excerpt', 'ar') : '') }}</textarea>
            <x-input-error :messages="$errors->get('excerpt.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="excerpt_en" :value="__('dashboard.articles.excerpt_en')" />
            <textarea id="excerpt_en" name="excerpt[en]" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('excerpt.en', $isEdit ? $article->getTranslation('excerpt', 'en') : '') }}</textarea>
            <x-input-error :messages="$errors->get('excerpt.en')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="body_ar" :value="__('dashboard.articles.body_ar_hint')" />
            <textarea id="body_ar" name="body[ar]" dir="rtl" rows="10" class="mt-1 block w-full font-mono text-base sm:text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body.ar', $isEdit ? $article->getTranslation('body', 'ar') : '') }}</textarea>
            <x-input-error :messages="$errors->get('body.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="body_en" :value="__('dashboard.articles.body_en_hint')" />
            <textarea id="body_en" name="body[en]" rows="10" class="mt-1 block w-full font-mono text-base sm:text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body.en', $isEdit ? $article->getTranslation('body', 'en') : '') }}</textarea>
            <x-input-error :messages="$errors->get('body.en')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="published_at" :value="__('dashboard.articles.published_at')" />
        <x-text-input id="published_at" name="published_at" type="datetime-local" class="mt-1 block w-full md:w-64" :value="old('published_at', $isEdit && $article->published_at ? $article->published_at->format('Y-m-d\TH:i') : '')" />
        <x-input-error :messages="$errors->get('published_at')" class="mt-2" />
    </div>

    <div class="flex items-center gap-6">
        <label class="inline-flex items-center">
            <input type="hidden" name="is_published" value="0">
            <input type="checkbox" name="is_published" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" {{ old('is_published', $isEdit ? $article->is_published : false) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-gray-600">{{ __('dashboard.articles.published_label') }}</span>
        </label>
    </div>

    @include('dashboard._seo-fields', ['seoOwner' => $isEdit ? $article : null])
</div>
