<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-text-main leading-tight">
            {{ __('dashboard.pages.heading') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mb-4 p-4 bg-blue-50 text-blue-800 rounded-md text-sm">
                {{ __('dashboard.pages.fixed_notice') }}
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border-default">
                    <thead class="bg-bg-soft">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.title') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.slug') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.pages.published_column') }}</th>
                            <th class="px-6 py-3 text-end text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-border-default">
                        @forelse ($pages as $page)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-main">{{ $page->title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $page->slug }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $page->is_published ? __('dashboard.common.yes') : __('dashboard.common.no') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm space-x-2 rtl:space-x-reverse">
                                    <a href="{{ route('dashboard.pages.sections.index', $page) }}" class="text-text-secondary hover:text-text-main">{{ __('dashboard.pages.sections_link') }}</a>
                                    <a href="{{ route('dashboard.pages.edit', $page) }}" class="text-primary-green hover:text-dark-green">{{ __('dashboard.common.edit') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-text-secondary">{{ __('dashboard.pages.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
