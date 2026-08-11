@php
    // Shared by the table and the export link, so a filtered export always
    // carries the same query string the table was built from.
    $currentFilters = request()->only(['converted', 'source', 'device', 'date_from', 'date_to']);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="min-w-0 break-words font-semibold text-xl text-text-main leading-tight">{{ __('dashboard.visitor_sessions.title') }}</h2>
            <p class="mt-1 text-sm text-text-secondary">{{ __('dashboard.visitor_sessions.subtitle') }}</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-flash />

        {{-- An empty table with no explanation reads as a broken feature.
             These two notices are the difference between "nothing happened"
             and "here is why nothing happened". --}}
        @unless ($trackingEnabled)
            <x-flash type="error" :message="__('dashboard.visitor_sessions.disabled_notice')" />
        @endunless

        <p class="text-xs leading-relaxed text-text-secondary">
            {{ __('dashboard.visitor_sessions.consent_notice', ['days' => $retentionDays]) }}
        </p>

        <x-card class="p-4">
            {{-- lg:grid-cols-3 before xl:grid-cols-5, matching the Leads
                 filter bar: the sidebar becomes permanent at exactly `lg`,
                 so a 5-column row there would leave each field ~130px. --}}
            <form method="GET" action="{{ route('dashboard.visitor-sessions.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <div>
                    <label for="converted" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.visitor_sessions.conversion_filter') }}</label>
                    <select name="converted" id="converted" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('dashboard.visitor_sessions.all') }}</option>
                        <option value="converted" @selected(request('converted') === 'converted')>{{ __('dashboard.visitor_sessions.converted') }}</option>
                        <option value="not_converted" @selected(request('converted') === 'not_converted')>{{ __('dashboard.visitor_sessions.not_converted') }}</option>
                    </select>
                </div>

                <div>
                    <label for="source" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.visitor_sessions.source') }}</label>
                    <select name="source" id="source" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('dashboard.visitor_sessions.all') }}</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->key }}" @selected(request('source') === $source->key)>{{ $source->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="device" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.visitor_sessions.device') }}</label>
                    <select name="device" id="device" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('dashboard.visitor_sessions.all') }}</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device }}" @selected(request('device') === $device)>{{ __('dashboard.visitor_sessions.device_'.$device) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.visitor_sessions.date_from') }}</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                </div>

                <div>
                    <label for="date_to" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.visitor_sessions.date_to') }}</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                </div>

                <div class="lg:col-span-3 xl:col-span-5 flex flex-wrap items-center gap-3">
                    <button type="submit" class="rounded-md bg-primary-green px-4 py-2 text-sm font-semibold text-white hover:bg-primary-green/90">
                        {{ __('dashboard.visitor_sessions.filter') }}
                    </button>
                    <a href="{{ route('dashboard.visitor-sessions.index') }}" class="dash-action text-sm text-text-secondary hover:text-text-main">
                        {{ __('dashboard.visitor_sessions.reset') }}
                    </a>

                    {{-- Carries the CURRENT filters, so the file always
                         matches the table it was exported from. --}}
                    <a href="{{ route('dashboard.visitor-sessions.export', $currentFilters) }}"
                        class="dash-action ms-auto rounded-md border border-primary-green px-4 py-2 text-sm font-semibold text-primary-green hover:bg-primary-green/5">
                        {{ __('dashboard.visitor_sessions.export') }}
                    </a>
                </div>
            </form>

            <p class="mt-3 text-xs text-text-secondary">
                {{ __('dashboard.visitor_sessions.export_limit_notice', ['limit' => number_format($exportLimit)]) }}
            </p>
        </x-card>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="overflow-x-auto">
                {{-- dash-table-stacked: below md this collapses to one card
                     per row, each cell printing its own data-label. See the
                     rules in app.css. --}}
                <table class="dash-table-stacked min-w-full divide-y divide-border-default">
                    <thead class="bg-bg-soft">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.visitor_sessions.started_at') }}</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.visitor_sessions.entry_page') }}</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.visitor_sessions.pages') }}</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.visitor_sessions.duration') }}</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.visitor_sessions.device') }}</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.visitor_sessions.source') }}</th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium uppercase text-text-secondary">{{ __('dashboard.visitor_sessions.converted') }}</th>
                            <th scope="col" class="px-6 py-3"><span class="sr-only">{{ __('dashboard.common.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default bg-white">
                        @forelse ($sessions as $session)
                            <tr>
                                <td data-label="{{ __('dashboard.visitor_sessions.started_at') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-main">
                                    {{ $session->started_at?->translatedFormat('d M Y — H:i') }}
                                </td>
                                <td data-label="{{ __('dashboard.visitor_sessions.entry_page') }}" class="px-6 py-4 text-sm text-text-secondary">
                                    <span dir="ltr" class="break-all">{{ $session->entry_url }}</span>
                                </td>
                                <td data-label="{{ __('dashboard.visitor_sessions.pages') }}" class="px-6 py-4 whitespace-nowrap text-sm tabular-nums text-text-main">
                                    {{ number_format($session->page_count) }}
                                </td>
                                <td data-label="{{ __('dashboard.visitor_sessions.duration') }}" class="px-6 py-4 whitespace-nowrap text-sm tabular-nums text-text-secondary">
                                    {{ $session->durationForHumans() }}
                                </td>
                                <td data-label="{{ __('dashboard.visitor_sessions.device') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                    {{ $session->device_type ? __('dashboard.visitor_sessions.device_'.$session->device_type) : '—' }}
                                    @if ($session->browser)
                                        <span class="text-xs text-text-secondary/70">· {{ $session->browser }}</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('dashboard.visitor_sessions.source') }}" class="px-6 py-4 text-sm text-text-secondary">
                                    <span dir="ltr">{{ $session->sourcePlatform() ?? '—' }}</span>
                                    @if ($session->campaignName())
                                        <span class="block text-xs text-text-secondary/70" dir="ltr">{{ $session->campaignName() }}</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('dashboard.visitor_sessions.converted') }}" class="px-6 py-4 whitespace-nowrap">
                                    @if ($session->hasConverted())
                                        <span class="inline-flex items-center rounded-full bg-primary-green/10 px-2.5 py-0.5 text-xs font-semibold text-primary-green">
                                            {{ __('dashboard.visitor_sessions.converted') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-text-secondary">{{ __('dashboard.visitor_sessions.not_converted') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="{{ route('dashboard.visitor-sessions.show', $session) }}" class="dash-action text-primary-green hover:text-dark-green">
                                        {{ __('dashboard.visitor_sessions.view') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-6 text-center text-sm text-text-secondary">
                                    {{ __('dashboard.visitor_sessions.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $sessions->links() }}</div>
    </div>
</x-app-layout>
