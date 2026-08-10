@props([
    // 'success' — a write completed. 'error' — a validation summary.
    'type' => 'success',
    // A single line. Defaults to the flashed `status` value, which is what
    // every dashboard redirect sets, so calling this component with no
    // arguments at all is the common case and the one it exists for.
    'message' => null,
    // Several lines, rendered as a list — the validation-summary case,
    // i.e. type="error" with :messages="$errors->all()".
    'messages' => [],
])

{{--
    NOTE FOR EDITORS: do not write a literal component tag anywhere in this
    file, comments included. Blade's tag compiler runs over the raw template
    text before comments are stripped, so a tag inside a comment compiles
    into a real nested render — and this component rendering itself does not
    end well.

    Replaces 19 hand-rolled copies of the same block across the dashboard,
    which had drifted into three different looks: raw Tailwind
    green-100/green-800, the same with an extra mb-4, and the brand
    primary-green tint used by Comments, Leads and Tracking Settings. The
    brand one wins here, so every dashboard flash matches — and the
    green-100/red-100 defaults leave the codebase.

    Spacing is NOT baked in: most callers sit inside a space-y-* container
    that already owns the gap. The eight whose parent does not are the ones
    that pass mb-4, which is exactly the set that carried it before.

    DELIBERATELY NOT USED BY the two public form confirmations
    (components/contact-form, public/consultation). Those are visually
    different on purpose — centred, larger radius, sized for a marketing
    page rather than an admin row — and each is immediately followed by its
    own conversion-tracking snippet keyed to that specific form. Folding
    them in would either regress their look or grow this component an option
    per caller.

    Red stays a raw utility for the error variant: the brand palette has no
    error colour, and danger-button already uses red-600 for the same
    semantic. red-800 on red-50 is 8.6:1.
--}}

@php
    $message ??= session('status');
    $messages = array_values(array_filter((array) $messages, fn ($line) => filled($line)));
    $isError = $type === 'error';
@endphp

@if (filled($message) || $messages !== [] || $slot->isNotEmpty())
    <div
        {{ $attributes->class([
            'rounded-md border p-4 text-sm',
            'border-primary-green/30 bg-primary-green/5 text-primary-green' => ! $isError,
            'border-red-300 bg-red-50 text-red-800' => $isError,
        ]) }}
        {{-- role="status" is polite, role="alert" is assertive: a saved
             confirmation should not interrupt a screen reader mid-sentence,
             a validation failure should. --}}
        role="{{ $isError ? 'alert' : 'status' }}"
    >
        @if (filled($message))
            {{ $message }}
        @endif

        @if ($messages !== [])
            <ul class="list-disc space-y-1 ps-5">
                @foreach ($messages as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        @endif

        {{ $slot }}
    </div>
@endif
