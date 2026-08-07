<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('dashboard.testimonials.edit_heading') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('dashboard.testimonials.update', $testimonial) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @include('dashboard.testimonials._form')

                    <div class="flex items-center justify-end gap-4 mt-6">
                        <a href="{{ route('dashboard.testimonials.index') }}" class="text-sm text-gray-600 underline">{{ __('dashboard.common.cancel') }}</a>
                        <x-primary-button>{{ __('dashboard.testimonials.update_button') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
