@php
    $errorLocale = request()->is('en', 'en/*') ? 'en' : app()->getLocale();
@endphp

@include('errors.layout', [
    'code' => '404',
    'title' => __('site.errors.not_found_title', [], $errorLocale),
    'message' => __('site.errors.not_found_message', [], $errorLocale),
])
