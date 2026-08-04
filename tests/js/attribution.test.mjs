// Plain Node test (no framework/dependency needed — Node's built-in test
// runner) for resources/js/attribution.js. This is pure client-side cookie
// logic that never touches the server, so PHPUnit can't exercise it; this
// is the actual proof for the "first-touch never overwritten / latest-touch
// always updated / untouched when no tracking params present" behavior.
//
// Run with: node --test tests/js

import assert from 'node:assert/strict';
import test from 'node:test';
import vm from 'node:vm';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const source = fs.readFileSync(
    path.join(__dirname, '../../resources/js/attribution.js'),
    'utf8'
);

/**
 * Simulates one page load. Reuses the same cookieStore object across calls
 * (like a real browser's cookie jar persisting across navigations), but
 * runs the script in a brand new vm context each time — attribution.js
 * declares top-level const/function bindings that would collide if the
 * same context/global scope were reused for a second script execution.
 */
function loadPage(cookieStore, { search = '', pathname = '/', referrer = '' } = {}) {
    const document = {
        get cookie() {
            return Object.entries(cookieStore)
                .map(([name, value]) => `${name}=${value}`)
                .join('; ');
        },
        set cookie(str) {
            const pair = str.split(';')[0];
            const eq = pair.indexOf('=');
            const name = pair.slice(0, eq).trim();
            const value = pair.slice(eq + 1);
            cookieStore[name] = value;
        },
        referrer,
    };

    const window = { location: { search, pathname } };

    const context = vm.createContext({
        document,
        window,
        Date,
        URLSearchParams,
        JSON,
        RegExp,
        encodeURIComponent,
        decodeURIComponent,
    });

    vm.runInContext(source, context);

    return context.window.BtsAttribution;
}

function decodeCookie(cookieStore, name) {
    const raw = cookieStore[name];

    return raw ? JSON.parse(decodeURIComponent(raw)) : null;
}

test('a page load with UTM params sets both first-touch and latest-touch cookies', () => {
    const cookieStore = {};

    loadPage(cookieStore, {
        search: '?utm_source=facebook&utm_medium=paid_social&utm_campaign=test&campaign_id=123&adset_id=456&ad_id=789',
        pathname: '/services',
    });

    const first = decodeCookie(cookieStore, 'bts_first_touch');
    const latest = decodeCookie(cookieStore, 'bts_latest_touch');

    assert.equal(first.utm_source, 'facebook');
    assert.equal(first.campaign_id, '123');
    assert.equal(first.landing_page, '/services');
    assert.equal(latest.utm_source, 'facebook');
});

test('navigating to a page with no tracking params preserves existing cookies untouched', () => {
    const cookieStore = {};

    loadPage(cookieStore, { search: '?utm_source=facebook', pathname: '/services' });
    const firstTouchRawAfterVisit1 = cookieStore['bts_first_touch'];
    const latestTouchRawAfterVisit1 = cookieStore['bts_latest_touch'];

    // Simulates the acceptance scenario: visitor navigates onward (losing
    // the query string) before eventually reaching the consultation form.
    loadPage(cookieStore, { search: '', pathname: '/consultation' });

    assert.equal(cookieStore['bts_first_touch'], firstTouchRawAfterVisit1);
    assert.equal(cookieStore['bts_latest_touch'], latestTouchRawAfterVisit1);
});

test('a second visit with DIFFERENT utm params updates latest-touch but leaves original first-touch intact', () => {
    const cookieStore = {};

    loadPage(cookieStore, { search: '?utm_source=facebook&utm_medium=paid_social', pathname: '/services' });
    const originalFirst = decodeCookie(cookieStore, 'bts_first_touch');

    loadPage(cookieStore, { search: '?utm_source=google&utm_medium=cpc', pathname: '/consultation' });

    const firstAfter = decodeCookie(cookieStore, 'bts_first_touch');
    const latestAfter = decodeCookie(cookieStore, 'bts_latest_touch');

    assert.deepEqual(firstAfter, originalFirst);
    assert.equal(firstAfter.utm_source, 'facebook');
    assert.equal(latestAfter.utm_source, 'google');
});

test('window.BtsAttribution exposes the parsed cookies for the form scripts to read', () => {
    const cookieStore = {};

    const api = loadPage(cookieStore, { search: '?utm_source=facebook', pathname: '/' });

    assert.equal(api.getFirstTouch().utm_source, 'facebook');
    assert.equal(api.getLatestTouch().utm_source, 'facebook');
});

test('a fully organic visitor with no attribution cookies gets null from both getters', () => {
    const cookieStore = {};

    const api = loadPage(cookieStore, { search: '', pathname: '/' });

    assert.equal(api.getFirstTouch(), null);
    assert.equal(api.getLatestTouch(), null);
});
