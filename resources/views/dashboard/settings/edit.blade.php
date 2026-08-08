@php
    use App\Services\Cms\SettingsService;

    /**
     * Reads the CURRENT value for a key, honouring old() so a failed
     * validation round-trip does not silently discard what was typed.
     */
    $current = function (string $key, ?string $locale = null) use ($values) {
        $stored = $values[$key] ?? null;

        if ($locale !== null) {
            $stored = is_array($stored) ? ($stored[$locale] ?? null) : null;

            return old("settings.{$key}.{$locale}", $stored);
        }

        return old("settings.{$key}", is_array($stored) ? null : $stored);
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-text-main leading-tight">{{ __('dashboard.settings.title') }}</h2>
            <p class="mt-1 text-sm text-text-secondary">{{ __('dashboard.settings.subtitle') }}</p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-red-100 text-red-800 rounded-md">
                    <ul class="list-disc space-y-1 ps-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- enctype: the logo is a file upload. --}}
            <form method="POST" action="{{ route('dashboard.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                @foreach ($definition as $group => $keys)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.settings.groups.'.$group) }}</h3>
                        <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.settings.group_hints.'.$group) }}</p>

                        <div class="mt-5 space-y-5">
                            @foreach ($keys as $key => $type)
                                @if ($type === SettingsService::TYPE_TRANSLATED)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label :for="$key.'_ar'" :value="__('dashboard.settings.fields.'.$key).' — '.__('dashboard.common.name_ar')" />
                                            <x-text-input :id="$key.'_ar'" :name="'settings['.$key.'][ar]'" type="text" dir="rtl"
                                                class="mt-1 block w-full" :value="$current($key, 'ar')" />
                                            <x-input-error :messages="$errors->get('settings.'.$key.'.ar')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label :for="$key.'_en'" :value="__('dashboard.settings.fields.'.$key).' — '.__('dashboard.common.name_en')" />
                                            <x-text-input :id="$key.'_en'" :name="'settings['.$key.'][en]'" type="text"
                                                class="mt-1 block w-full" :value="$current($key, 'en')" />
                                            <x-input-error :messages="$errors->get('settings.'.$key.'.en')" class="mt-2" />
                                        </div>
                                    </div>

                                @elseif ($type === SettingsService::TYPE_IMAGE)
                                    <div>
                                        <x-input-label :for="$key" :value="__('dashboard.settings.fields.'.$key)" />
                                        @if ($values[$key] ?? null)
                                            <img src="{{ Illuminate\Support\Facades\Storage::url($values[$key]) }}"
                                                alt="{{ __('dashboard.settings.current_logo') }}" class="mb-2 mt-1 h-16 w-auto">
                                        @endif
                                        <input id="{{ $key }}" name="{{ $key }}" type="file" accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                            class="mt-1 block w-full text-sm text-text-main">
                                        <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.settings.logo_hint') }}</p>
                                        <x-input-error :messages="$errors->get($key)" class="mt-2" />
                                    </div>

                                @else
                                    <div>
                                        <x-input-label :for="$key" :value="__('dashboard.settings.fields.'.$key)" />
                                        <x-text-input
                                            :id="$key"
                                            :name="'settings['.$key.']'"
                                            :type="match ($type) {
                                                SettingsService::TYPE_EMAIL => 'email',
                                                SettingsService::TYPE_URL => 'url',
                                                SettingsService::TYPE_PHONE => 'tel',
                                                default => 'text',
                                            }"
                                            dir="{{ $type === SettingsService::TYPE_TEXT ? 'auto' : 'ltr' }}"
                                            class="mt-1 block w-full"
                                            :value="$current($key)"
                                        />
                                        @if ($group === 'social')
                                            <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.settings.optional_hint') }}</p>
                                        @endif
                                        <x-input-error :messages="$errors->get('settings.'.$key)" class="mt-2" />
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex justify-end">
                    <x-primary-button>{{ __('dashboard.settings.save') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
