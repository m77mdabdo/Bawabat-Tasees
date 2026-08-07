@php
    $contentActive = request()->routeIs('dashboard.services.*', 'dashboard.countries.*', 'dashboard.faqs.*', 'dashboard.articles.*', 'dashboard.testimonials.*', 'dashboard.media.*', 'dashboard.pages.*', 'dashboard.comments.*');
    $marketingActive = request()->routeIs('dashboard.campaigns.*', 'dashboard.lead-sources.*', 'dashboard.tracking-settings.*');
@endphp

<aside
    :class="sidebarOpen ? 'translate-x-0' : 'max-lg:rtl:translate-x-full max-lg:ltr:-translate-x-full'"
    class="fixed inset-y-0 end-0 z-40 flex w-72 shrink-0 flex-col bg-dark-green transition-transform duration-200 ease-in-out lg:static lg:translate-x-0"
>
    <div class="flex h-20 shrink-0 items-center border-b border-white/10 px-6">
        <a href="{{ route('dashboard') }}">
            <img src="{{ asset('images/brand/logo-full-white-256.png') }}" alt="{{ __('site.brand.name') }}" class="h-9 w-auto">
        </a>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6">
        <x-dashboard.nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <rect x="3" y="3" width="7" height="7" rx="1" />
                <rect x="14" y="3" width="7" height="7" rx="1" />
                <rect x="3" y="14" width="7" height="7" rx="1" />
                <rect x="14" y="14" width="7" height="7" rx="1" />
            </svg>
            {{ __('dashboard.sidebar.dashboard') }}
        </x-dashboard.nav-link>

        <x-dashboard.nav-group :label="__('dashboard.sidebar.content')" :active="$contentActive">
            <x-slot name="icon">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                </svg>
            </x-slot>

            <x-dashboard.nav-link :href="route('dashboard.services.index')" :active="request()->routeIs('dashboard.services.*')">
                {{ __('dashboard.sidebar.services') }}
            </x-dashboard.nav-link>
            <x-dashboard.nav-link :href="route('dashboard.countries.index')" :active="request()->routeIs('dashboard.countries.*')">
                {{ __('dashboard.sidebar.countries') }}
            </x-dashboard.nav-link>
            <x-dashboard.nav-link :href="route('dashboard.faqs.index')" :active="request()->routeIs('dashboard.faqs.*')">
                {{ __('dashboard.sidebar.faqs') }}
            </x-dashboard.nav-link>
            <x-dashboard.nav-link :href="route('dashboard.articles.index')" :active="request()->routeIs('dashboard.articles.*')">
                {{ __('dashboard.sidebar.articles') }}
            </x-dashboard.nav-link>
            <x-dashboard.nav-link :href="route('dashboard.comments.index')" :active="request()->routeIs('dashboard.comments.*')">
                {{ __('dashboard.sidebar.comments') }}
            </x-dashboard.nav-link>
            <x-dashboard.nav-link :href="route('dashboard.testimonials.index')" :active="request()->routeIs('dashboard.testimonials.*')">
                {{ __('dashboard.sidebar.testimonials') }}
            </x-dashboard.nav-link>
            <x-dashboard.nav-link :href="route('dashboard.media.index')" :active="request()->routeIs('dashboard.media.*')">
                {{ __('dashboard.sidebar.media') }}
            </x-dashboard.nav-link>
            <x-dashboard.nav-link :href="route('dashboard.pages.index')" :active="request()->routeIs('dashboard.pages.*')">
                {{ __('dashboard.sidebar.pages') }}
            </x-dashboard.nav-link>
        </x-dashboard.nav-group>

        <x-dashboard.nav-link :href="route('dashboard.leads.index')" :active="request()->routeIs('dashboard.leads.*')">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <circle cx="9" cy="8" r="3" />
                <circle cx="17" cy="9" r="2.5" />
                <path d="M3 20c0-3 2.5-5 6-5s6 2 6 5M15 15c2.8 0 5 1.6 5 4.5" />
            </svg>
            {{ __('dashboard.sidebar.leads') }}
        </x-dashboard.nav-link>

        <x-dashboard.nav-group :label="__('dashboard.sidebar.marketing')" :active="$marketingActive">
            <x-slot name="icon">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M3 10v4a1 1 0 001 1h2l7 4V5l-7 4H4a1 1 0 00-1 1z" />
                    <path d="M17 9a4 4 0 010 6" />
                </svg>
            </x-slot>

            <x-dashboard.nav-link :href="route('dashboard.campaigns.index')" :active="request()->routeIs('dashboard.campaigns.*')">
                {{ __('dashboard.sidebar.campaigns') }}
            </x-dashboard.nav-link>
            <x-dashboard.nav-link :href="route('dashboard.lead-sources.index')" :active="request()->routeIs('dashboard.lead-sources.*')">
                {{ __('dashboard.sidebar.lead_sources') }}
            </x-dashboard.nav-link>
            <x-dashboard.nav-link :href="route('dashboard.tracking-settings.edit')" :active="request()->routeIs('dashboard.tracking-settings.*')">
                {{ __('dashboard.sidebar.tracking_settings') }}
            </x-dashboard.nav-link>
        </x-dashboard.nav-group>

        <x-dashboard.nav-link :href="route('dashboard.contact-messages.index')" :active="request()->routeIs('dashboard.contact-messages.*')">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <rect x="3" y="5" width="18" height="14" rx="2" />
                <path d="M3 7l9 6 9-6" />
            </svg>
            {{ __('dashboard.sidebar.contact_messages') }}
        </x-dashboard.nav-link>

        <x-dashboard.nav-link :href="route('dashboard.reports.index')" :active="request()->routeIs('dashboard.reports.*')">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path d="M4 20V10M10 20V4M16 20v-7M3 20h18" />
            </svg>
            {{ __('dashboard.sidebar.reports') }}
        </x-dashboard.nav-link>

        <x-dashboard.nav-link :href="route('dashboard.settings.index')" :active="request()->routeIs('dashboard.settings.*')">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <circle cx="12" cy="12" r="3" />
                <circle cx="12" cy="12" r="8" stroke-dasharray="2 3" />
            </svg>
            {{ __('dashboard.sidebar.settings') }}
        </x-dashboard.nav-link>

        <hr class="my-4 border-white/10">

        <x-dashboard.nav-link :href="route('home')">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path d="M15 19l-7-7 7-7M4 12h16" />
            </svg>
            {{ __('dashboard.sidebar.back_to_site') }}
        </x-dashboard.nav-link>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center gap-3 rounded-lg px-4 py-2.5 text-sm font-medium text-white/75 transition hover:bg-white/5 hover:text-white"
            >
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
                </svg>
                {{ __('dashboard.auth.logout') }}
            </button>
        </form>
    </nav>
</aside>

<div
    x-show="sidebarOpen"
    x-transition:enter="transition-opacity ease-linear duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="sidebarOpen = false"
    class="fixed inset-0 z-30 bg-black/40 lg:hidden"
    style="display: none;"
></div>
