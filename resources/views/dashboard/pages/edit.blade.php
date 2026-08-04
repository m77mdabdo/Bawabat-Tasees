<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Page') }} — {{ $page->getTranslation('title', 'ar') }}
            </h2>
            <a href="{{ route('dashboard.pages.sections.index', $page) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Manage Sections') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('dashboard.pages.update', $page) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="title_ar" :value="__('Title (Arabic)')" />
                                <x-text-input id="title_ar" name="title[ar]" type="text" dir="rtl" class="mt-1 block w-full" :value="old('title.ar', $page->getTranslation('title', 'ar'))" required />
                                <x-input-error :messages="$errors->get('title.ar')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="title_en" :value="__('Title (English)')" />
                                <x-text-input id="title_en" name="title[en]" type="text" class="mt-1 block w-full" :value="old('title.en', $page->getTranslation('title', 'en'))" />
                                <x-input-error :messages="$errors->get('title.en')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="body_ar" :value="__('Intro Body (Arabic) — HTML allowed: p, h2-h4, ul/ol/li, strong, em, a, img, blockquote.')" />
                                <textarea id="body_ar" name="body[ar]" dir="rtl" rows="8" class="mt-1 block w-full font-mono text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body.ar', $page->getTranslation('body', 'ar')) }}</textarea>
                                <x-input-error :messages="$errors->get('body.ar')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="body_en" :value="__('Intro Body (English)')" />
                                <textarea id="body_en" name="body[en]" rows="8" class="mt-1 block w-full font-mono text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body.en', $page->getTranslation('body', 'en')) }}</textarea>
                                <x-input-error :messages="$errors->get('body.en')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="meta_title_ar" :value="__('Meta Title (Arabic)')" />
                                <x-text-input id="meta_title_ar" name="meta_title[ar]" type="text" dir="rtl" class="mt-1 block w-full" :value="old('meta_title.ar', $page->getTranslation('meta_title', 'ar'))" required />
                                <x-input-error :messages="$errors->get('meta_title.ar')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="meta_title_en" :value="__('Meta Title (English)')" />
                                <x-text-input id="meta_title_en" name="meta_title[en]" type="text" class="mt-1 block w-full" :value="old('meta_title.en', $page->getTranslation('meta_title', 'en'))" />
                                <x-input-error :messages="$errors->get('meta_title.en')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="meta_description_ar" :value="__('Meta Description (Arabic)')" />
                                <textarea id="meta_description_ar" name="meta_description[ar]" dir="rtl" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>{{ old('meta_description.ar', $page->getTranslation('meta_description', 'ar')) }}</textarea>
                                <x-input-error :messages="$errors->get('meta_description.ar')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="meta_description_en" :value="__('Meta Description (English)')" />
                                <textarea id="meta_description_en" name="meta_description[en]" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('meta_description.en', $page->getTranslation('meta_description', 'en')) }}</textarea>
                                <x-input-error :messages="$errors->get('meta_description.en')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center gap-6">
                            <label class="inline-flex items-center">
                                <input type="hidden" name="is_published" value="0">
                                <input type="checkbox" name="is_published" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" {{ old('is_published', $page->is_published) ? 'checked' : '' }}>
                                <span class="ms-2 text-sm text-gray-600">{{ __('Published') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 mt-6">
                        <a href="{{ route('dashboard.pages.index') }}" class="text-sm text-gray-600 underline">{{ __('Cancel') }}</a>
                        <x-primary-button>{{ __('Update Page') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
