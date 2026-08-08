<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-text-main leading-tight">
                {{ __('dashboard.testimonials.heading') }}
            </h2>
            <a href="{{ route('dashboard.testimonials.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-green border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-dark-green">
                {{ __('dashboard.testimonials.new') }}
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
                <table class="dash-table-stacked min-w-full divide-y divide-border-default">
                    <thead class="bg-bg-soft">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.testimonials.client_column') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.testimonials.quote_column') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.active') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.sort_order') }}</th>
                            <th class="px-6 py-3 text-end text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-border-default">
                        @forelse ($testimonials as $testimonial)
                            <tr>
                                <td data-label="{{ __('dashboard.testimonials.client_column') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-main">{{ $testimonial->client_name }}</td>
                                <td data-label="{{ __('dashboard.testimonials.quote_column') }}" class="px-6 py-4 text-sm text-text-secondary">{{ Illuminate\Support\Str::limit($testimonial->quote, 60) }}</td>
                                <td data-label="{{ __('dashboard.common.active') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $testimonial->is_active ? __('dashboard.common.yes') : __('dashboard.common.no') }}</td>
                                <td data-label="{{ __('dashboard.common.sort_order') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $testimonial->sort_order }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm space-x-2 rtl:space-x-reverse">
                                    <a href="{{ route('dashboard.testimonials.edit', $testimonial) }}" class="dash-action text-primary-green hover:text-dark-green">{{ __('dashboard.common.edit') }}</a>
                                    <form action="{{ route('dashboard.testimonials.destroy', $testimonial) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('dashboard.testimonials.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dash-action text-red-600 hover:text-red-900">{{ __('dashboard.common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-text-secondary">{{ __('dashboard.testimonials.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $testimonials->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
