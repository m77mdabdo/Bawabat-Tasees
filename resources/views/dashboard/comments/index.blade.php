<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-text-main leading-tight">
            {{ __('dashboard.comments.heading') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-md bg-primary-green/5 border border-primary-green/30 p-4 text-primary-green">
                {{ session('status') }}
            </div>
        @endif

        <x-card class="p-4">
            <form method="GET" action="{{ route('dashboard.comments.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="status" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.comments.status') }}</label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('dashboard.comments.all') }}</option>
                        <option value="pending" @selected(request('status') === 'pending')>{{ __('dashboard.comments.status_pending') }}</option>
                        <option value="approved" @selected(request('status') === 'approved')>{{ __('dashboard.comments.status_approved') }}</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>{{ __('dashboard.comments.status_rejected') }}</option>
                    </select>
                </div>

                <div>
                    <label for="article_id" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.comments.article') }}</label>
                    <select name="article_id" id="article_id" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('dashboard.comments.all') }}</option>
                        @foreach ($articles as $article)
                            <option value="{{ $article->id }}" @selected((string) request('article_id') === (string) $article->id)>{{ $article->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-3">
                    <button type="submit" class="rounded-md bg-primary-green px-4 py-2 text-sm font-semibold text-white hover:bg-primary-green/90">
                        {{ __('dashboard.comments.filter') }}
                    </button>
                    <a href="{{ route('dashboard.comments.index') }}" class="text-sm text-text-secondary hover:text-text-main">
                        {{ __('dashboard.comments.reset') }}
                    </a>
                </div>
            </form>
        </x-card>

        <x-card class="overflow-hidden">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border-default">
                <thead class="bg-bg-soft">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.comments.article') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.comments.name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.comments.email') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.comments.comment') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.comments.status') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.comments.date') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.comments.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default bg-white">
                    @forelse ($comments as $comment)
                        <tr>
                            <td class="px-6 py-4 text-sm text-text-secondary">{{ $comment->article?->title ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-main">{{ $comment->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $comment->email }}</td>
                            <td class="px-6 py-4 text-sm text-text-secondary">{{ Illuminate\Support\Str::limit($comment->body, 80) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if ($comment->status === 'approved')
                                    <span class="inline-flex items-center rounded-full bg-primary-green/10 px-2.5 py-0.5 text-xs font-semibold text-primary-green">{{ __('dashboard.comments.status_approved') }}</span>
                                @elseif ($comment->status === 'rejected')
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">{{ __('dashboard.comments.status_rejected') }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-luxury-gold/10 px-2.5 py-0.5 text-xs font-semibold text-luxury-gold">{{ __('dashboard.comments.status_pending') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $comment->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center gap-2">
                                    @if ($comment->status !== 'approved')
                                        <form action="{{ route('dashboard.comments.approve', $comment) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-primary-green hover:text-primary-green/70">{{ __('dashboard.comments.approve') }}</button>
                                        </form>
                                    @endif
                                    @if ($comment->status !== 'rejected')
                                        <form action="{{ route('dashboard.comments.reject', $comment) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-luxury-gold hover:text-luxury-gold/70">{{ __('dashboard.comments.reject') }}</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('dashboard.comments.destroy', $comment) }}" method="POST" onsubmit="return confirm('{{ __('dashboard.comments.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">{{ __('dashboard.comments.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-text-secondary">{{ __('dashboard.comments.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </x-card>

        <div>
            {{ $comments->links() }}
        </div>
    </div>
</x-app-layout>
