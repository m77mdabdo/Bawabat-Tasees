<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-text-main leading-tight">{{ __('dashboard.campaigns.title') }}</h2>
            <a href="{{ route('dashboard.campaigns.create') }}" class="inline-flex min-h-[44px] shrink-0 items-center whitespace-nowrap rounded-md border border-transparent bg-primary-green px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-dark-green focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2">
                {{ __('dashboard.campaigns.add') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="dash-table-stacked min-w-full divide-y divide-border-default">
                        <thead class="bg-bg-soft">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.campaigns.name') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.campaigns.platform') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.campaigns.spend') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.campaigns.leads_count') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.campaigns.conversion_value') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.active') }}</th>
                                <th class="px-6 py-3 text-end text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-border-default">
                            @forelse ($campaigns as $campaign)
                                <tr>
                                    <td data-label="{{ __('dashboard.campaigns.name') }}" class="px-6 py-4 text-sm text-text-main">
                                        <a href="{{ route('dashboard.campaigns.show', $campaign) }}" class="font-medium text-primary-green hover:underline">{{ $campaign->name }}</a>
                                        @if ($campaign->external_campaign_id)
                                            <span class="block text-xs text-text-secondary" dir="ltr">{{ $campaign->external_campaign_id }}</span>
                                        @endif
                                    </td>
                                    <td data-label="{{ __('dashboard.campaigns.platform') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                        {{ $campaign->platform ? __('dashboard.campaigns.platforms.'.$campaign->platform) : '—' }}
                                    </td>
                                    <td data-label="{{ __('dashboard.campaigns.spend') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary" dir="ltr">
                                        {{ $campaign->spend !== null ? number_format((float) $campaign->spend, 2).' '.$campaign->currency : '—' }}
                                    </td>
                                    <td data-label="{{ __('dashboard.campaigns.leads_count') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $campaign->leads_count }}</td>
                                    <td data-label="{{ __('dashboard.campaigns.conversion_value') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary" dir="ltr">
                                        {{ number_format((float) $campaign->conversion_value_sum, 2) }} {{ $campaign->currency }}
                                    </td>
                                    <td data-label="{{ __('dashboard.common.active') }}" class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">
                                        {{ $campaign->is_active ? __('dashboard.common.yes') : __('dashboard.common.no') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-end text-sm space-x-2 rtl:space-x-reverse">
                                        <a href="{{ route('dashboard.campaigns.edit', $campaign) }}" class="dash-action text-primary-green hover:text-dark-green">{{ __('dashboard.common.edit') }}</a>
                                        <form action="{{ route('dashboard.campaigns.destroy', $campaign) }}" method="POST" class="inline"
                                            onsubmit="return confirm('{{ __('dashboard.campaigns.confirm_delete') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dash-action text-red-600 hover:text-red-900">{{ __('dashboard.common.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-text-secondary">{{ __('dashboard.campaigns.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">{{ $campaigns->links() }}</div>
        </div>
    </div>
</x-app-layout>
