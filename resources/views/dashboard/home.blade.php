@php
    // "Needs attention" cards all link to the plain CRUD index rather than
    // a pre-filtered list — none of the existing controllers support a
    // status query filter, and adding one would mean touching CRUD
    // controller logic, which is out of scope for this task (layout/nav/
    // home only). The "page sections" card links to the Pages index
    // (not a single sections list) because page_sections has no
    // cross-page index route — sections are only ever listed nested
    // under one page at a time.
    $attentionItems = [
        ['label' => __('dashboard.home.unpublished_articles'), 'value' => $unpublishedArticles, 'href' => route('dashboard.articles.index')],
        ['label' => __('dashboard.home.inactive_services'), 'value' => $inactiveServices, 'href' => route('dashboard.services.index')],
        ['label' => __('dashboard.home.inactive_page_sections'), 'value' => $inactivePageSections, 'href' => route('dashboard.pages.index')],
        ['label' => __('dashboard.home.pending_comments'), 'value' => $pendingComments, 'href' => route('dashboard.comments.index', ['status' => 'pending'])],
    ];

    $overviewItems = [
        ['label' => __('dashboard.home.total_published_services'), 'value' => $activeServices, 'href' => route('dashboard.services.index')],
        ['label' => __('dashboard.home.total_countries'), 'value' => $countries, 'href' => route('dashboard.countries.index')],
        ['label' => __('dashboard.home.total_published_articles'), 'value' => $publishedArticles, 'href' => route('dashboard.articles.index')],
        ['label' => __('dashboard.home.total_active_faqs'), 'value' => $activeFaqs, 'href' => route('dashboard.faqs.index')],
        ['label' => __('dashboard.home.total_active_testimonials'), 'value' => $activeTestimonials, 'href' => route('dashboard.testimonials.index')],
        ['label' => __('dashboard.home.total_media'), 'value' => $media, 'href' => route('dashboard.media.index')],
    ];

    $leadItems = [
        ['label' => __('dashboard.home.leads_today'), 'value' => $leadsToday, 'href' => route('dashboard.leads.index')],
        ['label' => __('dashboard.home.leads_this_week'), 'value' => $leadsThisWeek, 'href' => route('dashboard.leads.index')],
        ['label' => __('dashboard.home.total_leads'), 'value' => $totalLeads, 'href' => route('dashboard.leads.index')],
        ['label' => __('dashboard.home.converted_leads'), 'value' => $convertedLeads, 'href' => route('dashboard.leads.index', ['conversion' => 'converted'])],
        ['label' => __('dashboard.home.conversion_value'), 'value' => number_format($conversionValue, 2), 'href' => route('dashboard.leads.index', ['conversion' => 'converted'])],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-text-main leading-tight">
            {{ __('dashboard.home.heading') }} — {{ Auth::user()->name }}
        </h2>
    </x-slot>

    <div class="space-y-10">
        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-text-secondary">
                {{ __('dashboard.home.needs_attention') }}
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($attentionItems as $item)
                    <a href="{{ $item['href'] }}" class="block">
                        <x-card class="p-6 {{ $item['value'] > 0 ? 'border-luxury-gold/40' : '' }}">
                            <p class="text-sm font-medium text-text-secondary">{{ $item['label'] }}</p>
                            <p class="mt-2 text-3xl font-bold {{ $item['value'] > 0 ? 'text-luxury-gold' : 'text-text-main' }}">
                                {{ $item['value'] }}
                            </p>
                        </x-card>
                    </a>
                @endforeach
            </div>
        </section>

        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-text-secondary">
                {{ __('dashboard.home.content_overview') }}
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($overviewItems as $item)
                    <a href="{{ $item['href'] }}" class="block">
                        <x-card class="p-6">
                            <p class="text-sm font-medium text-text-secondary">{{ $item['label'] }}</p>
                            <p class="mt-2 text-3xl font-bold text-primary-green">{{ $item['value'] }}</p>
                        </x-card>
                    </a>
                @endforeach
            </div>
        </section>

        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-text-secondary">
                {{ __('dashboard.home.leads_overview') }}
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ($leadItems as $item)
                    <a href="{{ $item['href'] }}" class="block">
                        <x-card class="p-6">
                            <p class="text-sm font-medium text-text-secondary">{{ $item['label'] }}</p>
                            <p class="mt-2 text-3xl font-bold text-primary-green">{{ $item['value'] }}</p>
                        </x-card>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-app-layout>
