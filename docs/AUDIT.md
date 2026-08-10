# Launch-Readiness Audit — 2026-08-10

Read-only audit of Bawabat Taasees Al Sharikat across eight dimensions.

Severity: 🔴 high · 🟡 medium · 🟢 low. Effort: S (<2h) · M (half day) ·
L (a day or more).

> ## Remediation status — 2026-08-10, later the same day
>
> **Every code-side item in the backlog below has been fixed**, across five
> commits (`d14d64a`, `e554c6e`, `05d5bd0`, `a387697`, `fb4c618`). The audit
> body is left exactly as written so the before/after is legible; this box is
> the only edit.
>
> | | at audit | now |
> |---|---|---|
> | Tests | 788 / 2,790 assertions | **1,012 / 3,587 assertions** |
> | Test files | 62 | 68 |
> | Pint | failing, 5 files | **clean** |
> | JS bundle | 17.0 kB gzip | 25.6 kB gzip (`@alpinejs/focus`, see #13) |
> | CSS bundle | 11.5 kB gzip | 11.4 kB gzip |
> | `route:cache` / `config:cache` / `view:cache` | all succeed | all succeed |
> | `public/images` | 1.3 MB | 890 KB |
>
> **Fixed:** backlog items 3, 5, 6, 7, 8, 9, 10, 12, 13, 14, 15, 16, 17, 20,
> 21 — and 1 in part (the homepage title no longer emits a placeholder).
>
> **Still open, all of them yours rather than the code's:**
>
> 1. **Real contact/social values** at `/dashboard/settings` — `/` and
>    `/contact` still show the seeded placeholder phone, email and address,
>    and the WhatsApp float still resolves to `wa.me/9665`. The homepage
>    title is fixed; these are not.
> 2. **Production `.env`** — real `ADMIN_EMAIL`/`ADMIN_PASSWORD`,
>    `APP_URL=https://…`. Set `TRUSTED_PROXIES` only if the origin is also
>    reachable directly; production defaults to `*` (see
>    `config/trustedproxy.php`).
> 4. **Legal review** of the privacy policy and terms.
> 11. **Real-browser QA** — the list at the end of this file, plus one new
>    item flagged below.
>
> **⚠️ One change needs your eyes before you trust it.** Backlog #7, the
> dashboard drawer edge (`end-0` → `start-0` in
> `components/dashboard/sidebar.blade.php`). The reasoning is recorded in
> that file and in `DashboardResponsiveShellTest`, but it is a visual change
> and nothing here can render. Check at 375px in **both** locales: the drawer
> should slide in from the same edge the desktop sidebar occupies — left in
> English, right in Arabic — and never appear mid-screen.
>
> **Two things found while fixing, not in the audit below:**
>
> - `SeoTagService::imageUrl()` falls back to `logo-full-color-1024.png`, the
>   one image referenced from PHP rather than from a Blade view. The media
>   cleanup deleted it as an orphan and the og:image on every shared link
>   would have 404'd. Caught by the new `SeoTagServiceTest`, restored, and
>   the orphan scan now covers `app/`, `config/` and `database/`.
> - `dashboard/tracking-settings/edit.blade.php` reads its six fixed rows off
>   a keyed collection by key, so it 500s if `TrackingSettingSeeder` has not
>   run. Harmless in any real environment — `DatabaseSeeder` always runs it —
>   but it is a hard failure rather than an empty form, and it is one `?? null`
>   away from not being. Left alone as out of scope; noting it here.

---

**Baseline measured at audit time**

| | |
|---|---|
| Tests | **788 passing, 2,790 assertions, 15.3s** (was 533 / 1,665) |
| Test files | 62 |
| Pint | **FAILING — 5 files** (was clean) |
| `route:cache` / `config:cache` / `view:cache` | **all succeed** (previously blocked) |
| Bundle | `app.js` 46.9 kB raw / 17.0 kB gzip · `app.css` 66.2 kB / 11.5 kB gzip |
| Queries | `/` 17 · `/contact` 5 · `/services` 6 · `/dashboard` 20 · `/reports` 15 |
| Public routes | all 200 in both locales (`RouteSweepTest` + direct kernel sweep) |

**Verdict: 🟡 ready with caveats.**

There is no code defect blocking launch. Everything gating go-live is
content or deployment configuration, and one of those four items is a
five-minute job in a UI that already exists:

1. **Placeholder settings are live on the public site right now** — the
   homepage `<title>`, `og:title` and `twitter:title` in *both* locales
   literally read `Bawabat Taasees Al Sharikat (placeholder — update in
   Settings)`, and the WhatsApp float button on every page resolves to
   `https://wa.me/9665`.
2. **Legal pages are seeded starter text** and have not been reviewed.
3. **The production `.env` does not exist yet** (real admin credentials,
   real `APP_URL`).
4. **No trusted-proxy configuration**, so HSTS will never be sent and URL
   scheme detection will be wrong behind a TLS-terminating load balancer.

Everything the last audit called a blocker is fixed: the Settings manager
exists, security headers ship (including on exception responses), the gold
contrast is resolved, sitemap/robots/error pages exist, axios is gone, and
route caching works. The last two dashboard placeholders — Lead Sources and
Contact Messages — are now real features.

---

## 1. Completeness — what is left before launch

### 🔴 C1 · Seeded placeholder values are visible in production-facing output — S
The Settings UI exists (`dashboard/settings/edit.blade.php`) but nobody has
filled it in. Measured by rendering the pages through the HTTP kernel:

| URL | Placeholder strings in the response |
|---|---|
| `/` | **7** — incl. `<title>`, `og:title`, `twitter:title` |
| `/en` | **7** — same |
| `/contact` | **4** — phone, email, address |

Rendered homepage title, both locales:

```
<title>Bawabat Taasees Al Sharikat (placeholder — update in Settings)</title>
<meta property="og:title" content="Bawabat Taasees Al Sharikat (placeholder — update in Settings)">
```

And the WhatsApp float button, on **every** public page
(`components/whatsapp-float-button.blade.php:18`), strips non-digits from
`+966 5xx xxx xxx (placeholder — update in Settings)` and produces:

```
https://wa.me/9665
```

Source of the values: `database/seeders/SettingsSeeder.php:66-74, 86-87`.
Fix is data entry at `/dashboard/settings`, not code.

### 🔴 C2 · Legal pages are unreviewed starter copy — S (external)
`privacy-policy` and `terms-and-conditions` render `$page->body` seeded by
`PageContentSeeder`. Needs a lawyer, not a developer.

### 🔴 C3 · Production `.env` does not exist yet — S
The local `.env` is correctly `APP_ENV=local` / `APP_DEBUG=true`.
`.env.example` already ships the safe production defaults
(`APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`,
`LOG_STACK=daily`, `LOG_LEVEL=warning`, empty `ADMIN_*`) — but nothing has
consumed them yet. Also note the local database carries the dev fallback
admin `admin@example.test` alongside the real user; that account must not
exist in production. `AdminUserSeeder` already refuses placeholder
credentials outside local/testing.

### 🟢 C4 · Zero sample/demo data can reach a production seed — verified
`DatabaseSeeder::run()` (`database/seeders/DatabaseSeeder.php:28-45`) calls
eleven production seeders and **does not** call `DemoDataSeeder`.
`DemoDataSeeder.php:38-42` is the only caller of `TestimonialSeeder`,
`CampaignSeeder` and `ConversionEventSeeder`, is opt-in via
`--class=DemoDataSeeder`, and warns on run. `DemoDataSeparationTest` and
`SeedersTest` both assert the separation. (The local DB does hold 4
testimonials / 2 campaigns / 5 leads / 3 conversions — that is the opt-in
seeder, local only.)

### 🟡 C5 · Nothing notifies anyone of a new lead — S
`grep -rn "Mail::|Notification::|->notify(|Mailable" app/` returns **zero
matches**. A consultation or contact submission is written to `leads` and
that is all — someone has to remember to open the dashboard. `.env.example`
ships `MAIL_MAILER=log`. This may well be the intended design; flagging it
because "the contact form works" and "someone finds out about it" are
different claims.

### 🟡 C6 · The site logo cannot be removed once uploaded — S
`SettingsService::update()` (`app/Services/Cms/SettingsService.php:114-120`)
only writes an image key when a file is present, so an absent upload means
"keep". Correct for replace, but there is no clear/remove affordance.

### 🟡 C7 · The settings form offers SVG and then rejects it — S
`dashboard/settings/edit.blade.php:80` sets
`accept="image/jpeg,image/png,image/webp,image/svg+xml"` and
`UpdateSettingsRequest.php:53` lists `mimes:jpeg,png,webp,svg` — but the
`image` rule on the same line excludes SVG unless given `allow_svg`
(`vendor/laravel/framework/.../ValidatesAttributes.php:1494-1503`), and it
runs first. An admin who picks an SVG gets a confusing rejection.
`SettingsControllerTest.php:175-198` only covers PNG, so nothing catches it.

*(Silver lining: this means SVG upload — a stored-XSS vector, since
`/storage/settings/x.svg` is same-origin and navigable — is closed. Worth
making that deliberate rather than accidental.)*

### 🟢 C8 · The homepage has no `seo_meta` row — S
The 10 seeded `seo_meta` rows cover 6 Pages and 4 Articles. The homepage,
`/services`, `/countries`, `/faqs`, `/articles`, `/consultation` and
`/contact` have none. The index pages pass a `$title` from the view so they
resolve fine; the **homepage passes neither**, which is exactly why it falls
all the way through to `default_meta_title` in C1. Its description is fine
(`public/home.blade.php:1` passes `seo-description`).

### 🟢 C9 · No unfinished markers left
Zero `TODO`/`FIXME`/`HACK` in `app/`, `routes/`, `resources/views/`. Zero
"coming soon" placeholders — the three from the last audit are all built.
Zero unused Blade components (the dead `nav-link` / `responsive-nav-link`
were deleted).

---

## 2. Code quality & professionalism

### 🟡 Q1 · Pint is failing — regression since the last audit — S
`./vendor/bin/pint --test` → `fail`, 5 files:

| File | Fixer |
|---|---|
| `tests/Feature/Dashboard/DashboardHomeTest.php` | `no_unused_imports` |
| `tests/Feature/Dashboard/ContactMessageControllerTest.php` | `fully_qualified_strict_types`, `ordered_imports` |
| `routes/dashboard.php` | `ordered_imports` |
| `lang/ar/dashboard.php` | `no_extra_blank_lines` |
| `lang/en/dashboard.php` | `no_extra_blank_lines` |

All from the recent batch. One `pint` run clears it.

### 🟡 Q2 · An Arabic sentence is being used as a translation key — S
`app/Http/Requests/Dashboard/UpdateTrackingSettingsRequest.php:41`:

```php
__('يجب إدخال قيمة قبل تفعيل هذا المعرّف.')
```

No such key exists in `lang/en`, so `__()` returns the key verbatim — an
English-locale admin sees an Arabic validation error. It is the **only**
occurrence of the pattern in `app/`; every other string goes through
`dashboard.*` / `site.*`. Key parity is otherwise excellent: `dashboard`
542/542, `site` 140 vs 141 (`brand.tagline` is EN-only), `validation`
208/209 (the stock `custom.attribute-name.rule-name` stub).

### 🟢 Q3 · Dead code in the new settings stack — S
Zero call sites anywhere in `app/`, `resources/` or `tests/`:
- `SettingsRepository::group()` — `app/Services/Cms/SettingsRepository.php:92`
- `SettingsService::typeOf()` — `app/Services/Cms/SettingsService.php:85`
- `SettingsService::groupOf()` — `app/Services/Cms/SettingsService.php:90`

`group()` is also the only reason one might want an index on
`settings.group`, so removing it settles that question too.

### 🟢 Q4 · `LeadSourceService::delete()` does not do what its docblock says — S
`app/Services/Marketing/LeadSourceService.php:57-68`. The comment states
leads "are repointed to the raw key they arrived with". Nothing is
repointed — `leads.source_platform` already holds that string and is simply
left alone, which produces the right outcome by inaction. The
`DB::transaction()` wrapping a single `delete()` is also inert. The
behaviour is correct; the documentation describes a mechanism that is not
there, which is the kind of thing that misleads the next reader.

### 🟢 Q5 · Comments left stale by the recent work — S
- `resources/css/app.css:183-185` — "Used by exactly one component
  (`components/public/service-marquee`) on exactly one section of the
  homepage." The component is `components/public/marquee` and it is used
  **three** times (`public/home.blade.php:116, 158, 189`).
- `resources/views/components/whatsapp-float-button.blade.php:10-12` — "No
  other fixed-position UI (e.g. a 'back to top' button) exists anywhere in
  the project to collide with." One does, and it is explicitly positioned
  against this component (`components/back-to-top-button.blade.php`,
  wired at `layouts/public.blade.php:212`).
- `app/Models/Lead.php:52-56` — a docblock explaining why `is_handled` is
  not fillable sits directly above `const TYPE_CONTACT`.

### 🟡 Q6 · The flash/alert block is hand-rolled 25 times — M
25 view files each contain their own copy of

```blade
@if (session('status'))
    <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('status') }}</div>
@endif
```

and there are 19 uses of raw Tailwind defaults (`bg-green-100`,
`bg-red-100`, `text-green-800`, `text-red-800`) across 15 dashboard views.
These are now the **only** palette leftovers — `indigo` is at 0 occurrences
and `text-gray-`/`bg-gray-`/`border-gray-` at 0 files, so the palette sweep
otherwise landed cleanly. One `<x-flash>` component deletes 25 copies and
brings the last strings onto brand tokens.

### 🟢 Q7 · Framework boilerplate still present
`tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`.

### 🟢 Q8 · Review of the specific recent additions — mostly good
- **Settings service/definition** — the best-designed piece in this batch.
  `SettingsService::DEFINITION` is the single source of truth; the Form
  Request derives its rules from it (`UpdateSettingsRequest.php:30-50`) and
  the writer iterates the *definition*, never the request payload
  (`SettingsService.php:109-129`). An arbitrary settings key is
  structurally unwritable, not merely filtered. `normalise()` collapsing
  `""` to `null` is the right call given every consumer tests truthiness.
- **Marquee component/CSS** — the fragile parts are the parts that are
  documented and tested. The two-identical-units + 50% translate geometry,
  the per-item `margin-right` instead of `gap` (a `gap` track is half a gap
  short of a clean loop), and the forced `direction: ltr` viewport with
  per-card `dir` are each explained where they live and asserted in
  `MarqueeTest`. The `$pitch` constant (`marquee.blade.php:28`) duplicating
  the CSS widths is the one real coupling, and the comment says so.
- **Responsive layer** — a genuinely good decision to do stacked tables in
  CSS keyed on `data-label` rather than a mobile-only partial: no
  duplicated markup, no second query. The specificity reasoning
  (`app.css:136-142`) is correct.
- **Contact messages** — right call not to create a second table; the
  `abort_unless` type guard on the route-model-bound action is the
  non-obvious bit and it is there.
- **Lead sources** — `update()` cascading a key rename across
  `leads.source_platform` inside a transaction is the correct handling of a
  string-keyed pseudo-FK, and lowercase normalisation before the unique
  check closes the cross-driver collation gap.

Conventions (Form Request + `authorize(): true` behind route middleware,
services owning writes, translatable JSON columns, `lroute()` for public
links) are followed uniformly across all of it.

---

## 3. Performance & speed

Measured by driving the HTTP kernel in-process with `DB::listen` against
the seeded MySQL database, counting queries and flagging any SQL string
repeated ≥3× (the N+1 signature).

### 🟢 P1 · Per-request query counts are healthy
| Public | q | | Dashboard | q |
|---|---|---|---|---|
| `/` | 17 | | `/dashboard` | 20 |
| `/en` | 15 | | `/reports` | 15 |
| `/services` | 6 | | `/campaigns/{id}` | 11 |
| `/services/{slug}` | 7 | | `/leads` | 10 |
| `/articles/{slug}` | 8 | | `/lead-sources` | 9 |
| `/contact` | 5 | | `/comments` | 9 |
| `/about`, `/why-invest`, `/formation-process` | 7–8 | | `/contact-messages` | 7 |
| `/sitemap.xml` | 4 | | `/settings` | 5 |

`/` is down from 24 at the last audit.

### 🟢 P2 · One repeated query on the homepage — S
`select * from pages where slug = ? and is_published = ?` runs **3×** on
`/` — `HomeController::index():19` plus `pageSections()` at `:74`, called
twice. Three single-row indexed lookups; a single `whereIn` would fold them.
No other page has any SQL repeated 3× or more.

### 🟢 P3 · Settings is one query per request
`SettingsRepository` is a singleton (`AppServiceProvider.php:22`), loads all
16 rows once and answers from memory, and is only touched if something asks.
Down from 8 queries on `/`. `SettingsRepositoryTest` asserts the count
directly, including the "no query at all when nothing asks" case.

### ✅ P4 · Caching commands all work
`route:cache`, `config:cache` and `view:cache` each succeed (run and then
cleared during this audit). The closure-route blocker from the last audit is
gone — the coming-soon closures became real controllers. The only remaining
closure route is `robots.txt` (`routes/web.php:105`), and Laravel caches
closure routes fine; it is `route:cache` failing on *unserialisable* ones
that was the old problem.

### 🟡 P5 · Images still have no lazy-loading and no dimensions — S
**26 of 27** `<img>` tags project-wide have no `loading` attribute, and the
same 26 have no `width`/`height` — so no deferred loading below the fold and
CLS on every image. The single exception is the new marquee service card
(`public/partials/marquee-cards/service.blade.php:6-11`), which does have
`loading="lazy"`. Backlog item #9 from the last audit is untouched.

All 27 have `alt`.

### 🟡 P6 · 12 MB of video, and unreferenced assets — M
| File | Size | Loaded by |
|---|---|---|
| `videos/hero-bg.mp4` / `.webm` | 2.0 / 1.8 MB | `/` — `autoplay`, no `preload` attr |
| `videos/login-bg.mp4` / `.webm` | 1.9 / 1.5 MB | all 5 auth pages — `autoplay` |
| `videos/about/about-office-tour.mp4` | 2.0 MB | `/about` — click-to-play ✅ |
| `videos/about/about-office-tour-vertical.mp4` | 1.4 MB | **referenced nowhere** |

Plus 11 unused brand PNG variants (~750 KB), largest
`images/brand/logo-full-color-1024.png` at 320 KB. Neither autoplaying video
declares `preload`, so browsers fetch aggressively; `preload="none"` with the
existing poster would defer ~1.8 MB on the homepage's critical path.

### 🟢 P7 · Three marquees animate continuously, on- or off-screen — S
`app.css:242-275` runs an infinite `animation` on each `.bt-marquee__track`,
each carrying a permanent `will-change: transform` (`:261`) and a
`mask-image` on its viewport (`:234`). All three sections of the homepage
animate for as long as the page is open, including while scrolled far past.
An `IntersectionObserver` that toggles `animation-play-state` would cut most
of that. Cheap per frame; it is the always-on part that costs battery.

### 🟢 P8 · Bundle is in good shape
`app.js` 46.9 kB raw / **17.0 kB gzip**; `app.css` 66.2 kB / **11.5 kB gzip**.
axios is gone (`resources/js/bootstrap.js` deleted; `app.js` is 7 lines —
Alpine plus the attribution module). One leftover: `layouts/app.blade.php:6`
still emits `<meta name="csrf-token">`, which no JS reads any more.

### 🟢 P9 · Index coverage covers the new tables
`leads_type_is_handled_index` on `(type, is_handled)` backs both the inbox
query and the sidebar badge that runs on *every* dashboard render;
`lead_sources_key_unique`; `settings_key_unique`; `menu_items_tree_index` on
`(parent_id, is_visible, sort_order)`. No hot column found without one.

### 🟢 P10 · Unbounded queries unchanged and small
`LeadSource::ordered()->get()` (11 rows), `Service::orderBy()->get()`,
`Article::orderByDesc()->get()`. `LeadSourceService::leadCounts()` and
`unrecognisedSources()` are each a single grouped query, not a count per row.

### 🟢 P11 · One non-passive scroll listener — S
`layouts/public.blade.php:52` binds `@scroll.window` without `.passive` for
the header shadow. The back-to-top button sitting on the same page does use
`@scroll.window.passive` (`back-to-top-button.blade.php:38`) — so this is an
inconsistency, not an oversight in the new code.

---

## 4. Responsive & visual

> ⚠️ **Nothing in this section was visually confirmed.** Rendering is not
> possible in this environment. Everything below is derived from the Blade
> source and the *compiled* CSS in `public/build/assets/`. The manual-QA
> list at the end is the part that still needs a real browser.

### 🟡 R1 · The dashboard drawer is pinned to the wrong edge — S
`components/dashboard/sidebar.blade.php:29` pins the aside:

```
fixed inset-y-0 end-0 … lg:static lg:translate-x-0
```

and line 27 sets its closed state:

```
:class="sidebarOpen ? 'translate-x-0' : 'max-lg:rtl:translate-x-full max-lg:ltr:-translate-x-full'"
```

From the built stylesheet:

```css
.end-0{inset-inline-end:0px}
.max-lg\:ltr\:-translate-x-full:where([dir=ltr],[dir=ltr] *){--tw-translate-x:-100%}
.max-lg\:rtl\:translate-x-full:where([dir=rtl],[dir=rtl] *){--tw-translate-x:100%}
```

`inset-inline-end` is the **right** edge in LTR and the **left** edge in RTL.
To move a drawer pinned there off-screen you need `+100%` in LTR and `-100%`
in RTL. The applied values are the exact opposite in both directions — they
are the correct pair for a drawer pinned at inline-**start**. Closed, the
panel translates *inward*, not away.

Two supporting signals that `start-0` is what was meant:
- At `lg:static` the aside becomes the shell's **first flex child**
  (`layouts/app.blade.php:31-32`), so the desktop sidebar sits at the inline
  **start** — left in English, right in Arabic. The mobile drawer as coded
  lands on the opposite edge from it in both locales.
- The public mobile drawer, which is correct, does it the other way:
  physical `right-0` with `translate-x-full` (`layouts/public.blade.php:138`).

**Nothing is functionally broken**, because `app.css:46-53` sets
`visibility: hidden` on `.dash-drawer[data-open='false']` below `lg`, so the
closed panel is invisible regardless of where the transform put it. The
visible symptom is the open/close animation playing from mid-screen instead
of from the edge, and the drawer appearing on the opposite side from the
desktop sidebar. Fix is one token. Note
`DashboardResponsiveShellTest.php:92, 154` assert the literal string
`end-0`, so those assertions move with it — and neither test can catch the
geometry, only the string. **Confirm in a browser at 375px in both locales
before changing anything.**

### 🟢 R2 · Marquee structure is sound
Two identical units per track, animated by exactly 50% of track width, so
the frame at 100% is pixel-identical to the frame at 0%
(`marquee.blade.php:29-44, 79-89`). Spacing lives on each item as
`margin-right`, not `gap` — a 2N-item track has 2N−1 gaps, which leaves 50%
half a gap short and produces a per-cycle stutter (`app.css:242-257`). Unit
width is forced above the 1280px container (`marquee.blade.php:42-44`), and
speed is a constant 45 px/s across all three rows regardless of card size
(`:52`). `MarqueeTest` covers 25 cases including two-identical-halves,
unit-wider-than-viewport, equal px/s, locale flip, and reduced-motion. Each
section is guarded by `isNotEmpty()` (`home.blade.php:115, 159, 203`).

### 🟢 R3 · Stacked tables are structurally sound
`app.css:108-176`. Below `md`, `thead` is visually hidden, every `td` prints
its own `data-label` via `::before` at a fixed 40% gutter, and the
label-less actions cell becomes a full-width card footer. No duplicated
markup, no second query, desktop table untouched. The empty-state cell is
excluded via `:not([colspan])`.

### 🟢 R4 · Public mobile drawer is correct
Physical `right-0` + `translate-x-full` + `x-show`
(`layouts/public.blade.php:135-139`) — off-screen right in both locales,
and `x-show` removes it from the tab order when closed. Desktop nav
(`lg:flex`) and drawer (`lg:hidden`) are mutually exclusive.

### 🟡 R5 · Real-browser QA is still outstanding — M
See the checklist at the end. It is larger than last time because of the
three marquees.

---

## 5. Accessibility

### 🟢 A1 · The gold fix holds; nothing new broke it
Recomputed from `tailwind.config.js:17-35`:

| Combination | Ratio | AA normal |
|---|---|---|
| `gold-deep` #8A6A22 on white | **5.04:1** | ✅ |
| `gold-deep` on `bg-soft` | **4.72:1** | ✅ |
| `light-gold` #D4B76A on `dark-green` | **6.22:1** | ✅ |
| `gold-deep` on `bg-luxury-gold/10` over white | **4.60:1** | ✅ (narrow) |
| `text-secondary` on white | 5.13:1 | ✅ |
| `luxury-gold` #B8903A on white | 2.96:1 | ❌ — decorative only |

Every surviving `text-luxury-gold` / `text-light-gold` is on a dark ground:
`components/dashboard/nav-group.blade.php:7` and `nav-link.blade.php:6`
(sidebar active state on `dark-green`) and `public/home.blade.php:18` (hero
overlay). Nothing in the new work reintroduced a failing pair — the marquee
card badge correctly uses `text-gold-deep`
(`marquee-cards/service.blade.php:21`) and every section eyebrow on the
homepage uses `text-gold-deep` (`home.blade.php:35, 64, 108, 137, …`).

### 🟡 A2 · No pause control on any auto-moving content — S
`prefers-reduced-motion` is honoured in exactly two places
(`app.css:343-351` for the marquee, `back-to-top-button.blade.php:33` for
the scroll). Three moving things ignore it and offer no pause, which is
WCAG 2.2.2 (Pause, Stop, Hide):

- **Homepage hero video** — `home.blade.php:3-13`, `autoplay muted loop`,
  no controls, no reduced-motion opt-out.
- **Login background video** — `layouts/guest.blade.php:22-31`, same.
- **Testimonial carousel** — `home.blade.php:213`,
  `x-init="setInterval(() => { active = (active + 1) % count }, 6000)"` —
  auto-advances forever, no pause on hover or focus, and the interval is
  never cleared.

The marquee gets this right (pauses on `:hover`, `:focus-within` and touch;
drops to a plain scrollable row under reduced motion) — which is what makes
the other three stand out.

### 🟡 A3 · No skip-to-content link — S
Neither `<main>` carries an id and there is no skip link on either layout
(`layouts/public.blade.php:167`, `layouts/app.blade.php:123`). WCAG 2.4.1.
With a full navbar and three marquees ahead of it, the homepage is now a
long tab-walk before a keyboard user reaches content.

### 🟡 A4 · Dialog and dropdown keyboard behaviour is incomplete — S
- **Public mobile drawer** (`layouts/public.blade.php:135-164`) — no focus
  trap, no `@keydown.escape`, no `role="dialog"` / `aria-modal`. The
  dashboard drawer at least has Escape (`sidebar.blade.php:28`).
- **`x-dropdown`** (`components/dropdown.blade.php:16-34`) — no Escape, no
  `aria-expanded` on the trigger wrapper, no focus movement into the panel.
  It drives the dashboard user menu on every screen. The *public* nav
  dropdown is better: it sets `aria-expanded` / `aria-haspopup` and handles
  `@focusin`/`@focusout`/`@keydown.escape`
  (`components/public/nav-item.blade.php`).

### 🟢 A5 · Marquee accessibility is correct
Every duplicated card carries `aria-hidden="true"` on its wrapper
(`marquee.blade.php:101`); every link inside a duplicate carries
`tabindex="-1"` (`marquee-cards/service.blade.php:4, 30, 43`); duplicate
images blank their `alt` (`:8`); the step number is `aria-hidden` on clones
(`marquee-cards/formation-step.blade.php:5`). Pause-on-`:focus-within` is
pure CSS (`app.css:283-287`), so it works with JavaScript off — the Alpine
is only there for touch, where there is no `:hover`.

### 🟢 A6 · Back-to-top button is correct
Localised `aria-label`, `focus-visible` ring, `x-cloak`, passive scroll
listener, and it honours `prefers-reduced-motion` by switching to an instant
jump (`back-to-top-button.blade.php:33, 38, 51-52`).

### 🟢 A7 · Focus and alt text
27/27 images have `alt`. Every `focus:outline-none` in the codebase is
paired with a `focus:ring-2` or `focus-visible:ring-2` replacement —
the four unreplaced ones from the last audit went away with the dead
components.

### 🟢 A8 · Decorative arrow is hardcoded LTR-backwards — S
`&larr;` in every "learn more" / section CTA (`home.blade.php:39, 68, 112`,
`marquee-cards/service.blade.php:45`) — correct in Arabic, backwards in
English. It is `aria-hidden`, so this is cosmetic only.

---

## 6. Security

### 🔴 S1 · No trusted-proxy configuration — S
`grep -rn "trustProxies|forceScheme|TrustProxies|TrustHosts" bootstrap/ app/ config/`
returns **nothing**. Behind a TLS-terminating proxy or load balancer,
`$request->secure()` is `false`, which means:
- `SecurityHeaders.php:66` never sends `Strict-Transport-Security` — the
  header is gated on `$request->secure() && environment('production')`.
- `route()` / `asset()` and therefore the sitemap and the `Sitemap:` line in
  `robots.txt` emit whatever scheme `APP_URL` carries.

Must be resolved before going live behind HTTPS: set `APP_URL` to the
`https://` production origin **and** configure trusted proxies (or force the
scheme).

### 🟡 S2 · Dashboard writes are still unthrottled — S
`grep -rn throttle routes/` → 3 in `routes/web.php` (`throttle:5,1` on
comments, consultation, contact) and 2 in `routes/auth.php`
(`throttle:6,1`). `routes/dashboard.php` has **0** — every admin write,
including the transactional menu reorder and the settings save, is
unlimited. Requires an authenticated admin, so lower risk than a public
endpoint, but a stolen session has no ceiling. Unchanged from the last audit.

### 🟡 S3 · Admin values are interpolated into inline `<script>` with only length validation — S
`components/tracking-scripts.blade.php:36, 41, 52, 60, 66, 76` interpolate
`TrackingSetting` values straight into Meta Pixel / GTM / gtag / TikTok
snippets. Validation is `nullable|string|max:255`
(`UpdateTrackingSettingsRequest.php:23`) — no format constraint at all.

Blade's `{{ }}` escaping **does** hold here: `htmlspecialchars` with
`ENT_QUOTES` encodes both `<`/`>` (so `</script>` cannot terminate the
block) and `'` (so the JS string literal cannot be broken). So this is not
currently exploitable. But the entire defence is one escaping function
behaving correctly inside a `<script>` context, where HTML escaping is not
the natural boundary. Pin each ID to its platform's format
(`GTM-[A-Z0-9]+`, `G-[A-Z0-9]+`, digits for the Meta pixel) and the
question stops being interesting.

### 🟢 S4 · Security headers ship on every response path
`X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`,
`Permissions-Policy`, HSTS (gated on secure+production), CSP in
**Report-Only** by design, and `X-Powered-By` removed
(`app/Http/Middleware/SecurityHeaders.php`). Crucially they also cover
exception responses — 404s and the guest redirect off `/dashboard` — via
`bootstrap/app.php:37-39`. `SecurityHeadersTest` asserts all of it.

### 🟢 S5 · Session/cookie flags
`config/session.php:176` — `'secure' => env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production')`,
so production defaults to secure even if the env var is forgotten.
`http_only` true, `same_site` lax. `.env.example` sets it explicitly anyway.

### 🟢 S6 · Mass assignment on the new features is deliberate
- `is_handled` is **not** in `Lead::$fillable`; the only writer is
  `ContactMessageService::setHandled()`
  (`app/Services/Crm/ContactMessageService.php:46-52`).
- `is_system` is stripped in `LeadSourceService::normalise()`
  (`app/Services/Marketing/LeadSourceService.php:139`).
- Settings writes iterate `SettingsService::DEFINITION`, never the request
  array (`SettingsService.php:109-129`), and `validated()` has already
  dropped unknown keys because rules are generated from the same definition.
- `is_admin` remains excluded from `User::$fillable` with a unit test.

### 🟢 S7 · The contact-messages toggle cannot be used on other leads
`ContactMessageController::handle()` is route-model-bound to `Lead`, and
`:39` does `abort_unless($lead->type === Lead::TYPE_CONTACT, 404)` — so it
cannot flip state on consultation leads the inbox never shows.

### 🟢 S8 · XSS surface unchanged
Exactly 6 `{!! !!}`, all rendering `$page->body` or `$article->body`, both
sanitised on write through HTMLPurifier (`HtmlSanitizerService`, called from
`ArticleController:118` and `PageController:77`). Everything else escaped,
including all settings values, menu labels and contact-message bodies.

### 🟢 S9 · File uploads and secrets
Media uploads restricted to `jpeg,png,webp,mp4,webm` with per-MIME size
ceilings; stored via `$file->store(..., 'public')`, which generates a random
filename — no user-controlled path. The settings logo effectively excludes
SVG (see C7). `.env` has never been committed and `.gitignore` covers it.

### 🟡 S10 · `redirect()->back()` with no fallback — S
`ContactMessageController.php:43`. A POST arriving without a `Referer` lands
on `/`. Trivial, but `back()->withFallback(...)` is one call.

---

## 7. SEO & production readiness

### ✅ Resolved since the last audit
- `sitemap.xml` generated from live content, one entry per URL with
  `xhtml:link` alternates for both locales
  (`app/Http/Controllers/Public/SitemapController.php`); only published
  articles and active services are included.
- `robots.txt` served from a route so the `Sitemap:` line is absolute
  (`routes/web.php:96-119`), with the dashboard and all auth paths
  disallowed.
- Branded bilingual 403 / 404 / 419 / 500 / 503 pages, on a standalone shell
  that deliberately does **not** extend `layouts/public` so it renders when
  the database is unreachable (`resources/views/errors/layout.blade.php:1-6`),
  with `<meta name="robots" content="noindex">` and locale derived from the
  path (`:12`).

### 🟢 Canonical + hreflang
Self-referencing canonical plus `hreflang="ar"`, `hreflang="en"` and
`x-default` → Arabic on every public page
(`layouts/public.blade.php:32-38`), resolving the *same* route with the same
parameters via `route_in_locale()`.

### 🔴 Homepage meta is currently the placeholder
See **C1** / **C8**. The single highest-value SEO surface on the site.

### 🟢 Dashboard/auth pages are not `noindex`-tagged — S
`robots.txt` disallows them, which is sufficient for compliant crawlers, but
a `<meta name="robots" content="noindex">` on `layouts/app` and
`layouts/guest` is the version that survives a stray inbound link. Also
`layouts/app.blade.php:8` emits the same `<title>` on every dashboard
screen.

### 🟡 Logging and operations — S
`.env.example` already ships `LOG_STACK=daily` and `LOG_LEVEL=warning` ✅.
What is missing is anything that tells you a 500 happened: no error
tracking, no alerting, no health monitoring beyond Laravel's `/up`. There is
also no deployment runbook — `docs/setup-instructions.md` covers local setup
only, and the pre-launch checklist exists only in this file.

---

## 8. Test health

### ✅ 788 passing, 2,790 assertions, 15.3s, 62 files
Up from 533 / 1,665. The new features are well covered:

| Area | Coverage |
|---|---|
| Marquee | `MarqueeTest` — **25 cases**, incl. two-identical-halves, unit width, equal px/s, locale flip, clone `aria-hidden` + `tabindex`, reduced motion, and "no other section became a marquee" |
| Settings | `SettingsControllerTest`, `SettingsRepositoryTest` (13 cases incl. per-page query-count assertions) |
| Contact messages | `ContactMessageControllerTest` |
| Lead sources | `LeadSourceControllerTest` |
| Back-to-top | `BackToTopButtonTest` |
| Responsive pass | 4 files — `…ShellTest`, `…FormTest`, `…TableTest`, `…MetricsTest` |
| Every GET route | `RouteSweepTest` — sweeps both locales *and* the dashboard, with a meta-test guarding against the filters narrowing to nothing |
| Seed separation | `DemoDataSeparationTest`, `SeedersTest` |

### 🟡 T1 · Still no direct unit tests for the service layer — M
`tests/Unit/` holds four files: `ExampleTest`, `FactoriesTest`,
`HasSlugTest`, `ModelsTest`. Every service is exercised only *indirectly*
through feature tests. The two that most deserve direct tests are unchanged
from the last audit and are now more load-bearing, not less:

- **`HtmlSanitizerService`** — the XSS boundary for all six `{!! !!}` sites.
  Named in **zero** test files.
- **`SeoTagService`** — a five-level fallback chain that decides the title
  of every page, and the reason C1 manifests the way it does. Named in
  **zero** test files.

`SettingsService`, `ContactMessageService` and `LeadSourceService` are at
least named in their controllers' feature tests.

### 🟡 T2 · The two load-bearing helpers still have no direct test — S
`current_route_base_name()` and `route_in_locale()` (`app/helpers.php`)
appear in **0** test files. The first drives every nav active-state; the
second drives the language toggle *and* every `hreflang` tag *and* the error
pages' home links. `lroute()` appears in 3.

### 🟢 T3 · Known, acceptable gaps
`RouteSweepTest` skips routes with required parameters by design; those are
covered by their own section tests. Framework `ExampleTest` boilerplate
remains in both suites.

---

## Prioritised backlog

Most-blocking first.

| # | Task | Dimension | Sev | Effort | Anchor |
|---|---|---|---|---|---|
| 1 | Enter real contact/social/SEO values at `/dashboard/settings` — kills the placeholder in the homepage `<title>`/`og:title`/`twitter:title`, the `/contact` block, and `wa.me/9665` | Completeness · SEO | 🔴 | S | `database/seeders/SettingsSeeder.php:66-74, 86-87` |
| 2 | Create the production `.env` from `.env.example`: real `ADMIN_EMAIL`/`ADMIN_PASSWORD`, `APP_URL=https://…`; confirm no `admin@example.test` user exists | Completeness · Security | 🔴 | S | `.env.example:5-8, 24-25` |
| 3 | Configure trusted proxies / force HTTPS scheme — without it HSTS is never sent and URLs use the wrong scheme behind a load balancer | Security | 🔴 | S | `bootstrap/app.php:19-31`; `SecurityHeaders.php:66` |
| 4 | Legal review of privacy policy + terms | Completeness | 🔴 | S (external) | `PageContentSeeder`, pages `privacy-policy` / `terms-and-conditions` |
| 5 | Run `./vendor/bin/pint` — 5 files are drifting, a regression since the last audit | Code quality | 🟡 | S | `DashboardHomeTest`, `ContactMessageControllerTest`, `routes/dashboard.php`, `lang/{ar,en}/dashboard.php` |
| 6 | Fix the Arabic-string-as-translation-key so English admins get English errors | Code quality · i18n | 🟡 | S | `UpdateTrackingSettingsRequest.php:41` |
| 7 | Dashboard drawer pinned to the wrong edge — `end-0` should be `start-0`; confirm in a browser first, and move the two string assertions with it | Responsive | 🟡 | S | `components/dashboard/sidebar.blade.php:27-29`; tests at `DashboardResponsiveShellTest.php:92,154` |
| 8 | Pause control + `prefers-reduced-motion` for the hero video, login video and testimonial carousel (WCAG 2.2.2) | Accessibility | 🟡 | M | `home.blade.php:3-13, 213`; `layouts/guest.blade.php:22-31` |
| 9 | `loading="lazy"` + `width`/`height` on all 26 remaining images | Performance | 🟡 | S | 26 of 27 `<img>` tags |
| 10 | `preload="none"` on both autoplaying background videos; delete the unreferenced 1.4 MB vertical video and the 11 unused logo variants | Performance | 🟡 | S | `home.blade.php:3`; `guest.blade.php:22`; `public/videos/about/about-office-tour-vertical.mp4` |
| 11 | Real-browser QA pass at 375 / 768 / 1440 in both locales (list below) | Responsive · Visual | 🟡 | M | — |
| 12 | Skip-to-content link + `id` on `<main>` in both layouts | Accessibility | 🟡 | S | `layouts/public.blade.php:167`; `layouts/app.blade.php:123` |
| 13 | Focus trap + Escape + `role="dialog"` on the public drawer; Escape + `aria-expanded` on `x-dropdown` | Accessibility | 🟡 | S | `layouts/public.blade.php:135-164`; `components/dropdown.blade.php:16-34` |
| 14 | Throttle dashboard write routes | Security | 🟡 | S | `routes/dashboard.php` — 0 throttles today |
| 15 | Format validation on tracking IDs (they land inside inline `<script>`) | Security | 🟡 | S | `UpdateTrackingSettingsRequest.php:23` |
| 16 | Decide SVG: either add `allow_svg` and sanitise, or drop it from `accept` + `mimes` so the form stops offering what it rejects | Completeness | 🟡 | S | `settings/edit.blade.php:80`; `UpdateSettingsRequest.php:53` |
| 17 | Unit tests for `HtmlSanitizerService` and `SeoTagService`, plus the two untested helpers | Test health | 🟡 | M | `tests/Unit/` (4 files today) |
| 18 | Extract `<x-flash>` — deletes 25 hand-rolled copies and the last raw `green-100`/`red-100` tokens | Code quality | 🟡 | M | 25 views |
| 19 | Decide whether a new lead should notify anyone — there is no mail/notification code at all | Completeness | 🟡 | S | `grep Mail::` → 0 hits |
| 20 | Give the homepage its own `seo_meta` row (or pass a `$title`) so it stops depending on `default_meta_title` | SEO | 🟢 | S | 10 `seo_meta` rows cover Pages + Articles only |
| 21 | Delete dead code: `SettingsRepository::group()`, `SettingsService::typeOf()`/`groupOf()`, the stale `csrf-token` meta | Code quality | 🟢 | S | `SettingsRepository.php:92`; `SettingsService.php:85,90`; `layouts/app.blade.php:6` |
| 22 | Fix stale comments left by the recent batch | Code quality | 🟢 | S | `app.css:183`; `whatsapp-float-button.blade.php:10-12`; `Lead.php:52-56` |
| 23 | Correct `LeadSourceService::delete()`'s docblock and drop its inert transaction | Code quality | 🟢 | S | `LeadSourceService.php:57-68` |
| 24 | Pause marquee animations off-screen with an `IntersectionObserver` | Performance | 🟢 | S | `app.css:242-275` |
| 25 | Fold the homepage's 3 `pages` lookups into one `whereIn` | Performance | 🟢 | S | `HomeController.php:19, 74` |
| 26 | `.passive` on the header scroll listener | Performance | 🟢 | S | `layouts/public.blade.php:52` |
| 27 | `back()->withFallback()` on the contact-message toggle | Security | 🟢 | S | `ContactMessageController.php:43` |
| 28 | Direction-aware CTA arrow instead of a hardcoded `&larr;` | Accessibility · Visual | 🟢 | S | `home.blade.php:39,68,112`; `marquee-cards/service.blade.php:45` |
| 29 | `noindex` meta on dashboard/auth layouts; per-page dashboard `<title>` | SEO | 🟢 | S | `layouts/app.blade.php:8`; `layouts/guest.blade.php` |
| 30 | Error tracking / alerting + a deployment runbook in `README.md` | Production | 🟢 | M | — |
| 31 | Delete `ExampleTest` boilerplate | Code quality | 🟢 | S | `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` |
| 32 | Compress the remaining video assets (~8 MB after #10) | Performance | 🟢 | M | `public/videos` |
| 33 | Policies/Gates layer — only once a second admin role exists | Security | 🟢 | L | — |

---

## Manual QA required — not verifiable statically

Rendering was not possible in this environment, so **nothing in §4 and
nothing about visual appearance was confirmed**. Check in a real browser at
**375px**, **768px** and **1440px**, in **both `ar` (RTL) and `en` (LTR)**:

| Page / component | What to check |
|---|---|
| **Homepage marquees (×3)** | Seam at the loop point (should be invisible); the three rows alternating direction; that the whole pattern mirrors in Arabic; no page-level horizontal scroll; touch-swipe pausing the animation; that pause-on-hover and pause-on-focus actually fire |
| **Marquee under reduced motion** | With the OS setting on: animation off, clones gone, and the remaining row still horizontally scrollable to its end |
| **Dashboard drawer at 375px** | ⚠️ **CHANGED, needs confirmation.** `end-0` → `start-0`. It should slide in from the same edge the desktop sidebar occupies — left in English, right in Arabic — and never appear mid-screen |
| **Both drawers, keyboard** | Tab must stay inside the open drawer and return to the hamburger on close (`x-trap`); Escape must close both |
| **Skip link** | Tab once from a fresh page load: the link should appear top-start, and activating it should move focus into `<main>`, not just scroll |
| **Pause controls** | The hero video, login background and testimonial carousel each have a 44px toggle. Check the icon flips, the label swaps, and that with OS reduced-motion on none of the three is moving when the page loads |
| **Dashboard stacked tables below 768px** | All 12 tables: label gutter alignment, the actions footer row, the empty-state cell |
| Public navbar + mobile drawer | Open/close, RTL slide direction, whether focus escapes the drawer |
| **Nav dropdown on a real touch device** | Nest a menu item first, then test tap-to-open vs tap-to-follow |
| Homepage hero | Video on mobile (does it autoplay, does it play inline), the testimonial carousel, overlay legibility |
| The five auth pages | Card legibility over the moving video at each width |
| `dashboard/reports` | CSS bar charts and the 90-day spark chart at narrow widths |
| `dashboard/menu` | Reorder buttons on a phone; nested-item indent |
| Back-to-top + WhatsApp buttons | That they stack rather than overlap, at the bottom of a long page, with and without a WhatsApp number configured |
| Error pages | 404 and 500 at each width, in both locales |
