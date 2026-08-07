<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('dashboard.pages.sections_link') }} — {{ $page->title }}
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard.pages.edit', $page) }}" class="text-sm text-gray-600 underline">{{ __('dashboard.pages.back_to_page') }}</a>
                <a href="{{ route('dashboard.pages.sections.create', $page) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
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
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('dashboard.sections.order_column') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('dashboard.sections.key_column') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('dashboard.common.title') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('dashboard.common.active') }}</th>
                            <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('dashboard.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($sections as $section)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $section->sort_order }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $section->key }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $section->title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $section->is_active ? __('dashboard.common.yes') : __('dashboard.common.no') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm space-x-2 rtl:space-x-reverse">
                                    <a href="{{ route('dashboard.pages.sections.edit', [$page, $section]) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('dashboard.common.edit') }}</a>
                                    <form action="{{ route('dashboard.pages.sections.destroy', [$page, $section]) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('dashboard.sections.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">{{ __('dashboard.common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">{{ __('dashboard.sections.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
