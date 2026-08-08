# Full Project Audit — 2026-08-08

Read-only audit of Bawabat Taasees Al Sharikat across seven dimensions.
No code was changed; this file is the only write.

Severity: 🔴 high · 🟡 medium · 🟢 low. Effort: S (<2h) · M (half day) ·
L (a day or more).

**Baseline at audit time:** 533 tests passing (1,665 assertions), Pint
clean, all public + dashboard routes 200 in both locales.

**Headline:** the application is functionally complete and well-tested.
Three things genuinely block launch: **no Site Settings UI** (the contact
details on the live site are placeholders the admin cannot edit), **no
security headers**, and **a brand colour that fails WCAG AA**. Everything
else is polish.

---

## 1. Completeness

### 🔴 No Site Settings management — S/M
**The public site displays placeholder contact details and no admin can
change them.** `Setting` rows are read in
`app/Http/Controllers/Public/HomeController.php:22-26` and
`app/Http/Controllers/Public/ContactController.php:22-25`, but **no
controller writes them** — there is no Settings UI. Live values today:

```
contact_phone      +966 5xx xxx xxx (placeholder — update in Settings)
contact_email      info@example.com (placeholder — update in Settings)
contact_address    Riyadh, Saudi Arabia (placeholder — update in Settings)
default_meta_title Bawabat Taasees Al Sharikat (placeholder — …)
```

These are on the homepage and contact page right now. This is the single
biggest gap and matches the "Site Settings" capability flagged as a future
step in the navigation brief.

### 🟡 `social_*` settings are seeded but never rendered — S
`social_facebook`, `social_instagram`, `social_linkedin`, `social_twitter`
exist in `settings` (`database/seeders/SettingsSeeder.php:18-21`) and are
referenced **nowhere** in any view or controller. Either wire them into
the footer or drop them.

### 🟡 Three dashboard placeholders remain — M each
`routes/dashboard.php:79` — `lead-sources`, `contact-messages`,
`settings`. Each renders a branded "coming soon" card.
- **settings** → covered by the 🔴 above.
- **contact-messages** → arguably redundant: contact submissions already
  land in Leads with `type = contact`.
- **lead-sources** → 11 seeded rows, currently only editable by seeder.

### 🟢 TODO/FIXME — none outstanding
One match, `resources/views/public/services/show.blade.php:79`, is a
comment describing an *already-fixed* dead link.

### 🟡 Dead Breeze components — S
`x-nav-link` and `x-responsive-nav-link` have **0 usages**
(`resources/views/components/nav-link.blade.php`,
`responsive-nav-link.blade.php`). They also carry the only remaining
unreplaced `focus:outline-none` (see §5) and indigo styling.

### 🟢 `media` table has 0 rows
The media library is fully wired (controller + service + tests) but
unused. Not a defect — noted so it isn't mistaken for dead code.

---

## 2. Performance & speed

Measured by driving the HTTP kernel in-process with `DB::listen`.

### 🟡 `settings` is queried 3–8× per page as individual key lookups — S
| Page | Queries | of which `settings` |
|---|---|---|
| `/` | 24 | **8** |
| `/contact` | 12 | **7** |
| `/en` | 23 | **8** |
| `/services`, `/articles`, `/countries`, `/about` | 9–10 | 3 each |

Every page pays a minimum of 3: one in the `layouts.public` composer
(`app/Providers/AppServiceProvider.php:52`) and two in
`app/Services/Seo/SeoTagService.php:149` (`default_meta_title`,
`default_meta_description`). `HomeController` and `ContactController` add
4–5 more. **Fix:** load the `settings` table once per request (it is 10
rows) into a keyed collection, or cache it — one query instead of eight.

### 🔴 axios is 51% of the JS bundle and is never called — S
`resources/js/bootstrap.js:1-4` imports and globals axios. **Nothing
calls it** — no `axios(` or `window.axios` reference exists in
`resources/`. Measured by building with and without it:

| | raw | gzip |
|---|---|---|
| current `app.js` | 95.81 kB | 35.10 kB |
| without axios | 46.92 kB | 16.96 kB |
| **saving** | **−48.9 kB** | **−18.1 kB** |

There is no AJAX anywhere in this project (Alpine + full page loads).

### 🟡 No lazy-loading or dimensions on any public image — S
**16 of 16** `<img>` tags in `resources/views/public/**` and
`layouts/public.blade.php` have neither `loading="lazy"` nor
`width`/`height`. That means no deferred loading below the fold and
cumulative-layout-shift on every image. (Note: **all 25 images across the
project DO have `alt`** — see §5.)

