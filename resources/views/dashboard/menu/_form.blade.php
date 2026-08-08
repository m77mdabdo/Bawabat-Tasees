@php
    $isEdit = isset($item);
    $linkType = old('link_type', $isEdit ? $item->link_type : 'route');
@endphp

{{-- x-data drives only which field is REVEALED; the server still
     validates conditionally, so a JS-less submit cannot bypass it. --}}
<div class="space-y-6" x-data="{ linkType: '{{ $linkType }}' }">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="label_ar" :value="__('dashboard.menu.label_ar')" />
            <x-text-input id="label_ar" name="label[ar]" type="text" dir="rtl" class="mt-1 block w-full"
                :value="old('label.ar', $isEdit ? $item->getTranslation('label', 'ar', false) : '')" required />
            <x-input-error :messages="$errors->get('label.ar')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="label_en" :value="__('dashboard.menu.label_en')" />
            <x-text-input id="label_en" name="label[en]" type="text" class="mt-1 block w-full"
                :value="old('label.en', $isEdit ? $item->getTranslation('label', 'en', false) : '')" />
            <x-input-error :messages="$errors->get('label.en')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="link_type" :value="__('dashboard.menu.link_type')" />
        <select id="link_type" name="link_type" x-model="linkType"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach (App\Models\MenuItem::LINK_TYPES as $type)
                <option value="{{ $type }}" @selected($linkType === $type)>
                    {{ __('dashboard.menu.link_types.'.$type) }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('link_type')" class="mt-2" />
    </div>

    {{-- Route picker: the whitelist only, so a menu item can never point
         at a route that needs a bound parameter. --}}
    <div x-show="linkType === 'route'" x-cloak>
        <x-input-label for="route_name" :value="__('dashboard.menu.route_name')" />
        <select id="route_name" name="route_name"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">{{ __('dashboard.menu.route_placeholder') }}</option>
            @foreach ($routes as $name => $friendly)
                <option value="{{ $name }}" @selected(old('route_name', $isEdit ? $item->route_name : '') === $name)>
                    {{ $friendly }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('route_name')" class="mt-2" />
    </div>

    <div x-show="linkType === 'url'" x-cloak>
        <x-input-label for="url" :value="__('dashboard.menu.url')" />
        <x-text-input id="url" name="url" type="text" dir="ltr" class="mt-1 block w-full"
            :value="old('url', $isEdit ? $item->url : '')" placeholder="https://example.com" />
        <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.menu.url_hint') }}</p>
        <x-input-error :messages="$errors->get('url')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div x-show="linkType !== 'none'" x-cloak>
            <x-input-label for="target" :value="__('dashboard.menu.target')" />
            <select id="target" name="target"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (App\Models\MenuItem::TARGETS as $target)
                    <option value="{{ $target }}" @selected(old('target', $isEdit ? $item->target : '_self') === $target)>
                        {{ __('dashboard.menu.targets.'.$target) }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('target')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="parent_id" :value="__('dashboard.menu.parent')" />
            <select id="parent_id" name="parent_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">{{ __('dashboard.menu.parent_none') }}</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected((string) old('parent_id', $isEdit ? $item->parent_id : '') === (string) $parent->id)>
                        {{ $parent->label }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
        </div>
    </div>

    <div class="flex items-center gap-6">
        <label class="inline-flex items-center">
            <input type="hidden" name="is_visible" value="0">
            <input type="checkbox" name="is_visible" value="1" class="rounded border-gray-300 text-primary-green shadow-sm focus:ring-primary-green"
                {{ old('is_visible', $isEdit ? $item->is_visible : true) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-gray-600">{{ __('dashboard.menu.visible_label') }}</span>
        </label>
    </div>
</div>
