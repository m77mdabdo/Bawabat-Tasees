/**
 * First-party attribution capture. Runs on every page load (bundled into
 * the shared app.js entry point). Reads UTM/click-ID parameters from the
 * URL and persists them as two first-party cookies:
 *
 *  - bts_first_touch: set ONLY if it doesn't already exist. Never
 *    overwritten again for the life of the cookie — this is "how did
 *    this visitor originally find us."
 *  - bts_latest_touch: overwritten every time new tracking parameters
 *    are present in the URL (sliding 30-day expiry) — this is "what
 *    immediately preceded their most recent visit."
 *
 * If a page load has no tracking parameters at all, neither cookie is
 * touched — whatever's already stored is preserved untouched.
 */

const COOKIE_MAX_AGE_DAYS = 30;
const TRACKED_PARAMS = [
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_content',
    'utm_term',
    'campaign_id',
    'adset_id',
    'ad_id',
    'gclid',
    'fbclid',
    'ttclid',
];

function getCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));

    return match ? decodeURIComponent(match[1]) : null;
}

function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 24 * 60 * 60 * 1000).toUTCString();

    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
}

function extractTouchFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const touch = {};
    let hasAny = false;

    TRACKED_PARAMS.forEach((key) => {
        const value = params.get(key);
        if (value) {
            touch[key] = value;
            hasAny = true;
        }
    });

    if (!hasAny) {
        return null;
    }

    touch.landing_page = window.location.pathname;
    touch.referrer = document.referrer || null;
    touch.captured_at = new Date().toISOString();

    return touch;
}

function captureAttribution() {
    const touch = extractTouchFromUrl();

    if (!touch) {
        return;
    }

    if (!getCookie('bts_first_touch')) {
        setCookie('bts_first_touch', JSON.stringify(touch), COOKIE_MAX_AGE_DAYS);
    }

    setCookie('bts_latest_touch', JSON.stringify(touch), COOKIE_MAX_AGE_DAYS);
}

function readTouch(cookieName) {
    const raw = getCookie(cookieName);

    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch (e) {
        return null;
    }
}

captureAttribution();

window.BtsAttribution = {
    getFirstTouch: () => readTouch('bts_first_touch'),
    getLatestTouch: () => readTouch('bts_latest_touch'),
};
