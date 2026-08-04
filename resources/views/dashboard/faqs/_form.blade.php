@php
    $isEdit = isset($faq);
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="question_ar" :value="__('Question (Arabic)')" />
            <textarea id="question_ar" name="question[ar]" dir="rtl" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('question.ar', $isEdit ? $faq->getTranslation('question', 'ar') : '') }}</textarea>
            <x-input-error :messages="$errors->get('question.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="question_en" :value="__('Question (English)')" />
            <textarea id="question_en" name="question[en]" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('question.en', $isEdit ? $faq->getTranslation('question', 'en') : '') }}</textarea>
            <x-input-error :messages="$errors->get('question.en')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="answer_ar" :value="__('Answer (Arabic)')" />
            <textarea id="answer_ar" name="answer[ar]" dir="rtl" rows="5" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('answer.ar', $isEdit ? $faq->getTranslation('answer', 'ar') : '') }}</textarea>
            <x-input-error :messages="$errors->get('answer.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="answer_en" :value="__('Answer (English)')" />
            <textarea id="answer_en" name="answer[en]" rows="5" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('answer.en', $isEdit ? $faq->getTranslation('answer', 'en') : '') }}</textarea>
            <x-input-error :messages="$errors->get('answer.en')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="sort_order" :value="__('Sort Order')" />
        <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full md:w-32" :value="old('sort_order', $isEdit ? $faq->sort_order : 0)" />
        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
    </div>

    <div class="flex items-center gap-6">
        <label class="inline-flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm" {{ old('is_active', $isEdit ? $faq->is_active : true) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-gray-600">{{ __('Active') }}</span>
        </label>
    </div>
</div>