### 🟡 Media weight — M
`public/videos` totals **12 MB**; `public/images` 1.3 MB. Largest:
`hero-bg.mp4` 2.05 MB + `hero-bg.webm` 1.88 MB (homepage, autoplay),
`about-office-tour.mp4` 2.06 MB, `login-bg.*` 3.5 MB combined.
`logo-full-color-1024.png` is 314 KB and the navbar renders the 256px
variant — check no page pulls the 1024 one.

### 🟢 No N+1 queries found
Specifically verified: the new data-driven navbar is **exactly 2 queries**
regardless of item count (`AppServiceProvider.php:60-67` eager-loads
`visibleChildren`; `MenuItem::isActive()` and `hasVisibleChildren()` use
the loaded relation rather than re-querying). `LeadController:17`,
`CommentController:17` and `CampaignController:24-29` all eager-load or
aggregate correctly.

### 🟢 Index coverage is good
All 19 application tables carry sensible indexes, including the composites
added earlier (`services_is_active_sort_order_index`,
`page_sections_page_active_sort_index`,
`articles_is_published_published_at_index`, `leads_source_platform_index`,
`conversion_events_event_type_index`/`_occurred_at_index`,
`menu_items_tree_index`). No hot column found without one.

### 🟡 Route caching is blocked — S
`routes/dashboard.php:79-88` registers the three coming-soon placeholders
as **closures**, which makes `php artisan route:cache` fail. Converting
them to a tiny invokable controller unblocks route caching in production.

### 🟢 Unbounded queries — low risk today
`LeadSource::all()` (`AttributionService.php:78`, 11 rows),
`Service::orderBy()->get()` (`LeadController.php:29`),
`Article::orderByDesc()->get()` (`CommentController.php:26`). All are
small reference tables; the Article one is the only one that grows and is
worth revisiting past a few hundred posts.

---

## 3. Code quality

### 🟢 Pint: clean — 0 files drifting
The three previously-drifting files were fixed; `./vendor/bin/pint --test`
now reports `passed`.

### 🟡 No dedicated unit tests for 11 service/middleware classes — M
All are exercised *indirectly* through feature tests, so this is a
robustness gap rather than "untested":

```
Services/Dashboard/DashboardStatsService     Services/Cms/MenuService
Services/Cms/MediaLibraryService             Services/Cms/HtmlSanitizerService
Services/Cms/ContentPublishingService        Services/Marketing/CampaignService
Services/Marketing/ConversionEventService    Services/Seo/SeoMetaService
Services/Seo/SeoTagService                   Http/Middleware/EnsureUserIsAdmin
Http/Middleware/SetDashboardLocale
```

Highest value to unit-test directly: **HtmlSanitizerService** (it is the
XSS boundary) and **SeoTagService** (its fallback chain has five levels).

### 🟡 Two helpers have no direct test — S
`current_route_base_name()` and `route_in_locale()` (`app/helpers.php`)
appear in **0** test files. Both are load-bearing: the first drives every
nav active-state, the second drives the language toggle and `hreflang`.
`lroute()` is referenced in 3.

### 🟢 Conventions are consistently followed
Form Requests with `authorize(): true` + route middleware, services owning
writes, translatable JSON columns, `lroute()` for public links — applied
uniformly across all the recent work.

---

## 4. Visual design, UX & responsiveness

> ⚠️ **Nothing in this section was visually confirmed.** These are
> static-analysis findings from the Blade/Tailwind source. Rendering was
> not possible — see the manual-QA list at the end.

### 🟡 27 view files still use Breeze indigo — M
All in the dashboard/profile area; **the public site and all five auth
pages are clean** (verified). Worst offenders:
`dashboard/services/_form.blade.php` (10),
`dashboard/articles/_form.blade.php` (5),
`dashboard/faqs/_form.blade.php` (5), `dashboard/pages/edit.blade.php` (5),
`dashboard/menu/_form.blade.php` (4). Plus the shared
`components/text-input`, `primary-button`, `secondary-button`.

Fixing `components/text-input.blade.php` and `primary-button.blade.php`
alone would flip most dashboard forms to the brand palette in one change.

### 🟢 RTL correctness is strong
Only **3 files** use physical direction utilities, and 2 are deliberate:
- `components/whatsapp-float-button.blade.php` — `left-6` (intentional,
  fixed bottom-left)
- `layouts/public.blade.php:138` — `right-0` on the mobile drawer
  (intentional) and `left-6`
- `components/responsive-nav-link.blade.php` — `border-l` (**dead
  component**, 0 usages)

