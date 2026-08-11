{{--
    The cookie consent banner.

    THIS SITE HAD NO CONSENT MECHANISM before visitor session tracking was
    built. That is why this component exists: VisitorTrackingGate treats an
    absent decision as refusal, so without somewhere to grant it the feature
    would correctly record nothing, forever.

    Scope, stated plainly so nobody assumes more than is true: accepting here
    permits the visitor-session journey recording described in the privacy
    policy. It does NOT currently gate the Meta / GTM / GA4 / TikTok pixels,
    which still load unconditionally from components/tracking-scripts. Wiring
    those behind this banner is a separate decision with real analytics
    consequences, and pretending it had already been made would be worse than
    saying so here.

    Not rendered at all when tracking is switched off in Settings — a banner
    asking permission for something that is not happening is noise.

    z-40: above the floating buttons (z-30) so it cannot be obscured, below
    the mobile drawer panel (z-50) so it does not cover an open menu.
--}}
@if ($enabled)
    <div
        x-data="cookieConsent"
        x-show="! decided"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        role="region"
        aria-label="{{ __('site.consent.aria_label') }}"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-border-default bg-white/95 p-4 shadow-2xl backdrop-blur sm:p-5"
    >
        <div class="max-w-7xl mx-auto flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm leading-relaxed text-text-secondary">
                {{ __('site.consent.message') }}
                <a
                    href="{{ lroute('pages.privacy-policy') }}"
                    class="font-medium text-primary-green underline hover:text-dark-green focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green"
                >
                    {{ __('site.consent.policy_link') }}
                </a>
            </p>

            {{-- Decline first in the DOM and equally prominent. A banner
                 where refusing is harder than accepting is not consent. --}}
            <div class="flex shrink-0 flex-wrap gap-3">
                <button
                    type="button"
                    @click="decline()"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-border-default px-5 text-sm font-semibold text-text-main transition hover:border-primary-green hover:text-primary-green focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green"
                >
                    {{ __('site.consent.decline') }}
                </button>

                <button
                    type="button"
                    @click="accept()"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-md bg-primary-green px-5 text-sm font-semibold text-white transition hover:bg-dark-green focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-green focus-visible:ring-offset-2"
                >
                    {{ __('site.consent.accept') }}
                </button>
            </div>
        </div>
    </div>
@endif
