<div align="center">

<img src="public/images/brand/logo-full-color-256.png" alt="Bawabat Taasees Al Sharikat" width="190">

# بوابة تأسيس الشركات · Bawabat Taasees Al Sharikat

**A bilingual (Arabic-first, RTL) company-formation platform for the Saudi market — public marketing site, full content-management dashboard, lead attribution, and privacy-first visitor analytics.**

Built with Laravel 12 · PHP 8.2+ · Tailwind CSS · Alpine.js

<br>

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=flat-square&logo=alpinedotjs&logoColor=white)
![Tests](https://img.shields.io/badge/tests-1%2C211_passing-22c55e?style=flat-square)
![RTL](https://img.shields.io/badge/RTL-first-0B5D3B?style=flat-square)
![WCAG](https://img.shields.io/badge/WCAG_2.1-AA-0B5D3B?style=flat-square)

</div>

---

## Overview

Saudi Arabia's company-formation market is Arabic-speaking and mobile-heavy, and the
businesses in it live or die on paid acquisition. This platform is built around those
three facts.

It is **Arabic-first**: Arabic is the default locale with no URL prefix, the layout is
right-to-left by default, and English is the translation — not the other way round.
It is **mobile-first**: every screen, including the admin dashboard, was built and
tested at 375px before desktop. And it treats **lead attribution as a first-class
feature**, not an afterthought: every enquiry carries the campaign, ad set, and click
ID that produced it, all the way through to a return-on-ad-spend report.

Everything a non-technical administrator needs to run the business — services,
articles, pages, navigation, contact details, tracking pixels, legal copy — is
editable from the dashboard. No deployment is required to change a comma.

<div align="center">
<img src="docs/screenshots/01-home-ar.jpg" alt="Homepage, Arabic" width="100%">
<em>Homepage — Arabic (RTL), the default locale</em>
</div>

---

## Table of contents

- [Highlights](#highlights)
- [The public website](#the-public-website)
- [The admin dashboard](#the-admin-dashboard)
- [Lead attribution & marketing analytics](#lead-attribution--marketing-analytics)
- [Visitor session journeys](#visitor-session-journeys)
- [Bilingual & RTL architecture](#bilingual--rtl-architecture)
- [Accessibility](#accessibility)
- [Privacy & consent](#privacy--consent)
- [Security](#security)
- [Performance](#performance)
- [Architecture](#architecture)
- [Testing](#testing)
- [Getting started](#getting-started)
- [Deployment](#deployment)
- [Project statistics](#project-statistics)

---

## Highlights

| | |
|---|---|
| 🌍 **Fully bilingual** | Arabic (default, RTL) and English, with per-locale URLs, `hreflang` alternates and a language toggle that preserves the current page |
| 📝 **Complete CMS** | Services, countries, FAQs, articles, testimonials, pages, page sections, media library and a drag-to-reorder navigation manager |
| 🎯 **Lead attribution** | First-touch and latest-touch capture, UTM + click-ID persistence, campaign linking and ROAS reporting |
| 📊 **Visitor journeys** | Consent-gated session recording with a full page-by-page timeline and JSON export |
| 🔐 **Privacy by design** | Opt-in consent, DNT/GPC honoured, IP truncation, configurable retention with automatic pruning |
| ♿ **WCAG 2.1 AA** | Verified contrast, skip links, focus traps, reduced-motion support, 44px touch targets |
| 📱 **Mobile-first** | Including the dashboard: tables collapse to cards, off-canvas drawer, no horizontal scroll at 375px |
| ✅ **1,211 tests** | 4,098 assertions across feature and unit suites |

---

## The public website

Ten content-driven pages, every one of them editable from the dashboard and
available in both languages.

### Homepage

A full-height hero over a muted background video (with a pause control and
`prefers-reduced-motion` support), followed by auto-scrolling service, benefit and
process marquees, testimonials, supported countries, FAQs, latest articles and an
inline contact form.

<div align="center">
<img src="docs/screenshots/02-home-en.jpg" alt="Homepage, English" width="100%">
<em>The same homepage in English — the entire layout mirrors to LTR</em>
</div>

### Services

<table>
<tr>
<td width="50%"><img src="docs/screenshots/03-services-ar.jpg" alt="Services index"></td>
<td width="50%"><img src="docs/screenshots/04-service-detail-ar.jpg" alt="Service detail"></td>
</tr>
<tr>
<td align="center"><em>Services index — flagship services badged</em></td>
<td align="center"><em>Service detail — requirements and process steps</em></td>
</tr>
</table>

### Content pages

<table>
<tr>
<td width="50%"><img src="docs/screenshots/05-why-invest-ar.jpg" alt="Why invest"></td>
<td width="50%"><img src="docs/screenshots/06-formation-ar.jpg" alt="Formation process"></td>
</tr>
<tr>
<td align="center"><em>Why Invest — driven by editable page sections</em></td>
<td align="center"><em>Formation Process — numbered step sections</em></td>
</tr>
<tr>
<td width="50%"><img src="docs/screenshots/07-countries-ar.jpg" alt="Countries"></td>
<td width="50%"><img src="docs/screenshots/08-faqs-ar.jpg" alt="FAQs"></td>
</tr>
<tr>
<td align="center"><em>Supported countries</em></td>
<td align="center"><em>FAQs — accessible accordion</em></td>
</tr>
</table>

### Blog & lead capture

<table>
<tr>
<td width="50%"><img src="docs/screenshots/09-articles-ar.jpg" alt="Articles"></td>
<td width="50%"><img src="docs/screenshots/10-consultation-ar.jpg" alt="Consultation form"></td>
</tr>
<tr>
<td align="center"><em>Blog index with moderated comments on each article</em></td>
<td align="center"><em>Consultation request — the primary conversion form</em></td>
</tr>
</table>

Both public forms carry a honeypot field, rate limiting (5 requests/minute),
explicit consent capture, and hidden attribution snapshots that travel with the
submission.

### Mobile

Every page was designed at 375px first.

<table>
<tr>
<td width="50%"><img src="docs/screenshots/12-home-mobile-ar.jpg" alt="Mobile homepage"></td>
<td width="50%"><img src="docs/screenshots/13-services-mobile-en.jpg" alt="Mobile services, English"></td>
</tr>
<tr>
<td align="center"><em>Homepage at 390px (Arabic)</em></td>
<td align="center"><em>Services at 390px (English)</em></td>
</tr>
</table>

### Authentication & error pages

<table>
<tr>
<td width="50%"><img src="docs/screenshots/14-login.jpg" alt="Login"></td>
<td width="50%"><img src="docs/screenshots/15-404.jpg" alt="404 page"></td>
</tr>
<tr>
<td align="center"><em>Admin sign-in</em></td>
<td align="center"><em>Branded bilingual 404 — also 403, 419, 500 and 503</em></td>
</tr>
</table>

Error pages deliberately render on a standalone shell that performs **no database
queries**, so they still work when the database is the thing that is down.

---

## The admin dashboard

A complete back office in Arabic and English, with a locale toggle independent of
the public site — an administrator can work in Arabic while the site is being
viewed in English.

<div align="center">
<img src="docs/screenshots/20-dash-home.jpg" alt="Dashboard home" width="100%">
<em>Dashboard home — "needs attention" counters, content overview and lead metrics</em>
</div>

### Content management

<table>
<tr>
<td width="50%"><img src="docs/screenshots/21-dash-services.jpg" alt="Services admin"></td>
<td width="50%"><img src="docs/screenshots/38-dash-articles.jpg" alt="Articles admin"></td>
</tr>
<tr>
<td align="center"><em>Services — sortable, with flagship flags</em></td>
<td align="center"><em>Articles — publish scheduling</em></td>
</tr>
</table>

<div align="center">
<img src="docs/screenshots/22-dash-service-form.jpg" alt="Service editor" width="100%">
<em>Every content form is bilingual side-by-side, with per-record SEO overrides</em>
</div>

<table>
<tr>
<td width="50%"><img src="docs/screenshots/23-dash-pages.jpg" alt="Pages admin"></td>
<td width="50%"><img src="docs/screenshots/35-dash-media.jpg" alt="Media library"></td>
</tr>
<tr>
<td align="center"><em>Fixed pages — content editable, structure protected</em></td>
<td align="center"><em>Media library with alt-text enforcement</em></td>
</tr>
</table>

### Navigation manager

The public navbar is **data-driven, not hardcoded**. Items can be created,
reordered, nested one level deep, and hidden — all without a deployment.

<div align="center">
<img src="docs/screenshots/24-dash-menu.jpg" alt="Navigation manager" width="100%">
<em>Drag-free reordering with explicit controls, plus nesting and visibility toggles</em>
</div>

### Site settings

Contact details, social links, the site logo, SEO defaults and the analytics
retention policy — all editable, all validated per field type.

<div align="center">
<img src="docs/screenshots/32-dash-settings.jpg" alt="Site settings" width="100%">
<em>Settings are defined once in a single source of truth that drives the form, the validation rules and the seeder</em>
</div>

### Dashboard on mobile

<table>
<tr>
<td width="50%"><img src="docs/screenshots/36-dash-mobile.jpg" alt="Dashboard on mobile"></td>
<td width="50%"><img src="docs/screenshots/37-dash-leads-mobile.jpg" alt="Leads table on mobile"></td>
</tr>
<tr>
<td align="center"><em>Off-canvas drawer, stacked stat tiles</em></td>
<td align="center"><em>Data tables collapse into labelled cards — no sideways scrolling</em></td>
</tr>
</table>

The table-to-card transformation is pure CSS keyed on `data-label` attributes, so
there is no duplicated markup and no second query for the mobile view.

---

## Lead attribution & marketing analytics

Every enquiry is captured with the full marketing context that produced it.

**How it works.** A first-party script captures UTM parameters and click IDs
(`gclid`, `fbclid`, `ttclid`) on arrival and stores two cookies: `first_touch`,
written once and never overwritten, and `latest_touch`, refreshed whenever new
parameters appear. Both snapshots travel with the form submission. On the server
they are decoded, the source is resolved against a configurable lead-source table,
and the external campaign ID is linked to an internal campaign record so reports
can join on an indexed foreign key instead of matching free text.

<table>
<tr>
<td width="50%"><img src="docs/screenshots/25-dash-leads.jpg" alt="Leads"></td>
<td width="50%"><img src="docs/screenshots/26-dash-lead-detail.jpg" alt="Lead detail"></td>
</tr>
<tr>
<td align="center"><em>Leads — filterable by source, service, type, date and conversion</em></td>
<td align="center"><em>Lead detail with the full attribution record and conversion timeline</em></td>
</tr>
</table>

<div align="center">
<img src="docs/screenshots/27-dash-reports.jpg" alt="Reports" width="100%">
<em>Reports — conversion rate, conversion value, leads over time, and breakdowns by source, platform and request type</em>
</div>

<table>
<tr>
<td width="50%"><img src="docs/screenshots/28-dash-campaigns.jpg" alt="Campaigns"></td>
<td width="50%"><img src="docs/screenshots/34-dash-lead-sources.jpg" alt="Lead sources"></td>
</tr>
<tr>
<td align="center"><em>Campaigns with budget, spend and ROAS</em></td>
<td align="center"><em>Lead sources — renaming a key cascades across existing leads</em></td>
</tr>
</table>

<table>
<tr>
<td width="50%"><img src="docs/screenshots/31-dash-contact-msgs.jpg" alt="Contact messages"></td>
<td width="50%"><img src="docs/screenshots/33-dash-tracking.jpg" alt="Tracking settings"></td>
</tr>
<tr>
<td align="center"><em>Contact inbox with an unread badge in the sidebar</em></td>
<td align="center"><em>Tracking IDs — format-validated per platform, never hardcoded</em></td>
</tr>
</table>

---

## Visitor session journeys

A privacy-first analytics layer that answers a different question from attribution:
not *"where did this lead come from"* but *"what did this visitor actually do"*.

Server-side middleware appends a page view to the visitor's journey; a lightweight
`sendBeacon` client reports real time-on-page. Form submissions are recorded as
action events, and when a session converts it is linked to the lead it produced.

<div align="center">
<img src="docs/screenshots/29-dash-sessions.jpg" alt="Visitor sessions" width="100%">
<em>Sessions — entry page, page count, duration, device, source/campaign and conversion state</em>
</div>

<div align="center">
<img src="docs/screenshots/30-dash-session-detail.jpg" alt="Session journey" width="100%">
<em>The full ordered journey, with time on each page and the action that ended it</em>
</div>

**Designed not to cost anything at request time.** The middleware decides whether to
record during the request, then performs its single indexed read and single write in
`terminate()` — after the response has already been sent to the browser. A test pins
the cost at one `SELECT` plus one write.

**And designed not to grow without bound.** The journey is a JSON column on a row
rewritten on every page view, so it is capped at 100 events. Past the cap the stored
timeline stops growing but the true page count keeps counting, so a long session
still reads as one rather than silently looking like a 100-page visit.

Both the filtered list and any individual journey export as JSON.

---

## Bilingual & RTL architecture

Arabic is the default locale and lives at the root (`/services`); English is prefixed
(`/en/services`). Every public route is registered **twice** from a single closure —
once with canonical names, once with an `.en` suffix — and views resolve links through
a helper that automatically picks the right variant for the active locale.

```
/                      →  الرئيسية          /en                  →  Home
/services              →  خدماتنا           /en/services         →  Services
/services/{slug}       →  تفاصيل الخدمة      /en/services/{slug}  →  Service detail
```

- **Translatable content** is stored as JSON per field, with Arabic as the fallback
  so a record with no English translation renders its Arabic rather than a blank.
- **`hreflang` alternates** (`ar`, `en`, `x-default`) are emitted on every page,
  pointing at the same route with the same parameters in the other language.
- **The language toggle preserves the page** — switching languages on a service
  detail page lands on that same service, not the homepage.
- **Logical CSS properties** (`ps`/`pe`, `ms`/`me`, `start`/`end`) throughout, so one
  stylesheet serves both directions. The handful of physical properties that remain
  are deliberate and documented.
- **760 translation keys** across the site and dashboard, with no missing keys in
  either language.

---

## Accessibility

Targeted at **WCAG 2.1 AA** and verified rather than assumed.

- **Contrast** — every text/background pair in the brand palette was computed. The
  decorative gold fails AA on white at 2.96:1, so a second, darker gold token
  (5.04:1) exists specifically for text, and the decorative one is restricted to
  borders, icon fills and backgrounds.
- **Keyboard** — skip-to-content links on both layouts, focus traps on both
  off-canvas drawers, Escape to close, and focus returned to the trigger on dismiss.
- **Motion** — `prefers-reduced-motion` is honoured by the marquees, the background
  videos and the testimonial carousel, and every one of them has a visible pause
  control regardless (WCAG 2.2.2).
- **Touch targets** — a 44px minimum on form controls at every width, and on buttons
  and action links wherever a finger is doing the aiming.
- **Screen readers** — alt text on all 27 images, `aria-expanded`/`aria-haspopup` on
  dropdowns, `aria-current` on active filters, and duplicated marquee cards hidden
  from assistive technology and removed from the tab order.

---

## Privacy & consent

Session recording is **opt-in**. An absent decision is treated as a refusal.

- **Consent banner** where declining is exactly as prominent as accepting, and the
  copy states specifically what is being consented to.
- **DNT and GPC are honoured** and outrank the cookie — a browser-level refusal
  cannot be overridden by a banner the visitor may never have seen.
- **No full IP is ever stored.** Addresses are truncated before storage (IPv4 `/24`,
  IPv6 `/48`). Truncation rather than hashing, because a hash of a 32-bit address
  space is brute-forceable in seconds and therefore not anonymous.
- **No query strings are stored** anywhere in a journey — they routinely carry
  arbitrary user input, and the UTM set is already captured structurally.
- **No raw user-agent string** is kept; only three coarse buckets.
- **Configurable retention** (90 days by default) enforced by a scheduled prune
  command, and disclosed in the privacy policy.
- **Marketing pixels are gated too.** Google tags load under Consent Mode v2 in a
  denied state and are updated to granted on consent; Meta and TikTok are not loaded
  at all until the visitor agrees.

---

## Security

- **Security headers** on every response — including error responses, which bypass
  the middleware stack and are handled explicitly: HSTS (production HTTPS only),
  `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`
  and a report-only CSP. `X-Powered-By` is suppressed.
- **Trusted proxy handling** so HTTPS is detected correctly behind a TLS-terminating
  load balancer — without it, HSTS is never sent and every generated URL claims
  `http` on an `https` site.
- **HTML sanitisation on write** via HTMLPurifier against an explicit allow-list.
  Article and page bodies are the only fields permitted to contain markup, and they
  are cleaned before they reach the database, not on the way out.
- **Rate limiting** on public forms (5/min) and authenticated writes (60/min, keyed
  per user, with safe methods exempt).
- **Mass-assignment discipline** — admin flags, system flags and inbox read-state are
  deliberately excluded from `$fillable` and written only through their owning
  service.
- **Format-validated tracking IDs**, because those values are interpolated into inline
  `<script>` blocks.
- **Secure session cookies** defaulting to `secure` in production, `httpOnly` and
  `SameSite=Lax`.

---

## Performance

Measured by driving the HTTP kernel in-process and counting queries.

| Page | Queries |
|---|---|
| Homepage | 17 |
| Services / Articles / Countries | 6 |
| Contact | 5 |
| Dashboard home | 20 |
| Reports | 15 |

- **Settings load once per request** through a singleton repository — down from eight
  individual key lookups on the homepage.
- **No N+1 queries.** The data-driven navbar is exactly two queries regardless of how
  many items it holds; lead and campaign listings eager-load or aggregate.
- **Bundle:** 26.4 kB of JavaScript and 11.6 kB of CSS, gzipped.
- **Images** are lazy-loaded below the fold and carry intrinsic dimensions; page
  heroes are eagerly loaded and marked high priority instead.
- **`route:cache`, `config:cache` and `view:cache`** all run clean.

---

## Architecture

```
app/
├── Console/Commands/      Content translation, session retention pruning
├── Http/
│   ├── Controllers/
│   │   ├── Public/        Marketing site, forms, sitemap, analytics beacon
│   │   └── Dashboard/     Admin CRUD, reports, settings, sessions
│   ├── Middleware/        Locale, admin gate, security headers, session capture
│   └── Requests/          Form Requests — validation lives here, never in controllers
├── Models/                19 Eloquent models with translatable JSON fields
├── Services/
│   ├── Analytics/         Session capture, consent gate, IP anonymisation, UA parsing
│   ├── Cms/               Content publishing, sanitisation, menus, settings
│   ├── Crm/               Contact message inbox
│   ├── Marketing/         Attribution, campaigns, conversions, lead sources
│   └── Seo/               Meta resolution and tag rendering
└── View/Components/       Layout components

resources/
├── css/app.css            Tailwind + the responsive and marquee layers
├── js/                    Alpine, attribution capture, session beacon, motion controls
└── views/
    ├── components/        Reusable Blade components
    ├── dashboard/         Admin screens
    ├── public/            Marketing pages
    └── errors/            Branded bilingual error pages
```

**Conventions applied throughout:**

- Controllers stay thin — validation in Form Requests, writes owned by services.
- Authorisation at the route-middleware layer; Form Request `authorize()` is a
  documented pass-through rather than a second, divergent gate.
- Anything with a fixed set of keys (settings, tracking IDs) is defined **once** in a
  single constant that drives the editor UI, the validation rules and the seeder, so
  they cannot drift apart.
- Seeders bootstrap; they never overwrite. Content changes to an existing install ship
  as idempotent data migrations, so re-seeding can never destroy admin-edited copy.

---

## Testing

```
Tests:    1,211 passed (4,098 assertions)
Duration: ~22s
```

81 test files covering:

- **Every GET route** in both locales plus the dashboard, with a guard that fails if
  the sweep ever narrows to nothing and starts passing vacuously.
- **Public pages** — rendering, SEO tags, sitemap, canonical and `hreflang`.
- **Forms** — validation, honeypot, throttling, consent capture, attribution.
- **Dashboard CRUD** — authorisation, validation, XSS sanitisation on write.
- **Analytics** — capture, bot filtering, IP anonymisation, consent and DNT, lead
  linking, retention pruning, export shape.
- **Accessibility and responsive structure** — contrast tokens, touch targets, table
  stacking, drawer behaviour.
- **Security** — headers on normal and exception responses, CSP, cookie flags,
  trusted proxies.

```bash
php artisan test                 # full suite
php artisan test --filter=Lead   # a single area
./vendor/bin/pint --test         # code style
```

---

## Getting started

### Requirements

PHP 8.2+ · Composer · Node.js 18+ · MySQL 8 (or SQLite)

### Installation

```bash
git clone https://github.com/m77mdabdo/Bawabat-Tasees.git
cd Bawabat-Tasees

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Set `APP_ENV=local` and `APP_DEBUG=true` in `.env` for development, configure your
database connection, then:

```bash
php artisan migrate --seed
php artisan storage:link

npm run build      # or: npm run dev
php artisan serve
```

The seed loads **production content only** — real services, countries, FAQs, articles
and pages. Sample testimonials, campaigns, leads and conversions are opt-in:

```bash
php artisan db:seed --class=DemoDataSeeder
```

### Admin access

Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env` before seeding. Outside local and
testing environments the seeder refuses to fall back to placeholder credentials.

See [`docs/setup-instructions.md`](docs/setup-instructions.md) for the full setup
walkthrough.

---

## Deployment

```bash
composer install --no-dev --optimize-autoloader
npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Schedule the task runner so analytics retention is enforced:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

**Pre-launch checklist**

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` on `https://`
- [ ] Real `ADMIN_EMAIL` / `ADMIN_PASSWORD`
- [ ] `TRUSTED_PROXIES` set if behind a load balancer
- [ ] Real contact details, social links and SEO defaults entered in Settings
- [ ] Legal pages reviewed by a qualified professional
- [ ] HTTPS enforced at the web server
- [ ] `SESSION_SECURE_COOKIE=true`, `LOG_LEVEL=warning`, `LOG_STACK=daily`
- [ ] Demo data **not** loaded

---

## Project statistics

| | |
|---|---|
| PHP source | ~28,800 lines |
| Models | 19 |
| Controllers | 42 |
| Service classes | 20 |
| Form Requests | 30 |
| Blade views | 125 |
| Database migrations | 31 |
| Registered routes | 144 |
| Translation keys | 760 |
| Tests | 1,211 passing · 4,098 assertions |
| JS / CSS bundle | 26.4 kB / 11.6 kB gzipped |

---

<div align="center">

**بوابة تأسيس الشركات** · Bawabat Taasees Al Sharikat

</div>
