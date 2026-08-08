@php
    $errorLocale = request()->is('en', 'en/*') ? 'en' : app()->getLocale();
@endphp

@include('errors.layout', [
    'code' => '503',
    'title' => __('site.errors.maintenance_title', [], $errorLocale),
    'message' => __('site.errors.maintenance_message', [], $errorLocale),
])
