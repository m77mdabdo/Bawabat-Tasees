<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-text-main leading-tight">
                {{ __('dashboard.pages.sections_link') }} — {{ $page->title }}
            </h2>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('dashboard.pages.edit', $page) }}" class="dash-action text-sm text-text-secondary underline hover:text-text-main">{{ __('dashboard.pages.back_to_page') }}</a>
                <a href="{{ route('dashboard.pages.sections.create', $page) }}" class="inline-flex min-h-[44px] shrink-0 items-center whitespace-nowrap rounded-md border border-transparent bg-primary-green px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-dark-green focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2">
                    {{ __('dashboard.sections.new') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                <table class="dash-table-stacked min-w-full divide-y divide-border-default">
                    <thead class="bg-bg-soft">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.sections.order_column') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.sections.key_column') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.title') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.active') }}</th>
                            <th class="px-6 py-3 text-end text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-border-default">
                        @forelse ($sections as $section)
                            <tr>
                                <td data-label="{{ __('dashboard.sections.order_column') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $section->sort_order }}</td>
                                <td data-label="{{ __('dashboard.sections.key_column') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $section->key }}</td>
                                <td data-label="{{ __('dashboard.common.title') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-main">{{ $section->title }}</td>
                                <td data-label="{{ __('dashboard.common.active') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $section->is_active ? __('dashboard.common.yes') : __('dashboard.common.no') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm space-x-2 rtl:space-x-reverse">
                                    <a href="{{ route('dashboard.pages.sections.edit', [$page, $section]) }}" class="dash-action text-primary-green hover:text-dark-green">{{ __('dashboard.common.edit') }}</a>
                                    <form action="{{ route('dashboard.pages.sections.destroy', [$page, $section]) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('dashboard.sections.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dash-action text-red-600 hover:text-red-900">{{ __('dashboard.common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-text-secondary">{{ __('dashboard.sections.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
