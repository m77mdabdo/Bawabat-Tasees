@props(['value' => null, 'for' => null])

{{-- A real <label>, always — the inputs below are icon-leading, and a
     placeholder-as-label would leave screen readers with nothing. --}}
<label for="{{ $for }}" {{ $attributes->merge(['class' => 'block text-sm font-medium text-text-main']) }}>
    {{ $value ?? $slot }}
</label>
