<x-public-layout :seo-description="__('site.home.hero_subheading')">
    <section class="relative h-[85vh] min-h-[560px] w-full overflow-hidden">
        <video
            class="absolute inset-0 h-full w-full object-cover"
            poster="{{ asset('images/hero-poster.jpg') }}"
            autoplay
            muted
            loop
            playsinline
        >
            <source src="{{ asset('videos/hero-bg.webm') }}" type="video/webm">
            <source src="{{ asset('videos/hero-bg.mp4') }}" type="video/mp4">
        </video>

        <div class="absolute inset-0 bg-dark-green/70"></div>

        <div class="relative z-10 flex h-full flex-col items-center justify-center px-4 text-center text-white">
            <span class="mb-4 inline-block rounded-full border border-light-gold/50 bg-white/10 px-4 py-1.5 text-sm font-medium text-light-gold">
                {{ __('site.home.hero_eyebrow') }}
            </span>

            <h1 class="max-w-3xl text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">
                {{ __('site.home.hero_heading') }}
            </h1>

            <p class="mt-6 max-w-2xl text-lg text-white/85 sm:text-xl">
                {{ __('site.home.hero_subheading') }}
            </p>

            <div class="mt-10 flex flex-col items-center gap-4 sm:flex-row">
                <a
                    href="{{ lroute('consultation') }}"
                    class="inline-flex items-center justify-center rounded-md bg-primary-green px-8 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-primary-green/90"
                >
                    {{ __('site.home.cta_start') }}
                </a>

                <a
                    href="{{ lroute('consultation') }}"
                    class="inline-flex items-center justify-center rounded-md border border-white/40 bg-white/10 px-8 py-3 text-base font-semibold text-white transition hover:bg-white/20"
                >
                    {{ __('site.home.cta_consultation') }}
                </a>

                @if ($whatsappNumber)
                    <a
                        href="https://wa.me/{{ preg_replace('/\D/', '', $whatsappNumber) }}"
                        target="_blank"
                        rel="noopener"
                        onclick="if (typeof fbq === 'function') { fbq('trackCustom', 'WhatsAppClick'); }"
                        class="inline-flex items-center justify-center gap-2 rounded-md bg-[#25D366] px-8 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-[#25D366]/90"
                    >
                        <x-icons.whatsapp class="h-5 w-5" />
                        {{ __('site.home.cta_whatsapp') }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- About teaser: reuses the About page's own first paragraph (via
         HomeController::firstParagraph()) rather than duplicate-authoring
         separate homepage copy, plus one of the real About-page photos.
         Hidden entirely if the About page doesn't exist/isn't published. --}}
    @if ($aboutExcerpt)
        <section class="py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2">
                    <img
                        src="{{ asset('images/about/about-team-meeting.jpg') }}"
                        alt="{{ __('site.about.meeting_photo_alt') }}"
                        class="h-72 w-full rounded-2xl object-cover shadow-sm sm:h-96"
                    >

                    <div>
                        <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">
                            {{ __('site.home.about_eyebrow') }}
                        </span>
                        <h2 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">
                            {{ __('site.home.about_heading') }}
                        </h2>
                        <p class="mt-4 text-lg leading-relaxed text-text-secondary">
                            {{ $aboutExcerpt }}
                        </p>
                        <a
                            href="{{ lroute('pages.about') }}"
                            class="mt-6 inline-flex items-center gap-1 font-medium text-primary-green hover:text-primary-green/80"
                        >
                            {{ __('site.home.about_cta') }} <span aria-hidden="true">&larr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Services preview: 3 flagship + up to 3 more active services, ordered
         flagship-first. Hidden entirely if no active services exist yet. --}}
    @if ($homeServices->isNotEmpty())
        <section class="bg-bg-soft py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                    <div class="max-w-2xl">
                        <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">{{ __('site.home.services_eyebrow') }}</span>
                        <h2 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">{{ __('site.home.services_heading') }}</h2>
                    </div>
                    <a href="{{ lroute('services.index') }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-primary-green hover:text-primary-green/80">
                        {{ __('site.home.services_cta') }} <span aria-hidden="true">&larr;</span>
                    </a>
                </div>

                <div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($homeServices as $service)
                        <x-card class="flex flex-col">
                            <a href="{{ lroute('services.show', $service) }}" class="block">
                                @if ($service->cover_image)
                                    <img
                                        src="{{ Illuminate\Support\Facades\Storage::url($service->cover_image) }}"
                                        alt="{{ $service->name }}"
                                        class="h-48 w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-48 w-full items-center justify-center bg-bg-soft">
                                        <span class="text-4xl">🏢</span>
                                    </div>
                                @endif
                            </a>

                            <div class="flex flex-1 flex-col p-6">
                                @if ($service->is_flagship)
                                    <span class="mb-3 inline-flex w-fit items-center rounded-full bg-luxury-gold/10 px-3 py-1 text-xs font-semibold text-luxury-gold">
                                        {{ __('site.common.flagship_badge') }}
                                    </span>
                                @endif

                                <h3 class="text-xl font-semibold text-text-main">
                                    <a href="{{ lroute('services.show', $service) }}" class="hover:text-primary-green">
                                        {{ $service->name }}
                                    </a>
                                </h3>

                                <p class="mt-2 flex-1 text-sm text-text-secondary">
                                    {{ Illuminate\Support\Str::limit($service->summary, 100) }}
                                </p>

                                <a
                                    href="{{ lroute('services.show', $service) }}"
                                    class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary-green hover:text-primary-green/80"
                                >
                                    {{ __('site.common.learn_more') }} <span aria-hidden="true">&larr;</span>
                                </a>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Why Invest highlights: reads live from the same why-invest-saudi-arabia
         PageSection rows the full /why-invest page renders — no duplicated copy. --}}
    @if ($whyInvestSections->isNotEmpty())
        <section class="py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                    <div class="max-w-2xl">
                        <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">{{ __('site.home.why_invest_eyebrow') }}</span>
                        <h2 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">{{ __('site.home.why_invest_heading') }}</h2>
                    </div>
                    <a href="{{ lroute('pages.why-invest') }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-primary-green hover:text-primary-green/80">
                        {{ __('site.home.why_invest_cta') }} <span aria-hidden="true">&larr;</span>
                    </a>
                </div>

                @php
                    // Same icon-keyword map as public.pages.why-invest — kept in
                    // sync manually since it's a tiny fixed lookup table, not
                    // worth extracting into a shared PHP class for 6 entries.
                    $whyInvestIcons = [
                        'chart-line' => '📈',
                        'building-office' => '🏢',
                        'map' => '🗺️',
                        'trending-up' => '📊',
                        'clipboard-check' => '✅',
                        'gift' => '🎁',
                    ];
                @endphp

                <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($whyInvestSections as $section)
                        <x-card class="p-6">
                            <span class="text-3xl" aria-hidden="true">{{ $whyInvestIcons[$section->icon] ?? '⭐' }}</span>
                            <h3 class="mt-4 font-semibold text-text-main">{{ $section->title }}</h3>
                            @if ($section->description)
                                <p class="mt-2 text-sm text-text-secondary">
                                    {{ Illuminate\Support\Str::limit($section->description, 90) }}
                                </p>
                            @endif
                        </x-card>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Formation process preview: compact numbered strip (title only, no
         description) — reads live from the same formation-process
         PageSection rows the full /formation-process page renders. --}}
    @if ($formationSections->isNotEmpty())
        <section class="bg-bg-soft py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                    <div class="max-w-2xl">
                        <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">{{ __('site.home.formation_eyebrow') }}</span>
                        <h2 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">{{ __('site.home.formation_heading') }}</h2>
                    </div>
                    <a href="{{ lroute('pages.formation-process') }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-primary-green hover:text-primary-green/80">
                        {{ __('site.home.formation_cta') }} <span aria-hidden="true">&larr;</span>
                    </a>
                </div>

                <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($formationSections as $index => $section)
                        <div class="flex items-center gap-4 rounded-xl border border-border-default bg-white p-5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-green text-sm font-bold text-white">
                                {{ $index + 1 }}
                            </span>
                            <h3 class="font-semibold text-text-main">{{ $section->title }}</h3>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Testimonials: Alpine auto-advancing carousel once more than 3 exist,
         plain grid otherwise. Hidden entirely if none are active yet. --}}
    @if ($testimonials->isNotEmpty())
        <section class="py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">{{ __('site.home.testimonials_eyebrow') }}</span>
                    <h2 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">{{ __('site.home.testimonials_heading') }}</h2>
                </div>

                @if ($testimonials->count() > 3)
                    <div
                        x-data="{ active: 0, count: {{ $testimonials->count() }} }"
                        x-init="setInterval(() => { active = (active + 1) % count }, 6000)"
                        class="relative mx-auto mt-12 max-w-3xl"
                    >
                        @foreach ($testimonials as $index => $testimonial)
                            <div x-show="active === {{ $index }}" x-transition.opacity>
                                <x-card class="p-8 text-center">
                                    <p class="text-lg text-text-main">&ldquo;{{ $testimonial->quote }}&rdquo;</p>
                                    <p class="mt-4 font-semibold text-text-main">{{ $testimonial->client_name }}</p>
                                    @if ($testimonial->client_title || $testimonial->client_country)
                                        <p class="text-sm text-text-secondary">
                                            {{ collect([$testimonial->client_title, $testimonial->client_country])->filter()->implode(' — ') }}
                                        </p>
                                    @endif
                                </x-card>
                            </div>
                        @endforeach

                        {{-- The visible dot stays 8px (h-2 w-2, unchanged design), but
                             each <button> gets an invisible p-2 hit area around it
                             (-m-2 cancels the padding's effect on layout/spacing) so
                             the actual tappable target is closer to a usable mobile
                             touch-target size instead of exactly 8x8px. --}}
                        <div class="mt-6 flex justify-center gap-2">
                            @foreach ($testimonials as $index => $testimonial)
                                <button
                                    type="button"
                                    @click="active = {{ $index }}"
                                    class="-m-2 flex items-center justify-center rounded-full p-2"
                                    aria-label="{{ __('site.home.testimonial_slide_label') }} {{ $index + 1 }}"
                                >
                                    <span :class="active === {{ $index }} ? 'bg-primary-green' : 'bg-border-default'" class="h-2 w-2 rounded-full"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($testimonials as $testimonial)
                            <x-card class="p-6">
                                <p class="text-text-main">&ldquo;{{ $testimonial->quote }}&rdquo;</p>
                                <p class="mt-4 font-semibold text-text-main">{{ $testimonial->client_name }}</p>
                                @if ($testimonial->client_title || $testimonial->client_country)
                                    <p class="text-sm text-text-secondary">
                                        {{ collect([$testimonial->client_title, $testimonial->client_country])->filter()->implode(' — ') }}
                                    </p>
                                @endif
                            </x-card>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Countries teaser: compact flag+name strip, same fallback (🌍) as
         /countries for records with no flag image. Hidden entirely if no
         active countries exist yet. --}}
    @if ($homeCountries->isNotEmpty())
        <section class="bg-bg-soft py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">{{ __('site.home.countries_eyebrow') }}</span>
                        <h2 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">{{ __('site.home.countries_heading') }}</h2>
                    </div>
                    <a href="{{ lroute('countries.index') }}" class="inline-flex items-center gap-1 font-medium text-primary-green hover:text-primary-green/80">
                        {{ __('site.home.countries_cta') }} <span aria-hidden="true">&larr;</span>
                    </a>
                </div>

                <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($homeCountries as $country)
                        <x-card class="flex min-w-0 items-center gap-3 p-4">
                            @if ($country->flag_image)
                                <img
                                    src="{{ Illuminate\Support\Facades\Storage::url($country->flag_image) }}"
                                    alt="{{ $country->name }}"
                                    class="h-10 w-10 shrink-0 rounded-full border border-border-default object-cover"
                                >
                            @else
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-bg-soft text-base">
                                    🌍
                                </div>
                            @endif
                            <span class="truncate font-medium text-text-main">{{ $country->name }}</span>
                        </x-card>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FAQ preview: first 3-4 active FAQs, question + answer, same
         accordion styling reference as /faqs (kept static here — no
         expand/collapse needed for a short 3-4 item teaser). Hidden
         entirely if no active FAQs exist yet. --}}
    @if ($homeFaqs->isNotEmpty())
        <section class="py-16 sm:py-24">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">{{ __('site.home.faqs_eyebrow') }}</span>
                    <h2 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">{{ __('site.home.faqs_heading') }}</h2>
                </div>

                <div class="mt-10 space-y-4">
                    @foreach ($homeFaqs as $faq)
                        <x-card class="p-6">
                            <h3 class="font-semibold text-text-main">{{ $faq->question }}</h3>
                            <p class="mt-2 text-text-secondary">{{ $faq->answer }}</p>
                        </x-card>
                    @endforeach
                </div>

                <div class="mt-8 text-center">
                    <a href="{{ lroute('faqs.index') }}" class="inline-flex items-center gap-1 font-medium text-primary-green hover:text-primary-green/80">
                        {{ __('site.home.faqs_cta') }} <span aria-hidden="true">&larr;</span>
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- Latest articles: 3 most recently published, same card style as
         /articles. Hidden entirely if nothing is published yet. --}}
    @if ($latestArticles->isNotEmpty())
        <section class="bg-bg-soft py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                    <div class="max-w-2xl">
                        <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">{{ __('site.home.articles_eyebrow') }}</span>
                        <h2 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">{{ __('site.home.articles_heading') }}</h2>
                    </div>
                    <a href="{{ lroute('articles.index') }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-primary-green hover:text-primary-green/80">
                        {{ __('site.home.articles_cta') }} <span aria-hidden="true">&larr;</span>
                    </a>
                </div>

                <div class="mt-10 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latestArticles as $article)
                        <x-card class="flex flex-col">
                            <a href="{{ lroute('articles.show', $article) }}" class="block">
                                @if ($article->cover_image)
                                    <img
                                        src="{{ Illuminate\Support\Facades\Storage::url($article->cover_image) }}"
                                        alt="{{ $article->title }}"
                                        class="h-48 w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-48 w-full items-center justify-center bg-white">
                                        <span class="text-4xl">📰</span>
                                    </div>
                                @endif
                            </a>

                            <div class="flex flex-1 flex-col p-6">
                                <time class="text-xs font-medium text-text-secondary" datetime="{{ $article->published_at->toIso8601String() }}">
                                    {{ $article->published_at->locale(app()->getLocale())->translatedFormat('d F Y') }}
                                </time>

                                <h3 class="mt-2 text-xl font-semibold text-text-main">
                                    <a href="{{ lroute('articles.show', $article) }}" class="hover:text-primary-green">
                                        {{ $article->title }}
                                    </a>
                                </h3>

                                @if ($article->excerpt)
                                    <p class="mt-2 flex-1 text-sm text-text-secondary">
                                        {{ Illuminate\Support\Str::limit($article->excerpt, 100) }}
                                    </p>
                                @endif

                                <a
                                    href="{{ lroute('articles.show', $article) }}"
                                    class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary-green hover:text-primary-green/80"
                                >
                                    {{ __('site.common.read_more') }} <span aria-hidden="true">&larr;</span>
                                </a>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Contact section: the real, functional /contact form embedded
         directly on the homepage (not just a link to it) — same route,
         same StoreContactRequest, same honeypot/rate-limiting/
         attribution capture, via x-contact-form. redirect-to="home"
         sends a successful submit back to `/#contact` (see
         ContactController::redirectTarget()) instead of /contact. --}}
    <section id="contact" class="py-16 sm:py-24">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <span class="text-sm font-semibold uppercase tracking-wide text-luxury-gold">
                    {{ __('site.contact.eyebrow') }}
                </span>
                <h2 class="mt-2 text-3xl font-bold text-text-main sm:text-4xl">
                    {{ __('site.contact.heading') }}
                </h2>
                <p class="mt-4 text-lg text-text-secondary">
                    {{ __('site.contact.subheading') }}
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-10 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <x-card class="p-6">
                        <h3 class="font-semibold text-text-main">{{ __('site.contact.info_heading') }}</h3>

                        <dl class="mt-4 space-y-4 text-sm">
                            @if ($contactPhone)
                                <div>
                                    <dt class="font-medium text-text-secondary">{{ __('site.contact.phone_label') }}</dt>
                                    <dd class="mt-1 text-text-main">{{ $contactPhone }}</dd>
                                </div>
                            @endif

                            @if ($contactWhatsapp)
                                <div>
                                    <dt class="font-medium text-text-secondary">{{ __('site.contact.whatsapp_label') }}</dt>
                                    <dd class="mt-1 text-text-main">{{ $contactWhatsapp }}</dd>
                                </div>
                            @endif

                            @if ($contactEmail)
                                <div>
                                    <dt class="font-medium text-text-secondary">{{ __('site.contact.email_label') }}</dt>
                                    <dd class="mt-1 text-text-main">{{ $contactEmail }}</dd>
                                </div>
                            @endif

                            @if ($contactAddress)
                                <div>
                                    <dt class="font-medium text-text-secondary">{{ __('site.contact.address_label') }}</dt>
                                    <dd class="mt-1 text-text-main">{{ $contactAddress }}</dd>
                                </div>
                            @endif
                        </dl>
                    </x-card>
                </div>

                <div class="lg:col-span-3">
                    <x-contact-form redirect-to="home" />
                </div>
            </div>
        </div>
    </section>

    {{-- Final CTA band: same 3 actions as the hero, closing the page. --}}
    <section class="bg-dark-green py-16 sm:py-24">
        <div class="max-w-4xl mx-auto px-4 text-center sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white sm:text-4xl">
                {{ __('site.home.final_cta_heading') }}
            </h2>
            <p class="mt-4 text-lg text-white/80">
                {{ __('site.home.final_cta_subheading') }}
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a
                    href="{{ lroute('consultation') }}"
                    class="inline-flex items-center justify-center rounded-md bg-primary-green px-8 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-primary-green/90"
                >
                    {{ __('site.home.cta_start') }}
                </a>

                <a
                    href="{{ lroute('consultation') }}"
                    class="inline-flex items-center justify-center rounded-md border border-white/40 bg-white/10 px-8 py-3 text-base font-semibold text-white transition hover:bg-white/20"
                >
                    {{ __('site.home.cta_consultation') }}
                </a>

                @if ($whatsappNumber)
                    <a
                        href="https://wa.me/{{ preg_replace('/\D/', '', $whatsappNumber) }}"
                        target="_blank"
                        rel="noopener"
                        onclick="if (typeof fbq === 'function') { fbq('trackCustom', 'WhatsAppClick'); }"
                        class="inline-flex items-center justify-center gap-2 rounded-md bg-[#25D366] px-8 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-[#25D366]/90"
                    >
                        <x-icons.whatsapp class="h-5 w-5" />
                        {{ __('site.home.cta_whatsapp') }}
                    </a>
                @endif
            </div>
        </div>
    </section>
</x-public-layout>
