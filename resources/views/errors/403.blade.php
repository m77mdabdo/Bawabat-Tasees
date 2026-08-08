@php
    $errorLocale = request()->is('en', 'en/*') ? 'en' : app()->getLocale();
@endphp

@include('errors.layout', [
    'code' => '403',
    'title' => __('site.errors.forbidden_title', [], $errorLocale),
    'message' => __('site.errors.forbidden_message', [], $errorLocale),
])
