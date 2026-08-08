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
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <ol class="relative border-s-2 border-border-default ps-8">
                    @foreach ($sections as $index => $section)
                        <li class="relative {{ $loop->last ? '' : 'pb-10' }}">
                            <span class="absolute -start-[calc(2rem+1px)] flex h-8 w-8 items-center justify-center rounded-full bg-primary-green text-sm font-bold text-white">
                                {{ $index + 1 }}
                            </span>

                            <h2 class="font-semibold text-text-main">
                                {{ $section->title }}
                            </h2>

                            @if ($section->description)
                                <p class="mt-1 text-sm text-text-secondary">
                                    {{ $section->description }}
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif
</x-public-layout>