Everything else uses logical properties (`start`/`end`, `ps`/`pe`,
`ms`/`me`).

### 🟡 Reorder buttons are below the 44px touch target — S
`resources/views/dashboard/menu/index.blade.php:92-104` — the ▲▼⇥⇤
buttons use `p-1` around a ~16px glyph ≈ **24×24px**, well under the 44px
guideline. Four of them sit side by side, making mis-taps likely on a
phone.

### 🟡 Desktop dropdown behaviour on touch is unverified — S to fix, needs QA
`resources/views/components/public/nav-item.blade.php:14-22` binds
`@mouseenter`, `@mouseleave`, `@click`, `@focusin` and `@focusout`. On
touch devices a tap fires a synthetic mouseenter *and* the click, so the
first tap may open-then-immediately-toggle. **This must be tested on a
real phone.** Note the seeded menu has no dropdowns, so this only bites
once an admin nests an item.

### 🟢 All 12 dashboard tables have `overflow-x-auto`
Verified across every `<table>` in `resources/views/dashboard/**`.

### 🟢 Dashboard sidebar mobile behaviour is correct
`components/dashboard/sidebar.blade.php:7-8` uses the mutually-exclusive
`max-lg:` / `lg:` media queries established by the earlier regression fix.

---

## 5. Accessibility

### 🔴 `luxury-gold` fails WCAG AA — S/M
Computed contrast ratios for the brand palette as actually used:

| Combination | Ratio | AA normal (4.5:1) | AA large (3:1) |
|---|---|---|---|
| text-main on white | 16.53:1 | ✅ | ✅ |
| text-secondary on white | 5.13:1 | ✅ | ✅ |
| text-secondary on bg-soft | 4.82:1 | ✅ | ✅ |
| primary-green on white | 7.95:1 | ✅ | ✅ |
| white on primary-green | 7.95:1 | ✅ | ✅ |
| white on dark-green | 12.13:1 | ✅ | ✅ |
| **luxury-gold on white** | **2.96:1** | ❌ | ❌ |
| **luxury-gold on bg-soft** | **2.78:1** | ❌ | ❌ |
| **light-gold on white** | **1.95:1** | ❌ | ❌ |

`text-luxury-gold` / `text-light-gold` appear in **17 files**, including
public-facing copy: `public/home.blade.php` (8 section eyebrows, lines
76, 104, 131, 167, 214, 242, 311, 349, 378),
`public/consultation.blade.php:5`, and the auth links I added
(`components/auth/link.blade.php:1`,
`components/auth/link-button.blade.php:7`).

`light-gold` on `dark-green` (the sidebar active state,
`components/dashboard/nav-link.blade.php:6`) is fine — it is gold on a
dark background, not on white.

**Fix:** darken the gold used *for text* (keep the current gold for
borders/backgrounds/accents, which have no contrast requirement). A
~#8A6A22 passes AA on white.

### 🟢 Every image has alt text
**0 of 25** `<img>` tags are missing `alt`. *(An earlier pass of this
audit reported 14 missing — that was a false positive caused by `->`
inside Blade expressions truncating the tag-matching regex. Corrected
with a quote-aware scan.)*

### 🟡 Four dead/near-dead components strip focus without replacing it — S
`focus:outline-none` with no `focus-visible` fallback in
`components/nav-link.blade.php:5-6`,
`components/responsive-nav-link.blade.php:5-6`,
`components/dropdown-link.blade.php:1`. The first two are **unused**;
deleting them resolves it. `dropdown-link` is used once (the dashboard
user menu).

### 🟢 ARIA on the new interactive components is correct
`components/public/nav-item.blade.php:26-28` sets `aria-expanded` and
`aria-haspopup`; `mobile-nav-item.blade.php:16` sets `aria-expanded`; the
auth password toggle (`components/auth/input.blade.php:62-64`) sets a
localised `aria-label` and `aria-pressed`; icon-only nav buttons carry
`aria-label`.

### 🟡 Keyboard operability — needs manual confirmation
The dropdown handles `@focusin`/`@focusout`/`@keydown.escape`, which is
the right shape, but tab-order through an open dropdown and focus-trapping
in the mobile drawer **cannot be verified statically**.

---

## 6. Security

### 🔴 No security headers at all — S
A live response from `/` carries only:
`Cache-Control, Connection, Content-Type, Date, Host, Set-Cookie, X-Powered-By`.

**Missing:** `Strict-Transport-Security`, `Content-Security-Policy`,
`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`,
`Permissions-Policy`. Also `X-Powered-By` leaks the PHP version and
should be suppressed. A small middleware fixes all of it.

