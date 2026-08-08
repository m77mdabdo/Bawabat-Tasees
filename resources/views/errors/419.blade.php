@php
    $errorLocale = request()->is('en', 'en/*') ? 'en' : app()->getLocale();
@endphp

@include('errors.layout', [
    'code' => '419',
    'title' => __('site.errors.expired_title', [], $errorLocale),
    'message' => __('site.errors.expired_message', [], $errorLocale),
])
