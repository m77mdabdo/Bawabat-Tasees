@php
    $errorLocale = request()->is('en', 'en/*') ? 'en' : app()->getLocale();
@endphp

@include('errors.layout', [
    'code' => '500',
    'title' => __('site.errors.server_error_title', [], $errorLocale),
    'message' => __('site.errors.server_error_message', [], $errorLocale),
])
