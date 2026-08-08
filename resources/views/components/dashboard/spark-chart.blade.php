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
    {{--
        min-w-[3px] per bar, with the container scrolling instead of the
        page. The 90-day range packs 90 bars into ~340px on a phone,
        which is under 3px each once the gaps are taken out — thin
        enough to read as noise. Letting the strip scroll keeps every
        bar at a legible width instead of shrinking them all to nothing.

        Both this strip and the axis labels sit in the same scroll box
        so the end label stays over the last bar rather than drifting.
    --}}
    <div class="-mx-1 overflow-x-auto px-1">
        <div class="min-w-full" style="width: max-content;">
            <div class="flex h-40 items-end gap-px" aria-hidden="true">
                @foreach ($rows as $row)
                    <div class="group relative min-w-[3px] flex-1 rounded-t bg-primary-green/80 hover:bg-primary-green"
                        style="height: {{ $row['value'] > 0 ? max(3, round($row['value'] / $max * 100)) : 1 }}%"
                        title="{{ $row['label'] }}: {{ $row['value'] }}"></div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between gap-4 text-xs text-text-secondary" aria-hidden="true">
                <span dir="ltr">{{ $rows->first()['label'] }}</span>
                <span dir="ltr">{{ $rows->last()['label'] }}</span>
            </div>
        </div>
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
