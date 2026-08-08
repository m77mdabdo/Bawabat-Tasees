@props([
    'rows' => [],
    'valueKey' => 'value',
    'format' => 'number',
    'accent' => 'green',
])

@php
    $rows = collect($rows);
    $max = (float) $rows->max(fn ($row) => (float) ($row[$valueKey] ?? 0)) ?: 1;

    // Literal class strings: Tailwind's JIT scans source text, so a
    // dynamically built "bg-{$accent}" would never be generated.
    $barClass = $accent === 'gold' ? 'bg-luxury-gold' : 'bg-primary-green';
@endphp

@if ($rows->isEmpty())
    <p class="rounded-md border border-dashed border-border-default px-4 py-6 text-center text-sm text-text-secondary">
        {{ __('dashboard.reports.no_data') }}
    </p>
@else
    {{--
        The label and the number are always rendered as real text, so the
        chart is fully readable without seeing the bars. The bar itself is
        decoration and is hidden from assistive tech rather than being
        given a redundant aria-label.
    --}}
    <ul class="space-y-3">
        @foreach ($rows as $row)
            @php
                $value = (float) ($row[$valueKey] ?? 0);
                $width = max(2, round($value / $max * 100));
            @endphp
            <li>
                <div class="flex items-baseline justify-between gap-3 text-sm">
                    <span class="min-w-0 truncate text-text-main">{{ $row['label'] }}</span>
                    <span class="shrink-0 font-semibold text-text-main" dir="ltr">
                        {{ $format === 'money' ? number_format($value, 2) : number_format($value) }}
                    </span>
                </div>
                <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-bg-soft" aria-hidden="true">
                    <div class="h-full rounded-full {{ $barClass }}" style="width: {{ $width }}%"></div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
