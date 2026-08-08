<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="min-w-0 break-words font-semibold text-xl text-text-main leading-tight">
                {{ __('dashboard.articles.heading') }}
            </h2>
            <a href="{{ route('dashboard.articles.create') }}" class="inline-flex min-h-[44px] shrink-0 items-center whitespace-nowrap rounded-md border border-transparent bg-primary-green px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-dark-green focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2">
                {{ __('dashboard.articles.new') }}
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
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.title') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.slug') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.articles.published_column') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.articles.published_at_column') }}</th>
                            <th class="px-6 py-3 text-end text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-border-default">
                        @forelse ($articles as $article)
                            <tr>
                                <td data-label="{{ __('dashboard.common.title') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-main">{{ $article->title }}</td>
                                <td data-label="{{ __('dashboard.common.slug') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $article->slug }}</td>
                                <td data-label="{{ __('dashboard.articles.published_column') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $article->is_published ? __('dashboard.common.yes') : __('dashboard.common.no') }}</td>
                                <td data-label="{{ __('dashboard.articles.published_at_column') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $article->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm space-x-2 rtl:space-x-reverse">
                                    <a href="{{ route('dashboard.articles.edit', $article) }}" class="dash-action text-primary-green hover:text-dark-green">{{ __('dashboard.common.edit') }}</a>
                                    <form action="{{ route('dashboard.articles.destroy', $article) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('dashboard.articles.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dash-action text-red-600 hover:text-red-900">{{ __('dashboard.common.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-text-secondary">{{ __('dashboard.articles.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $articles->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
