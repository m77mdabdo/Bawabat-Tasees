<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('dashboard.faqs.heading') }}
            </h2>
            <a href="{{ route('dashboard.faqs.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('dashboard.faqs.new') }}
            </a>
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
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('dashboard.faqs.question_column') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('dashboard.common.active') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('dashboard.common.sort_order') }}</th>
                            <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('dashboard.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($faqs as $faq)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $faq->question }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $faq->is_active ? __('dashboard.common.yes') : __('dashboard.common.no') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $faq->sort_order }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm space-x-2 rtl:space-x-reverse">
                                    <a href="{{ route('dashboard.faqs.edit', $faq) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('dashboard.common.edit') }}</a>
                                    <form action="{{ route('dashboard.faqs.destroy', $faq) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('dashboard.faqs.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">{{ __('dashboard.common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">{{ __('dashboard.faqs.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $faqs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
