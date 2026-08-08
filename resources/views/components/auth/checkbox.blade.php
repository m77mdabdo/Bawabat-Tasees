@props(['id', 'name', 'label'])

<label for="{{ $id }}" class="inline-flex cursor-pointer items-center gap-2 text-sm text-text-secondary">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="checkbox"
        {{ $attributes->merge(['class' => 'h-4 w-4 rounded border-border-default text-primary-green shadow-sm focus:ring-primary-green']) }}
    >
    <span>{{ $label }}</span>
</label>
