{{-- Timeline of one visitor session: page views and actions (form submissions). Values are escaped. --}}
@php($session = $getRecord())
@php($journey = $session->journey())
@if ($session->isTruncated())
    <p style="font-size:.8rem;color:#8A6A22;margin:0 0 .75rem">{{ __('dashboard.visitor_sessions.journey_truncated', ['limit' => \App\Models\VisitorSession::MAX_EVENTS]) }}</p>
@endif
<ol class="bt-timeline">
    @forelse ($journey as $event)
        <li class="{{ $event['type'] === 'action' ? 'is-action' : '' }}">
            <time>{{ \Illuminate\Support\Carbon::parse($event['ts'])->timezone('Asia/Riyadh')->format('H:i:s') }}</time>
            · <strong>{{ $event['type'] === 'action' ? __('dashboard.visitor_sessions.action') : __('dashboard.visitor_sessions.pageview') }}</strong>
            @if (! empty($event['label'])) · {{ $event['label'] }} @endif
            <div class="bt-url" dir="ltr">{{ $event['url'] ?? '' }}</div>
            @if (! empty($event['duration_ms']))
                <div style="font-size:.75rem;color:#66706A">{{ __('dashboard.visitor_sessions.time_on_page') }}: {{ round($event['duration_ms'] / 1000) }}s</div>
            @endif
        </li>
    @empty
        <li>{{ __('dashboard.visitor_sessions.empty') }}</li>
    @endforelse
</ol>
