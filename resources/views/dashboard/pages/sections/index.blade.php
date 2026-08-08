<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-text-main leading-tight">
                {{ __('dashboard.pages.sections_link') }} — {{ $page->title }}
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard.pages.edit', $page) }}" class="text-sm text-text-secondary underline">{{ __('dashboard.pages.back_to_page') }}</a>
                <a href="{{ route('dashboard.pages.sections.create', $page) }}" class="inline-flex items-center px-4 py-2 bg-primary-green border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-dark-green">
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
                <table class="min-w-full divide-y divide-border-default">
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $section->sort_order }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $section->key }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-main">{{ $section->title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $section->is_active ? __('dashboard.common.yes') : __('dashboard.common.no') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm space-x-2 rtl:space-x-reverse">
                                    <a href="{{ route('dashboard.pages.sections.edit', [$page, $section]) }}" class="text-primary-green hover:text-dark-green">{{ __('dashboard.common.edit') }}</a>
                                    <form action="{{ route('dashboard.pages.sections.destroy', [$page, $section]) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('dashboard.sections.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">{{ __('dashboard.common.delete') }}</button>
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