### 🔴 Pre-launch `.env` hardening not done — S
`.env` currently has `APP_ENV=local`, `APP_DEBUG=true`, and **no**
`ADMIN_EMAIL`/`ADMIN_PASSWORD`. Correct for local dev, fatal in
production — `APP_DEBUG=true` renders full stack traces with environment
contents to any visitor. (`.env.example` already documents the safe
values; `AdminUserSeeder` already refuses to run with placeholder
credentials outside local/testing.)

### 🟡 `SESSION_SECURE_COOKIE` is unset — S
`config/session.php:172` reads `env('SESSION_SECURE_COOKIE')` with no
default, so cookies are sent over plain HTTP. Must be `true` in
production. `http_only` (true) and `same_site` (lax) are already correct.

### 🟡 No throttling on dashboard writes — S
`routes/web.php` has 3 `throttle:5,1` (the public forms);
`routes/dashboard.php` has **0**. Every admin write — including the
transactional menu reorder — is unthrottled. Lower risk than public
endpoints since it requires an authenticated admin, but a compromised
session has no rate limit.

### 🟡 No Policy/Gate layer — M
Authorisation is entirely route-middleware (`['auth','admin']`), and every
Form Request's `authorize()` returns `true` by documented convention.
Correct for a single-admin-role app; it becomes a real gap the moment a
second role (e.g. editor) exists.

### 🟢 XSS surface is correctly bounded
Exactly **6** `{!! !!}` usages, all rendering either `$page->body` or
`$article->body`. Both are sanitised on write through
`HtmlSanitizerService` (`ArticleController.php`, `PageController.php`).
Everything else — including all menu labels and page sections — is
escaped.

### 🟢 File uploads are validated
`StoreMediaRequest.php:29` restricts to `jpeg,png,webp,mp4,webm` with a
per-MIME size ceiling; `MediaLibraryService.php:20` and
`ContentPublishingService.php:16` use `$file->store()`, which generates a
random filename — no user-controlled path.

### 🟢 Secrets
`.env` has never been committed (`git log --all -- .env` is empty; not in
`git ls-files`). `.gitignore` covers `.env`, `.env.backup`,
`.env.production`.

### 🟢 Open redirect
`ContactController::redirectTarget()` compares against a single literal
(`'home'`); no user-supplied URL is followed.

### 🟢 Mass assignment
`is_admin` is cast but deliberately excluded from `User::$fillable`, with
a unit test asserting it.

---

## 7. SEO & production readiness

### 🔴 No sitemap.xml — S
`public/sitemap.xml` does not exist and no route generates one. With 26
indexable URLs (13 pages × 2 locales) plus per-service and per-article
detail pages, this is the biggest remaining SEO gap.

### 🟡 robots.txt is minimal — S
`public/robots.txt` is `User-agent: * / Disallow:` — allows everything,
but has **no `Sitemap:` directive** (which needs the sitemap first).

### 🟡 No custom error pages — S
`resources/views/errors/` does not exist. A 404 currently renders
Laravel's bare "Not Found" page — unbranded, no navigation, no language
toggle, a dead end for visitors and crawlers. Needs at least `404` and
`500`.

### 🟢 Canonical + hreflang are correct
Verified live on `/services`: one self-referencing canonical, plus
`hreflang="ar"`, `hreflang="x-default"` and `hreflang="en"` pointing at
the correct per-page URLs. SEO meta coverage is complete (10 `seo_meta`
rows seeded; full tag set incl. Open Graph and Twitter cards).

### 🟡 Logging is dev-shaped — S
`.env`: `LOG_CHANNEL=stack`, `LOG_STACK=single`, `LOG_LEVEL=debug`. In
production this writes an unbounded single file at debug verbosity.
Should be `daily` with `LOG_LEVEL=error` (or `warning`).

### Pre-launch checklist

Items marked ✅ were completed in Batch A (2026-08-08).

- [ ] Copy `.env.example` to the production `.env` and fill real values —
      it now ships `APP_ENV=production`, `APP_DEBUG=false`,
      `SESSION_SECURE_COOKIE=true`, `LOG_STACK=daily`, `LOG_LEVEL=warning`
      and empty `ADMIN_*` ✅ *(defaults prepared; the deploy still has to
      use them)*
