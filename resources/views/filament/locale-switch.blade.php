@php($current = app()->getLocale())
<form method="POST" action="{{ route('dashboard.locale') }}" class="flex items-center">
    @csrf
    <button type="submit" title="{{ $current === 'ar' ? 'English' : 'العربية' }}" class="bt-locale-switch">
        <x-filament::icon icon="heroicon-o-language" class="h-4 w-4" />
        <span>{{ $current === 'ar' ? 'EN' : 'عربي' }}</span>
    </button>
</form>
