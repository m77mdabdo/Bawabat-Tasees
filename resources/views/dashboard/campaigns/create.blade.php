<x-app-layout>
    <x-slot name="header">
        <h2 class="min-w-0 break-words font-semibold text-xl text-text-main leading-tight">{{ __('dashboard.campaigns.add') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('dashboard.campaigns.store') }}">
                    @csrf
                    @include('dashboard.campaigns._form')
                    <div class="mt-6 flex flex-wrap items-center justify-end gap-4">
                        <a href="{{ route('dashboard.campaigns.index') }}" class="dash-action text-sm text-text-secondary underline hover:text-text-main">{{ __('dashboard.common.cancel') }}</a>
                        <x-primary-button>{{ __('dashboard.campaigns.create_button') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
