<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('dashboard.media.heading') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-900 mb-4">{{ __('dashboard.media.upload_heading') }}</h3>
                <form method="POST" action="{{ route('dashboard.media.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="file" :value="__('dashboard.media.file_hint')" />
                        <input id="file" name="file" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" class="mt-1 block w-full text-sm text-gray-700" required>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="alt_text" :value="__('dashboard.media.alt_text')" />
                        <x-text-input id="alt_text" name="alt_text" type="text" class="mt-1 block w-full md:w-96" :value="old('alt_text')" />
                        <x-input-error :messages="$errors->get('alt_text')" class="mt-2" />
                    </div>

                    <x-primary-button>{{ __('dashboard.media.upload_button') }}</x-primary-button>
                </form>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @forelse ($media as $item)
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        @if ($item->type === 'video')
                            <div class="h-32 bg-gray-800 flex items-center justify-center text-white text-xs uppercase tracking-widest">
                                {{ __('dashboard.media.video_label') }}
                            </div>
                        @else
                            <img src="{{ Illuminate\Support\Facades\Storage::disk($item->disk)->url($item->path) }}" alt="{{ $item->alt_text }}" class="h-32 w-full object-cover">
                        @endif
                        <div class="p-2 text-xs text-gray-600 space-y-1">
                            <div class="truncate" title="{{ $item->alt_text }}">{{ $item->alt_text ?: __('dashboard.media.no_alt_text') }}</div>
                            <div class="text-gray-400">{{ strtoupper($item->type) }} · {{ number_format($item->size / 1024, 0) }} KB</div>
                            <form action="{{ route('dashboard.media.destroy', $item) }}" method="POST" onsubmit="return confirm('{{ __('dashboard.media.confirm_delete') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">{{ __('dashboard.common.delete') }}</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-sm text-gray-500 py-8">{{ __('dashboard.media.empty') }}</div>
                @endforelse
            </div>

            <div>
                {{ $media->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
