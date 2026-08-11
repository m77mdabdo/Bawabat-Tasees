<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="min-w-0 break-words font-semibold text-xl text-text-main leading-tight">
                    {{ __('dashboard.visitor_sessions.journey') }}
                </h2>
                <p class="mt-1 text-sm text-text-secondary">
                    {{ $session->started_at?->translatedFormat('d M Y — H:i') }}
                    · {{ $session->durationForHumans() }}
                    · {{ trans_choice('dashboard.visitor_sessions.page_count', $session->page_count, ['count' => number_format($session->page_count)]) }}
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap gap-3">
                <a href="{{ route('dashboard.visitor-sessions.export-one', $session) }}"
                    class="dash-action rounded-md border border-primary-green px-4 py-2 text-sm font-semibold text-primary-green hover:bg-primary-green/5">
                    {{ __('dashboard.visitor_sessions.export_one') }}
                </a>
                <a href="{{ route('dashboard.visitor-sessions.index') }}" class="dash-action text-sm text-text-secondary hover:text-text-main">
                    {{ __('dashboard.visitor_sessions.back') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- The timeline is the point of this page, so it gets two thirds at
             xl and the whole width below it. --}}
        <div class="xl:col-span-2 space-y-4">
            @if ($session->isTruncated())
                <x-flash
                    type="error"
                    :message="__('dashboard.visitor_sessions.journey_truncated', ['limit' => \App\Models\VisitorSession::MAX_EVENTS])"
                />
            @endif

            <x-card class="p-4 sm:p-6">
                <ol class="relative space-y-0">
                    @foreach ($journey as $index => $event)
                        @php
                            $isAction = ($event['type'] ?? null) === \App\Models\VisitorSession::TYPE_ACTION;
                            $isLast = $index === count($journey) - 1;
                            $timestamp = \Illuminate\Support\Carbon::parse($event['ts']);
                        @endphp

                        <li class="relative flex gap-4 pb-6 last:pb-0">
                            {{-- The connecting rail. Drawn on every step but
                                 the last, so the line ends at the final dot
                                 instead of trailing into nothing.

                                 start-[11px] not left: this flips with the
                                 dashboard's direction, and a timeline rail on
                                 the wrong side in Arabic would sit across the
                                 text. --}}
                            @unless ($isLast)
                                <span class="absolute top-6 bottom-0 start-[11px] w-px bg-border-default" aria-hidden="true"></span>
                            @endunless

                            <span @class([
                                'relative z-10 mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full ring-4 ring-white',
                                'bg-luxury-gold' => $isAction,
                                'bg-primary-green/15' => ! $isAction,
                            ])>
                                @if ($isAction)
                                    <svg class="h-3.5 w-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                        <path d="M5 13l4 4L19 7" />
                                    </svg>
                                @else
                                    <span class="h-2 w-2 rounded-full bg-primary-green" aria-hidden="true"></span>
                                @endif
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide {{ $isAction ? 'text-gold-deep' : 'text-text-secondary' }}">
                                        {{ $isAction ? __('dashboard.visitor_sessions.action') : __('dashboard.visitor_sessions.pageview') }}
                                    </span>
                                    <span class="text-xs tabular-nums text-text-secondary">{{ $timestamp->translatedFormat('H:i:s') }}</span>
                                </div>

                                @if ($isAction && ! empty($event['label']))
                                    <p class="mt-1 text-sm font-semibold text-text-main">{{ $event['label'] }}</p>
                                @endif

                                <p class="mt-1 break-all text-sm text-text-main" dir="ltr">{{ $event['url'] ?? '—' }}</p>

                                <p class="mt-1 text-xs text-text-secondary">
                                    {{ __('dashboard.visitor_sessions.time_on_page') }}:
                                    @if (! empty($event['duration_ms']))
                                        <span class="tabular-nums">{{ number_format($event['duration_ms'] / 1000, 1) }}s</span>
                                    @else
                                        {{ __('dashboard.visitor_sessions.no_duration') }}
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach

                    @if ($journey === [])
                        <li class="py-6 text-center text-sm text-text-secondary">{{ __('dashboard.visitor_sessions.empty') }}</li>
                    @endif
                </ol>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card class="p-4 sm:p-6">
                <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.visitor_sessions.session_details') }}</h3>

                <dl class="mt-4 space-y-3 text-sm">
                    @foreach ([
                        'entry_page' => $session->entry_url,
                        'referrer' => $session->referrer,
                        'device' => $session->device_type ? __('dashboard.visitor_sessions.device_'.$session->device_type) : null,
                        'country' => $session->country,
                    ] as $key => $value)
                        <div class="flex flex-wrap justify-between gap-2">
                            <dt class="text-text-secondary">{{ __('dashboard.visitor_sessions.'.$key) }}</dt>
                            <dd class="min-w-0 break-all text-end text-text-main" dir="auto">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach

                    @if ($session->browser || $session->platform)
                        <div class="flex flex-wrap justify-between gap-2">
                            <dt class="text-text-secondary">{{ $session->browser ?? '—' }}</dt>
                            <dd class="text-end text-text-main">{{ $session->platform ?? '—' }}</dd>
                        </div>
                    @endif

                    <div class="flex flex-wrap justify-between gap-2 border-t border-border-default pt-3">
                        <dt class="text-text-secondary">
                            {{ __('dashboard.visitor_sessions.ip_prefix') }}
                            <span class="block text-xs text-text-secondary/70">{{ __('dashboard.visitor_sessions.ip_prefix_hint') }}</span>
                        </dt>
                        <dd class="text-end tabular-nums text-text-main" dir="ltr">{{ $session->ip_prefix ?? '—' }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card class="p-4 sm:p-6">
                <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.visitor_sessions.lead') }}</h3>

                @if ($session->lead)
                    <p class="mt-3 text-sm text-text-main">{{ $session->lead->full_name }}</p>
                    <a href="{{ route('dashboard.leads.show', $session->lead) }}" class="dash-action mt-2 text-sm text-primary-green hover:text-dark-green">
                        {{ __('dashboard.visitor_sessions.view_lead') }}
                    </a>
                @else
                    <p class="mt-3 text-sm text-text-secondary">{{ __('dashboard.visitor_sessions.no_lead') }}</p>
                @endif
            </x-card>

            <x-card class="p-4 sm:p-6">
                <h3 class="text-sm font-semibold text-text-main">{{ __('dashboard.visitor_sessions.attribution') }}</h3>

                @php $attribution = $session->attribution ?? []; @endphp

                @if ($attribution === [])
                    <p class="mt-3 text-sm text-text-secondary">{{ __('dashboard.visitor_sessions.no_attribution') }}</p>
                @else
                    @foreach (['first_touch', 'latest_touch'] as $touch)
                        @if (! empty($attribution[$touch]) && is_array($attribution[$touch]))
                            <div class="mt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-text-secondary">
                                    {{ __('dashboard.visitor_sessions.'.$touch) }}
                                </p>
                                <dl class="mt-2 space-y-1 text-sm">
                                    @foreach ($attribution[$touch] as $key => $value)
                                        @if (is_scalar($value) && filled($value))
                                            <div class="flex flex-wrap justify-between gap-2">
                                                <dt class="text-text-secondary" dir="ltr">{{ $key }}</dt>
                                                <dd class="min-w-0 break-all text-end text-text-main" dir="ltr">{{ $value }}</dd>
                                            </div>
                                        @endif
                                    @endforeach
                                </dl>
                            </div>
                        @endif
                    @endforeach
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
