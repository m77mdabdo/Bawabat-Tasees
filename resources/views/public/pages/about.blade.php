<x-public-layout :title="$page->title . ' — ' . __('site.brand.name')" :seo-model="$page">
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

    {{--
        Real photos — licensed Pexels stock (free for commercial use, no
        attribution required per the Pexels License), processed and moved
        into public/images/about/ from the original public/about-media/
        drop folder (now removed). A staggered two-photo block, offset on
        larger screens for visual interest rather than a plain side-by-side grid.
    --}}
    <section class="py-8 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <img
                    src="{{ asset('images/about/about-team-meeting.jpg') }}"
                    alt="{{ __('site.about.meeting_photo_alt') }}"
                    class="h-72 w-full rounded-2xl object-cover shadow-sm sm:h-96"
                >
                <img
                    src="{{ asset('images/about/about-office-building.jpg') }}"
                    alt="{{ __('site.about.office_photo_alt') }}"
                    class="h-72 w-full rounded-2xl object-cover shadow-sm sm:mt-10 sm:h-96"
                >
            </div>
        </div>
    </section>

    <section class="bg-bg-soft py-16 sm:py-24">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <x-card class="p-8 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary-green/10 text-primary-green">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <rect x="3" y="7" width="18" height="13" rx="2" />
                            <path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2" />
                            <path d="M3 13h18" />
                        </svg>
                    </span>
                    <h2 class="mt-5 font-semibold text-text-main">{{ __('site.about.expertise_heading') }}</h2>
                    <p class="mt-2 text-sm text-text-secondary">
                        {{ __('site.about.expertise_body') }}
                    </p>
                </x-card>

                <x-card class="p-8 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary-green/10 text-primary-green">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M12 3l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V6l7-3z" />
                            <path d="M9 12l2 2 4-4" />
                        </svg>
                    </span>
                    <h2 class="mt-5 font-semibold text-text-main">{{ __('site.about.compliance_heading') }}</h2>
                    <p class="mt-2 text-sm text-text-secondary">
                        {{ __('site.about.compliance_body') }}
                    </p>
                </x-card>

                <x-card class="p-8 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary-green/10 text-primary-green">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M4 20v-4h4v4M10 20v-8h4v8M16 20v-12h4v12" />
                        </svg>
                    </span>
                    <h2 class="mt-5 font-semibold text-text-main">{{ __('site.about.support_heading') }}</h2>
                    <p class="mt-2 text-sm text-text-secondary">
                        {{ __('site.about.support_body') }}
                    </p>
                </x-card>
            </div>
        </div>
    </section>

    {{--
        Click-to-play video — NOT autoplay (only the homepage hero
        autoplays). A poster frame (extracted via ffmpeg) is shown as a
        plain image with a play button overlay; the real <video> element
        only starts loading/playing once clicked. Same licensed-Pexels
        source as the photos above.
    --}}
    <section class="py-16 sm:py-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">{{ __('site.about.tour_eyebrow') }}</span>
                <h2 class="mt-2 text-2xl font-bold text-text-main sm:text-3xl">{{ __('site.about.tour_heading') }}</h2>
            </div>

            <div x-data="{ playing: false }" class="relative mt-10 overflow-hidden rounded-2xl shadow-lg">
                <button
                    type="button"
                    x-show="!playing"
                    @click="playing = true; $nextTick(() => $refs.aboutVideo.play())"
                    class="group relative block w-full"
                    aria-label="{{ __('site.about.play_video') }}"
                >
                    <img
                        src="{{ asset('images/about/about-office-tour-poster.jpg') }}"
                        alt="{{ __('site.about.video_poster_alt') }}"
                        class="h-72 w-full object-cover sm:h-[28rem]"
                    >
                    <span class="absolute inset-0 flex items-center justify-center bg-dark-green/30 transition group-hover:bg-dark-green/40">
                        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white/90 text-primary-green shadow-lg transition group-hover:scale-105">
                            <svg class="h-7 w-7 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M8 5v14l11-7-11-7z" />
                            </svg>
                        </span>
                    </span>
                </button>

                <video
                    x-ref="aboutVideo"
                    x-show="playing"
                    style="display: none;"
                    controls
                    playsinline
                    class="h-72 w-full bg-black object-cover sm:h-[28rem]"
                >
                    <source src="{{ asset('videos/about/about-office-tour.mp4') }}" type="video/mp4">
                </video>
            </div>
        </div>
    </section>
</x-public-layout>