- [ ] Real `ADMIN_EMAIL` / `ADMIN_PASSWORD` in the production `.env`
- [ ] HTTPS enforced at the web server / load balancer
- [x] ✅ Security headers middleware deployed (`app/Http/Middleware/SecurityHeaders.php`)
- [ ] Real contact details + social links — **still blocked on the
      Settings UI (Batch B)**; the site currently shows seeded placeholders
- [x] ✅ `sitemap.xml` generated + absolute `Sitemap:` line in `robots.txt`
- [x] ✅ Branded bilingual 404 / 403 / 419 / 500 / 503 pages
- [ ] Demo data NOT loaded (`DemoDataSeeder` is opt-in; verify 0
      testimonials/campaigns/leads/conversions in production)
- [ ] Legal pages reviewed by a lawyer (seeded copy is starter text)
- [ ] `php artisan config:cache` + `route:cache` (route caching still
      needs the coming-soon closure fix, backlog #15)
- [ ] Tighten the CSP from Report-Only to enforcing (needs a nonce-based
      script-src and Alpine's CSP build — see the comment in
      `SecurityHeaders.php`)

---

## Ranked task backlog

Ordered by blocking-ness × impact.

| # | Task | Dimension | Sev | Effort |
|---|---|---|---|---|
| 1 | Pre-launch `.env` hardening: `APP_ENV=production`, `APP_DEBUG=false`, real admin creds, `SESSION_SECURE_COOKIE=true`, `LOG_STACK=daily`/`LOG_LEVEL=error` | Security | 🔴 | S |
| 2 | Security-headers middleware (HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy; suppress `X-Powered-By`) | Security | 🔴 | S |
| 3 | **Site Settings manager** — edit contact phone/email/address/WhatsApp, social links, SEO defaults; wire social links into the footer | Completeness | 🔴 | M |
| 4 | Fix `luxury-gold`/`light-gold` text contrast (darken the text variant, keep gold for accents) — 17 files | Accessibility | 🔴 | S/M |
| 5 | Drop axios from the bundle (−48.9 kB raw / −18.1 kB gzip, 51% of JS) | Performance | 🔴 | S |
| 6 | Generate `sitemap.xml` + add `Sitemap:` to robots.txt | SEO | 🔴 | S |
| 7 | Custom branded 404 / 500 error pages (bilingual) | SEO/UX | 🟡 | S |
| 8 | Load `settings` once per request instead of 3–8 single-key queries | Performance | 🟡 | S |
| 9 | Add `loading="lazy"` + width/height to all 16 public images | Performance | 🟡 | S |
| 10 | Manual responsive/touch QA pass (list below) | UX | 🟡 | M |
| 11 | Enlarge the menu-reorder touch targets to ≥44px | UX | 🟡 | S |
| 12 | Throttle dashboard write routes | Security | 🟡 | S |
| 13 | Replace Breeze indigo with the brand palette — start with `text-input` + `primary-button` (27 files) | Visual | 🟡 | M |
| 14 | Unit tests for `HtmlSanitizerService` + `SeoTagService`, and for the two untested helpers | Code quality | 🟡 | M |
| 15 | Convert coming-soon route closures to an invokable controller so `route:cache` works | Performance | 🟡 | S |
| 16 | Delete dead `nav-link` / `responsive-nav-link` components (also clears unreplaced `focus:outline-none`) | Code quality | 🟢 | S |
| 17 | Build or drop the `lead-sources` and `contact-messages` placeholders | Completeness | 🟢 | M |
| 18 | Compress/trim the 12 MB of video assets | Performance | 🟢 | M |
| 19 | Policies/Gates layer — only once a second admin role is needed | Security | 🟢 | L |

---

## Manual QA required (not verifiable statically)

Rendering was not possible in this environment, so **nothing in §4 was
visually confirmed**. Check in a real browser at **375px (mobile)**,
**768px (tablet)** and **1440px (desktop)**, in **both `ar` (RTL) and `en`
(LTR)**:

| Page / component | What to check |
|---|---|
| `public/home.blade.php` | All 10 sections; the hero video on mobile; the testimonial slider; no horizontal scroll |
| Public navbar + mobile drawer | Drawer opens/closes, RTL slide direction, focus trap |
| **Nav dropdown on a real touch device** | Nest an item first, then verify tap-to-open vs tap-to-follow (see §4) |
| The five auth pages | Card legibility over the moving video at each width |
| `dashboard/menu/index.blade.php` | Reorder buttons on a phone; the nested-item indent |
| `dashboard/reports/index.blade.php` | CSS bar charts at narrow widths |
| All dashboard forms | Field stacking, select widths, RTL alignment |
| Dashboard sidebar | Off-canvas below 1024px, both locales |
