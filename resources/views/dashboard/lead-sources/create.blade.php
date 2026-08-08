<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-text-main leading-tight">{{ __('dashboard.lead_sources.add') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('dashboard.lead-sources.store') }}">
                    @csrf
                    @include('dashboard.lead-sources._form')
                    <div class="mt-6 flex flex-wrap items-center justify-end gap-4">
                        <a href="{{ route('dashboard.lead-sources.index') }}" class="dash-action text-sm text-text-secondary underline hover:text-text-main">{{ __('dashboard.common.cancel') }}</a>
                        <x-primary-button>{{ __('dashboard.lead_sources.create_button') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
