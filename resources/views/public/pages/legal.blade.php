<x-public-layout :title="$page->title . ' — ' . __('site.brand.name')">
    <section class="bg-dark-green py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-white sm:text-4xl">
                {{ $page->title }}
            </h1>
        </div>
    </section>

    <section class="py-16">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            {{--
                Rendered raw because $page->body is sanitized through
                HtmlSanitizerService::sanitizeArticleBody() (the same
                allow-list used for Article body) before it is ever saved
                — see App\Http\Controllers\Dashboard\PageController::sanitize().
                Every other field on this page renders via {{ }}.
            --}}
            <div class="article-body leading-relaxed text-text-main">
                {!! $page->body !!}
            </div>
        </div>
    </section>
</x-public-layout>
