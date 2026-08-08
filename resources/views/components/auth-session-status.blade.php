@props(['status'])

@if ($status)
    {{-- Auth-only component (no dashboard usage), so it is restyled in
         place rather than duplicated under components/auth. --}}
    <div {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-xl border border-primary-green/20 bg-primary-green/5 px-4 py-3 text-sm font-medium text-primary-green']) }}>
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 10-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span>{{ $status }}</span>
    </div>
@endif
