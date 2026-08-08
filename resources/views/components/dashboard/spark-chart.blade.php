@props(['rows' => []])

@php
    $rows = collect($rows);
    $max = (int) $rows->max('value') ?: 1;
    $total = (int) $rows->sum('value');
@endphp

@if ($total === 0)
    <p class="rounded-md border border-dashed border-border-default px-4 py-6 text-center text-sm text-text-secondary">
        {{ __('dashboard.reports.no_data') }}
    </p>
@else
    {{--
        Column chart as flex-grown divs — no charting library. The numeric
        series is also exposed as a visually-hidden table so the data is
        available to screen readers rather than being locked in the shape.
    --}}
    <div class="flex h-40 items-end gap-px" aria-hidden="true">
        @foreach ($rows as $row)
            <div class="group relative flex-1 rounded-t bg-primary-green/80 hover:bg-primary-green"
                style="height: {{ $row['value'] > 0 ? max(3, round($row['value'] / $max * 100)) : 1 }}%"
                title="{{ $row['label'] }}: {{ $row['value'] }}"></div>
        @endforeach
    </div>
    <div class="mt-2 flex justify-between text-xs text-text-secondary" aria-hidden="true">
        <span dir="ltr">{{ $rows->first()['label'] }}</span>
        <span dir="ltr">{{ $rows->last()['label'] }}</span>
    </div>

    <table class="sr-only">
        <caption>{{ __('dashboard.reports.leads_over_time') }}</caption>
        <thead>
            <tr><th scope="col">{{ __('dashboard.reports.range_label') }}</th><th scope="col">{{ __('dashboard.reports.total_leads') }}</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr><th scope="row">{{ $row['label'] }}</th><td>{{ $row['value'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif
