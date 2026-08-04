@php
    $icons = [
        'chart-line' => '📈',
        'building-office' => '🏢',
        'map' => '🗺️',
        'trending-up' => '📊',
        'clipboard-check' => '✅',
        'gift' => '🎁',
    ];
@endphp

<x-public-layout :title="$page->title . ' — ' . __('site.brand.name')">
    <section class="bg-dark-green py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-white sm:text-4xl">
                {{ $page->title }}
            </h1>
        </div>
    </section>

    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            {{--
                Rendered raw because $page->body is sanitized through
                HtmlSanitizerService::sanitizeArticleBody() before it is
                ever saved — see Dashboard\PageController::sanitize().
            --}}
            <div class="article-body leading-relaxed text-text-main">
                {!! $page->body !!}
            </div>
        </div>
    </section>

    @if ($sections->isNotEmpty())
        <section class="bg-bg-soft py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($sections as $section)
                        <x-card class="p-6">
                            <span class="text-3xl" aria-hidden="true">
                                {{ $icons[$section->icon] ?? '⭐' }}
                            </span>
                            <h2 class="mt-4 font-semibold text-text-main">
                                {{ $section->title }}
                            </h2>
                            @if ($section->description)
                                <p class="mt-2 text-sm text-text-secondary">
                                    {{ $section->description }}
                                </p>
                            @endif
                        </x-card>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-public-layout>
