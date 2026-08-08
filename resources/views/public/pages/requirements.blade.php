<x-public-layout :title="$page->title . ' — ' . __('site.brand.name')" :seo-model="$page">
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
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <ul class="space-y-4">
                    @foreach ($sections as $section)
                        <li class="flex items-start gap-4 rounded-xl border border-border-default bg-white p-5 shadow-sm">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-green/10 text-primary-green" aria-hidden="true">
                                ✓
                            </span>
                            <div>
                                <h2 class="font-semibold text-text-main">
                                    {{ $section->title }}
                                </h2>
                                @if ($section->description)
                                    <p class="mt-1 text-sm text-text-secondary">
                                        {{ $section->description }}
                                    </p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</x-public-layout>
