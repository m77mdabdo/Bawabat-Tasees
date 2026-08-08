<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-text-main leading-tight">{{ __('dashboard.lead_sources.title') }}</h2>
                <p class="mt-1 text-sm text-text-secondary">{{ __('dashboard.lead_sources.subtitle') }}</p>
            </div>
            <a href="{{ route('dashboard.lead-sources.create') }}" class="inline-flex items-center px-4 py-2 bg-primary-green border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-dark-green">
                {{ __('dashboard.lead_sources.add') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-default">
                        <thead class="bg-bg-soft">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.lead_sources.key') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.lead_sources.label') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.lead_sources.leads_count') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.active') }}</th>
                                <th class="px-6 py-3 text-end text-xs font-medium text-text-secondary uppercase">{{ __('dashboard.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-border-default">
                            @forelse ($sources as $source)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-main">
                                        <span dir="ltr" class="font-mono">{{ $source->key }}</span>
                                        @if ($source->is_system)
                                            <span class="ms-2 inline-flex items-center rounded-full bg-bg-soft px-2 py-0.5 text-xs font-medium text-gold-deep">{{ __('dashboard.lead_sources.system_badge') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $source->label }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $leadCounts[$source->key] ?? 0 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary">{{ $source->is_active ? __('dashboard.common.yes') : __('dashboard.common.no') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-end text-sm space-x-2 rtl:space-x-reverse">
                                        <a href="{{ route('dashboard.lead-sources.edit', $source) }}" class="text-primary-green hover:text-dark-green">{{ __('dashboard.common.edit') }}</a>

                                        <form action="{{ route('dashboard.lead-sources.active', $source) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-primary-green hover:text-dark-green">
                                                {{ $source->is_active ? __('dashboard.lead_sources.deactivate') : __('dashboard.lead_sources.activate') }}
                                            </button>
                                        </form>

                                        {{-- A seeded source is what AttributionService matches the
                                             standard ad platforms against, so its delete prompt spells
                                             out the consequence instead of the generic one. --}}
                                        <form action="{{ route('dashboard.lead-sources.destroy', $source) }}" method="POST" class="inline"
                                            onsubmit="return confirm('{{ $source->is_system ? __('dashboard.lead_sources.confirm_delete_system') : __('dashboard.lead_sources.confirm_delete') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">{{ __('dashboard.common.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-text-secondary">{{ __('dashboard.lead_sources.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- utm_source values that arrived but resolve to no source. These
                 are already stored on the leads; showing them turns an
                 invisible gap into an actionable one. --}}
            @if ($unrecognised !== [])
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.lead_sources.unrecognised_heading') }}</h3>
                    <p class="mt-1 text-xs text-text-secondary">{{ __('dashboard.lead_sources.unrecognised_hint') }}</p>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($unrecognised as $value => $count)
                            <li class="inline-flex items-center gap-2 rounded-full border border-border-default px-3 py-1 text-xs text-text-secondary">
                                <span dir="ltr" class="font-mono text-text-main">{{ $value }}</span>
                                <span>{{ $count }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
