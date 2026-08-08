@props(['links' => []])

@php
    // Only entries with a value render — an unconfigured social must not
    // produce a broken icon linking nowhere.
    $links = collect($links)->filter(fn ($url) => is_string($url) && trim($url) !== '');
@endphp

@if ($links->isNotEmpty())
    <ul class="mt-6 flex flex-wrap items-center gap-3">
        @foreach ($links as $key => $url)
            <li>
                <a
                    href="{{ $url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="{{ __('dashboard.settings.fields.social_'.$key) }}"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/20 text-white/80 transition hover:border-white hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                >
                    @switch($key)
                        @case('facebook')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h3l1-3h-4v-2c0-.6.4-1 1-1z"/></svg>
                            @break
                        @case('instagram')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                            @break
                        @case('twitter')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.9 2H22l-7.3 8.3L23 22h-6.6l-5.2-6.8L5.3 22H2.2l7.8-8.9L1.6 2h6.8l4.7 6.2zm-1.1 18h1.8L7.3 3.9H5.4z"/></svg>
                            @break
                        @case('linkedin')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.9 8.5v11H3.5v-11zM5.2 3a2 2 0 110 4 2 2 0 010-4zM20.5 19.5H17v-5.6c0-1.4-.5-2.3-1.7-2.3-.9 0-1.5.6-1.7 1.2-.1.2-.1.6-.1.9v5.8H10s.1-9.9 0-11h3.4v1.6c.5-.7 1.3-1.8 3.2-1.8 2.3 0 4 1.5 4 4.8z"/></svg>
                            @break
                        @case('youtube')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12s0-3.3-.4-4.8a2.5 2.5 0 00-1.8-1.8C18.3 5 12 5 12 5s-6.3 0-7.8.4A2.5 2.5 0 002.4 7.2C2 8.7 2 12 2 12s0 3.3.4 4.8a2.5 2.5 0 001.8 1.8C5.7 19 12 19 12 19s6.3 0 7.8-.4a2.5 2.5 0 001.8-1.8C22 15.3 22 12 22 12zM10 15V9l5.2 3z"/></svg>
                            @break
                        @case('tiktok')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.5 2h-3v13.2a2.6 2.6 0 11-2.2-2.6v-3a5.6 5.6 0 105.2 5.6V9.2c1 .7 2.2 1.1 3.5 1.2v-3a4.3 4.3 0 01-3.5-3.4z"/></svg>
                            @break
                        @case('snapchat')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2c2.8 0 4.7 2.1 4.7 5 0 .8-.1 1.6-.1 2 .4.2.9.1 1.4-.1.5-.2 1 .1 1.1.6.1.5-.2.9-.8 1.2-.7.3-1.4.5-1.5.9-.1.4.4 1.2 1 2 .7.9 1.6 1.6 2.6 1.9.4.1.6.4.5.8-.1.5-.9.9-2.2 1.1-.1.4-.2.8-.3 1.1-.1.3-.3.4-.7.3-.5-.1-1.1-.2-1.9-.1-.7.1-1.3.6-2.1 1.1-.6.4-1.2.7-1.7.7s-1.1-.3-1.7-.7c-.8-.5-1.4-1-2.1-1.1-.8-.1-1.4 0-1.9.1-.4.1-.6 0-.7-.3-.1-.3-.2-.7-.3-1.1-1.3-.2-2.1-.6-2.2-1.1-.1-.4.1-.7.5-.8 1-.3 1.9-1 2.6-1.9.6-.8 1.1-1.6 1-2-.1-.4-.8-.6-1.5-.9-.6-.3-.9-.7-.8-1.2.1-.5.6-.8 1.1-.6.5.2 1 .3 1.4.1 0-.4-.1-1.2-.1-2 0-2.9 1.9-5 4.7-5z"/></svg>
                            @break
                    @endswitch
                </a>
            </li>
        @endforeach
    </ul>
@endif
