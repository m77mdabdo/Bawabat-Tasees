<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-text-main leading-tight">
            {{ __('dashboard.leads.heading') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-md bg-primary-green/5 border border-primary-green/30 p-4 text-primary-green">
                {{ session('status') }}
            </div>
        @endif

        <x-card class="p-4">
            {{--
                lg:grid-cols-3 (not straight to 5) — the dashboard
                sidebar becomes permanently visible at exactly this
                same `lg` breakpoint (1024px), so the content area
                shrinks by 288px right when this grid would otherwise
                jump to 5 columns, leaving each filter field only
                ~130px wide. xl:grid-cols-5 (1280px) gives the full
                5-column row room to breathe.
            --}}
            <form method="GET" action="{{ route('dashboard.leads.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <div>
                    <label for="type" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.leads.type') }}</label>
                    <select name="type" id="type" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('dashboard.leads.all') }}</option>
                        <option value="consultation" @selected(request('type') === 'consultation')>{{ __('dashboard.leads.type_consultation') }}</option>
                        <option value="contact" @selected(request('type') === 'contact')>{{ __('dashboard.leads.type_contact') }}</option>
                    </select>
                </div>

                <div>
                    <label for="source_platform" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.leads.source') }}</label>
                    <select name="source_platform" id="source_platform" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('dashboard.leads.all') }}</option>
                        @foreach ($sourcePlatforms as $platform)
                            <option value="{{ $platform }}" @selected(request('source_platform') === $platform)>{{ $platform }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="requested_service_id" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.leads.requested_service') }}</label>
                    <select name="requested_service_id" id="requested_service_id" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                        <option value="">{{ __('dashboard.leads.all') }}</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) request('requested_service_id') === (string) $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.leads.date_from') }}</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                </div>

                <div>
                    <label for="date_to" class="block text-xs font-medium text-text-secondary">{{ __('dashboard.leads.date_to') }}</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border-border-default text-sm focus:border-primary-green focus:ring-primary-green">
                </div>

                <div class="lg:col-span-3 xl:col-span-5 flex items-center gap-3">
                    <button type="submit" class="rounded-md bg-primary-green px-4 py-2 text-sm font-semibold text-white hover:bg-primary-green/90">
                        {{ __('dashboard.leads.filter') }}
                    </button>
                    <a href="{{ route('dashboard.leads.index') }}" class="text-sm text-text-secondary hover:text-text-main">
                        {{ __('dashboard.leads.reset') }}
                    </a>
                </div>
            </form>
        </x-card>

        <x-card class="overflow-hidden">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border-default">
                <thead class="bg-bg-soft">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.leads.name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.leads.phone') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.leads.requested_service') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.leads.source') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.leads.campaign') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.leads.received_at') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.leads.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default bg-white">
                    @forelse ($leads as $lead)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-main">{{ $lead->full_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $lead->phone }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $lead->requestedService?->name ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $lead->source_platform ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $lead->campaign_name ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $lead->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-end text-sm space-x-2 rtl:space-x-reverse">
                                <a href="{{ route('dashboard.leads.show', $lead) }}" class="text-primary-green hover:text-primary-green/70">{{ __('dashboard.leads.view') }}</a>
                                <form action="{{ route('dashboard.leads.destroy', $lead) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('dashboard.leads.confirm_archive') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">{{ __('dashboard.leads.archive') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-text-secondary">{{ __('dashboard.leads.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </x-card>

        <div>
            {{ $leads->links() }}
        </div>
    </div>
</x-app-layout>
