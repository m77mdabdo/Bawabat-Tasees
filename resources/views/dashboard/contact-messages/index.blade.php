<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-text-main leading-tight">{{ __('dashboard.contact_messages.title') }}</h2>
            <p class="mt-1 text-sm text-text-secondary">{{ __('dashboard.contact_messages.subtitle') }}</p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('status') }}</div>
            @endif

            <nav class="flex flex-wrap gap-2" aria-label="{{ __('dashboard.contact_messages.title') }}">
                @foreach (['all', 'unhandled', 'handled'] as $option)
                    <a href="{{ route('dashboard.contact-messages.index', $option === 'all' ? [] : ['filter' => $option]) }}"
                        @if ($filter === $option) aria-current="page" @endif
                        class="rounded-full px-4 py-2 text-sm font-medium {{ $filter === $option ? 'bg-primary-green text-white' : 'bg-white text-text-secondary hover:text-text-main' }}">
                        {{ __('dashboard.contact_messages.filter_'.$option) }}
                    </a>
                @endforeach
            </nav>

            {{-- Message-first: the body is the reason this page exists, so
                 it leads and the metadata sits around it. Deliberately not
                 a table — the Leads section already has that view. --}}
            @forelse ($messages as $message)
                <article class="bg-white shadow-sm sm:rounded-lg p-6 {{ $message->is_handled ? '' : 'border-s-4 border-primary-green' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-text-main">{{ $message->full_name }}</h3>
                            <p class="mt-1 text-xs text-text-secondary">
                                <span dir="ltr">{{ $message->email }}</span>
                                @if ($message->phone)
                                    · <span dir="ltr">{{ $message->phone }}</span>
                                @endif
                            </p>
                        </div>

                        <div class="text-end">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $message->is_handled ? 'bg-bg-soft text-text-secondary' : 'bg-primary-green/10 text-primary-green' }}">
                                {{ $message->is_handled ? __('dashboard.contact_messages.handled') : __('dashboard.contact_messages.unhandled') }}
                            </span>
                            <p class="mt-1 text-xs text-text-secondary">{{ $message->created_at->translatedFormat('d M Y — H:i') }}</p>
                        </div>
                    </div>

                    <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-text-main">
                        {{ $message->message ?: __('dashboard.contact_messages.no_message') }}
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-4 border-t border-border-default pt-4 text-sm">
                        @if ($message->email)
                            {{-- rawurlencode: a subject line with & or # would
                                 otherwise truncate the mailto. --}}
                            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode(__('dashboard.contact_messages.title')) }}"
                                class="text-primary-green hover:text-dark-green">{{ __('dashboard.contact_messages.reply') }}</a>
                        @endif

                        <a href="{{ route('dashboard.leads.show', $message) }}" class="text-primary-green hover:text-dark-green">
                            {{ __('dashboard.contact_messages.view_lead') }}
                        </a>

                        @if ($message->source_platform)
                            <span class="text-xs text-text-secondary">
                                {{ __('dashboard.contact_messages.source') }}: <span dir="ltr">{{ $message->source_platform }}</span>
                            </span>
                        @endif

                        <form action="{{ route('dashboard.contact-messages.handled', $message) }}" method="POST" class="ms-auto">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="unhandle" value="{{ $message->is_handled ? 1 : 0 }}">
                            <button type="submit" class="min-h-[44px] px-3 text-primary-green hover:text-dark-green">
                                {{ $message->is_handled ? __('dashboard.contact_messages.mark_unhandled') : __('dashboard.contact_messages.mark_handled') }}
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-sm text-text-secondary">
                    {{ __('dashboard.contact_messages.empty') }}
                </div>
            @endforelse

            <div>{{ $messages->links() }}</div>
        </div>
    </div>
</x-app-layout>
