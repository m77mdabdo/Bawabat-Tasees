<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Media Library') }}
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
                <h3 class="font-medium text-gray-900 mb-4">{{ __('Upload New Media') }}</h3>
                <form method="POST" action="{{ route('dashboard.media.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="file" :value="__('File — image: jpeg/png/webp max 5MB, video: mp4/webm max 100MB')" />
                        <input id="file" name="file" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" class="mt-1 block w-full text-sm text-gray-700" required>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="alt_text" :value="__('Alt Text')" />
                        <x-text-input id="alt_text" name="alt_text" type="text" class="mt-1 block w-full md:w-96" :value="old('alt_text')" />
                        <x-input-error :messages="$errors->get('alt_text')" class="mt-2" />
                    </div>

                    <x-primary-button>{{ __('Upload') }}</x-primary-button>
                </form>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @forelse ($media as $item)
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        @if ($item->type === 'video')
                            <div class="h-32 bg-gray-800 flex items-center justify-center text-white text-xs uppercase tracking-widest">
                                {{ __('Video') }}
                            </div>
                        @else
                            <img src="{{ Illuminate\Support\Facades\Storage::disk($item->disk)->url($item->path) }}" alt="{{ $item->alt_text }}" class="h-32 w-full object-cover">
                        @endif
                        <div class="p-2 text-xs text-gray-600 space-y-1">
                            <div class="truncate" title="{{ $item->alt_text }}">{{ $item->alt_text ?: __('No alt text') }}</div>
                            <div class="text-gray-400">{{ strtoupper($item->type) }} · {{ number_format($item->size / 1024, 0) }} KB</div>
                            <form action="{{ route('dashboard.media.destroy', $item) }}" method="POST" onsubmit="return confirm('{{ __('Delete this media item?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-sm text-gray-500 py-8">{{ __('No media uploaded yet.') }}</div>
                @endforelse
            </div>

            <div>
                {{ $media->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
