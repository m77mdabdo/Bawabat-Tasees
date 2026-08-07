<x-public-layout :title="$article->title . ' — ' . __('site.brand.name')">
    <article>
        @if ($article->cover_image)
            <div class="h-64 w-full overflow-hidden sm:h-96">
                <img
                    src="{{ Illuminate\Support\Facades\Storage::url($article->cover_image) }}"
                    alt="{{ $article->title }}"
                    class="h-full w-full object-cover"
                >
            </div>
        @endif

        <div class="max-w-3xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
            <time class="text-sm font-medium text-luxury-gold" datetime="{{ $article->published_at->toIso8601String() }}">
                {{ $article->published_at->locale(app()->getLocale())->translatedFormat('d F Y') }}
            </time>

            <h1 class="mt-3 text-3xl font-bold text-text-main sm:text-4xl">
                {{ $article->title }}
            </h1>

            @if ($article->excerpt)
                <p class="mt-4 text-lg text-text-secondary">
                    {{ $article->excerpt }}
                </p>
            @endif

            {{--
                Rendered raw (not escaped via {{ }}) because $article->body is
                sanitized through HtmlSanitizerService::sanitizeArticleBody()
                against a strict tag/attribute allow-list at save time (see
                App\Http\Controllers\Dashboard\ArticleController::sanitize()).
                This is the only field in the project allowed to contain real
                HTML — every other field (service name/summary, country name,
                FAQ question/answer, etc.) renders via {{ }}.
            --}}
            <div class="article-body mt-8 leading-relaxed text-text-main">
                {!! $article->body !!}
            </div>

            <div class="mt-12 border-t border-border-default pt-8">
                <a href="{{ lroute('articles.index') }}" class="text-sm font-medium text-primary-green hover:text-primary-green/80">
                    &rarr; {{ __('site.articles.back_link') }}
                </a>
            </div>

            {{-- Comments: only APPROVED comments ever reach this view (see
                 ArticleController::show — filtered server-side via the
                 `approved` scope), oldest first. Body is always plain text
                 via {{ }} — comments are untrusted public input and are
                 never rendered as HTML under any circumstance. --}}
            <div class="mt-16 border-t border-border-default pt-8">
                <h2 class="text-xl font-bold text-text-main">
                    {{ __('site.articles.comments_heading') }} ({{ $comments->count() }})
                </h2>

                @if ($comments->isEmpty())
                    <p class="mt-4 text-sm text-text-secondary">
                        {{ __('site.articles.comments_empty') }}
                    </p>
                @else
                    <div class="mt-6 space-y-6">
                        @foreach ($comments as $comment)
                            <div class="rounded-xl border border-border-default bg-white p-5">
                                <p class="font-semibold text-text-main">{{ $comment->name }}</p>
                                <time class="text-xs text-text-secondary" datetime="{{ $comment->created_at->toIso8601String() }}">
                                    {{ $comment->created_at->locale(app()->getLocale())->translatedFormat('d F Y') }}
                                </time>
                                <p class="mt-2 text-sm text-text-main">{{ $comment->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (session('status'))
                    <div class="mt-8 rounded-xl border border-primary-green/30 bg-primary-green/5 p-4 text-center text-primary-green">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ lroute('articles.comments.store', $article) }}" class="mt-8 space-y-4 rounded-2xl border border-border-default bg-white p-6 shadow-sm">
                    @csrf

                    {{-- Honeypot: same field/markup as the Leads
                         consultation/contact forms (real visitors never
                         see it — CSS-hidden, not type="hidden"). If
                         filled, the controller silently accepts the
                         request without creating a Comment. NOT `sr-only` —
                         that utility stays VISIBLE to screen readers by
                         design, the opposite of what a honeypot needs; see
                         the same note in contact-form.blade.php. --}}
                    <div class="absolute h-px w-px overflow-hidden opacity-0 pointer-events-none" aria-hidden="true" tabindex="-1">
                        <label for="website_url">{{ __('site.common.honeypot_label') }}</label>
                        <input type="text" name="website_url" id="website_url" autocomplete="off" tabindex="-1" value="{{ old('website_url') }}">
                    </div>

                    <h3 class="font-semibold text-text-main">{{ __('site.articles.comment_form_heading') }}</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-medium text-text-main">{{ __('site.articles.name_label') }} *</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-text-main">{{ __('site.articles.email_label') }} *</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="body" class="block text-sm font-medium text-text-main">{{ __('site.articles.comment_label') }} *</label>
                        <textarea name="body" id="body" rows="4" required
                            class="mt-1 block w-full rounded-md border-border-default focus:border-primary-green focus:ring-primary-green">{{ old('body') }}</textarea>
                        @error('body')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <p class="text-xs text-text-secondary">
                        {{ __('site.articles.comment_privacy_note') }}
                    </p>

                    <button type="submit" class="rounded-md bg-primary-green px-6 py-2.5 text-sm font-semibold text-white hover:bg-primary-green/90">
                        {{ __('site.articles.submit_comment') }}
                    </button>
                </form>
            </div>
        </div>
    </article>
</x-public-layout>
