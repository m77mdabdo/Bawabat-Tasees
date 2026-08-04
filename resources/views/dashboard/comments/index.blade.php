<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-text-main leading-tight">
            {{ __('التعليقات') }}
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
                    <label for="status" class="block text-xs font-medium text-text-secondary">{{ __('الحالة') }}</label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('الكل') }}</option>
                        <option value="pending" @selected(request('status') === 'pending')>{{ __('قيد المراجعة') }}</option>
                        <option value="approved" @selected(request('status') === 'approved')>{{ __('معتمد') }}</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>{{ __('مرفوض') }}</option>
                    </select>
                </div>

                <div>
                    <label for="article_id" class="block text-xs font-medium text-text-secondary">{{ __('المقال') }}</label>
                    <select name="article_id" id="article_id" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('الكل') }}</option>
                        @foreach ($articles as $article)
                            <option value="{{ $article->id }}" @selected((string) request('article_id') === (string) $article->id)>{{ $article->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-3">
                    <button type="submit" class="rounded-md bg-primary-green px-4 py-2 text-sm font-semibold text-white hover:bg-primary-green/90">
                        {{ __('تصفية') }}
                    </button>
                    <a href="{{ route('dashboard.comments.index') }}" class="text-sm text-text-secondary hover:text-text-main">
                        {{ __('إعادة تعيين') }}
                    </a>
                </div>
            </form>
        </x-card>

        <x-card class="overflow-hidden">
            <table class="min-w-full divide-y divide-border-default">
                <thead class="bg-bg-soft">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('المقال') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('الاسم') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('البريد الإلكتروني') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('التعليق') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('الحالة') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('التاريخ') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-text-secondary uppercase">{{ __('إجراءات') }}</th>
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
                                    <span class="inline-flex items-center rounded-full bg-primary-green/10 px-2.5 py-0.5 text-xs font-semibold text-primary-green">{{ __('معتمد') }}</span>
                                @elseif ($comment->status === 'rejected')
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">{{ __('مرفوض') }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-luxury-gold/10 px-2.5 py-0.5 text-xs font-semibold text-luxury-gold">{{ __('قيد المراجعة') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $comment->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center gap-2">
                                    @if ($comment->status !== 'approved')
                                        <form action="{{ route('dashboard.comments.approve', $comment) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-primary-green hover:text-primary-green/70">{{ __('اعتماد') }}</button>
                                        </form>
                                    @endif
                                    @if ($comment->status !== 'rejected')
                                        <form action="{{ route('dashboard.comments.reject', $comment) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-luxury-gold hover:text-luxury-gold/70">{{ __('رفض') }}</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('dashboard.comments.destroy', $comment) }}" method="POST" onsubmit="return confirm('{{ __('حذف هذا التعليق نهائيًا؟') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">{{ __('حذف') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-text-secondary">{{ __('لا توجد تعليقات بعد.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        <div>
            {{ $comments->links() }}
        </div>
    </div>
</x-app-layout>
