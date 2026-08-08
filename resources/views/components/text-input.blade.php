@props(['disabled' => false])

{{-- Brand palette, replacing the Breeze defaults. Shared by every
     dashboard and profile form, so this one file sets the look of all of
     them. --}}
<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-md border-border-default shadow-sm focus:border-primary-green focus:ring-primary-green']) }}>
