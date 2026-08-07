# Tasks Log

## 2026-08-02 — Database Foundation: migrations and models created

Created the full database schema and Eloquent models for "Bawabat Taasees
Al Sharikat" — no CRM-style tables, roles/permissions, or workflow tables
were introduced, per project scope.

**Package installed:** `spatie/laravel-translatable` (^6.14) — used on every
model with `json, translatable` fields.

**Slug generation:** No sluggable package installed. Implemented a small
`App\Models\Concerns\HasSlug` trait (auto-generates a unique slug from a
translatable source field on `creating()` if `slug` is empty) since the
requirement was simple enough not to warrant an extra dependency.

### Tables created (migrations)
- `users` — added `is_admin` (boolean, default false) via
  `add_is_admin_to_users_table`
- `pages`
- `page_sections` (FK → pages, cascade delete)
- `services` (soft deletes)
- `countries`
- `faqs`
- `articles` (soft deletes)
- `testimonials`
- `media` (FK → users, nullable, null on delete)
- `seo_meta` (polymorphic via `seo_metable_type` / `seo_metable_id`)
- `settings`
- `lead_sources`
- `campaigns`
- `leads` (FK → services, nullable, null on delete; soft deletes)
- `tracking_settings`
- `conversion_events` (FK → leads, nullable, null on delete)

### Models created (app/Models/)
`Page`, `PageSection`, `Service`, `Country`, `Faq`, `Article`,
`Testimonial`, `Media`, `SeoMeta`, `Setting`, `LeadSource`, `Campaign`,
`Lead`, `TrackingSetting`, `ConversionEvent`. Updated existing `User`
model (`is_admin` added to `$casts` only, intentionally excluded from
`$fillable`).

### Verification
- `php artisan migrate:fresh` — 19 migrations, zero errors.
- `php artisan tinker` — created and reloaded Service, Page, Lead,
  Setting, Country, Faq, Article, Testimonial, Media, SeoMeta,
  LeadSource, Campaign, TrackingSetting, ConversionEvent, PageSection,
  and User(is_admin) records; translatable fields, casts, relationships,
  auto-slugs, and scopes all verified working.
- `tests/Unit/ModelsTest.php` — 11 tests / 28 assertions, all passing.

## 2026-08-03 — Seeders created: AdminUser, LeadSource, Settings, TrackingSettings

Seeded baseline reference/lookup data so the app has usable data from a
fresh `migrate:fresh --seed`. No demo content (services/countries/faqs/
articles/testimonials/campaigns) was seeded — that's a separate task.

### Seeders created (database/seeders/)
- `AdminUserSeeder` — one admin user, keyed on email via `updateOrCreate`,
  `is_admin` set explicitly (not mass-assigned, since `is_admin` is
  intentionally excluded from `User::$fillable`). Reads `ADMIN_EMAIL` /
  `ADMIN_PASSWORD` from `.env`; falls back to `admin@example.test` /
  `Xk9#mPz2Qw7!` with a console warning if either is unset.
- `LeadSourceSeeder` — 11 rows (facebook, instagram, google, tiktok,
  snapchat, linkedin, whatsapp, organic, referral, direct, other),
  `is_active = true`, `sort_order` 0–10 in listed order.
- `SettingsSeeder` — 10 rows: 4 `contact` group, 4 `social` group, 2
  `seo_defaults` group, each with a clearly-labeled placeholder value.
- `TrackingSettingSeeder` — 6 rows (meta_pixel_id, gtm_container_id,
  ga4_measurement_id, google_ads_conversion_id,
  google_ads_conversion_label, tiktok_pixel_id), `value = null`,
  `is_active = false`.
- `DatabaseSeeder` updated to call all four in order; removed the
  scaffold `User::factory()` "Test User" call so the seeded DB matches
  the acceptance criteria exactly.

### Env
`.env.example` — added `ADMIN_EMAIL=admin@example.test` and
`ADMIN_PASSWORD=change-me-please` placeholders (no real credentials
committed).

### Verification
- `php artisan migrate:fresh --seed` — zero errors. Row counts: 1 user
  (`is_admin = true`), 11 `lead_sources`, 10 `settings`, 6
  `tracking_settings` (all `is_active = false`).
- `php artisan db:seed` run a second time — identical row counts,
  confirming `updateOrCreate` idempotency, no duplicates, no errors.
- `tests/Feature/SeedersTest.php` — 3 tests / 13 assertions, all passing
  (baseline counts, idempotency on double-seed, admin password is
  bcrypt-hashed and `is_admin` is true).
- Full suite: `php artisan test` — 14 tests / 41 assertions, all passing.

## 2026-08-03 — Dashboard authentication implemented (Breeze + EnsureUserIsAdmin middleware)

Added login/logout for the dashboard and locked `/dashboard` down to
authenticated admins only. No public registration, no roles/permissions
package — single `is_admin` boolean gate, matching project scope.

### Installed
`laravel/breeze` (^2.4, dev dependency) via `breeze:install blade`. Pulled
in Alpine.js, Tailwind Forms plugin, and the Blade auth scaffolding
(controllers, requests, views, routes/auth.php).

### Created
- `app/Http/Middleware/EnsureUserIsAdmin.php` — guest → redirect to login;
  authenticated non-admin → 403; admin → pass through.
- `routes/dashboard.php` — `/dashboard` prefix, `['auth', 'admin']`
  middleware, one placeholder route named `dashboard`.
- `resources/views/dashboard/placeholder.blade.php` — "Dashboard — logged
  in as {email}", using Breeze's unstyled `x-app-layout` as-is.
- `tests/Feature/DashboardAuthTest.php` — the 6 required scenarios.

### Modified
- `bootstrap/app.php` — registered `'admin' => EnsureUserIsAdmin::class`
  middleware alias.
- `routes/web.php` — removed Breeze's default `/dashboard` route/view
  (replaced by `routes/dashboard.php`); added `require __DIR__.'/dashboard.php'`.
- `routes/auth.php` — removed the `GET`/`POST /register` routes.
- `.env.example` — unchanged (ADMIN_EMAIL/ADMIN_PASSWORD already added in
  the seeders task).

### Removed (dead code once registration was cut)
- `app/Http/Controllers/Auth/RegisteredUserController.php`
- `resources/views/auth/register.blade.php`
- `tests/Feature/Auth/RegistrationTest.php` (tested the now-removed route)
- `resources/views/dashboard.blade.php` (Breeze's default view, replaced)

### Conflicts found and resolved
- Breeze's installer created its own `/dashboard` route in `routes/web.php`
  guarded by `['auth', 'verified']` — removed it in favor of
  `routes/dashboard.php` with `['auth', 'admin']`, since `verified` isn't
  meaningful here (`User` doesn't implement the `MustVerifyEmail`
  contract) and doesn't enforce the admin gate.
- `AuthenticatedSessionController@store` already redirects via
  `route('dashboard', absolute: false)` rather than a hardcoded path, so
  no change was needed there — naming the new route `dashboard` was
  sufficient to repoint the post-login redirect.
- Breeze's generated `RegistrationTest` failed once `/register` was
  removed (expected — it was testing the feature we intentionally cut),
  so it was deleted rather than left red.

### Verification
- `php artisan route:list` — no `register` route present at all.
- `tests/Feature/DashboardAuthTest.php` — 6 tests / 19 assertions, all
  passing (guest redirect, admin 200, non-admin 403, invalid login stays
  guest, logout + re-redirect, register unreachable + no row created).
- Full suite: `php artisan test` — 41 tests / 115 assertions, all passing
  (no regressions in Breeze's own auth/profile tests or prior
  models/seeders tests).
- Manual end-to-end check via `php artisan serve` + `curl`: guest →
  redirected to `/login`; POST `/login` with seeded admin credentials →
  302 to `/dashboard`; GET `/dashboard` → 200 showing
  "Dashboard — logged in as admin@example.test"; `/register` → 404;
  POST `/logout` → 302 to `/`; `/dashboard` after logout → redirects to
  `/login` again.

## 2026-08-03 — Dashboard CRUD — Services, Countries, FAQs, Testimonials

Full CRUD dashboard screens for the four simplest, structurally-similar
content models. Articles, Media, Leads, Settings, Campaigns, and
TrackingSettings intentionally excluded — each needs its own task.

### Standing decisions (documented, not oversights)
- **No per-resource Policies.** The single-admin gate is already enforced
  at the route level via `['auth', 'admin']` on every `routes/dashboard.php`
  route. Adding Policies on top would duplicate that check for a
  single-role app with no per-record ownership concept, so every new
  `Store*`/`Update*Request::authorize()` just returns `true` with a
  comment pointing at the route middleware as the actual gate.
- **`ContentPublishingService` for shared upload logic.** All four
  controllers inject `App\Services\Cms\ContentPublishingService` (never
  `new`'d directly) for the one non-trivial piece of shared logic in this
  task: storing/replacing cover/flag/avatar images. Validation itself
  (mime, size) stays in the Form Requests, not the service — the service
  only handles storage mechanics (random filename via `store()`, deleting
  the previous file on replace).
- **No repository interfaces.** Controllers call Eloquent directly
  (`Service::create()`, `$country->update()`, etc.) — introducing
  repository abstractions for four simple CRUD models would be
  overengineering with no second implementation in sight.

### Created
- `app/Services/Cms/ContentPublishingService.php` — `storeImage()`,
  `replaceImage()`, `deleteImage()`; injected via constructor DI into
  `ServiceController`, `CountryController`, `TestimonialController`
  (`FaqController` has no image field, so it doesn't depend on this
  service).
- `app/Http/Controllers/Dashboard/{Service,Country,Faq,Testimonial}Controller.php`
  — thin resource controllers (`index`, `create`, `store`, `edit`,
  `update`, `destroy`; no `show`, not needed per scope). Mass assignment
  goes through `$request->safe()->except([...file field...])`, never
  `$request->all()`.
- `app/Http/Requests/Dashboard/{Store,Update}{Service,Country,Faq,Testimonial}Request.php`
  (8 files) — full field-by-field validation matching each migration
  exactly; Arabic (`*.ar`) required, English (`*.en`) nullable, on every
  translatable field; `image|mimes:jpeg,png,webp|max:2048` on every
  upload field; slug uniqueness on `Update*Request` ignores the current
  record via route-model-bound `$this->route('service')` etc.
- `resources/views/dashboard/{services,countries,faqs,testimonials}/{index,create,edit}.blade.php`
  (12 files) plus one `_form.blade.php` partial per resource (shared by
  create/edit to avoid duplicating the field markup) — plain Breeze
  Tailwind styling, no brand design applied. Delete buttons use a native
  `onsubmit="return confirm(...)"` prompt.
- `tests/Concerns/CreatesAdminUsers.php` — shared `makeAdmin()` helper
  (same pattern as `DashboardAuthTest`, since `is_admin` isn't fillable).
- `tests/Feature/Dashboard/{Service,Country,Faq,Testimonial}ControllerTest.php`
  — index/guest/non-admin/store/update/destroy coverage per resource,
  plus image-upload accept/reject cases for the three resources with an
  image field, plus a duplicate-slug rejection test for Country.

### Modified
- `routes/dashboard.php` — added `Route::resource(...)->except('show')`
  for all four under a `Route::name('dashboard.')` group (nested inside
  the existing `['auth','admin']` group, without touching the existing
  `dashboard` named route used by the login redirect).
- `resources/views/layouts/navigation.blade.php` — added nav links
  (desktop + mobile) to the four new index pages so they're reachable
  from the UI; no styling changes.

### Verification
- `php artisan route:list --path=dashboard` — 25 routes, all four
  resources correctly `dashboard.{resource}.{action}` named, no `show`
  route present.
- `php artisan test --filter=Dashboard` — 46 tests / 114 assertions,
  all passing.
- Full suite: `php artisan test` — 81 tests / 210 assertions, all
  passing (no regressions in auth/seeders/models tests).
- Manual end-to-end check via `php artisan serve` + `curl`: logged in as
  seeded admin, created a Service with a real JPEG cover image and
  Arabic+English content → appeared in the index list and on disk under
  `storage/app/public/services/`; submitted an edit with a non-image file
  (`/etc/hosts`) → redirected back to the edit form with a validation
  error, record left unchanged; deleted the record → 302 to index, gone
  from `Service::query()` but still found via `Service::withTrashed()`,
  confirming the soft-delete (Country/Faq/Testimonial use hard deletes
  per their migrations, confirmed via `assertDatabaseMissing` in tests).

## 2026-08-03 — Dashboard CRUD — Articles and Media Library

Full CRUD for Article (the project's first field allowed to contain real
HTML) and a standalone Media Library. Same standing decisions as the
previous CRUD task apply: no per-resource Policies (route-level
`['auth','admin']` gate), no repository interfaces.

### Sanitization package and allow-list
Installed **`mews/purifier`** (^3.4, wraps HTMLPurifier) —
`composer require mews/purifier` +
`php artisan vendor:publish --provider="Mews\Purifier\PurifierServiceProvider"`
published `config/purifier.php`. Added two custom profiles:
- **`article`** — `p,h2,h3,h4,ul,ol,li,strong,em,a[href],img[src|alt],blockquote`.
  Everything else (`script`, `style`, `iframe`, `on*` event attributes,
  `javascript:` URIs) is removed because HTMLPurifier is allow-list based:
  anything not explicitly permitted is stripped, not escaped.
- **`plain_text`** — empty allow-list, used by
  `HtmlSanitizerService::stripAllTags()` for article title/excerpt. This
  replaced an initial `strip_tags()` implementation after a test caught a
  real bug: PHP's `strip_tags()` only removes tags, not the *content*
  inside removed elements, so `<script>alert(1)</script>` became the
  literal text `alert(1)` instead of disappearing. Not an XSS risk on its
  own (title/excerpt always render via `{{ }}`), but wrong behavior for a
  "strip everything" field — switched to routing both fields through
  HTMLPurifier so element content is removed too, not just the tags.
- Sanitization happens in `ArticleController::sanitize()`, called from
  both `store()` and `update()` before `Article::create()`/`update()` —
  chosen over the Form Request's `passedValidation()` hook so the same
  "validated input becomes final persisted data" transformation step
  already used for `cover_image` (via `ContentPublishingService`) covers
  body sanitization too, in one consistent place.
- **XSS test confirmed passing** — `test_store_strips_script_tags_and_event_handlers_from_body`
  posts `<p>Hello</p><script>alert('xss')</script><img src=x onerror=alert(1)>`
  and asserts the stored value contains neither `<script` nor `onerror`
  nor `alert(`, while `<p>Hello</p>` survives. Verified twice: once via
  the automated test (in-memory sqlite), once manually via a real HTTP
  POST + `php artisan tinker` + a raw `SELECT body FROM articles` — the
  stored JSON was `{"ar":"<p>Hello</p><img src=\"x\" alt=\"x\">", ...}`,
  confirming sanitization happens before the row is ever written, not
  just on display.
- `{!! $article->body !!}` is documented as the *only* field in the
  project allowed to skip `{{ }}` — reserved for the future public
  article-detail view, with a code comment required at that call site
  explaining why (not built in this task, since it's dashboard-only
  scope; noting it here so the convention isn't lost).

### Environment limitation — 100MB video upload (reported, not silently reduced)
Neither local PHP environment currently supports a real 100MB upload:
- **CLI PHP** (Homebrew, what `php artisan serve`/`artisan` use) —
  `/opt/homebrew/etc/php/8.5/php.ini`: `upload_max_filesize=2M`,
  `post_max_size=8M`.
- **XAMPP's Apache-bundled PHP** (what actually serves this app via
  XAMPP normally) — `/Applications/XAMPP/xamppfiles/etc/php.ini`:
  `upload_max_filesize=40M`, `post_max_size=40M`, `memory_limit=512M`,
  `max_execution_time=120`.

The `StoreMediaRequest` validation rule is still implemented correctly
at 100MB for videos (`mp4`/`webm`) vs 5MB for images — that's the right
application-level behavior for a production environment with a properly
raised `php.ini`. But on this machine, any upload over ~40MB (Apache) or
~2MB (CLI) will be rejected/truncated by PHP itself before Laravel's
validation ever runs. Automated tests aren't affected (`UploadedFile::fake()`
doesn't go through a real HTTP upload, so it bypasses `php.ini` entirely).
To actually test a large video upload in the browser, raise
`upload_max_filesize` and `post_max_size` to at least `100M` in
`/Applications/XAMPP/xamppfiles/etc/php.ini` and restart Apache.

### Created
- `app/Services/Cms/HtmlSanitizerService.php` — `sanitizeArticleBody()`
  (allow-list HTML) and `stripAllTags()` (empty allow-list, plain text).
- `app/Services/Cms/MediaLibraryService.php` — `upload()` (creates a
  `Media` row: path/disk/mime_type/size/type/alt_text/uploaded_by) and
  `delete()` (removes both the disk file and the DB row, so nothing is
  orphaned).
- `app/Http/Controllers/Dashboard/ArticleController.php` — `index/create/
  store/edit/update/destroy`; injects both `ContentPublishingService`
  (cover image, reused unchanged from the previous task) and
  `HtmlSanitizerService`.
- `app/Http/Controllers/Dashboard/MediaController.php` — `index/store/
  destroy` only (no create/edit page, upload happens inline on the index
  page per scope); injects `MediaLibraryService`.
- `app/Http/Requests/Dashboard/{Store,Update}ArticleRequest.php` — slug
  unique/ignore-self, `cover_image` image|max:2048, `published_at`
  nullable date, `is_published` boolean, `title` required (ar required/en
  optional), `excerpt` fully nullable end-to-end (matches its nullable DB
  column — the "ar required" convention only applies to fields that are
  themselves required), `body` required (ar required/en optional,
  max:50000 to allow for HTML markup overhead).
- `app/Http/Requests/Dashboard/StoreMediaRequest.php` — `file` validated
  via `mimes:jpeg,png,webp,mp4,webm` plus a closure rule enforcing 5MB
  for images / 100MB for videos (Laravel's `max` rule can't vary by mime
  type on its own); `alt_text` nullable string.
- `resources/views/dashboard/articles/{index,create,edit,_form}.blade.php`
  — plain `<textarea>` for the body field (no WYSIWYG, per scope), with a
  label noting which HTML tags survive sanitization.
- `resources/views/dashboard/media/index.blade.php` — upload form inline
  at the top, grid of thumbnails (images) / video badges, alt text, size,
  delete with `onsubmit="return confirm(...)"`.
- `tests/Feature/Dashboard/ArticleControllerTest.php` — 13 tests:
  index/guest/non-admin, store/update valid+invalid, duplicate slug,
  cover image accept/reject, the XSS-stripping test, and a
  title/excerpt-markup-stripping test.
- `tests/Feature/Dashboard/MediaControllerTest.php` — 8 tests:
  index/guest/non-admin, image upload, video upload, invalid mime
  rejection, oversized image rejection, delete removes both DB row and
  disk file (`Storage::disk('public')->assertMissing()`).

### Modified
- `config/purifier.php` — added the `article` and `plain_text` profiles
  described above.
- `routes/dashboard.php` — added `Route::resource('articles', ...)
  ->except('show')` and `Route::resource('media', ...)
  ->only(['index','store','destroy'])->parameters(['media' => 'media'])`
  — the explicit parameter override was necessary because
  `Str::singular('media')` resolves to `medium`, which would have
  mismatched the `Media $media` type-hint in `MediaController`.
- `resources/views/layouts/navigation.blade.php` — added Articles and
  Media nav links (desktop + mobile), no styling changes.
- `composer.json`/`composer.lock` — added `mews/purifier` (^3.4).

### Explicitly out of scope (documented decisions, not oversights)
- No media picker wired into Service/Country/Testimonial/Article cover
  image fields — those still use their own direct upload input, per this
  task's instructions. Unifying them behind the Media Library is a future
  enhancement.
- No WYSIWYG editor for the article body — plain `<textarea>` is correct
  for this task; deferred to the later styling/UX task.

### Verification
- `php artisan route:list --path=dashboard` — 34 routes total; `articles`
  and `media` correctly named `dashboard.articles.*` /
  `dashboard.media.*`; `{media}` parameter confirmed NOT singularized to
  `{medium}`.
- `php artisan test --filter=Article` / `--filter=Media` — 13 + 8 = 21
  tests, all passing.
- Full suite: `php artisan test` — 102 tests / 274 assertions, all
  passing (no regressions in prior auth/seeders/models/CRUD tests).
- Manual end-to-end via `php artisan serve` + `curl`: posted the exact
  malicious body from the task (`<p>Hello</p><script>alert('xss')</script><img src=x onerror=alert(1)>`)
  through the real HTTP form path → confirmed via raw SQL that only
  `<p>Hello</p><img src="x" alt="x">` was stored; uploaded a real JPEG to
  the Media Library → appeared in the grid with correct type/alt text,
  file present on disk; deleted it → DB row and disk file both
  confirmed gone.

## 2026-08-03 — Full project health check completed

**102/102 tests passing, 54/54 routes verified working correctly, 1 real
issue found — see `docs/testing/health-check-2026-08-03.md` for full
detail.** No new features added; this task only verified what already
exists, end to end, from a clean bootstrap.

**The one real issue:** the branding/homepage-hero task has not actually
been done. Raw assets (logo variants, a real favicon, a hero video) were
dropped into `public/photos/` and `public/videos/` around the time of
this health check, but none of it is wired up — `public/favicon.ico` is
still an empty 0-byte file, `welcome.blade.php` is still the unmodified
Breeze default, `APP_NAME` is still `Laravel`, and `tailwind.config.js`
has no brand color tokens. `TASKS.md` correctly has no entry for this
work, since it genuinely wasn't done — this isn't a documentation gap,
it's a feature gap. See the health-check report §4 for a recommended
follow-up task breakdown.

**Two minor, non-blocking findings, left as-is per this task's scope**
(reported, not fixed): (1) `package.json` has a dangling unused
`@tailwindcss/vite: ^4.0.0` devDependency left over from before Breeze
installed its own v3-based Tailwind setup — causes no build issue, just
dead weight; (2) this project is still not a git repository at all
(`git status` / `git ls-files` checks were N/A for that reason) —
`.gitignore` is already correctly configured for when `git init` does
happen.

Every other check passed cleanly: guest/non-admin route protection,
`/register` 404, the Article XSS-sanitization test (re-run in isolation),
`is_admin` mass-assignment protection (live-tested with an actual
self-elevation attempt via `/profile`), `storage:link` correctly
configured, Service/Article cover images upload and display correctly,
Media Library deletes don't affect other resources' images, and empty
English translations render as clean empty strings with zero errors
logged across the entire testing session.

No files were modified except this entry and the new health-check
report — no code changes were made in this task.

## 2026-08-03 — All Laravel/Breeze default branding removed, real brand assets + homepage hero wired in

Every remaining trace of default Laravel/Breeze branding is gone —
`grep -ril "laravel" resources/views` now returns **zero matches**
(confirmed after removing `welcome.blade.php`, the only file that still
contained the literal string). `/`, `/login`, and `/dashboard` were all
checked live over real HTTP and show the real logo, real app name, and
real favicon; none show the Laravel SVG or "Laravel" text anywhere.

**Video: 3.53MB → 2.00MB (mp4, 43.3% smaller) / 1.84MB (webm, 47.9%
smaller).** Source was 718×540 (smaller than the 1920px cap, so no
upscaling was applied — output stayed at 718×540), 18.6s, with an audio
track that was stripped. Both outputs were decoded frame-by-frame with
`ffmpeg -f null -` before the source was deleted, to catch corruption
before removing the only copy.

### Part A — Assets placed
- Moved all 16 files from `public/photos/` to `public/images/brand/`
  (exact filenames preserved) — confirmed count before and after the
  move, then removed the now-empty `public/photos/`.

### Part B — Laravel/Breeze branding removed
- `.env`, `.env.example` — `APP_NAME` changed from `Laravel` to
  `"بوابة تأسيس الشركات"`.
- `resources/views/welcome.blade.php` — **deleted** (only after the new
  homepage was verified working at `/` — see Part E).
- `resources/views/components/application-logo.blade.php` — replaced
  the inline Laravel SVG mark with an `<img>` pointing at
  `logo-icon-color-128.png`. Checked both call sites
  (`layouts/guest.blade.php` for login, `layouts/navigation.blade.php`
  for the dashboard sidebar) and trimmed the now-dead
  `fill-current text-gray-*` classes from both, since those were
  SVG-fill-only styling that does nothing on an `<img>`.
- `grep -ril "laravel" resources/views` — before this task, the only
  file containing the literal string was `welcome.blade.php` itself
  (title fallback, "Laravel has an incredibly rich ecosystem" copy, two
  `laravel.com`/`cloud.laravel.com` links, an HTML comment). Deleting
  that file resolved 100% of the matches — confirmed clean with a fresh
  grep afterward.
- `public/favicon.ico` — was a 0-byte empty file; replaced with the real
  786-byte icon copied from `public/images/brand/favicon.ico`. Added
  `<link rel="icon">` and `<link rel="apple-touch-icon">` tags to
  `layouts/guest.blade.php`, `layouts/app.blade.php`, and the new
  `layouts/public.blade.php`.
- Also updated the `<title>{{ config('app.name', 'Laravel') }}</title>`
  fallback-to-'Laravel' default in both existing layouts to just
  `config('app.name')`, for defense in depth (harmless either way since
  `APP_NAME` is now always set, but removes the literal string from the
  source entirely).

### Part C — Video processing
Source `IMG_3401.mp4`: h264, 718×540, 18.6s, ~1.59Mbps, 3,697,496 bytes,
with an AAC audio track. Since 18.6s is well under the 20-30s
trim-decision threshold, no trimming question was needed — proceeded
straight to encoding.
- `ffmpeg -vf "scale='min(1920,iw)':-2" -c:v libx264 -crf 23 -preset slow -an -movflags +faststart`
  → `public/videos/hero-bg.mp4`, 2,097,481 bytes, 718×540 (no upscale).
- `ffmpeg -vf "scale='min(1920,iw)':-2" -c:v libvpx-vp9 -crf 32 -b:v 0 -an`
  → `public/videos/hero-bg.webm`, 1,926,379 bytes, 718×540.
- `ffmpeg -ss 00:00:01 -vframes 1` → `public/images/hero-poster.jpg`,
  718×540 JPEG, 65,654 bytes.
- Both outputs decoded cleanly end-to-end (`ffmpeg -f null -`, exit code
  0 for both) and were confirmed reachable/playing over real HTTP before
  `public/videos/IMG_3401.mp4` was deleted.

### Part D — Tailwind brand tokens
- `tailwind.config.js` — added the 8 brand colors (`primary-green`,
  `dark-green`, `luxury-gold`, `light-gold`, `bg-soft`, `text-main`,
  `text-secondary`, `border-default`) under `theme.extend.colors`;
  changed default `font-sans` from Figtree to `'IBM Plex Sans Arabic'`
  (with the existing system-font fallback stack kept as final
  fallback).
- Font loaded via Bunny Fonts CDN (`fonts.bunny.net`) — same CDN Breeze
  already used for Figtree, so no new font-loading approach was
  introduced; verified the `ibm-plex-sans-arabic` slug resolves
  correctly (both Arabic and Latin subsets) before wiring it in.
- `resources/css/app.css` — added a small `@layer base` block applying
  `bg-bg-soft text-text-main` to `body` globally.
- `docs/decisions/03-design-tokens.md` — created, documents the full
  color table, the font choice and why, and where each token is wired
  in.
- `package.json` — removed the unused `@tailwindcss/vite: ^4.0.0`
  devDependency flagged by the health check. Double-checked first that
  `vite.config.js` never imports/registers it (only
  `laravel-vite-plugin` is used) and that the live toolchain is
  entirely v3-based (`postcss.config.js` uses classic `tailwindcss: {}`,
  resolved package was `tailwindcss@3.4.19`) — confirmed genuinely
  unused before removing. `npm install` afterward removed 11 packages
  total (the plugin + its transitive deps), 0 vulnerabilities.

### Part E — Public layout + homepage hero
- `app/View/Components/PublicLayout.php` — a class-based Blade component
  mirroring the existing `AppLayout`/`GuestLayout` pattern (discovered
  by debugging a real "Unable to locate a class or view for component
  [public-layout]" error — `<x-app-layout>` doesn't work via generic
  Blade path-convention magic as I first assumed; Breeze scaffolds real
  PHP classes under `app/View/Components/` for its layouts, so
  `<x-public-layout>` needed the same treatment to resolve to
  `layouts/public.blade.php`).
- `resources/views/layouts/public.blade.php` — `dir="rtl"`, favicon/app-name
  in `<head>`, header (full-color logo desktop / icon-only mobile,
  linking home; nav with Home wired to the real route and
  Services/About/Contact as explicitly TODO-commented `href="#"`
  placeholders until those public pages exist), footer (`dark-green`
  background, white-silhouette logo, real company name in the copyright
  line).
- `app/Http/Controllers/Public/HomeController.php` — single `index()`
  action, thin: fetches `contact_whatsapp` from `Setting` and passes it
  to the view, nothing else.
- `resources/views/public/home.blade.php` — hero section:
  `hero-bg.webm`/`.mp4` background video (`autoplay muted loop
  playsinline`, `.webm` source listed first since browsers pick the
  first supported `<source>`), `hero-poster.jpg` poster,
  `dark-green/70` overlay for text contrast, the exact Arabic copy
  specified (label, heading, description, both CTA buttons), and a
  WhatsApp button built from the live `contact_whatsapp` setting value
  (not hardcoded) — note the seeded value is still the placeholder
  string from the seeders task (`"+966 5xx xxx xxx (placeholder — update
  in Settings)"`), so the `wa.me` link currently resolves to a
  non-working partial number until a real WhatsApp number is entered via
  Settings; this is expected/correct behavior given the current data,
  not a bug in the wiring.
- `routes/web.php` — `/` now points at `HomeController@index` (named
  `home`) instead of the inline `view('welcome')` closure.

### Verification
- `grep -ril "laravel" resources/views` → **no output** (fully clean).
- `php artisan test --filter=Home` → 1 passed (4 assertions): asserts
  `/` is 200, contains the real hero heading, contains a reference to
  `hero-bg.mp4`, and does not contain the literal string "Laravel"
  anywhere in the response body.
- Full suite: `php artisan test` → **103 tests / 278 assertions, all
  passing** (was 102/274 before this task — the +1 is the new
  `HomeTest`). One pre-existing test needed a fix as a direct
  consequence of this task's own change: `tests/Feature/ExampleTest.php`
  hit `/` without `RefreshDatabase`, and `/` now queries the `settings`
  table via `HomeController` — added `use RefreshDatabase;` to fix it
  (this is fallout from changing the `/` route in this task, not an
  unrelated fix).
- Manual, real-HTTP verification (not just automated tests) of all
  three required surfaces: `/` (200, real hero heading present, `<video>`
  tag with both `.webm`/`.mp4` sources, dark-green overlay, all three
  logo `<img>` tags render, favicon/apple-touch-icon links present,
  zero "Laravel" text), `/login` (real icon logo, no SVG, zero "Laravel"
  text, correct title), `/dashboard` as the seeded admin (real logo in
  nav, correct title, favicon present, zero "Laravel" text). Every
  static asset (`favicon.ico`, `apple-touch-icon.png`, all 3 logo
  variants used, both video formats, the poster image) independently
  checked reachable at 200 via direct `GET` requests, and the video
  files confirmed served with the correct `Content-Type: video/mp4`.

### Judgment calls / things worth flagging
- `preg_replace('/\D/', '', $whatsappNumber)` in `home.blade.php` builds
  the `wa.me` link from whatever digits are in the setting — correct
  logic, but see the placeholder-data note above.
- HTTP range-request support for the video files could not be verified
  under `php artisan serve` (its built-in dev server doesn't support
  `Range` headers at all — returned 200 instead of 206 for a ranged
  request). This is a known limitation of PHP's built-in server, not of
  Laravel or Apache; in the actual XAMPP/production deployment, static
  files under `public/` are served directly by Apache, which supports
  range requests natively. Worth a spot-check under real Apache once
  this is deployed there, but not something `php artisan serve` can
  confirm either way.
- Services/About/Contact nav links are explicit TODO-commented `href="#"`
  placeholders, per this task's scope (homepage only, no other public
  pages built).

## 2026-08-03 — Public pages — Services, Countries, FAQs, Articles

Built the public-facing read side for the four CMS resources whose
dashboard CRUD already existed. No dashboard/admin code touched except
the one nav link this task explicitly called for.

### Routes (all 6, verified)
| Route | Name | Test result |
|---|---|---|
| `GET /services` | `services.index` | 200, active-only, flagship badge, empty state |
| `GET /services/{service:slug}` | `services.show` | 200 valid+active; 404 inactive/unknown |
| `GET /countries` | `countries.index` | 200, active-only, notes shown, empty state |
| `GET /faqs` | `faqs.index` | 200, active-only, sort_order respected, empty state |
| `GET /articles` | `articles.index` | 200, published+past-only, newest first, empty state |
| `GET /articles/{article:slug}` | `articles.show` | 200 valid; 404 future/draft/unknown |

### A real, pre-existing bug this task uncovered and fixed
`APP_LOCALE`/`APP_FALLBACK_LOCALE` were still `en` — inherited from the
original Laravel scaffold and never corrected in any earlier task, since
nothing before this one actually rendered a translatable model field
through spatie/translatable's locale-aware getter (dashboard views all
call `getTranslation('name', 'ar')` explicitly; this task's public views
use the plain `$model->name` accessor, which resolves via
`app()->getLocale()`). Caught during manual verification: a service with
only an Arabic translation rendered with a **completely blank name** on
the public page (no fallback locale to catch it), and a service with
both `ar`/`en` showed the English name despite the entire site being
Arabic-only with no locale switcher. Fixed by setting
`APP_LOCALE=ar` / `APP_FALLBACK_LOCALE=ar` in both `.env` and
`.env.example` — directly necessary for this task's own acceptance
criteria ("content shown is pulled live from the database" requires the
content to actually render). Re-ran the full suite after the change:
123/123 still passing, confirmed no regressions in dashboard views
(which were already locale-explicit and unaffected either way).

### `<x-card>` component
No shared card component existed from earlier tasks — built a minimal
one (`resources/views/components/card.blade.php`, a plain
`$attributes->merge()` wrapper) since services/countries/articles all
needed the same card shell. Reused across all three index pages.

### Created
- `app/Http/Controllers/Public/{Service,Country,Faq,Article}Controller.php`
  — thin, fetch-only. `ServiceController@show` and `ArticleController@show`
  each add one `abort_unless(...)` line so an inactive/unpublished
  record 404s even though route-model binding alone would happily
  resolve it by slug regardless of status.
- `resources/views/components/card.blade.php` — shared card shell (see above).
- `resources/views/public/services/{index,show}.blade.php`,
  `resources/views/public/countries/index.blade.php`,
  `resources/views/public/faqs/index.blade.php` (Alpine `x-data`/`x-show`
  accordion — used core `x-transition:enter/-start/-end` instead of
  `x-collapse`, since `@alpinejs/collapse` isn't installed and the task
  said no extra JS library), `resources/views/public/articles/{index,show}.blade.php`.
- `tests/Feature/Public/{Service,Country,Faq,Article}PageTest.php` — 20
  tests total.

### Modified
- `routes/web.php` — added all 6 routes.
- `resources/views/layouts/public.blade.php` — "خدماتنا" now points at
  `route('services.index')`; "من نحن" and "تواصل معنا" left as `href="#"`
  TODO placeholders exactly as before, since those pages aren't built yet.
- `app/View/Components/PublicLayout.php` — added an optional `$title`
  constructor property so `<x-public-layout :title="...">` actually
  reaches the `<title>` tag (it wouldn't have otherwise — a class-based
  Blade component only exposes attributes that match a declared
  constructor property; everything else lands in the generic
  `$attributes` bag and never reaches `{{ $title ?? ... }}` in the view).
  Used on every new page for a real per-page browser tab title instead
  of the generic site name everywhere.
- `resources/css/app.css` — added a scoped `.article-body` rule set
  targeting exactly the tags `HtmlSanitizerService` allows through (p,
  h2-h4, ul/ol/li, strong, em, a, img, blockquote), instead of pulling
  in `@tailwindcss/typography` for a handful of elements.
- `.env`, `.env.example` — `APP_LOCALE`/`APP_FALLBACK_LOCALE` `en` → `ar`
  (see bug writeup above).

### `{!! !!}` usage — the comment convention
`resources/views/public/articles/show.blade.php` is the first place in
the project `{!! !!}` is actually used. Carries the explanatory comment
promised in the earlier Articles task: sanitized via
`HtmlSanitizerService::sanitizeArticleBody()` at save time, the only
field in the project allowed to skip `{{ }}` — everything else (service
name/summary, country name/notes, FAQ question/answer, article
title/excerpt) renders escaped via `{{ }}`.

### Verification
- `php artisan test --filter=Public` — 20 tests / 44 assertions, all passing.
- Full suite: `php artisan test` — **123 tests / 322 assertions, all
  passing** (was 103/278 before this task).
- Manual, real-HTTP verification of every route (valid + invalid slugs,
  active/inactive, published/draft/future), plus a live end-to-end
  check: edited a Service's Arabic name via the dashboard, reloaded the
  public `/services/{slug}` page with no code change, confirmed the new
  name appeared immediately (same check exists as an automated test:
  `ServicePageTest::test_dashboard_edit_is_reflected_on_public_page`).
  Also spot-checked responsive classes (`sm:hidden`, `sm:grid-cols-*`)
  are present on all three grid/index pages for mobile-width layout.

### Explicitly out of scope (per this task)
- Contact, Consultation, About, Why-Invest, Formation Process,
  Requirements, Privacy, Terms — separate task groups.
- English locale/translation switcher — not built; the `APP_LOCALE=ar`
  fix above makes Arabic the correct single-locale default, it does not
  add any switching capability.
- Service detail's consultation CTA links to `href="#"` with a
  TODO comment (`/consultation` doesn't exist until Group C) — not a
  silent dead link, matches the project's established TODO-comment
  convention for not-yet-built routes.

## 2026-08-03 — Pages/Sections CMS + About/Why-Invest/Formation-Process/Requirements public pages

Built a generic Pages + Page Sections dashboard CRUD on top of the
`pages`/`page_sections` tables (present since the original migrations
task, never had a UI until now), seeded four real pages with starter
Arabic copy, and built the four public pages that render them.

**⚠️ The seeded starter copy is a draft, not final content.** Written to
be factually conservative (no unverifiable statistics, no specific legal
claims — e.g. the formation-process/requirements copy explicitly notes
"details vary by case"), but it has not been legally reviewed. Please
read through `/about`, `/why-invest`, `/formation-process`, and
`/requirements` and edit anything that needs correcting via the new
Pages dashboard screen before treating this as production copy.

### Routes (all 4, verified)
| Route | Name | Test result |
|---|---|---|
| `GET /about` | `pages.about` | 200 with content; 404 if page missing/unpublished |
| `GET /why-invest` | `pages.why-invest` | 200, active sections only, correct order |
| `GET /formation-process` | `pages.formation-process` | 200, steps in correct sort_order |
| `GET /requirements` | `pages.requirements` | 200, active sections only |

### Design decisions
- **4 dedicated controller methods + 4 dedicated views, not one generic
  `show($slug)`.** The four pages share the same data shape (intro body
  + ordered active sections) but need meaningfully different layouts:
  About is prose-only, Why Invest is an advantage-card grid, Formation
  Process is a numbered timeline, Requirements is a checklist. A single
  templated view would need slug-based branching for layout anyway, so
  a dedicated view per page stays simpler to read and edit than one
  view with four internal layout branches.
- **No page create/destroy in the dashboard**, per this task's explicit
  scope decision — the four pages are fixed and seeded, not
  admin-creatable in v1. `Route::resource('pages', ...)->only(['index',
  'edit', 'update'])`; covered by an automated test asserting the
  `store`/`destroy`/`create` routes don't exist.
- **`page_sections.content` isn't `HasTranslations`** (it's a single
  JSON blob holding `title`/`description`/`icon` together, not spatie's
  one-column-per-field pattern), so `PageSection` got three new Eloquent
  attribute accessors (`title()`, `description()`, `icon()` via
  `Attribute::make()`) that do locale-aware lookups into that JSON blob
  with an `ar` fallback — mirrors how every other translatable field in
  the project behaves, without needing spatie/laravel-translatable on a
  field that isn't structured for it.
- **`key` is a required manual text field**, not auto-generated —
  `page_sections.key` is a required NOT NULL column with no uniqueness
  constraint at the DB level; admins type a short stable identifier
  (e.g. `vision-2030`, `step-1`) rather than it being derived from the
  title.
- **Section `icon` is a plain keyword string**, not an upload — mapped
  to an emoji per view (`chart-line` → 📈, etc.) with a `⭐` fallback for
  unmapped keywords, matching the same dependency-free placeholder
  convention Group A used for missing cover images.
- **Reused `HtmlSanitizerService` unchanged** — `Page.body` goes through
  `sanitizeArticleBody()` (same `'article'` HTMLPurifier allow-list as
  Articles), `title`/`meta_title`/`meta_description` through
  `stripAllTags()`. No second sanitization path was built. Section
  `title`/`description` are always plain text validated as plain
  strings — never sanitized as HTML because they're never allowed to
  contain any, consistent with "always renders via `{{ }}`."

### Created
- `app/Http/Controllers/Dashboard/PageController.php` — `index/edit/update`
  only; injects `HtmlSanitizerService`.
- `app/Http/Controllers/Dashboard/PageSectionController.php` — full CRUD
  nested under a page; repackages the form's flat `title`/`description`/
  `icon` fields into the `content` JSON shape before persisting.
- `app/Http/Requests/Dashboard/UpdatePageRequest.php`,
  `StorePageSectionRequest.php`, `UpdatePageSectionRequest.php`.
- `resources/views/dashboard/pages/{index,edit}.blade.php`,
  `resources/views/dashboard/pages/sections/{index,create,edit,_form}.blade.php`
  — same established pattern as Services/Countries/Articles (table +
  `_form` partial shared by create/edit).
- `database/seeders/PageContentSeeder.php` — seeds `about` (no sections),
  `why-invest-saudi-arabia` (6 sections), `formation-process` (7 ordered
  steps), `required-documents` (6 sections). Pages are
  `updateOrCreate`-keyed on slug; sections are cleared and re-created on
  each run (`page_sections.key` isn't globally unique, so
  delete-then-recreate is the safe idempotent approach) — **same caveat
  as every other content seeder in this project: re-running this seeder
  resets pages/sections back to the starter copy, it does not
  merge/preserve admin edits.** This matches the established
  `SettingsSeeder`/`LeadSourceSeeder` `updateOrCreate` behavior exactly;
  flagging it because it's easy to forget once real content has been
  edited in via the dashboard.
- `app/Http/Controllers/Public/PageController.php` — `about()`,
  `whyInvest()`, `formationProcess()`, `requirements()`; shared private
  `publishedPage(string $slug)` helper (404s on missing or unpublished).
- `resources/views/public/pages/{about,why-invest,formation-process,requirements}.blade.php`.
- `tests/Feature/Dashboard/PageControllerTest.php` (7 tests),
  `tests/Feature/Dashboard/PageSectionControllerTest.php` (9 tests),
  `tests/Feature/Public/PagesTest.php` (7 tests) — 23 tests total.

### Modified
- `app/Models/PageSection.php` — added the three accessors described above.
- `routes/dashboard.php` — added `pages` (index/edit/update only) and
  nested `pages.sections` (full CRUD) resources.
- `routes/web.php` — added the 4 public routes.
- `resources/views/layouts/navigation.blade.php` — added a "Pages" nav
  link (desktop + mobile).
- `resources/views/layouts/public.blade.php` — "من نحن" now points at
  `route('pages.about')`; "تواصل معنا" left untouched as instructed.
- `database/seeders/DatabaseSeeder.php` — registered `PageContentSeeder`.

### Verification
- `php artisan route:list` — all 4 public routes and all 9 dashboard
  routes (4 page + 5 nested section) present and correctly named.
- `php artisan test --filter=Page` — 46 tests / 103 assertions, all
  passing.
- Full suite: `php artisan test` — **146 tests / 373 assertions, all
  passing** (was 123/322 before this task; +23 new tests, exact match).
- Manual, real-HTTP verification of every route, plus live checks: (1)
  edited the About page's title via the dashboard, reloaded `/about`
  with no code change, confirmed the new title appeared immediately;
  (2) created a new Why-Invest section marked inactive via the
  dashboard, confirmed it did NOT appear on `/why-invest`, then deleted
  the test section; (3) confirmed formation-process steps render in the
  exact seeded order (1–7) on the actual rendered page, not just in the
  database. Also spot-checked responsive classes on all 4 new pages for
  mobile-width layout.

## 2026-08-03 — Dashboard shell redesigned — branded sidebar, top bar, real KPI home

Replaced Breeze's default top-nav dashboard chrome with a fixed,
right-aligned (RTL) dark-green sidebar, a slim top bar, and a real KPI
home page — no CRUD business logic touched, only layout/nav/home.

### Live vs. "قريبًا" placeholder sections
| Section | Status |
|---|---|
| لوحة التحكم (home) | **Live** — real KPI counts |
| المحتوى → الخدمات/الدول/الأسئلة الشائعة/المقالات/الشهادات/المكتبة الإعلامية/الصفحات | **Live** — all 7 link to existing working CRUD |
| العملاء المحتملون (Leads) | قريبًا |
| التسويق → الحملات/مصادر العملاء/إعدادات التتبع | قريبًا (all 3) |
| رسائل التواصل | قريبًا |
| التقارير | قريبًا |
| الإعدادات | قريبًا |
| العودة إلى الموقع / تسجيل الخروج | **Live** — existing routes reused unchanged |

### A real regression this task caused and fixed
`tests/Feature/DashboardAuthTest::test_authenticated_admin_can_access_dashboard`
asserted the old placeholder view's literal "logged in as :email" text —
broken the moment the dashboard home was replaced with real KPI content
(which shows the admin's *name*, not email, in the header and top bar).
Fixed the assertion to check for the name instead, with a comment
explaining why — direct fallout of this task's own intentional change,
not an unrelated fix.

### Design decisions
- **`$header`/`$slot` contract preserved exactly** — every one of the 28
  existing dashboard Blade views uses `<x-app-layout><x-slot name="header">`
  without modification; `layouts/app.blade.php` still honors both slots,
  so no CRUD view needed touching.
- **Page header kept as its own full-width row, not crammed into the top
  bar.** First attempt put `$header` inline in the slim top bar
  alongside the hamburger button and avatar — but several existing CRUD
  index views (e.g. Services) put a title *and* a "New X" action button
  inside their header slot via `flex justify-between`, which would have
  looked cramped squeezed between the hamburger icon and the avatar
  dropdown. Moved `$header` to its own full-width bar directly below the
  top bar instead, matching the original layout's visual treatment more
  closely and giving those title+button rows room to breathe.
- **No `x-collapse` plugin** — confirmed `@alpinejs/collapse` still isn't
  installed (checked before building, per the task's instruction to
  reuse it "if already available"). The sidebar's collapsible groups use
  core Alpine `x-show` + `x-transition`, same pattern as the FAQ
  accordion and the existing `<x-dropdown>` component.
- **Mobile pattern: off-canvas drawer.** Below the `lg` breakpoint the
  sidebar is `fixed` and translated fully off-screen
  (`translate-x-full`, since it's anchored `right-0` for RTL); a
  hamburger button in the top bar toggles a shared `sidebarOpen` Alpine
  state (declared once on the outermost layout `<div>`, inherited by
  both the sidebar and the top bar button via normal DOM nesting — no
  Alpine store needed) plus a click-to-close backdrop overlay. At `lg`
  and above the sidebar becomes permanently visible and part of the
  static flex layout.
- **Simple geometric SVG icons, not a icon font/library.** No icon
  package is installed in this project; rather than risk mis-recalled
  complex Heroicons path data rendering as garbled shapes, every sidebar
  icon is built from basic, verifiably-correct SVG primitives (rects,
  circles, simple paths) — clean and professional without adding a
  dependency.
- **`dir="rtl"` added to the dashboard `<html>` tag** — the old layout
  set `lang="ar"` but never set `dir`, meaning the dashboard chrome had
  actually been rendering left-to-right this whole time despite Arabic
  content and RTL-aware public-site conventions elsewhere. Fixed to
  match the public layout's existing `dir="rtl"` convention, directly
  required by this task's "RTL-correct throughout" requirement.
- **No notification bell / search / language toggle** — deliberately
  deferred exactly as instructed. None have a real backing feature
  (no notifications data, no dashboard search, no English UI) and this
  project's standing rule is no fake/dead UI.
- **"Needs attention" cards link to the plain CRUD index, not a
  pre-filtered list.** None of the existing controllers (Article/
  Service/PageSection) support a status query filter, and adding one
  would mean touching CRUD controller logic — explicitly out of scope
  for this task. The "inactive page sections" card links to the Pages
  index rather than a sections list, since `page_sections` has no
  cross-page index route (sections are only ever listed nested under
  one page at a time).

### Created
- `app/Services/Dashboard/DashboardStatsService.php` — 9 count methods,
  each one real query (`Article::where('is_published', ...)`,
  `Service::active()`, etc.), zero fabricated numbers.
- `app/Http/Controllers/Dashboard/DashboardHomeController.php` — thin,
  injects `DashboardStatsService`, passes 9 counts to the view.
- `resources/views/components/dashboard/{sidebar,nav-group,nav-link,coming-soon}.blade.php`
  — `nav-link.blade.php` wasn't in the originally-listed file set but
  was added since the same active/hover link markup repeats ~15 times
  across the sidebar; a shared component avoids 15x-duplicated Tailwind
  class strings prone to drift.
- `resources/views/dashboard/home.blade.php` — the KPI home, looping
  over two small PHP arrays (attention items, overview items) rather
  than hand-writing 9 near-identical card blocks.
- `resources/views/dashboard/coming-soon.blade.php` — full-page wrapper
  (`<x-app-layout>` + the `coming-soon` component) used by all 7
  placeholder routes.

### Modified
- `resources/views/layouts/app.blade.php` — full restructure: sidebar +
  top bar + full-width page-header row + content area; added `dir="rtl"`.
- `routes/dashboard.php` — `/` now points at `DashboardHomeController`;
  added 7 "قريبًا" placeholder routes via a small closure loop over two
  title/message arrays (no controller needed for static placeholder
  content).
- `tests/Feature/DashboardAuthTest.php` — one assertion fixed (see
  regression note above).

### Deleted (fully replaced, no longer referenced anywhere)
- `resources/views/dashboard/placeholder.blade.php` — the old "logged in
  as :email" home content, replaced by `dashboard/home.blade.php`.
- `resources/views/layouts/navigation.blade.php` — the old Breeze
  top-nav, replaced by the sidebar; confirmed via grep it was only
  referenced from `layouts/app.blade.php` before removing it.

### Verification
- `php artisan route:list --path=dashboard` — 54 routes, all present
  and correctly named.
- `php artisan test --filter=Dashboard` — includes the new
  `DashboardHomeTest` (19 tests: exact-count assertions, live count
  updates, all 7 placeholders × admin-200 and guest-redirect, 3 spot-
  checked existing CRUD screens).
- Full suite: `php artisan test` — **165 tests / 422 assertions, all
  passing** (was 146/373 before this task; +19 new tests, one
  pre-existing test's assertion updated per the regression note above).
- Manual, real-HTTP verification: logged in, created real Service/
  Country/Faq/Testimonial/Article/Media test data, confirmed the
  dashboard home's 9 numbers matched exactly what was created (not
  hardcoded); unpublished an Article and reloaded — "مقالات غير منشورة"
  went 1→2 and "إجمالي المقالات المنشورة" went 1→0 with no code change;
  visited all 7 placeholder routes and confirmed each shows its own
  distinct branded message, never a 404/500; confirmed the "المحتوى"
  nav group auto-opens when on a content-CRUD page and stays closed
  otherwise; confirmed the active-state gold highlight applies to
  exactly the current route's link; spot-checked 3 existing CRUD index
  pages still render correctly inside the new shell.
- Confirmed via `storage/logs/laravel.log` timestamps that zero new
  errors were logged across this entire manual testing session.

## 2026-08-03 — Leads + Attribution Capture + Consultation/Contact Forms

First public-facing forms that write to the database, plus first-party
UTM/click-ID attribution capture, plus a real Leads dashboard screen
replacing the "قريبًا" placeholder. No conversion-event logging/pixel
firing, no lead status/pipeline field — both explicitly out of scope.

### Part A — First-party attribution capture (client-side)
- `resources/js/attribution.js` — runs on every page load (bundled into
  the single shared `app.js` entry point used by both public and
  dashboard layouts). Reads `utm_source/medium/campaign/content/term`,
  `campaign_id/adset_id/ad_id`, `gclid/fbclid/ttclid` from the URL query
  string; if any are present, builds a "touch" object (+ `landing_page`,
  `referrer`, `captured_at`) and persists it via two first-party cookies:
  `bts_first_touch` (set once, 30-day expiry, never overwritten again)
  and `bts_latest_touch` (overwritten every time new tracking params are
  present, sliding 30-day expiry). A page load with no tracking params
  touches neither cookie. Exposes `window.BtsAttribution.getFirstTouch()`
  / `.getLatestTouch()` for the form pages to read.
- **No JS test framework existed in this project** (`package.json` had no
  Jest/Vitest/etc.) — added a dependency-free test using Node's built-in
  `node:test` runner (`tests/js/attribution.test.mjs`, run via
  `npm test` / `node --test tests/js`), which shims `document`/`window`
  in a `vm` context and runs the real `attribution.js` source directly
  (not a reimplementation). This is the actual proof for "first-touch
  never overwritten" and "latest-touch always updated, untouched when no
  params present" — pure client-side cookie logic that PHPUnit can't
  exercise since no JS runs in a PHP HTTP test.

### Part B/C — Consultation and Contact forms + server-side lead creation
- `/consultation` (`resources/views/public/consultation.blade.php`,
  `ConsultationController`, `StoreConsultationRequest`) — full field set
  per spec; `requested_service_id` populated from `Service::active()`,
  validated via `Rule::exists('services','id')->where('is_active', true)`.
- `/contact` (`resources/views/public/contact.blade.php`,
  `ContactController`, `StoreContactRequest`) — simpler form (full_name,
  email, phone optional, message required), plus real contact info
  (`contact_phone/whatsapp/email/address` from `Setting`) shown in a
  sidebar card.
- Both forms: one shared CSS-hidden (not `type="hidden"`) honeypot field
  `website_url`; a required consent checkbox driving `consent_given`/
  `consented_at`; hidden `first_touch_snapshot`/`latest_touch_snapshot`
  fields populated client-side from the cookies just before submit;
  `throttle:5,1` on both POST routes; Post/Redirect/Get with a flashed
  Arabic `status` message.
- `app/Services/Marketing/AttributionService.php` — decodes both
  snapshot JSON blobs; populates Lead's flat reporting columns (utm_*,
  campaign_id/adset_id/ad_id, gclid/fbclid/ttclid, landing_page_url,
  referrer_url) **from latest-touch, not first-touch** — documented in a
  code comment: latest-touch is what immediately preceded this specific
  conversion, so it's the more useful single answer for campaign/channel
  reporting, while both full JSON snapshots are still preserved verbatim
  in `first_touch`/`latest_touch` regardless. `source_platform` matches
  `utm_source` against `lead_sources.key` case-insensitively (pure-PHP
  comparison over `LeadSource::all()`, not `whereRaw('LOWER(key)...')`,
  to stay portable between MySQL production and SQLite tests without
  relying on `key` not being treated specially by either dialect) and
  falls back to the raw value if unrecognized — never hard-fails.
- Honeypot check is the first line of both controllers' `store()`
  methods (`$request->filled('website_url')`) — returns the exact same
  success redirect a genuine submission gets, but skips `Lead::create()`
  entirely. Deliberately not a validation rule: a validation-layer
  rejection would 422 and tip the bot off that it was caught.
- Mass assignment via `$request->safe()->except([...])` only, never
  `$request->all()`; all lead data (including free-text `message`)
  renders via `{{ }}` everywhere in the dashboard — proven by a test
  posting a raw `<script>` tag as the message and asserting it comes
  back HTML-escaped.

### A required schema fix found while building the contact form
The original `leads` migration had `phone` as `NOT NULL` with no
default — fine for consultation (phone required there) but broken for
contact, where the task spec explicitly marks phone optional. Omitting
phone on `/contact` threw a real `NOT NULL constraint failed` in testing.
Added `database/migrations/..._make_phone_nullable_on_leads_table.php`
(`$table->string('phone')->nullable()->change()`) — a small, directly
necessary schema fix, not scope creep.

### Part D — Dashboard Leads screen
- `app/Http/Controllers/Dashboard/LeadController.php` — `index` (paginated
  20/page, filterable by `type`/`source_platform`/`requested_service_id`/
  `date_from`/`date_to` via `when()` chains), `show`, `destroy` (soft
  delete/archive — `Lead` already had `SoftDeletes`, no hard delete).
- `resources/views/dashboard/leads/{index,show}.blade.php` — built using
  the project's actual brand tokens and Arabic labels (`x-card`,
  `text-main`/`text-secondary`/`primary-green`), **not** the older plain
  gray/indigo English-label Breeze scaffold every other CRUD index view
  (Services/Countries/Faqs/Testimonials/Articles) still uses. Flagging
  this as a judgment call, not an inconsistency I introduced: those
  older screens predate the branded dashboard-shell redesign and were
  out of scope to restyle; Leads is a brand-new screen built after that
  redesign and the app is Arabic-first, so it follows the shell's actual
  design system instead of matching the older unstyled pattern.
- `routes/dashboard.php` — `leads` removed from the "قريبًا" placeholder
  loop, replaced with `Route::resource('leads', LeadController::class)
  ->only(['index','show','destroy'])`. Sidebar link required no change
  (already pointed at `route('dashboard.leads.index')`).
- `DashboardStatsService` — added `leadsTodayCount()`,
  `leadsThisWeekCount()`, `totalLeadsCount()` (all real `Lead::` COUNT
  queries; soft-deleted/archived leads correctly excluded via the
  default global scope). Added as a **new** "نظرة عامة على العملاء
  المحتملين" section on the dashboard home, not merged into the existing
  "نظرة عامة على المحتوى" (content) section or its `$overviewItems`
  array — leads aren't content, and the task said not to touch existing
  counts, only add.

### Modified
- `resources/js/app.js` — added `import './attribution';`.
- `routes/web.php` — added `consultation`/`consultation.store` and
  `contact`/`contact.store` (POST routes `throttle:5,1`).
- `resources/views/layouts/public.blade.php` — "تواصل معنا" now points
  at `route('contact')` — the last remaining `href="#"` placeholder nav
  link in the project.
- `app/Models/Lead.php` — added `consent_given`/`consented_at` to
  `$fillable` and `casts()`.
- `app/Http/Controllers/Dashboard/DashboardHomeController.php` — passes
  the 3 new lead counts to the view.
- `resources/views/dashboard/home.blade.php` — added the new leads
  overview section (see above).
- `tests/Feature/Dashboard/DashboardHomeTest.php` — removed `leads` from
  the `comingSoonRoutes` data provider (direct fallout of `leads` no
  longer being a placeholder route), added lead-count assertions to the
  exact-counts test, added `dashboard.leads.index` to the
  existing-CRUD-still-works spot check.
- `package.json` — added `"test": "node --test tests/js"`.

### Created (migrations)
- `..._add_consent_to_leads_table.php` — `consent_given` boolean
  (default false), `consented_at` nullable timestamp.
- `..._make_phone_nullable_on_leads_table.php` — see schema-fix note above.

### Created (tests)
- `tests/js/attribution.test.mjs` — 5 tests (Node built-in runner).
- `tests/Feature/Public/ConsultationFormTest.php` — 10 tests: page load,
  valid submission, the exact acceptance-criteria URL
  (`/consultation?utm_source=facebook&utm_medium=paid_social&utm_campaign=test&campaign_id=123&adset_id=456&ad_id=789`)
  populating every attribution column correctly, flat columns following
  latest-touch while first-touch JSON stays untouched, fully-organic
  submission with null attribution, honeypot, rate limiting, consent
  required, inactive service rejected, invalid email rejected.
- `tests/Feature/Public/ContactFormTest.php` — 7 tests: page load +
  real Settings-sourced contact info shown, valid submission, honeypot,
  rate limiting, message required, consent required, attribution mapping.
- `tests/Feature/Dashboard/LeadControllerTest.php` — 11 tests: guest/
  non-admin blocked, index list, filter by type/source/service/date
  range, show renders both cards, XSS-escaping proof, destroy soft-
  deletes, archived leads excluded from index.

### Verification
- `php artisan test` — **191 tests / 546 assertions, all passing** (was
  165/422 before this task; +26 new PHP tests, one pre-existing test's
  data provider + assertions updated per the fallout note above).
- `npm test` (`node --test tests/js`) — **5/5 passing**, including the
  two tests that directly prove the acceptance criteria "navigating to
  another page first (losing the query string) then submitting still
  correctly attributes via cookie" and "a second visit with different
  UTM params updates latest-touch but original first-touch remains."
- `npm run build` — clean, `dist` bundle confirmed to contain the
  `bts_first_touch`/`bts_latest_touch` cookie-name strings (i.e.
  attribution.js is genuinely bundled into the shipped `app.js`, not
  dead code).
- Manual, real-HTTP verification via `php artisan serve` + `curl`: GET
  `/consultation?utm_source=facebook&utm_medium=paid_social&utm_campaign=test&campaign_id=123&adset_id=456&ad_id=789`
  → 200; GET `/contact` → 200; confirmed the honeypot input renders as a
  real (not `type="hidden"`) CSS-offscreen text input and the two
  attribution hidden fields are present and empty until JS populates
  them client-side.

### Explicitly out of scope (per this task, not started)
Conversion-event logging/pixel firing, lead status/pipeline field,
Marketing/Campaigns/Tracking Settings/Pixels dashboard sections (all
still "قريبًا").

## 2026-08-03 — Full homepage sections, navbar overhaul, About media, Tracking Settings + Meta Pixel wiring

Turned the homepage from a single hero section into a real, full-length
scrollable page, rebuilt the public navbar into a sticky bar with a
services dropdown and WhatsApp CTA, gave the About page a real visual
treatment, and built the Tracking Settings dashboard screen + conditional
Meta Pixel/GTM/GA4/Google Ads/TikTok injection. The hero video files
(`public/videos/hero-bg.mp4`/`.webm`) were never touched, re-encoded, or
replaced.

### Data gap found and reported honestly (per this task's explicit instruction)
Before building anything, queried the actual database:
**0 flagship services, 0 active services, 0 active testimonials, 0
published articles.** Only the `why-invest-saudi-arabia` (6 sections) and
`formation-process` (7 sections) pages from an earlier task have real
content. This means **3 of the 6 new homepage sections (Services,
Testimonials, Latest Articles) currently render nothing and hide
themselves** — this is correct, tested behavior, not a bug: acceptance
criterion #7 required a graceful empty state (hide entirely) rather than
a broken-looking block, and item 1 of this task explicitly said to
report rather than invent fake flagship services/testimonials. The
homepage will visibly fill in once real Services/Testimonials/Articles
are added via their existing dashboard CRUD screens (built in earlier
tasks) — no further code change will be needed for that to happen, since
every section reads live from the database.

### About-page media — no real files found, so no stock photography was used
Searched the entire project (`public/`, `storage/app/`, and every
directory plausibly named `about-media`/`photos`/`media`/`assets`) for
any photo or video placed for the About page. **Found nothing new** —
only the already-integrated brand logos (`public/images/brand/`) and the
unrelated homepage hero video/poster exist. Per this task's explicit
instruction not to fall back to generic stock photography, the About
page instead got a professional icon/stat-card section — "خبرتنا",
"التزامنا بالامتثال", "دعمك خطوة بخطوة" — using simple hand-authored
stroke-SVG icons (briefcase, shield-check, ascending-steps) in the same
minimal geometric style already established for the dashboard sidebar
icons, not a third-party icon font/library.

### Part A — Homepage sections
`app/Http/Controllers/Public/HomeController.php` now builds 5 more view
variables alongside the existing `whatsappNumber`, each reading real
data with `->take()` limits, no fabricated content:
- `homeServices` — `Service::active()->orderByDesc('is_flagship')->orderBy('sort_order')->take(6)`.
- `whyInvestSections` / `formationSections` — a shared private
  `pageSections($slug, $limit)` helper that reads live from the same
  `PageSection` rows the full `/why-invest` and `/formation-process`
  pages already render (no duplicated copy to maintain); returns an
  empty collection if the page is missing/unpublished, so the section
  just hides instead of erroring.
- `testimonials` — `Testimonial::active()->orderBy('sort_order')`.
- `latestArticles` — same published+past query `ArticleController@index`
  already uses, `->take(3)`.

`resources/views/public/home.blade.php` — added, in order: Services
preview (flagship badge reused from `/services`), Why Invest highlights
(same icon-keyword map as `public.pages.why-invest`), Formation Process
compact numbered strip (title only, no description — the "not full
detail" distinction from the full timeline page), Testimonials (Alpine
auto-advancing carousel with dot navigation once more than 3 exist,
plain grid otherwise — untestable visually with real data since 0 exist,
but the `count() > 3` branch was exercised manually with temporary
tinker-created records, screenshotted mentally via `assertSee`, then
removed), Latest Articles (same card style as `/articles`), and an
always-visible dark-green final CTA band reusing the hero's exact 3
buttons. Every data-driven section is wrapped in
`@if ($collection->isNotEmpty())`.
- The hero's two CTA buttons (`href="#"` since the homepage task before
  Leads/Consultation existed) now point at `route('consultation')` —
  that route didn't exist when the hero was first built; wiring it now
  is a direct, necessary fix now that it does, not scope creep.

### Part B — Navbar overhaul
`resources/views/layouts/public.blade.php` header rebuilt:
- **Sticky + scroll shadow**: `x-data="{ scrolled: false }"` +
  `@scroll.window="scrolled = window.scrollY > 40"` toggling a
  `shadow-md` class — Alpine only, no new JS library.
- **Services dropdown**: hover-opens (`@mouseenter`/`@mouseleave`) and
  click-toggles, listing every active service by name + a "كل الخدمات"
  link; shows a plain "no services yet" line instead of an empty panel
  when none are active.
- **New links added**: "الدول" (Countries) and "المدونة" — the "Blog"
  label the task asked for, pointing at the existing `articles.index`
  route/`Article` model unchanged (label-only rename, exactly as
  instructed).
- **WhatsApp CTA**: visually distinct filled `#25D366` button (not a
  plain text link), reads `contact_whatsapp` from `Setting`, `onclick`
  fires `fbq('trackCustom', 'WhatsAppClick')` guarded by
  `typeof fbq === 'function'` so it's a no-op with no console error when
  Meta Pixel isn't active.
- **Mobile**: hamburger → slide-in drawer from the right (RTL), same
  link list stacked, WhatsApp button prominent at the top, backdrop
  click-to-close — same drawer pattern already established in the
  dashboard sidebar (`translate-x-full` off-canvas + shared Alpine
  state), reused for consistency rather than inventing a second pattern.
- **`app/Providers/AppServiceProvider.php`** — added a
  `View::composer('layouts.public', ...)` supplying `navServices` and
  `navWhatsapp` to every public page. The navbar renders via
  `layouts.public` from ~10 different controllers (Home, Service,
  Country, Faq, Article, Page ×4, Consultation, Contact); a composer was
  the correct tool here instead of adding the same 2 queries to every
  one of those controllers individually.

### Part D — Tracking Settings dashboard + Meta Pixel/GTM/GA4/Ads/TikTok wiring
- `app/Http/Controllers/Dashboard/TrackingSettingController.php` —
  `edit`/`update` only (no index/create/destroy — the 6 rows are fixed,
  seeded once, never created or deleted from the dashboard). `update()`
  only ever writes to `TrackingSetting` rows already found via
  `TrackingSetting::all()`, keyed by their own DB `key` — request array
  keys are read, never trusted as row identifiers, so there's no way to
  write an arbitrary new row from the form payload.
- `app/Http/Requests/Dashboard/UpdateTrackingSettingsRequest.php` — a
  `withValidator()` after-hook rejects activating a key with an empty
  value (would silently render nothing anyway — caught at submit time
  instead).
- `resources/views/dashboard/tracking-settings/edit.blade.php` — one
  card per key (label + value input + active checkbox), matching the
  project's established card-based dashboard styling.
- `routes/dashboard.php` — `tracking-settings` removed from the "قريبًا"
  loop; real `GET`/`PUT` routes added as `dashboard.tracking-settings.
  {edit,update}` (not `.index`, since there's no list — the sidebar link
  was updated to match, the only necessary sidebar change).
- `resources/views/components/tracking-scripts.blade.php` — one query
  (`TrackingSetting::where('is_active', true)->whereNotNull('value')...`),
  then a conditional block per platform: **Meta Pixel** (official base
  snippet + automatic `PageView` + `<noscript>` fallback pixel),
  **GTM** (official base snippet), **GA4 + Google Ads** (share a single
  `gtag.js` loader if either is active, each gets its own
  `gtag('config', ...)` call — avoids loading the loader script twice),
  **TikTok Pixel** (official base snippet + automatic page view). Every
  script tag is async/dynamically-inserted per each platform's own
  recommended pattern — none of it blocks rendering. IDs are never
  hardcoded — always read from the DB row's `value` at render time.
  Included in `layouts/public.blade.php`'s `<head>` via `<x-tracking-scripts />`.
- **Meta Pixel event wiring**: automatic `PageView` on every public page
  (part of the base snippet); `fbq('track', 'Lead')` added inside
  `consultation.blade.php`'s existing `session('status')` success block;
  `fbq('track', 'Contact')` added the same way in `contact.blade.php`;
  `fbq('trackCustom', 'WhatsAppClick')` on every WhatsApp link's
  `onclick` (navbar ×2, hero, final CTA band) — all four call sites
  guard with `typeof fbq === 'function'` first, so they're silent no-ops
  whenever Meta Pixel isn't active, never a JS error.
  - Judgment call: the honeypot-caught path in `ConsultationController`/
    `ContactController` redirects with the exact same success flash as a
    genuine submission (by original design, so bots can't tell they were
    caught), so the `Lead`/`Contact` pixel event fires on that path too.
    Not distinguished from a real submission — a firmly minor, harmless
    side effect (one extra client-side pixel event, no server-side
    Lead row, no data exposure) not worth extra plumbing to special-case.
- **GTM container ID / GA4 measurement ID / Google Ads conversion
  ID+label / TikTok Pixel ID**: same conditional-injection mechanism
  built and ready (confirmed live in the "verification" section below),
  but this project has no real IDs for any of them yet — only Meta
  Pixel's `Lead`/`Contact`/`WhatsAppClick` events were wired, per the
  task's own scope ("base snippets only" for the other four platforms).

### Created
- `app/Http/Controllers/Dashboard/TrackingSettingController.php`
- `app/Http/Requests/Dashboard/UpdateTrackingSettingsRequest.php`
- `resources/views/dashboard/tracking-settings/edit.blade.php`
- `resources/views/components/tracking-scripts.blade.php`
- `tests/Feature/Dashboard/TrackingSettingControllerTest.php` (7 tests)

### Modified
- `app/Http/Controllers/Public/HomeController.php`,
  `resources/views/public/home.blade.php`,
  `resources/views/layouts/public.blade.php`,
  `resources/views/public/pages/about.blade.php`,
  `resources/views/public/consultation.blade.php`,
  `resources/views/public/contact.blade.php`.
- `app/Providers/AppServiceProvider.php` — the `View::composer` described above.
- `routes/dashboard.php` — real `tracking-settings` routes, replacing the placeholder.
- `resources/views/components/dashboard/sidebar.blade.php` — one route-name fix (`.index` → `.edit`).
- `tests/Feature/Dashboard/DashboardHomeTest.php` — `tracking-settings`
  removed from the `comingSoonRoutes` data provider (direct fallout of
  it no longer being a placeholder route — same pattern as the `leads`
  fix in the previous task).
- `tests/Feature/HomeTest.php` — expanded from 1 test to 9: hero
  unchanged, empty-state hiding, each data-driven section shown when
  real data exists, navbar content, tracking-scripts rendering nothing
  vs. rendering the Meta Pixel base code with a fake test ID.

### Verification
- `php artisan test` — **204 tests / 598 assertions, all passing** (was
  191/546 before this task; +14 net new: 7 TrackingSettingControllerTest
  + 8 new HomeTest cases − 2 removed `tracking-settings` coming-soon
  cases + 1 pre-existing HomeTest kept).
- `npm test` (`node --test tests/js`) — 5/5 still passing, unaffected by this task.
- `npm run build` — clean.
- Manual, real-HTTP verification via `php artisan serve` + `curl`,
  logged in as the seeded admin:
  1. Every public route (`/`, `/services`, `/countries`, `/faqs`,
     `/articles`, `/about`, `/why-invest`, `/formation-process`,
     `/requirements`, `/consultation`, `/contact`) returns 200, no new
     entries in `storage/logs/laravel.log`.
  2. Homepage source confirmed to contain all new section headings and
     `hero-bg.mp4` (hero untouched).
  3. **Full Meta Pixel wiring proof, without a real ID**: `PUT
     /dashboard/tracking-settings` with
     `settings[meta_pixel_id][value]=999999999999999&is_active=1` → DB
     row confirmed updated → reloaded `/` → response body now contains
     `fbq('init', '999999999999999')`,
     `connect.facebook.net/en_US/fbevents.js`, and the
     `facebook.com/tr?id=999999999999999` noscript fallback. Resubmitted
     the form with `is_active` omitted (unchecked) → reloaded `/` → zero
     occurrences of `fbq('init'` in the response. Test ID reset to
     `null`/inactive afterward so no dev leftovers remain.
  4. About page source confirmed to contain all 3 icon-card headings.

### How to verify Meta Pixel firing with a REAL Pixel ID (for the user)
1. Log in to the dashboard → "التسويق" → "إعدادات التتبع".
2. Enter the real Meta Pixel ID in the first field, check "مفعّل", save.
3. Open the site in a browser with the [Meta Pixel Helper](https://chromewebstore.google.com/detail/meta-pixel-helper/fdgfkebogiimcoedlicjlajpkdmockpc)
   extension installed — it will show a green check and the `PageView`
   event on every page. Or open DevTools → Network tab, filter `facebook.com/tr`,
   and confirm a request fires on page load and again after submitting
   `/consultation` or `/contact` (a `Lead`/`Contact` event) or clicking
   the WhatsApp button (a `WhatsAppClick` custom event).

## 2026-08-03 — Blog comments system — public submission + dashboard moderation

Visitors can now leave a comment on any article's public detail page;
every comment is held as `pending` and invisible to the public until an
admin approves it from a new dashboard moderation screen. Reused the
Leads task's honeypot + throttle pattern exactly, per this task's
explicit instruction — no new anti-spam mechanism was invented.

### Confirmed: pending-by-default cannot be bypassed
`CommentSubmissionTest::test_forging_a_status_field_in_the_request_cannot_bypass_moderation`
POSTs a comment with a forged `status=approved` field in the payload and
asserts the row is still created with `status = 'pending'`. This holds
because `StoreCommentRequest` never validates/accepts a `status` field
at all, and `CommentController::store()` hardcodes
`'status' => 'pending'` on every `Comment::create()` call regardless of
anything in the request — there is no code path from public input to an
approved comment. **Test passes**, confirmed in the run below.

### Part 1–2 — comments table + public submission
- `database/migrations/..._create_comments_table.php` — `article_id`
  (FK, cascade-deletes with the article), `name`, `email`, `body` (text),
  `status` (plain string, default `'pending'`, indexed — a DB-level ENUM
  was deliberately avoided, matching the same convention already used
  for `leads.type` elsewhere in this project, for MySQL/SQLite
  portability), `ip_address` (nullable), timestamps.
- `app/Models/Comment.php` — `belongsTo(Article)`, `scopeApproved()`,
  `scopePending()`.
- `app/Models/Article.php` — added one `comments(): HasMany` relationship
  method. Nothing else in the model touched — no CRUD/sanitization logic
  changed, per this task's explicit restriction.
- `app/Http/Requests/Public/StoreCommentRequest.php` — `name`/`email`/
  `body` required (`body` max 2000 chars); `website_url` honeypot
  deliberately unvalidated (same reasoning as `StoreConsultationRequest`
  — a validation rule would 422 and tip off the bot).
- `app/Http/Controllers/Public/CommentController.php` (`store` only) —
  honeypot checked first, identical response for both the honeypot and
  real-submission paths; also re-checks the article is published (same
  `abort_unless` as `ArticleController::show`) so a direct POST can't
  comment on an unpublished/future article that never had a visible form.
- `routes/web.php` — `POST /articles/{article:slug}/comments`, named
  `articles.comments.store`, `throttle:5,1` — same limiter as
  `/consultation` and `/contact`.
- `resources/views/public/articles/show.blade.php` — approved comments
  list (oldest first — see rationale below) + the comment form, reusing
  the exact honeypot markup (CSS-offscreen `<div>`, real `type="text"`
  input, not `type="hidden"`) from `consultation.blade.php`. Comment
  `body` renders only via `{{ }}` — proven by a dedicated XSS test
  posting `<script>alert(1)</script>` as the body and asserting it comes
  back HTML-escaped, never raw.
  - **Ordering: oldest first.** A flat, non-threaded comment section
    reads more naturally as a chronological conversation from first to
    last on an informational/corporate blog like this one, rather than
    the "newest first" pattern that makes sense for a fast-moving social
    feed. Stated here per this task's explicit ask to state and justify
    the choice.

### Part 4 — Dashboard moderation
- `app/Http/Controllers/Dashboard/CommentController.php` — `index`
  (paginated 20/page, filterable by `status` and `article_id` via
  `when()` chains — same pattern as `LeadController::index`), `approve`,
  `reject` (both just flip `status` and redirect with a flash message),
  `destroy` (hard delete — comments aren't a business record worth
  archiving the way Leads are, so no soft-delete here).
- `resources/views/dashboard/comments/index.blade.php` — filter card +
  table (article, name, email, truncated body, status badge,
  approve/reject/delete actions gated by current status so e.g. an
  already-approved comment doesn't show a redundant "approve" button).
- `routes/dashboard.php` — `Route::resource('comments', ...)
  ->only(['index','destroy'])` + two explicit `PATCH` routes for
  `approve`/`reject`.
- `resources/views/components/dashboard/sidebar.blade.php` — "التعليقات"
  added under the existing المحتوى group (between المقالات and
  الشهادات); `$contentActive` route-match list extended to include
  `dashboard.comments.*`.
- `app/Services/Dashboard/DashboardStatsService.php` — added
  `pendingCommentsCount(): int` (`Comment::pending()->count()`) — one
  new method appended, nothing else in the service touched.
- `app/Http/Controllers/Dashboard/DashboardHomeController.php` +
  `resources/views/dashboard/home.blade.php` — one new entry appended to
  the existing `$attentionItems` array ("تعليقات قيد المراجعة", linking
  to the comments index pre-filtered to `status=pending`); the
  "يحتاج انتباهك" section's grid was widened from `sm:grid-cols-3` to
  `sm:grid-cols-2 lg:grid-cols-4` to fit the 4th card cleanly — the only
  layout change, the section itself was not rebuilt.

### Created
- `database/migrations/2026_08_03_013747_create_comments_table.php`
- `app/Models/Comment.php`
- `app/Http/Requests/Public/StoreCommentRequest.php`
- `app/Http/Controllers/Public/CommentController.php`
- `app/Http/Controllers/Dashboard/CommentController.php`
- `resources/views/dashboard/comments/index.blade.php`
- `tests/Feature/Public/CommentSubmissionTest.php` (12 tests)
- `tests/Feature/Dashboard/CommentControllerTest.php` (11 tests)

### Modified
- `app/Models/Article.php` (added `comments()` relation only)
- `app/Http/Controllers/Public/ArticleController.php` (`show()` now
  also passes approved comments)
- `resources/views/public/articles/show.blade.php`
- `routes/web.php`, `routes/dashboard.php`
- `resources/views/components/dashboard/sidebar.blade.php`
- `app/Services/Dashboard/DashboardStatsService.php`
- `app/Http/Controllers/Dashboard/DashboardHomeController.php`
- `resources/views/dashboard/home.blade.php`

### Verification
- `php artisan test --filter=Comment` — **23 tests / 72 assertions, all
  passing**, including the pending-by-default/anti-bypass test above.
- Full suite: `php artisan test` — **227 tests / 670 assertions, all
  passing** (was 204/598 before this task; +23 new, zero regressions,
  no pre-existing test needed fixing this time).
- Manual, real-HTTP verification via `php artisan serve` + `curl`,
  exactly the steps below:
  1. Created a real published article via tinker (`manual-verify-post`).
  2. `GET /articles/manual-verify-post` → 200, "التعليقات (0)".
  3. `POST /articles/manual-verify-post/comments` with a real
     name/email/body → 302 back to the article page with the "سيظهر بعد
     المراجعة" flash message; reloaded the article page → still
     "التعليقات (0)" and the submitted comment body **not** present
     anywhere in the HTML — confirmed pending comments are genuinely
     invisible, not just hidden by CSS.
  4. Logged in as the seeded admin, `GET /dashboard/comments?status=pending`
     → 200, the new comment listed.
  5. `PATCH /dashboard/comments/{id}/approve` → 302.
  6. Reloaded `/articles/manual-verify-post` → now "التعليقات (1)" with
     the comment's name and body visible in the page source.
  7. Cleaned up the manually-created test article/comments afterward.

### How to verify moderation yourself, step by step
1. Visit any real published article at `/articles/{slug}`.
2. Scroll to "التعليقات" at the bottom, fill in the "أضف تعليقًا" form
   (name, email, a comment), submit.
3. You'll see "تم إرسال تعليقك وسيظهر بعد المراجعة" — refresh the page:
   your comment is **not** there yet.
4. Log in to the dashboard → sidebar → المحتوى → التعليقات (or go
   straight to `/dashboard/comments`).
5. Find your comment (filter by "قيد المراجعة" if needed) and click
   "اعتماد".
6. Go back to the article page and refresh — your comment now appears,
   oldest-first among any other approved comments.
7. To test rejection: submit another comment, click "رفض" instead of
   "اعتماد" in the dashboard — it will never appear publicly, and its
   status badge in the dashboard list turns to "مرفوض". Click "حذف" on
   any comment to remove it permanently.
8. The dashboard home ("لوحة التحكم") "يحتاج انتباهك" section shows a
   live "تعليقات قيد المراجعة" count — submit a new comment, reload the
   dashboard home, and watch the number increase by 1; approve or reject
   it and reload again to watch it go back down.

## 2026-08-03 — Full homepage sections, navbar overhaul, About media (Pexels, licensed for commercial use), Tracking Settings + Meta Pixel wiring

Re-issued task covering the same scope as an earlier session's homepage/
navbar/tracking work — Parts A (homepage sections), B (navbar overhaul),
and D (Tracking Settings + Meta Pixel) were already fully built and
verified working; this pass **re-verified all three still work correctly**
(full test suite + real HTTP checks, see below) and completed the one
genuinely new piece: **Part C, real About-page media**, since real
photo/video files were placed at `public/about-media/` for the first time
this session (the earlier attempt found nothing there and used an
icon/stat-card fallback instead — that fallback is now supplemented with,
not replaced by, the real media).

### Confirmed already in place (Parts A, B, D — spot-checked, not rebuilt)
- Homepage: `app/Http/Controllers/Public/HomeController.php` +
  `resources/views/public/home.blade.php` already had all 6 sections
  (Services preview, Why Invest highlights, Formation Process strip,
  Testimonials carousel/grid, Latest Articles, final CTA band), each
  reading live data with graceful empty-state hiding.
- Navbar: `resources/views/layouts/public.blade.php` already had the
  sticky scroll-shadow header, services dropdown, "المدونة" label,
  WhatsApp CTA button, and mobile slide-in drawer.
- Tracking: `TrackingSettingController`, `UpdateTrackingSettingsRequest`,
  `resources/views/dashboard/tracking-settings/edit.blade.php`, and
  `resources/views/components/tracking-scripts.blade.php` (Meta Pixel/
  GTM/GA4/Google Ads/TikTok conditional injection) all already existed
  and already wired into `routes/dashboard.php` and the public layout.
- Re-ran the full suite (228 tests) and hit `/`, `/about`, `/services`,
  `/consultation`, `/contact` over real HTTP in this task to confirm
  none of this had regressed — see Verification below.

### Part C — About page real media (the actual new work this task)
**Source files found exactly as described**, confirmed via `ls` before
touching anything: `public/about-media/pexels-werner-pfennig-6949525.jpg`
(business meeting), `pexels-memory-lane-2157293172-35188667.jpg` (office
building), `13536405_2160_3840_30fps.mp4` (vertical, 2160×3840, 4.2s, no
audio track), `13575013_1920_1080_60fps.mp4` (landscape, 1920×1080,
5.2s, no audio track).

**Processing (ffmpeg + sips, both available on this machine):**
- Photos resized to max 1600px wide (`scale='min(1600,iw)':-2`, `-q:v 3`
  JPEG) and moved to `public/images/about/`:
  `about-team-meeting.jpg` (1600×1066, 164KB, was 1.39MB at 5200×3467)
  and `about-office-building.jpg` (1600×1066, 129KB, was 751KB at
  3888×2592 — smaller pixel count than the other but still shrunk from
  its huge original resolution).
- **Both** videos re-encoded (`libx264 -crf 23 -preset slow -an
  -movflags +faststart`, same settings as the existing hero video for
  consistency) and moved to `public/videos/about/`: the landscape one as
  `about-office-tour.mp4` (2.11MB, the one actually embedded) and the
  vertical one as `about-office-tour-vertical.mp4` (1.49MB, moved and
  properly named per the task's "move both" instruction, but not
  embedded on this page — a portrait-orientation clip doesn't fit a
  standard content-width desktop video embed; kept available on disk for
  a possible future mobile/social use).
- Poster frame extracted from the embedded (landscape) video only:
  `ffmpeg -ss 00:00:01 -vframes 1` → `public/images/about/about-office-tour-poster.jpg`
  (1920×1080, 70KB).
- **Both re-encoded videos decoded cleanly end-to-end**
  (`ffmpeg -v error -i ... -f null -`, exit code 0 for both) before
  `public/about-media/` was deleted — same corruption-check discipline
  used for the original hero video encode.
- `public/about-media/` removed once every file was confirmed migrated
  and reachable over real HTTP (see Verification).

**Integration** (`resources/views/public/pages/about.blade.php`):
- Two photos as a staggered two-column block (`sm:mt-10` offset on the
  second image) directly below the intro text.
- The existing icon/stat cards ("خبرتنا" / "التزامنا بالامتثال" /
  "دعمك خطوة بخطوة") from the earlier no-media session were **kept, not
  removed** — they're real, non-fabricated content and the task never
  asked for them to be replaced, only for real media to be added around
  them.
- Click-to-play video section further down: `x-data="{ playing: false }"`,
  a `<button>` showing the poster image + a play-icon overlay by default,
  swapping to the real `<video controls>` element (which only calls
  `.play()` once clicked, via `$nextTick(() => $refs.aboutVideo.play())`)
  — never autoplaying. The `<video>` element starts with
  `style="display: none;"` as a static fallback (same convention already
  used elsewhere in this project for `x-show`-gated elements, e.g. the
  dashboard mobile drawer) to avoid a flash-of-visible-content before
  Alpine initializes.

**Licensing note (for the record, per this task's explicit instruction):**
all four source files are Pexels stock assets, free for commercial use
under the Pexels License — no attribution is legally required, and none
was added.

### Created/Modified
- `public/images/about/about-team-meeting.jpg`,
  `public/images/about/about-office-building.jpg`,
  `public/images/about/about-office-tour-poster.jpg` (new)
- `public/videos/about/about-office-tour.mp4`,
  `public/videos/about/about-office-tour-vertical.mp4` (new)
- `resources/views/public/pages/about.blade.php` (modified — photos +
  video sections added, icon cards kept)
- `tests/Feature/Public/PagesTest.php` (modified — one new test)
- `public/about-media/` (deleted, now empty and confirmed migrated)

### Verification
- `php artisan test` — **228 tests / 677 assertions, all passing** (was
  227/670 before this task; +1 new About-media test, zero regressions —
  confirms Parts A/B/D from the earlier session are still fully intact).
- `npm run build` — clean.
- Manual, real-HTTP verification via `php artisan serve` + `curl`:
  1. `/`, `/about`, `/services`, `/consultation`, `/contact` all 200.
  2. `/about` response body confirmed to reference all 3 new image paths
     and the video path; `/images/about/about-team-meeting.jpg`,
     `/images/about/about-office-building.jpg`,
     `/images/about/about-office-tour-poster.jpg`, and
     `/videos/about/about-office-tour.mp4` all independently fetched at
     200, the video served with `Content-Type: video/mp4`.
  3. **Hero video confirmed byte-for-byte unchanged**: `public/videos/hero-bg.mp4`
     still exactly 2,097,481 bytes and `hero-bg.webm` still exactly
     1,926,379 bytes, matching the exact sizes recorded when they were
     first encoded — same file, same mtime, never touched.
  4. Homepage re-confirmed to still contain all section headings, the
     `hero-bg.mp4` reference, and the navbar's sticky/dropdown Alpine
     markers (`servicesOpen`, `scrolled`) — nothing regressed.
  5. `public/about-media/` confirmed gone (`ls` → "No such file or
     directory").

### How to verify the About page media yourself
1. Visit `/about` in a browser.
2. Below the intro paragraph you'll see two real photos side by side
   (staggered on desktop).
3. Scroll further past the "خبرتنا" cards to "جولة سريعة داخل مكاتبنا" —
   you'll see a poster image with a play button; the video does **not**
   play automatically. Click it — the real video loads and plays with
   native controls.
4. Confirm nothing autoplayed on page load except the homepage hero
   (the About page video only starts once you click it).

### Data gap still standing (unchanged from the earlier session, restated per this task's rule 7)
Flagship services, active services, active testimonials, and published
articles are still all **0** in the database — the Services/Testimonials/
Latest-Articles homepage sections still correctly hide themselves. This
is unrelated to this task's scope (no fake data was invented) and was
already reported in the prior TASKS.md entry; restating it here since
this task's behavior rules explicitly ask for it again.

## 2026-08-03 — Navbar polish — bigger logo, WhatsApp moved to floating button, language toggle with Arabic fallback added

Three targeted navbar corrections: enlarged the logo, moved WhatsApp out
of the navbar into a site-wide floating button, and added a working
language toggle in the spot WhatsApp used to occupy. Nothing else in the
navbar/homepage/hero was touched.

### Inspected first (per this task's rule 1)
The earlier "Full Homepage Sections + Navbar Overhaul" task had already
run — `resources/views/layouts/public.blade.php` already had the sticky
header, services dropdown, and a WhatsApp CTA button in both the desktop
bar and the mobile drawer. Applied this task's exact requirements against
that real state rather than assuming a blank slate.

### 1 — Logo size
`h-10` (40px) → `h-14` (56px), a 40% increase, on both the desktop
full-color logo and the mobile icon-only variant in the main header bar.
Left the header's own height at `h-20` (80px) — 56px logo still clears
the bar with 12px above/below at every breakpoint, no layout growth
needed. The mobile *drawer's* internal logo (a separate, smaller image
in the drawer's own header row) was deliberately left untouched — this
task asked for "the header logo," not every logo instance on the page.

### 2–3 — WhatsApp: removed from the navbar, added as a floating button
- Removed the WhatsApp `<a>` block from both the desktop bar (next to the
  hamburger button) and the mobile drawer (it was the first item in the
  drawer's nav list) — zero WhatsApp markup remains inside
  `<header>...</header>` now (proven by a dedicated test, see below).
- **Created** `resources/views/components/whatsapp-float-button.blade.php`
  — a `@props(['number'])` component, `fixed bottom-6 left-6` (literal
  physical classes, not the logical `start-*`/`end-*` ones used
  elsewhere in this layout — deliberately pinned to the same visual
  corner regardless of which direction the new language toggle puts the
  page in), `z-30` (matches the header's own stacking level, so it sits
  below the mobile drawer's backdrop/panel — z-40/z-50 — and never
  fights the drawer for visibility while open; confirmed no other
  fixed-position UI like a "back to top" button exists anywhere in the
  project to collide with). Reuses the exact same `contact_whatsapp`
  Setting, `wa.me` link construction, and `fbq('trackCustom',
  'WhatsAppClick')` pixel-event guard the removed nav button used.
  Included once in `layouts/public.blade.php`, right before `</body>`,
  so it renders on every public page regardless of scroll position.
- **Icon**: no WhatsApp icon asset existed anywhere in the project
  already (the removed nav button was text-only). Rather than risk
  hand-reproducing the actual trademarked WhatsApp glyph's bezier-curve
  path from memory and getting a garbled shape, built a simple
  speech-bubble icon from a `<rect>` (rounded corners) + a straight-line
  triangle tail `<path>` — zero curves, 100% verifiably correct — same
  "simple geometric SVG primitives, not a mis-recalled complex path"
  principle already documented for this project's dashboard sidebar
  icons and About-page icon cards.
- Hero button and Contact page WhatsApp access were **not touched** —
  both still work exactly as before, per this task's explicit "don't
  remove WhatsApp access from the site" instruction.

### 4 — Language toggle + Arabic fallback
**No locale-switching mechanism existed anywhere in the project before
this task** (confirmed via a grep across `app/`/`routes/`/`config/` —
the only `getLocale()`/`setLocale()`-adjacent hits were `PageSection`'s
translation accessors and Article/Page dashboard controllers reading the
*current* locale, never anything that lets a visitor *change* it). Per
this task's rule 7, built the minimal version from scratch:
- `app/Http/Middleware/SetLocale.php` — reads `session('locale')`, calls
  `app()->setLocale()` if it's a supported value (`ar`/`en`); does
  nothing otherwise (falls through to `APP_LOCALE=ar`). Registered
  globally on the `web` middleware group in `bootstrap/app.php`.
- `app/Http/Controllers/Public/LocaleController.php` (`update` action
  only) — validates the locale param against the same supported list
  (`abort_unless(...,404)` otherwise), writes it to the session,
  redirects back to whatever page the visitor was on.
- `routes/web.php` — `GET /locale/{locale}`, named `locale.switch`.
- **Session-based, not URL-prefixed** (no `/en/...` route structure
  exists anywhere in this project, and building one would mean touching
  every single public route — far beyond this task's navbar-only scope).
  The toggle persists until switched again, the same pattern used for
  Laravel's own flash-session conventions already relied on elsewhere in
  this project (e.g. the Leads/Consultation success messages).
- `resources/views/layouts/public.blade.php` — `<html lang dir>` is now
  dynamic (`dir="rtl"` for `ar`, `dir="ltr"` for `en`) instead of the
  previous hardcoded `lang="ar" dir="rtl"`. The toggle itself sits
  exactly where the WhatsApp button used to (desktop bar) and at the top
  of the mobile drawer, labeled with whichever locale you'd switch *to*
  (`EN` while on Arabic, `عربي` while on English).
- **Translatable fallback** — `spatie/laravel-translatable` in the
  version installed here ships no publishable `config/translatable.php`
  (just an in-memory `Translatable` singleton with a `fallback()`
  method) — there is nothing to "modify" at that path in this package
  version. The real equivalent: added
  `Translatable::fallback(fallbackLocale: config('app.fallback_locale'))`
  to `AppServiceProvider::boot()`. Behaviorally this was already
  happening implicitly (`HasTranslations::getFallbackLocale()` already
  cascades to `config('app.fallback_locale')`, which has been `'ar'`
  since an earlier task), but it's now a deliberate, explicit setting
  rather than a side effect of an uninitialized property default —
  and it's the thing that makes today's toggle honest rather than a
  dead button: switching to English before any model has real English
  content still shows every Service/Page/Article/etc.'s Arabic value,
  proven by `LocaleTest::test_translatable_fallback_returns_arabic_when_english_is_missing`
  and the HTTP-level equivalent right after it.

### Honest scope note on what "switching to English" visibly does today
Per this task's explicit instruction not to build full English
translations here: switching the toggle correctly flips `dir`
(rtl→ltr) and correctly falls back to Arabic for any model field with no
English translation yet (which today is *every* field — no English
content has been entered anywhere). It does **not** yet translate this
project's static Arabic UI strings (nav labels, buttons, headings) —
those are all `__('نص عربي بالكامل')` calls using the Arabic phrase
itself as the translation key, and no `lang/en.json` catalog exists to
map them to English. Building that catalog is exactly the "separate
future task" this task's own instructions point to — the toggle
mechanism and the model-data fallback (the two things asked for) are
both fully functional today.

### Created
- `app/Http/Middleware/SetLocale.php`
- `app/Http/Controllers/Public/LocaleController.php`
- `resources/views/components/whatsapp-float-button.blade.php`
- `tests/Feature/LocaleTest.php` (7 tests)

### Modified
- `resources/views/layouts/public.blade.php` (logo size, WhatsApp
  removed from nav, language toggle added, dynamic `<html lang dir>`,
  floating button included)
- `bootstrap/app.php` (registered `SetLocale` on the `web` middleware group)
- `routes/web.php` (`locale.switch` route)
- `app/Providers/AppServiceProvider.php` (`Translatable::fallback(...)`)
- `tests/Feature/HomeTest.php` — the old
  `test_navbar_has_services_dropdown_countries_blog_label_and_whatsapp_cta`
  test asserted a `wa.me` link was present *somewhere on the page*, which
  would have kept passing even after WhatsApp moved out of the nav (the
  floating button also renders a `wa.me` link) — split it into a plain
  nav-content test plus a new dedicated
  `test_whatsapp_is_not_in_the_navbar_but_is_a_floating_button` that
  actually asserts no `wa.me` markup exists inside `<header>...</header>`
  specifically. Direct fallout of this task's own change, not unrelated.

### Verification
- `php artisan test --filter=Locale` — **7 tests / 14 assertions, all
  passing**.
- `php artisan test --filter=Home` (per the terminal commands this task
  expected) — 25 tests, all passing (matches both `HomeTest` and
  `DashboardHomeTest` by substring).
- Full suite: `php artisan test` — **236 tests / 694 assertions, all
  passing** (was 228/677 before this task; +7 new `LocaleTest` cases,
  +1 net new `HomeTest` case after the split described above).
- `npm run build` — clean.
- Manual, real-HTTP verification via `php artisan serve` + `curl`:
  1. Confirmed `class="hidden sm:block h-14 w-auto"` on the logo (was
     `h-10`).
  2. Confirmed zero `wa.me` occurrences anywhere before `</header>`
     (covers both the desktop bar and the mobile drawer).
  3. Confirmed the floating button (`fixed bottom-6 left-6`) is present
     and links to the real `contact_whatsapp` number.
  4. `GET /locale/en` → 302 back to the referring page; the *next*
     request's `<html>` tag switched from `lang="ar" dir="rtl"` to
     `lang="en" dir="ltr"`, and the toggle link now correctly offered
     `locale/ar` to switch back.
  5. `GET /locale/fr` (unsupported) → 404, session untouched.

### How to verify each of the 3 changes yourself
1. **Logo**: open `/` at both desktop and mobile widths — the logo is
   visibly larger than before, still vertically centered in the bar, no
   overlap with the nav links or the hamburger icon.
2. **WhatsApp**: look at the navbar (desktop bar and, on a narrow
   screen, the hamburger drawer) — no WhatsApp button/icon anywhere in
   either. Look at the bottom-left corner of the screen instead — a
   green circular button is there, stays put while you scroll, and
   clicking it opens `https://wa.me/{number}` in a new tab.
3. **Language toggle**: click "EN" in the navbar (desktop, where
   WhatsApp used to be) or at the top of the mobile drawer — the page
   reloads in English with `dir="ltr"`; every Service/Page/Article name
   still shows correctly in Arabic (no blank fields) because no English
   translations have been entered yet and the fallback catches it.
   Click "عربي" to switch back.

## 2026-08-04 — Full English translation — infrastructure + content

The public site is now genuinely bilingual: every static UI string, every
public route, and all real database content (4 Pages + 19 PageSections —
the only content that actually exists, see the honest gap note below)
now has a real, professionally-written English translation. Dashboard/
admin UI, hero video, and Leads/Consultation backend logic were not
touched, per this task's explicit restrictions.

### `docs/decisions/00-technical-decisions.md` does not exist
Checked first, as this task's rule 1 required. This file has never been
created in any earlier task — `find`/`ls` across `docs/` confirms it.
The routing structure below therefore follows this task's own explicit,
repeatedly-stated specification (no-prefix Arabic, `/en/...` English,
toggle preserves the current page) rather than a source document that
doesn't exist. Flagging this clearly rather than silently inventing or
ignoring the reference, per rule 7.

### A real architectural conflict found and resolved: the previous session's locale mechanism had to be replaced, not extended
The earlier "Navbar Polish" task built a **session-based** locale toggle
(`/locale/{locale}`, a `locale` session key, no URL prefix for English at
all) — a deliberate, reasonable choice at the time, since no `/en/...`
routing requirement had been specified yet. This task explicitly requires
real `/en/...` URLs (routing, hreflang alternates, and the acceptance
criteria all depend on English having its own crawlable, bookmarkable
URLs) — session state can't provide that. The old mechanism was fully
removed and replaced:
- **Deleted**: `app/Http/Controllers/Public/LocaleController.php`, the
  `/locale/{locale}` route, the global `SetLocale` middleware
  registration in `bootstrap/app.php`.
- **A genuine Laravel routing limitation discovered by direct testing**,
  not assumed: a route like `Route::get('/{locale?}/services', ...)`
  does **not** match `/services` once `{locale}` is omitted — Laravel/
  Symfony route compilation doesn't support an optional parameter
  followed by required literal segments. Proved this with a throwaway
  test route (`/{locale?}/zzztest` → 404 on `/zzztest`, 200 on
  `/en/zzztest`) before designing around it, rather than shipping a
  routing structure that silently 404s on every Arabic URL.
- **The actual fix** — `routes/web.php`: every public route is now
  registered **twice** via one shared closure — canonical names with no
  prefix for Arabic (`services.index`, `services.show`, ...), and
  `"{name}.en"` names under an `en` prefix for English
  (`services.index.en`, `services.show.en`, ...). `Route::pattern` isn't
  involved at all now; the `en` prefix is a literal path segment.
- **`app/helpers.php`** (new, autoloaded via a `composer.json`
  `"files"` entry — `composer dump-autoload` run after adding it) — two
  small global helpers so ~15 Blade views didn't all need bespoke
  if/else locale branching:
  - `lroute($name, $params, $absolute)` — resolves to the `.en` variant
    automatically when the current locale is English, otherwise plain
    `route()`. Every `route(...)` call across every public Blade view
    was changed to `lroute(...)` (dashboard/auth views were **not**
    touched — they have no English variant, and `lroute()` gracefully
    falls through to plain `route()` for names with no `.en` counterpart
    anyway, so it would have been harmless either way, but changing them
    was out of scope).
  - `route_in_locale($locale)` — builds the URL for the **current**
    route in the given locale, preserving every route parameter (a
    service slug, an article slug, ...). This is what makes the
    language toggle preserve the current page and what generates the
    hreflang tags.
- **`app/Http/Middleware/SetLocale.php`** (rewritten) — now reads
  `Route::currentRouteName()` and sets the app locale to `en` if it ends
  in `.en`, `ar` otherwise. Applied per-group in `routes/web.php`, not
  globally.
- **Navbar toggle** (`resources/views/layouts/public.blade.php`) — now
  built from `route_in_locale()` instead of a fixed `/locale/{locale}`
  link; verified to preserve the current page including dynamic slugs
  (see `LocaleTest::test_locale_toggle_preserves_the_current_service_page`).
- **hreflang tags** — added to `<head>`: `ar`, `en`, and `x-default`
  (pointing at the Arabic/no-prefix URL, since that's this site's actual
  default), generated the same way, present on every public route.

### Translatable fallback locale — confirmed, made explicit
`spatie/laravel-translatable` in the version installed here ships **no
publishable `config/translatable.php`** — just an in-memory
`Translatable` singleton with a `fallback()` method (confirmed by reading
the package source; there's nothing to "modify" at that config path in
this version). It was already falling back to `config('app.fallback_locale')`
(`'ar'`, set in an earlier task) implicitly, through an uninitialized
property default — behaviorally correct already, but not a deliberate
setting. Made explicit in `app/Providers/AppServiceProvider.php`:
`Translatable::fallback(fallbackLocale: config('app.fallback_locale'))`.
This is the mechanism that keeps every English page honest today even
before all content has a real translation — proven directly by
`LocaleTest::test_translatable_fallback_returns_arabic_when_english_is_missing`
and the equivalent full-HTTP-request test right after it.

### Hardcoded Arabic string extraction — `lang/ar/site.php` / `lang/en/site.php`
One `site.php` file per locale (not split further — the total string
count didn't justify multiple files), organized into `nav`, `footer`,
`common`, and one section per page (`home`, `services`, `countries`,
`faqs`, `articles`, `consultation`, `contact`, `about`). Every hardcoded
`__('نص عربي...')` call across every public Blade view and the layout
was replaced with `__('site.section.key')`. The English side is
hand-written natural business English for an audience of foreign
investors — not a mechanical/literal translation (e.g. "خطوات العمل" →
"Process", not "Work Steps"; "جاهز لتبدأ؟" → "Ready to Get Started?",
not "Ready to Start?").

**Two real bugs found and fixed while doing this extraction, not just
string moves:**
1. `resources/views/public/services/show.blade.php` had a stale
   `href="#"` TODO link on its "Book a Free Consultation" button — the
   comment said "once Group C builds it," but `/consultation` has existed
   since the Leads/Consultation task and was simply never wired up here.
   Fixed to `lroute('consultation')`.
2. Every article/comment publish date was rendered via
   `->locale('ar')->translatedFormat(...)` **unconditionally**, regardless
   of the actual current locale — dates would have stayed in Arabic month
   names even on the English site. Fixed to `->locale(app()->getLocale())`
   in `home.blade.php`, `articles/index.blade.php`, and
   `articles/show.blade.php` (both the article date and the comment date).

### Brand name — the "pick one consistent form" decision
Using **"Bawabat Taasees Al Sharikat"** as the single consistent English
form everywhere the brand name itself appears (nav logo alt text, footer,
every page `<title>`) — not "Companies Establishment Gate" or any other
literal machine-translation of "بوابة تأسيس الشركات". The fuller
descriptor "Company Formation Gateway" is stored as a separate
`site.brand.tagline` key and used contextually (available for meta
descriptions/taglines) rather than appended to the name on every single
occurrence, which would make page titles and the footer look cluttered
and unprofessional. This is a judgment call on the task's two offered
options — picking the short transliterated form as the actual *name*,
with the English descriptor available as a *tagline*, is how real
bilingual GCC corporate sites typically handle this, and reads as more
professional in a browser tab or footer line than repeating the full
phrase everywhere.

### Part B — the content:translate-to-english command
`app/Console/Commands/TranslateContentToEnglish.php`
(`content:translate-to-english {--force}`):
- Iterates Service, Country, Faq, Article, Testimonial, Page,
  PageSection (read/written directly — its `content` JSON blob isn't a
  spatie/laravel-translatable field, so `getTranslation()`/
  `setTranslation()` don't apply to it), and SeoMeta.
- **No machine-translation API is wired into this project, and none was
  requested** — every English string was written by hand for this task,
  stored in a dictionary **keyed by the exact Arabic source text**. A
  field only gets translated if its Arabic value is an exact match in
  that dictionary; anything else is reported as "no translation
  available" rather than silently skipped or guessed at — this is what
  keeps a future run over new (currently nonexistent) Service/Country/
  Faq/Article/Testimonial content from disappearing into a false
  "nothing to report" — proven by
  `TranslateContentToEnglishTest::test_reports_arabic_content_with_no_available_translation`.
- Idempotent by default (skips any field with existing non-empty
  English), `--force` overwrites.
- HTML body fields (`Page.body`, `Service.body`, `Article.body`) are
  translated **with their wrapping tags preserved**, then still passed
  through `HtmlSanitizerService::sanitizeArticleBody()` before saving —
  no special bypass of that sanitizer for command-authored content.
- Prints a per-model table (translated / skipped-existing /
  skipped-no-translation) plus a full total and a list of every
  untranslated field for manual follow-up.
- **A real bug found by testing, not assumed away**: the command's
  running-totals were instance properties on the Command class, and
  Laravel reuses the same resolved Command instance across multiple
  `Artisan::call()`s within one process — the idempotency test's second
  call was silently inheriting the first call's counts. Fixed by
  resetting all three tracking properties at the top of `handle()`.
  Caught by `TranslateContentToEnglishTest::test_running_twice_is_idempotent`,
  which failed before the fix and passes after it.

### Real command output (run against the actual project database)
**Honest finding, exactly as flagged in every earlier task's TASKS.md
entry**: Service, Country, Faq, Article, Testimonial, and SeoMeta all
have **0 rows** in this database — only the 4 Pages (about,
why-invest-saudi-arabia, formation-process, required-documents) and
their 19 PageSections, seeded in the earlier Pages/Sections task, have
real content. This is not a limitation of the command — it faithfully
reflects that no other business content has been entered yet. The
command is fully generic and ready for all 8 models the moment real data
exists.

First run:
```
+-------------+------------+---------------------------+------------------------------------+
| Model       | Translated | Skipped (already English) | Skipped (no translation available) |
+-------------+------------+---------------------------+------------------------------------+
| Page        | 16         | 0                         | 0                                  |
| PageSection | 38         | 0                         | 0                                  |
| Service     | 0          | 0                         | 0                                  |
| Country     | 0          | 0                         | 0                                  |
| Faq         | 0          | 0                         | 0                                  |
| Article     | 0          | 0                         | 0                                  |
| Testimonial | 0          | 0                         | 0                                  |
| SeoMeta     | 0          | 0                         | 0                                  |
+-------------+------------+---------------------------+------------------------------------+
Total fields translated: 54
Total fields already had English content (skipped): 0
```
Second run (immediately after, no changes in between):
```
| Page        | 0          | 16                        | 0                                  |
| PageSection | 0          | 38                        | 0                                  |
Total fields translated: 0
Total fields already had English content (skipped): 54
```
Idempotency confirmed with real output, not just the test suite.

### Draft-copy caveat carried forward (task requirement #6)
The Arabic starter copy for `about`, `why-invest-saudi-arabia`,
`formation-process`, and `required-documents` was flagged in an earlier
task's TASKS.md entry as **draft content pending legal/professional
review, not final**. Their new English translations are translations of
that same draft copy — the command's own console output ends with an
explicit reminder of this every time it runs, and it's restated here:
**the English versions of these 4 pages are not more final than the
Arabic originals and should go through the same review before being
treated as production copy.**

### Part C — SEO/meta
`SeoMeta.meta_title`/`meta_description` are covered by the same command
(0 rows exist, same honest-gap note as above). Every page `<title>` tag
already correctly reflects the active locale — confirmed via
`LocaleTest::test_html_dir_is_rtl_for_arabic_and_ltr_for_english` and the
brand-name assertion in `test_english_page_shows_english_brand_name_and_nav_labels`
— since `:title="..."` on every public view now builds from
`__('site.brand.name')` / translated page titles instead of the
locale-blind `config('app.name')`.

### Created
- `lang/ar/site.php`, `lang/en/site.php`
- `app/helpers.php` (+ `composer.json` `autoload.files` entry)
- `app/Console/Commands/TranslateContentToEnglish.php`
- `tests/Feature/Console/TranslateContentToEnglishTest.php` (6 tests)

### Modified
- `routes/web.php` (dual locale route registration, replacing the
  session-based mechanism)
- `bootstrap/app.php` (removed global `SetLocale` registration, added
  the `setlocale` middleware alias)
- `app/Http/Middleware/SetLocale.php` (rewritten — route-name based, not
  session-based)
- `app/Providers/AppServiceProvider.php` (explicit `Translatable::fallback()`)
- `resources/views/layouts/public.blade.php` (hreflang tags, `lroute()`,
  toggle rebuilt on `route_in_locale()`, all hardcoded strings extracted)
- `resources/views/public/home.blade.php`
- `resources/views/public/services/index.blade.php`
- `resources/views/public/services/show.blade.php` (+ dead-link fix)
- `resources/views/public/countries/index.blade.php`
- `resources/views/public/faqs/index.blade.php`
- `resources/views/public/articles/index.blade.php` (+ date-locale fix)
- `resources/views/public/articles/show.blade.php` (+ date-locale fix ×2)
- `resources/views/public/consultation.blade.php`
- `resources/views/public/contact.blade.php`
- `resources/views/public/pages/about.blade.php`
- `resources/views/public/pages/why-invest.blade.php`
- `resources/views/public/pages/formation-process.blade.php`
- `resources/views/public/pages/requirements.blade.php`
- `resources/views/components/whatsapp-float-button.blade.php`
- `tests/Feature/LocaleTest.php` (fully rewritten for the new URL-based
  mechanism — the old session-based tests no longer apply)

### Deleted
- `app/Http/Controllers/Public/LocaleController.php`

### Verification
- `php artisan test` — **245 tests / 728 assertions, all passing** (was
  239/709 before this task; +6 new `TranslateContentToEnglishTest`
  cases; `LocaleTest` fully rewritten, net 0 change in its own count).
- `php artisan test --filter=Locale` — 10 tests, all passing, including
  the toggle-preserves-current-page and hreflang-present tests.
- `php artisan content:translate-to-english` (real output above) — run
  twice, second run confirmed idempotent.
- `npm run build` — clean.
- Manual, real-HTTP verification via `php artisan serve` + `curl`: all
  11 public routes return 200 under both the no-prefix Arabic form and
  the `/en/` prefix (22 URLs total). The 4 translated pages
  (`/en/about`, `/en/why-invest`, `/en/formation-process`,
  `/en/requirements`) contain their full English content with **zero**
  Arabic text leaking through except the intentional "العربية"
  language-toggle label (kept in Arabic script by design, the same way
  "Français" stays untranslated inside an English menu).
- **Limitation, stated plainly**: this environment has no browser or
  screenshot tooling available to me, so the "375px/768px/1280px visual
  RTL/LTR correctness" verification could not be done as an actual
  rendered-pixel check. What I *did* verify: no Tailwind class strings
  were touched anywhere in this task (only `__()`/`lroute()` swaps and
  the two bug fixes above), the `dir` attribute switches correctly and
  is proven by test, and every English string was written with length
  in mind against the existing (unchanged) layout containers. Recommend
  an actual visual pass in a real browser at those three widths before
  calling this task's RTL/LTR item fully closed.

### How to review the English site yourself, page by page
1. `/en` — homepage. Hero, "Why Invest" and "Formation Process" preview
   sections show full English content (these read from the newly
   translated PageSections); Services/Testimonials/Latest-Articles
   sections are correctly hidden (0 real records — same standing gap
   noted in every earlier homepage-related task entry, not new).
2. `/en/about`, `/en/why-invest`, `/en/formation-process`,
   `/en/requirements` — full English content, freshly written by the
   command. Read these specifically for tone/accuracy since they're the
   only pages with real translated body copy.
3. `/en/services`, `/en/countries`, `/en/faqs`, `/en/articles` — English
   UI chrome (heading, empty-state message) with the empty state
   correctly showing, since no real records exist yet for any of these.
4. `/en/consultation`, `/en/contact` — full English forms; submitting
   either works identically to the Arabic versions (no backend logic was
   touched).
5. Click the language toggle from any of the above — confirms it lands
   on the Arabic version of the *same* page, not the homepage (this is
   directly tested, but worth clicking through yourself on a few pages).
6. View source on any English page and confirm the three `hreflang`
   `<link>` tags in `<head>`.

## 2026-08-04 — Dashboard fully translated to Arabic — no English leftovers

### Context
The dashboard (Services, Countries, FAQs, Testimonials, Articles, Media,
Pages/Sections, Profile, Breeze auth screens) was originally scaffolded
in mixed Arabic/English — Breeze's default `__('English phrase')` calls
were never swept to Arabic, and Laravel 10+ no longer ships `ar`
translations for its own validation/pagination/passwords/auth strings.
This task audited every dashboard view and controller, eliminated every
remaining English string, and confirmed the dashboard is now 100%
Arabic for staff. The dashboard remains single-locale (Arabic-only, no
toggle) by standing decision — unrelated to and independent from the
public site's separate `/en/` bilingual work.

### Core Laravel lang files published + translated
Laravel 10+ no longer ships `ar` translations for its framework
strings, so `php artisan lang:publish` was run to pull the English
defaults into `lang/en/`, then each was fully translated into
`lang/ar/`:
- `lang/ar/validation.php` — every built-in validation rule (required,
  email, min/max/between for string/numeric/file/array, unique,
  confirmed, etc.), plus a full `'attributes'` array translating every
  field name used across this project's Form Requests (gathered via
  grep of `app/Http/Requests/Dashboard/*.php` and `Public/*.php`) so
  error sentences read naturally instead of mixing an English field
  name into an Arabic sentence.
- `lang/ar/pagination.php` — `previous` → "السابق", `next` → "التالي".
  Laravel's default Tailwind pagination view already calls
  `__('pagination.previous')`/`__('pagination.next')` internally with
  no `dir="ltr"` override, so translating this one file fixes
  pagination arrows across every paginated list in the dashboard with
  no Blade changes needed.
- `lang/ar/passwords.php` — password-reset flow messages (reset, sent,
  throttled, token, user-not-found).
- `lang/ar/auth.php` — login failure, wrong-password, and throttle
  messages.

### Created
- `lang/ar/dashboard.php` — new file, ~230 keys across `common`,
  `services`, `countries`, `faqs`, `testimonials`, `articles`, `media`,
  `pages`, `sections`, `profile`, `auth`, and `flash` groups. Every
  hardcoded English string found in the audit was extracted here.
- `tests/Feature/Dashboard/DashboardArabicValidationTest.php` — two
  Feature tests (`test_service_store_validation_errors_are_in_arabic`,
  `test_country_store_validation_errors_are_in_arabic`) that submit an
  empty payload to two different Form Requests and assert the resulting
  error message contains Arabic script and does **not** contain the
  English default "field is required" text — proof the published
  `validation.php` translation is actually wired up, not just present
  on disk.

### Modified — Blade views (every `__('English phrase')` replaced with `__('dashboard.key')`)
- `resources/views/dashboard/services/{index,create,edit,_form}.blade.php`
- `resources/views/dashboard/countries/{index,create,edit,_form}.blade.php`
- `resources/views/dashboard/faqs/{index,create,edit,_form}.blade.php`
- `resources/views/dashboard/testimonials/{index,create,edit,_form}.blade.php`
- `resources/views/dashboard/articles/{index,create,edit,_form}.blade.php`
- `resources/views/dashboard/media/index.blade.php`
- `resources/views/dashboard/pages/{index,edit}.blade.php`
- `resources/views/dashboard/pages/sections/{index,create,edit,_form}.blade.php`
- `resources/views/auth/{login,forgot-password,reset-password,confirm-password,verify-email}.blade.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/profile/partials/{update-profile-information-form,update-password-form,delete-user-form}.blade.php`

  While already touching every table-header `<th>` line for the text
  swap, also corrected a pre-existing RTL bug in every CRUD index view:
  data-column headers used physical `text-left` under `dir="rtl"`
  (doesn't mirror), changed to `text-right`; action-link containers
  gained `space-x-reverse` alongside the existing `space-x-2` so
  Edit/Delete links space correctly right-to-left.

### Modified — bug fix, missing RTL attribute
- `resources/views/layouts/guest.blade.php` — the `<html>` tag only set
  `lang="{{ ... }}"`, missing `dir="rtl"` entirely (unlike
  `layouts/app.blade.php`, which already had it from an earlier task).
  This meant the login and password-reset pages were rendering LTR.
  Added `dir="rtl"` to match `layouts/app.blade.php`.

### Modified — 8 controllers, hardcoded English flash messages
Found via `grep -n "with('status'" app/Http/Controllers/Dashboard/*.php`
— all 8 replaced `->with('status', 'X created/updated/deleted.')` with
`->with('status', __('dashboard.flash.<key>'))`, new keys added to the
`flash` group in `lang/ar/dashboard.php`:
- `app/Http/Controllers/Dashboard/ServiceController.php` — created/updated/deleted
- `app/Http/Controllers/Dashboard/CountryController.php` — created/updated/deleted
- `app/Http/Controllers/Dashboard/FaqController.php` — created/updated/deleted
- `app/Http/Controllers/Dashboard/TestimonialController.php` — created/updated/deleted
- `app/Http/Controllers/Dashboard/ArticleController.php` — created/updated/deleted
- `app/Http/Controllers/Dashboard/MediaController.php` — created/deleted
- `app/Http/Controllers/Dashboard/PageController.php` — updated
- `app/Http/Controllers/Dashboard/PageSectionController.php` — created/updated/deleted

  (`LeadController`, `CommentController`, `TrackingSettingController`
  already used `__('Arabic string')` directly — confirmed clean, not
  modified.)

### Confirmed clean, not modified
- `resources/views/components/dashboard/*` (sidebar, home/KPI cards),
  `resources/views/dashboard/leads/*`, `resources/views/dashboard/comments/*`,
  `resources/views/dashboard/tracking-settings/*`,
  `resources/views/components/coming-soon.blade.php` — zero English
  found via grep sweep; these were already fully Arabic from earlier
  tasks.
- `resources/views/layouts/app.blade.php` — already had `dir="rtl"`
  hardcoded, no English text.
- Delete-confirmation `confirm()` JS prompts — all already pull from
  `dashboard.*.confirm_delete` keys, confirmed Arabic.

### Not touched (explicitly out of scope)
- The public site and its separate `/en/` English translation
  (`lang/ar/site.php` / `lang/en/site.php`, `resources/views/public/**`,
  `layouts/public.blade.php`) — untouched, confirmed via grep that the
  only `site.*` keys remaining are in public-site files, not dashboard
  files.
- No EN/AR toggle was added to the dashboard — it remains Arabic-only
  by design.
- No business logic was changed — only display strings.

### Verification
- `php artisan test` — **247 tests / 736 assertions, all passing** (was
  245/728 before this task; +2 new
  `DashboardArabicValidationTest` cases).
- `php artisan test --filter=Dashboard` — **131 tests / 352 assertions,
  all passing**.
- Full grep sweep confirming zero `__('English...')` calls remain
  anywhere under `resources/views/dashboard`, `resources/views/auth`,
  `resources/views/profile`, `resources/views/layouts/{app,guest}.blade.php`,
  and `resources/views/components/dashboard` — every match is now
  `__('dashboard.*')`.

### How to verify this yourself
1. Log in as admin (`/login` — confirm the page itself now renders
   RTL and every label is Arabic: "البريد الإلكتروني", "كلمة المرور",
   "تذكرني", "تسجيل الدخول").
2. Visit every dashboard section (Services, Countries, FAQs,
   Testimonials, Articles, Media, Pages → Sections, Leads, Tracking
   Settings, Comments) — sidebar, headings, table columns, empty
   states, and buttons should all read in Arabic.
3. Trigger a validation error on **two different forms** to match the
   acceptance criteria — e.g. submit the Service create form with the
   Arabic name left blank, and separately the Country create form the
   same way. Both should show a red Arabic error message under the
   field (something like "حقل الاسم (عربي) مطلوب."), never the English
   default "The name.ar field is required."
4. Trigger a delete confirmation (e.g. click Delete on any Service,
   Country, FAQ, Testimonial, Article, Media item, or Page Section) —
   the native browser `confirm()` dialog should show Arabic text.
5. Create/update/delete any record and confirm the green flash banner
   at the top shows an Arabic sentence (e.g. "تم إنشاء الخدمة.") not
   "Service created."
6. Visit `/profile` — confirm "معلومات الملف الشخصي", "تحديث كلمة
   المرور", and "حذف الحساب" sections are all Arabic, and that saving
   either form shows "تم الحفظ." Try the password-reset flow
   (`/forgot-password`) and confirm every screen is Arabic end to end.
7. If any list has enough records to paginate, confirm the pagination
   controls read "السابق" / "التالي" instead of "Previous" / "Next".

## 2026-08-04 — Dashboard made fully bilingual (ar/en) with per-admin persisted toggle

### Context
The previous task made the dashboard 100% Arabic with no toggle, by
standing decision. This task reverses that decision: staff now get a
genuine English version of the dashboard alongside Arabic, switchable
per admin via a top-bar toggle, persisted on the `users` table so it
survives logout/login — independent of whatever locale a public-site
visitor happens to be browsing in (that mechanism, `SetLocale` +
`lang/{ar,en}/site.php`, was not touched).

### Inspection findings (done before writing any code, per behavior rule 1)
- The public site's locale is resolved by `SetLocale` middleware from
  the **route name** (`{name}.en` suffix vs canonical) — stateless,
  per-visitor, no user/session involved. Confirmed safe to leave
  completely alone.
- No "Carbon-per-request approach from an earlier Final Arabic Sweep
  task" exists anywhere in this project — grepped `TASKS.md` and
  `app/` for both terms, zero hits. That task and mechanism were never
  built (a similar phantom-reference was flagged in an earlier task
  around a `docs/decisions/00-technical-decisions.md` that also never
  existed). Built `Carbon::setLocale()` fresh inside the new
  `SetDashboardLocale` middleware instead of pretending to "extend"
  something that isn't there.
- `spatie/laravel-translatable`'s fallback was already configured in
  `AppServiceProvider` (`Translatable::fallback(fallbackLocale: config('app.fallback_locale'))`,
  which is `'ar'`) — meaning `$model->name`-style magic accessors on
  Service/Country/Faq/Testimonial/Article/Page are **already**
  locale-aware with Arabic fallback, with zero code changes needed for
  that part of requirement 6. The only places that needed fixing were
  ~10 spots where CRUD **index** views had hardcoded
  `->getTranslation('field', 'ar')` (baked in from the previous
  Arabic-only task) instead of the locale-aware magic accessor — every
  create/edit **form** already correctly shows both `[ar]`/`[en]`
  inputs explicitly and those were left untouched, exactly matching
  "forms always show both languages regardless of toggle."
- `PageSection` (used for Why-Invest/Formation-Process step cards) is
  a raw JSON `content` column, not a Translatable model — but it
  already had a locale-aware `title`/`description` accessor
  (`$this->content['title'][app()->getLocale()] ?? ... ['ar']`) built
  in an earlier public-site task. Dashboard views were using
  `$section->content['title']['ar']` directly instead of that
  accessor — fixed to use `$section->title`.
- Found and fixed a **real bug** while writing the locale toggle test:
  routes/dashboard.php built its 5 "coming soon" placeholder
  title/message strings via `__()` calls sitting directly in the route
  file, evaluated once when the file loads — which happens during
  routing bootstrap, **before** any request-scoped middleware runs.
  This meant Reports/Settings/Campaigns/Lead Sources/Contact Messages
  would always render in whatever locale was active at boot time,
  never respecting the toggle. Fixed by moving the `__()` calls inside
  the route closures (lazy, evaluated per-request after
  `dashboardlocale` middleware sets the locale) — covered by
  `test_coming_soon_placeholder_pages_respect_the_admins_locale`.
- Confirmed via `grep` which dashboard screens actually exist:
  Services/Countries/FAQs/Testimonials/Articles/Media/Pages+Sections
  (full CRUD), Leads (index/show/archive), Comments
  (index/approve/reject/destroy), Tracking Settings (single edit
  form) — all real and built. **Campaigns, Lead Sources, Contact
  Messages, Reports, and Settings are still "coming soon" placeholders
  with no real content or fields** — per behavior rule 7, these were
  translated as placeholders (their title/message chrome now flips
  ar/en correctly) and nothing more was invented for them.
- Confirmed Tailwind 3.4.19 is installed (not the `^3.1.0` floor in
  `package.json`) — full support for `rtl:`/`ltr:` variants and
  logical properties (`text-start`/`text-end`, `ms-*`/`me-*`,
  `start-*`/`end-*`), already used correctly in the existing
  `x-dropdown` component. Used the same idiom throughout rather than
  inventing a second RTL mechanism.

### Created
- `database/migrations/2026_08_04_195246_add_locale_to_users_table.php`
  — `locale` string(5) column on `users`, default `'ar'`, positioned
  after `is_admin`. Ran via `php artisan migrate`; existing/seeded
  admin unaffected (still `'ar'` until they explicitly toggle).
- `lang/en/dashboard.php` — full English translation of every key in
  `lang/ar/dashboard.php`, same nesting, verified key-for-key parity
  by script (`324` keys each side, zero missing either direction).
  Written as natural admin-panel English ("Save", "Are you sure you
  want to delete this service?"), not literal translation.
- `app/Http/Middleware/SetDashboardLocale.php` — reads
  `$request->user()?->locale ?? 'ar'`, calls `app()->setLocale()` and
  `Carbon::setLocale()`. Registered as the `dashboardlocale` alias in
  `bootstrap/app.php`.
- `app/Http/Controllers/Dashboard/LocaleController.php` — single
  `update()` action, thin: flips `auth()->user()->locale` between
  `ar`/`en` and persists it, always operating on `$request->user()`
  (never accepts a user id from the request, so one admin can never
  change another's preference).
- `tests/Feature/Dashboard/DashboardLocaleTest.php` — 12 new Feature
  tests (see Verification below).

### Modified — lang files
- `lang/ar/dashboard.php` — added `sidebar`, `topbar`, `home`,
  `leads`, `comments`, `tracking_settings`, and `coming_soon` key
  groups (these screens were previously left as raw
  `__('Arabic literal string')` phrase-keys, which is why the earlier
  Arabic-only task's audit reported them "already 100% Arabic" — true
  for Arabic, but meant there was no English counterpart to switch to).
  Also added 5 more `flash.*` keys (comment approve/reject/delete,
  lead archive, tracking settings update).
- `lang/en/validation.php` — added a 73-entry `attributes` array
  mirroring `lang/ar/validation.php`'s custom field-name translations.
  **Found via a failing test**, not by inspection: without this, an
  English-locale admin submitting an empty Service form saw the
  mixed-language message *"The الاسم (عربي) field is required."* —
  Laravel's translator resolved the rule template from `lang/en/`
  (present) but fell through to the `fallback_locale` (`'ar'`) for the
  specific `attributes.name.ar` key, which only existed on the Arabic
  side. Now both files have identical attribute keys, so English-locale
  admins get fully English messages like *"The name (Arabic) field is
  required."*

### Modified — routing, middleware, model
- `app/Models/User.php` — added `locale` to `$fillable` (deliberately;
  unlike `is_admin`, this is safe for a user to self-set — task's own
  explicit instruction).
- `bootstrap/app.php` — registered `dashboardlocale` middleware alias.
- `routes/dashboard.php` — added `dashboardlocale` to the dashboard
  group's middleware stack; added `PATCH dashboard/locale` →
  `dashboard.locale.update`; fixed the coming-soon eager-`__()` bug
  described above.
- `routes/web.php` — added `dashboardlocale` to the `/profile` route
  group's middleware (Profile is reachable only once authenticated, so
  there's always a `$user` to read from).
- `routes/auth.php` — added `dashboardlocale` to the post-login
  `auth` group (verify-email, confirm-password, password update,
  logout) for the same reason. Pre-login screens (`login`,
  `forgot-password`, `reset-password`) are **not** in this group —
  there is no user record to read a preference from before
  authentication, so those stay on the app's default locale (`ar`),
  which was already the case and is unaffected by this task.

### Modified — layout & toggle UI
- `resources/views/layouts/app.blade.php` — `dir` attribute on
  `<html>` changed from hardcoded `"rtl"` to
  `{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}`. Added the
  locale toggle as a form (`PATCH` to `dashboard.locale.update`) in
  the top bar next to the admin avatar/name dropdown, showing
  "English" when currently Arabic and "عربي" when currently English.
  Restructured the top bar's flex layout (removed an unused empty
  spacer `<span>`, grouped the new toggle with the existing dropdown)
  rather than bolting the toggle on awkwardly. Dropdown/Logout labels
  switched to `dashboard.topbar.profile` / `dashboard.auth.logout`.

### Modified — sidebar + RTL/LTR mirroring
- `resources/views/components/dashboard/sidebar.blade.php` — every
  label converted from a raw Arabic `__()` phrase-key to
  `dashboard.sidebar.*`. Also fixed physical-direction classes that
  only ever worked for RTL: `right-0` → `end-0` (logical, flips
  automatically with `dir`), and the open/closed transform classes
  changed from a single `translate-x-full` to
  `rtl:translate-x-full ltr:-translate-x-full` for the closed state so
  the sidebar slides off to the correct side in both directions.
- Table headers/action cells across **all 7 CRUD index views**
  (`services`, `countries`, `faqs`, `testimonials`, `articles`,
  `pages`, `pages/sections`) plus `leads/index.blade.php` and
  `leads/show.blade.php`: `text-right` → `text-start`, `text-left` →
  `text-end`, `space-x-2 space-x-reverse` → `space-x-2
  rtl:space-x-reverse` — these were hardcoded physical-direction
  utilities added in the earlier Arabic-only task (correct for RTL
  only); now they flip correctly for LTR using Tailwind's built-in
  logical-property and `rtl:`/`ltr:` variant support, the same idiom
  already used by the pre-existing `x-dropdown` component.

### Modified — views converted to `dashboard.*` keys (previously raw Arabic phrase-keys, now bilingual)
- `resources/views/dashboard/home.blade.php`
- `resources/views/dashboard/leads/index.blade.php`
- `resources/views/dashboard/leads/show.blade.php`
- `resources/views/dashboard/comments/index.blade.php`
- `resources/views/dashboard/tracking-settings/edit.blade.php`

### Modified — controllers (flash messages, previously raw Arabic `__()` phrase-keys)
- `app/Http/Controllers/Dashboard/CommentController.php` — approve/reject/delete
- `app/Http/Controllers/Dashboard/LeadController.php` — archive
- `app/Http/Controllers/Dashboard/TrackingSettingController.php` — update

### Modified — locale-aware data display (requirement 6: active locale + Arabic fallback in lists, both languages always in forms)
- `resources/views/dashboard/services/index.blade.php`
- `resources/views/dashboard/countries/index.blade.php`
- `resources/views/dashboard/faqs/index.blade.php`
- `resources/views/dashboard/testimonials/index.blade.php`
- `resources/views/dashboard/articles/index.blade.php`
- `resources/views/dashboard/pages/index.blade.php`
- `resources/views/dashboard/pages/edit.blade.php` (header title only — the form fields below were already correct)
- `resources/views/dashboard/pages/sections/index.blade.php`
- `resources/views/dashboard/pages/sections/create.blade.php` (header title only)
- `resources/views/dashboard/pages/sections/edit.blade.php` (header title only)

  Pattern in each: `$model->getTranslation('field', 'ar')` (hardcoded
  Arabic) → `$model->field` (locale-aware magic accessor with Arabic
  fallback, already wired up project-wide via `AppServiceProvider`).
  `_form.blade.php` partials and the inline form fields in
  `pages/edit.blade.php` were **not** touched — those correctly keep
  explicit `getTranslation($field, 'ar')` / `getTranslation($field, 'en')`
  calls so both language inputs always show regardless of the toggle.

### Not touched (explicitly out of scope)
- Public site's `SetLocale` middleware, `lang/{ar,en}/site.php`,
  `resources/views/public/**`, `layouts/public.blade.php` — completely
  separate mechanism, confirmed unaffected by
  `test_dashboard_locale_toggle_does_not_affect_public_site_locale`.
- No public self-registration route was added — `dashboard.locale.update`
  sits inside the existing `['auth', 'admin', 'dashboardlocale']`
  group and never accepts a user id.
- Business logic — only display strings, locale resolution, and the
  new `locale` column were touched.
- `resources/views/layouts/guest.blade.php` (login/forgot-password)
  intentionally left with static `dir="rtl"` — see routing note above.

### Verification
- `php artisan test` — **259 tests / 771 assertions, all passing**
  (was 247/736 before this task; +12 new
  `DashboardLocaleTest` cases).
- `php artisan test --filter=Dashboard` — **142 tests / 383 assertions,
  all passing**.
- `php artisan test --filter=Locale` — **21 tests / 60 assertions, all
  passing** (10 pre-existing public-site `LocaleTest` + 11 of the new
  dashboard ones matching the filter name).
- Manual: `php artisan tinker` — confirmed `__('dashboard.sidebar.dashboard')`
  resolves to `"Dashboard"` under `app()->setLocale('en')` and
  `"لوحة التحكم"` under `'ar'`, and `dashboard.coming_soon.reports_title`
  resolves correctly per-locale (proving the route-file lazy-evaluation
  fix actually works, not just passes its own test).
- Grepped `resources/views/dashboard`, `resources/views/components/dashboard`,
  `app/Http/Controllers/Dashboard`, `routes/dashboard.php` for any
  remaining `__('literal string')` calls not pointing at a
  `dashboard.*` key — zero found.

### How to verify this yourself
1. Log in as admin — dashboard loads in Arabic/RTL exactly as before
   (untouched default).
2. Click the "English" button in the top bar next to your avatar —
   the whole dashboard (sidebar, current page, table headers, buttons)
   switches to English/LTR immediately, and the sidebar itself
   visually mirrors (now sits on the left, opens/closes from the left).
3. Click through every real section — Services, Countries, FAQs,
   Testimonials, Articles, Media, Pages → Sections, Leads, Comments,
   Tracking Settings — confirm full English chrome, no leftover
   Arabic. Click Campaigns/Lead Sources/Contact Messages/Reports/
   Settings in the sidebar — confirm the "coming soon" placeholder
   text is in English too (these remain placeholders with no real
   content, by design — not part of this task to build).
4. On any list (e.g. Services), confirm each row shows the record's
   **English** name/title if one was entered, or its **Arabic** value
   as a fallback if the English field was left blank when the record
   was created — never a blank cell.
5. Open any Create or Edit form (e.g. Services → Add Service) —
   confirm both an Arabic-labeled input and an English-labeled input
   are visible for every translatable field, unaffected by which
   language the surrounding page chrome is in.
6. Submit a form with a required field left blank (e.g. Service create
   with the Arabic name blank) — confirm the validation error under
   the field reads in English, e.g. "The name (Arabic) field is
   required."
7. Log out and log back in — confirm the dashboard is still English
   (this is the persistence requirement: it's stored on your user
   record, not just the browser session).
8. Click "عربي" to toggle back — confirm everything (including RTL
   layout, sidebar side, table alignment) is restored exactly as it
   was before step 2.
9. Open the public site (`/` and `/en/`) in a separate tab — confirm
   its language/RTL-LTR is completely unrelated to whatever you just
   set in the dashboard.

## 2026-08-05 — Full bilingual QA sweep completed — top bar RTL/LTR mirroring fixed, exhaustive audit in docs/testing/bilingual-audit-2026-08-05.md

**Summary: 4 real issues found and fixed** — the top-bar mirroring bug,
2 hardcoded-brand-name spots, and 7 mislabeled table column headers.
Full itemized checklist covering every public route and dashboard
screen in both locales: `docs/testing/bilingual-audit-2026-08-05.md`.

### Part A — Top bar RTL/LTR mirroring bug (real bug, root-caused)
`resources/views/layouts/app.blade.php`'s `<header>` used
`justify-between` with two children: a hamburger button (`lg:hidden`)
and the admin-controls cluster (locale toggle + avatar dropdown). At
desktop widths the hamburger is removed from the box tree entirely
(`display: none`), leaving exactly one flex child — and per the CSS
spec, `justify-content: space-between` with a single item collapses to
`flex-start`, landing the cluster on the wrong side (and the same
physical side regardless of `dir`) instead of mirroring.

**Fix**: `justify-between` → `ms-auto` (`margin-inline-start: auto`) on
the cluster div — pushes it to the logical end of the row
unconditionally, independent of the hamburger's visibility. Resolves
to the LEFT edge under `dir="rtl"`, RIGHT edge under `dir="ltr"`.

While in there, also fixed two physical-positioning leftovers in the
sidebar that the earlier CRUD-table RTL fix never covered (it only
touched `resources/views/dashboard/**`, not
`resources/views/components/dashboard/*`):
- `resources/views/components/dashboard/sidebar.blade.php` — `right-0`
  → `end-0` (logical); closed-state `translate-x-full` → conditional
  `rtl:translate-x-full ltr:-translate-x-full` so it slides off to the
  correct side in both directions.

### Part B — Exhaustive bilingual audit
Real dev-database content barely exists yet (1 placeholder Service, 1
Country, 0 everything else — content entry is the client's job via the
dashboard). Used two methods together: (1) `curl` against a running
`php artisan serve` for the 5 real-content public pages (Home, About,
Why Invest, Formation Process, Requirements), and (2) a one-time,
since-deleted Feature test (`BilingualAuditDumpTest.php`) that seeded
full bilingual representative data — including a real run of the
`content:translate-to-english` command, the same one that produced the
dev DB's actual English page content — and dumped all 92 route×locale
HTML renders to disk for grep + manual review. Full methodology,
including a test-harness false-positive that was caught and
re-verified against a real server (not silently trusted), is in the
audit doc.

**3 real issues found (plus the topbar bug above) and fixed**:
1. Dashboard `<title>` used `config('app.name')` (fixed to the Arabic
   company name via `.env`) instead of the locale-aware
   `__('site.brand.name')` the public site already uses — browser tab
   title stayed Arabic even in English mode.
   `resources/views/layouts/app.blade.php`.
2. Same bug in the sidebar logo's `alt` text —
   `resources/views/components/dashboard/sidebar.blade.php`.
3. 7 CRUD index table column headers (Services, Countries, FAQs,
   Testimonials, Articles, Pages, Page Sections) were labeled "Name
   (Arabic)" / "الاسم (عربي)" etc., left over from when the dashboard
   was Arabic-only and that column always showed the Arabic value. An
   earlier task made the cell content itself locale-aware (shows the
   record's English value when toggled), but never updated the header
   label to match — so an English-locale admin saw an English name
   under a column literally saying "(Arabic)". Added locale-neutral
   `dashboard.common.name` / `dashboard.common.title` /
   `dashboard.faqs.question_column` / `dashboard.testimonials.quote_column`
   keys (both languages); the old `_ar`-suffixed keys are untouched and
   still correctly label the create/edit form's Arabic-specific input.

No other issues found across 92 page×locale renders, automated grep
screening (Arabic-on-English pages, English-Breeze-leftovers-on-Arabic
pages, unresolved translation keys, mixed-language lines), and manual
line-by-line reading of a representative sample.

### Created
- `docs/testing/bilingual-audit-2026-08-05.md` — the full itemized
  checklist (route/screen × locale × checked × issues × fixed) for
  every public route and dashboard screen, plus the methodology and
  spot-check instructions.

### Modified
- `resources/views/layouts/app.blade.php` — `justify-between` → `ms-auto`
  fix on the top bar; `<title>` brand name fix.
- `resources/views/components/dashboard/sidebar.blade.php` — `right-0`
  → `end-0`, closed-state transform made direction-aware, logo `alt`
  brand name fix.
- `lang/ar/dashboard.php` / `lang/en/dashboard.php` — added
  `common.name`, `common.title`, `faqs.question_column`,
  `testimonials.quote_column` (locale-neutral); renamed
  `faqs.question_ar_column` → `question_column` and
  `testimonials.quote_ar_column` → `quote_column` in place (both had no
  other usages). Verified key-for-key parity between the two files
  (326 keys each, zero missing either direction).
- `resources/views/dashboard/services/index.blade.php`,
  `countries/index.blade.php`, `faqs/index.blade.php`,
  `testimonials/index.blade.php`, `articles/index.blade.php`,
  `pages/index.blade.php`, `pages/sections/index.blade.php` — column
  header key swaps (issue #3).
- `tests/Feature/Dashboard/DashboardLocaleTest.php` — 2 new tests:
  `test_dashboard_html_dir_is_rtl_for_arabic_and_ltr_for_english_admins`,
  `test_top_bar_controls_use_logical_end_margin_not_a_fragile_justify_between`
  (the latter is a regression guard for the exact root cause found in
  Part A — asserts `ms-auto` is present and `justify-between` is gone
  from the `<header>`).

### Created then deleted (audit tooling, not a permanent test)
- `tests/Feature/BilingualAuditDumpTest.php` — used once to generate
  the 92 HTML dumps this audit is based on, then deleted per the
  task's framing (this was audit infrastructure, not a regression
  test — the actual regression protection lives in the 2 tests added
  to `DashboardLocaleTest.php` above).

### Verification
- `php artisan test` — **261 tests / 777 assertions, all passing** (was
  259/771 before this task; +2 new).
- `php artisan test --filter=Dashboard` — **145 tests / 393 assertions,
  all passing**.
- `php artisan test --filter=Locale` — **24 tests / 70 assertions, all
  passing**.
- `npm run build` — clean (58 modules, no errors).

### Honesty note on what was and wasn't visually verified
This environment has no browser or screenshot tooling. The top-bar
mirroring fix (Part A) is verified by: understanding exactly why the
old code broke (traced to the CSS flexbox single-item collapse
behavior), a regression test confirming the new markup, and the fact
that the same `ms-auto`/logical-property pattern is already used
correctly elsewhere in this codebase. It has **not** been visually
confirmed pixel-by-pixel in an actual browser — see the spot-check
steps below, item 1, before considering this fully closed.

### How to verify this yourself
1. **Top bar mirroring** — log in, toggle to English. The toggle
   button + your avatar should now sit on the right edge of the top
   bar (sidebar on the left). Toggle back to Arabic — both should sit
   on the left (sidebar back on the right). This is the one item not
   yet confirmed in an actual browser — check it first.
2. **Services index column header** — visit Services in English: the
   first column should say "Name" (not "Name (Arabic)"), and the row
   underneath should show the service's actual English name. Toggle to
   Arabic — column says "الاسم", row shows the Arabic name.
3. **Dashboard browser tab title** — toggle to English, check the
   browser tab / `<title>` tag and the sidebar logo's alt text — should
   read "Bawabat Taasees Al Sharikat", not the Arabic company name.
4. **Public English content pages** — visit `/en/why-invest` and
   `/en/formation-process` — every section card should be full English
   with zero Arabic leftover text.
5. Read `docs/testing/bilingual-audit-2026-08-05.md` for the complete
   route-by-route record, including the two items marked "does not
   exist" (Privacy Policy, Terms and Conditions — no such routes exist
   anywhere in this project, confirmed via `route:list`; not built, as
   that would be a new feature, not a translation fix).

## 2026-08-05 — URGENT FIX — dashboard sidebar visibility regression from RTL/LTR task, root cause and fix

### Symptom
`/dashboard` at desktop width showed the top bar and content area
correctly, but the sidebar area was blank white space — the sidebar
was rendering completely off-screen.

### Root cause (confirmed empirically, not guessed)
The previous task's RTL/LTR mirroring fix changed the sidebar's Alpine
`:class` binding to:
```
:class="sidebarOpen ? 'translate-x-0' : 'rtl:translate-x-full ltr:-translate-x-full'"
```
with **no breakpoint qualification** on the closed-state classes,
while the sidebar's static `class` attribute separately carries
`lg:translate-x-0` (meant to force the sidebar visible at desktop
widths regardless of `sidebarOpen`).

Both `.rtl\:translate-x-full` and `.lg\:translate-x-0` target the same
CSS property (`transform`) and, after compiling, turned out to have
**equal specificity** — Tailwind wraps the `[dir="rtl"]` condition in
`:where(...)`, which by CSS spec always contributes zero specificity,
so `.rtl\:translate-x-full:where([dir=rtl],[dir=rtl] *)` has the exact
same specificity (0,1,0) as `.lg\:translate-x-0`. With specificity
tied, the browser falls back to **source order** — and I confirmed by
directly inspecting the compiled `public/build/assets/app-*.css` that
`.rtl\:translate-x-full` (no `@media` wrapper — always active) is
emitted **after** `.lg\:translate-x-0` (wrapped in
`@media (min-width:1024px)`) in Tailwind's default variant-ordering.
So at desktop width, with `dir="rtl"` (the app's default locale is
`ar`), both rules matched the element and the later one
(`.rtl\:translate-x-full`) won the cascade — permanently translating
the sidebar 100% off-screen even though the viewport was well above
the `lg` breakpoint. This is why the bug was universally reproducible:
every dashboard visit starts in Arabic.

### Fix
Scoped the closed-state RTL/LTR classes to `max-lg:`:
```
:class="sidebarOpen ? 'translate-x-0' : 'max-lg:rtl:translate-x-full max-lg:ltr:-translate-x-full'"
```
`resources/views/components/dashboard/sidebar.blade.php`. This
compiles to `.max-lg\:rtl\:translate-x-full` /
`.max-lg\:ltr\:-translate-x-full`, both wrapped in
`@media not all and (min-width:1024px)` — confirmed by re-inspecting
the rebuilt CSS. That media query and `lg:translate-x-0`'s
`@media (min-width:1024px)` are **mutually exclusive by construction**,
so there is no longer any specificity/cascade-order question to get
wrong: below 1024px only the direction-aware slide applies, at
1024px and above only `lg:translate-x-0` applies. The RTL/LTR
mirroring behavior itself (which direction the drawer slides off to
below `lg`) is fully preserved — only the missing breakpoint scoping
was fixed.

### Modified
- `resources/views/components/dashboard/sidebar.blade.php` — one line,
  the `:class` binding described above.
- `tests/Feature/Dashboard/DashboardLocaleTest.php` — added
  `test_sidebar_closed_state_transform_is_scoped_below_the_lg_breakpoint`,
  a regression guard asserting the exact `max-lg:`-scoped class string
  is present and `lg:translate-x-0` is still there.

### Verification
- `php artisan test` — **262 tests / 779 assertions, all passing** (was
  261/777 before this fix; +1 new test).
- `php artisan test --filter=Dashboard` — **146 tests / 395 assertions,
  all passing**.
- `npm run build` — clean; verified the fix by directly reading the
  compiled CSS's `@media` boundaries for
  `.max-lg\:rtl\:translate-x-full`, `.max-lg\:ltr\:-translate-x-full`,
  and `.lg\:translate-x-0` (excerpts above) — this is what makes this a
  confirmed fix rather than a guess-and-hope.
- **Honesty note**: as with the previous task, no browser is available
  in this environment, so the actual pixel-level rendering at 375px/
  768px/1280px was not visually confirmed — the fix is verified by
  tracing the exact CSS cascade mechanism that broke it and confirming
  the compiled output no longer has any overlapping media-query
  condition between the two competing rules. Please do the manual
  checks below before considering this fully closed.

### How to verify this yourself
1. **Desktop (1280px+)**: load `/dashboard` in Arabic (the default) —
   the dark-green sidebar should be visible immediately on the right
   side, no hamburger icon shown, no click needed. Toggle to English —
   sidebar should now sit on the left, still permanently visible.
2. **Tablet (768px)**: resize the browser (or use dev tools device
   emulation) — sidebar should now be off-canvas by default (hidden),
   with a hamburger icon in the top bar. Click it — sidebar slides in
   from the right in Arabic, left in English. Click the backdrop or
   the X — it slides back out.
3. **Mobile (375px)**: same behavior as tablet — off-canvas by default,
   hamburger toggles it, correct side per locale.
4. Open the browser console at all three widths — no Alpine.js errors
   or warnings.

## 2026-08-05 — Login page background video added (IMG_3416 compressed to login-bg.mp4/.webm)

### Source video
`ffprobe public/videos/IMG_3416.mp4`: H.264 + AAC, 1280×720, 30fps,
~10.0s, overall bitrate ~1.95 Mbps, **2,444,614 bytes** (~2.33 MB).

### Processing (ffmpeg available, same exact pattern as the homepage
hero video — commands reused verbatim from that task's TASKS.md entry
for consistency):
```
ffmpeg -i IMG_3416.mp4 -vf "scale='min(1920,iw)':-2" -c:v libx264 -crf 23 -preset slow -an -movflags +faststart login-bg.mp4
ffmpeg -i IMG_3416.mp4 -vf "scale='min(1920,iw)':-2" -c:v libvpx-vp9 -crf 32 -b:v 0 -an login-bg.webm
ffmpeg -i IMG_3416.mp4 -ss 00:00:01 -vframes 1 login-poster.jpg
```
Source was already 1280×720 (below the 1920px cap), so the scale
filter correctly did not upscale it — both outputs stayed 1280×720.

**Before/after size**:
- `login-bg.mp4` — **1,987,752 bytes** (~1.90 MB), H.264, no audio
  track — **18.7% smaller** than the source despite it being a re-encode
  at a similar visual bitrate, purely from dropping the AAC audio track
  (source had audio; background videos are always muted, so it was
  stripped, matching the hero video's `-an`).
- `login-bg.webm` — **1,554,161 bytes** (~1.48 MB), VP9, no audio —
  **36.4% smaller** than the source.
- `login-poster.jpg` — 52,705 bytes, 1280×720 JPEG, extracted at the
  1-second mark.

Both compressed outputs were decode-verified end-to-end
(`ffmpeg -v error -i ... -f null -`, exit code 0 for both) before the
source file was deleted, matching the same integrity check used for
the hero video.

### Modified
- `resources/views/layouts/guest.blade.php` — added the video as a
  `position: absolute; inset-0; object-cover` full-viewport background
  (`autoplay muted loop playsinline`, `poster`, `.webm` source before
  `.mp4`, matching `resources/views/public/home.blade.php`'s hero
  exactly), a `bg-dark-green/70` overlay (the same brand token/opacity
  the homepage hero uses) between the video and the content, and
  restructured the centered wrapper so the logo + login card sit in a
  `position: relative; z-10` layer above the overlay. The login card
  itself keeps its white background, now with `shadow-lg` (up from
  `shadow-md`) to read clearly against the video/overlay per the
  "elegant cards, soft shadow" brand direction.

  While inspecting this file (per behavior rule 1) for the "renders
  correctly in both locales — logo, direction, any text" check this
  task explicitly asked for, found and fixed two small pre-existing
  inconsistencies directly relevant to that check, not new scope:
  `dir="rtl"` was hardcoded (now
  `{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}`, matching
  `layouts/app.blade.php`'s pattern) and `<title>` used
  `config('app.name')` (the raw Arabic company name; now
  `__('site.brand.name')`, locale-aware). These have **zero visual
  effect** on `/login`, `/forgot-password`, `/reset-password` — those
  three are pre-authentication, so `app()->getLocale()` is always the
  app default (`ar`) there, exactly as before. They matter for the
  other two screens that share this same layout post-login —
  `confirm-password` and `verify-email` — which **do** carry the
  `dashboardlocale` middleware (added in an earlier task) and can
  render in English for an English-locale admin; previously those two
  screens would have shown English text inside a `dir="rtl"` container
  with an Arabic `<title>`, a mismatch. Now consistent in both cases.
- `resources/views/components/application-logo.blade.php` — `alt`
  attribute changed from `config('app.name')` to
  `__('site.brand.name')`, same reasoning, only used on this layout.

### Created
- `public/videos/login-bg.mp4`, `public/videos/login-bg.webm`,
  `public/images/login-poster.jpg`.

### Deleted
- `public/videos/IMG_3416.mp4` — only after both compressed outputs
  were decode-verified.

### Confirmed untouched
- `resources/views/public/home.blade.php` and
  `public/videos/hero-bg.{mp4,webm}` / `public/images/hero-poster.jpg`
  — not read for content, not modified; grepped the rebuilt homepage
  HTML and confirmed it still references `hero-bg`, not `login-bg`.
- `resources/views/layouts/app.blade.php` and
  `resources/views/components/dashboard/sidebar.blade.php` — not
  touched; confirmed the sidebar-visibility fix
  (`max-lg:rtl:translate-x-full max-lg:ltr:-translate-x-full`) and the
  top-bar mirroring fix (`ms-auto flex items-center gap-4`) from the
  two immediately preceding tasks are both still present verbatim in
  the source files.

### Verification
- `php artisan test --filter=Auth` — **22 tests / 53 assertions, all
  passing** (login, logout, email verification, password confirmation,
  password reset, password update, dashboard auth — none of this
  task's changes touch authentication logic, only the surrounding
  visual layer).
- `php artisan test` (full suite) — **262 tests / 779 assertions, all
  passing** — identical count to before this task, confirming zero
  regressions anywhere.
- `npm run build` — clean.
- Manual `curl` against a running `php artisan serve`: confirmed
  `/login` renders the `<video>` tag with correct `poster`/source
  order/attributes; confirmed `/forgot-password` (same guest layout)
  also gets the video; confirmed the homepage still only references
  `hero-bg`, never `login-bg`.

### Honesty note
As with the two preceding tasks, no browser is available in this
environment, so the actual video playback, overlay contrast, and card
legibility were not visually confirmed pixel-by-pixel — verified
instead by: decode-integrity-checking both compressed files, exact
markup-pattern reuse from the already-shipped, presumably-reviewed
homepage hero, and confirming via `curl` that the correct tags/
attributes/file paths are actually present in the rendered HTML. A
real-browser check (autoplay actually starts, overlay reads well
against the specific footage in this clip, mobile `object-cover`
framing looks intentional and not awkwardly cropped) is recommended
before calling this fully done.

### How to verify this yourself
1. Visit `/login` — the background should be a looping, muted video
   with a dark green tint over it, and a white login card clearly
   readable in the center.
2. Visit `/forgot-password` — same background video (shared layout).
3. Resize to mobile width (~375px) — video should fill the viewport
   without stretching/distortion (`object-cover`), card still centered
   and readable.
4. Right-click the video → confirm it's playing (or check dev tools
   Network tab for `login-bg.webm`/`.mp4`, not `IMG_3416.mp4`, which no
   longer exists).
5. Open `/` (homepage) in another tab — confirm the existing hero video
   there is completely unaffected.
6. Log in, then (if email verification/password-confirmation screens
   are reachable in your flow) toggle your dashboard locale to English
   first, then visit one of those — confirm the page now reads
   left-to-right with the correct `dir="ltr"`, unlike before this
   task.

## 2026-08-05 — Real WhatsApp icon site-wide + homepage Countries/About/FAQ preview sections added

### Part A — Real WhatsApp icon

**Icon source**: the `simple-icons` npm package (v16.28.0,
https://www.npmjs.com/package/simple-icons — a well-maintained,
widely-used open-source brand-icon library), installed as a
`devDependency` (`npm install --save-dev simple-icons`, build-time
source of truth only, nothing shipped to the browser beyond the
compiled inline SVG). Path data copied from
`node_modules/simple-icons/icons/whatsapp.svg` and verified
**byte-for-byte identical** against the installed package file via a
script comparison before use — not hand-drawn or recalled from memory.

**Every usage found** (searched the whole codebase for "whatsapp",
confirmed 3 actual icon-bearing spots, not just text mentions):
1. `resources/views/components/whatsapp-float-button.blade.php` — the
   site-wide floating button. Previously a placeholder rect+triangle
   speech-bubble (deliberately simple shapes, explicitly documented at
   the time as "no risk of a mis-recalled path" — superseded now by an
   actually-verified real path).
2. `resources/views/public/home.blade.php` hero CTA button — had a
   `gap-2` class already reserved for an icon, but no icon element
   existed yet.
3. `resources/views/public/home.blade.php` final-CTA-band button — same
   as #2, a second identical button.

`resources/views/public/contact.blade.php` and
`resources/views/public/consultation.blade.php` only contain the text
label "WhatsApp" (a contact-info value and a form field label,
respectively) — no icon graphic there to replace, confirmed by reading
both files.

### Created
- `resources/views/components/icons/whatsapp.blade.php` — the single
  shared component (`<x-icons.whatsapp class="h-X w-X" />`), all 3
  usages above now reference it instead of inline/duplicated SVG.
  `fill="currentColor"`, so it inherits color from its parent exactly
  like the old placeholder did (white on the green circular button).

### Modified
- `resources/views/components/whatsapp-float-button.blade.php` —
  inline placeholder SVG replaced with `<x-icons.whatsapp>`.
- `resources/views/public/home.blade.php` — icon added to both
  WhatsApp CTA buttons (hero + final CTA band).
- `package.json` / `package-lock.json` — `simple-icons` added as a
  devDependency.

### Part B — Homepage: 3 new preview sections

**Data availability, checked honestly before building anything** (per
behavior rule 7 — reporting the real dev database state rather than
assuming or faking content):
- **Countries**: 1 active record exists (مصر/Egypt) — real, not
  fabricated for this task. It has **no English name** set yet, so on
  `/en` this card correctly falls back to showing "مصر" (the existing,
  already-tested Translatable fallback behavior, not a bug) — worth
  the client adding an English name via the dashboard when convenient.
  It also has no flag image, so it uses the same 🌍 fallback the full
  `/countries` page already uses for flagless records.
- **FAQs**: **zero active FAQs exist** in the real database right now.
  The new FAQ section is fully built and will appear automatically the
  moment real FAQs are added via the dashboard, but **it does not
  render on the live site today** — confirmed by curling the real dev
  database (0 matches for the section heading). This is the honest
  state, not a claim that FAQs are showing.
- **About**: real, already-translated content exists (from an earlier
  translation task) — the teaser reuses the About page's own first
  `<p>` paragraph as-is (via a new `HomeController::firstParagraph()`
  helper), rather than duplicate-authoring separate homepage copy or
  crudely truncating mid-sentence by character count.

**Placement** (Hero first, Final CTA last, as required; everything
else inserted so no two adjacent sections share the same background
tone, without changing any existing section's classes):
1. Hero (unchanged)
2. **NEW: About teaser** — plain background, photo (reuses
   `about-team-meeting.jpg` from the earlier About Media task) +
   excerpt + "read more" link to `/about`.
3. Services preview (unchanged)
4. Why-Invest highlights (unchanged)
5. Formation-Process preview (unchanged)
6. Testimonials (unchanged)
7. **NEW: Countries teaser** — soft-gray background, compact
   flag+name card grid, links to `/countries`.
8. **NEW: FAQ teaser** — plain background, first 4 active FAQs
   (question + answer), links to `/faqs`.
9. Latest Articles (unchanged)
10. Final CTA band (unchanged)

All 3 new sections follow the exact same empty-state rule as every
existing homepage section — wrapped in `@if ($collection->isNotEmpty())`
/ `@if ($excerpt)`, hiding completely rather than rendering a
broken/empty block.

### Modified
- `app/Http/Controllers/Public/HomeController.php` — added
  `aboutPage`, `aboutExcerpt`, `homeCountries` (`Country::active()`,
  take 8), `homeFaqs` (`Faq::active()`, take 4) to the view data, plus
  the new `firstParagraph()` helper.
- `lang/ar/site.php` / `lang/en/site.php` — added `home.about_*`,
  `home.countries_*`, `home.faqs_*` keys (9 new keys each side,
  verified paired — no new mismatches introduced; one pre-existing,
  unrelated `brand.tagline` EN-only key was already there before this
  task and was left alone).

### Not touched (confirmed)
- The homepage Hero video/section, `public/videos/hero-bg.*`, the
  login page video, and the entire `resources/views/layouts/app.blade.php`
  / dashboard sidebar were not read for content and not modified.
- No existing homepage section (Services, Why-Invest, Formation-Process,
  Testimonials, Articles, Final CTA) was rebuilt, restyled, or
  duplicated — all their Blade markup is byte-for-byte what it was
  before this task; only new sections were inserted around them.

### Verification
- `php artisan test --filter=HomeTest` — **29 tests / 103 assertions,
  all passing** (was 25 tests before this task; +4 new: About teaser,
  Countries teaser, FAQ teaser, and a WhatsApp-icon regression test
  asserting the real path string is present and the old placeholder
  rect is gone).
- `php artisan test` (full suite) — **266 tests / 796 assertions, all
  passing** — zero regressions.
- `npm run build` — clean.
- Manual `curl` against a running server, against the **real dev
  database**: confirmed the About teaser and Countries teaser
  (showing the real "مصر" record) both render; confirmed the FAQ
  section correctly does NOT render (0 active FAQs); confirmed the
  WhatsApp path string appears exactly 3 times and the old placeholder
  rect 0 times; confirmed the English homepage (`/en`) shows the real
  English About excerpt and the English section headings, with the
  Country name correctly falling back to Arabic (no English
  translation on that one record yet, as noted above).

### How to verify this yourself
1. **WhatsApp icon**: visit any public page, look at the floating
   green button in the bottom-left corner — should show the real
   WhatsApp phone-in-a-speech-bubble mark, not a plain rectangle. Visit
   `/` and look at the two green "Chat on WhatsApp" buttons (hero and
   the dark final-CTA band near the bottom) — same real icon should
   appear to the start-side of the text on both.
2. **Homepage new sections (Arabic)**: visit `/` and scroll — right
   after the hero you should see an About teaser with a real office
   photo and a "read more" link to `/about`. Continue scrolling past
   Testimonials — you should see a "الدول التي ندعمها" strip showing
   at least the one real country (مصر) with a 🌍 placeholder (no flag
   image yet), linking to `/countries`. The FAQ section will **not**
   appear (zero FAQs currently exist — add one via the dashboard to
   see it render).
3. **English (`/en`)**: repeat the same scroll — About teaser and
   Countries section headings should be in English; the country card
   itself will still show "مصر" until an English name is added for it
   in the dashboard (this is the existing, correct fallback behavior,
   not a new bug).
4. Confirm `/services`, `/countries`, `/faqs`, `/about` (the full
   pages these teasers link to) still work exactly as before — nothing
   on those pages was touched.

## 2026-08-05 — Homepage Contact Us section added, reusing existing contact form logic — no duplicated validation/attribution code

### Inspection (before building anything)
Confirmed `/contact` is fully built and working: `ContactController`
(create/store), `StoreContactRequest` (validation), honeypot
(`website_url`), `throttle:5,1` rate limiting already on the POST
route, `AttributionService` resolving UTM/first-touch/latest-touch into
the `leads` table with `type='contact'` — all pre-existing from the
earlier Leads/Attribution task, confirmed via
`tests/Feature/Public/ContactFormTest.php` (7 passing tests) before
touching anything. Nothing needed to be built from scratch — this task
was purely: extract the existing form into something reusable, then
reuse it twice.

**Found one real, pre-existing, untested gap while inspecting**:
`ContactController::store()` always redirected to `route('contact')` —
the plain Arabic route, never the `.en` variant — regardless of the
visitor's locale. Every existing test only ever asserted against the
Arabic route, so this had never been exercised. Fixed as part of this
task (see below) since the same redirect-target logic needed touching
anyway to support the new "redirect home vs redirect to /contact"
requirement.

### Created
- `resources/views/components/contact-form.blade.php` — the entire
  form (honeypot, hidden attribution-snapshot fields, all inputs,
  validation error display, submit button, success banner + Meta Pixel
  tracking script, and the JS that populates the attribution hidden
  fields from `window.BtsAttribution`), extracted verbatim from the
  old `/contact` view with one addition: a `redirectTo` prop (default
  `'contact'`) rendered as a hidden `redirect_to` field, read by
  `ContactController::redirectTarget()` to decide where a successful
  submit sends the visitor back to. This is the single source of truth
  now — both `/contact` and the new homepage section render this exact
  component, same `<form action>` (`lroute('contact.store')`), so
  there is no duplicated markup or logic anywhere.

### Modified
- `resources/views/public/contact.blade.php` — the entire form block
  replaced with `<x-contact-form redirect-to="contact" />`. The
  surrounding heading and contact-info card (reading from `Setting`)
  are unchanged.
- `resources/views/public/home.blade.php` — new Contact section
  (`id="contact"`, plain background — contrasts both the Articles
  section before it and the dark Final CTA band after it, matching the
  established alternation pattern from the previous homepage task, no
  existing section's classes touched), placed directly before the
  Final CTA band as instructed. Same heading/subheading/info-card
  layout as the standalone page (reusing the exact same `site.contact.*`
  lang keys — no new lang keys needed), embedding
  `<x-contact-form redirect-to="home" />`.
- `app/Http/Controllers/Public/ContactController.php` — `store()` now
  computes a `$redirectTarget` once via a new private
  `redirectTarget()` method instead of hardcoding `route('contact')`
  in two places:
  ```php
  private function redirectTarget(StoreContactRequest $request): string
  {
      if ($request->input('redirect_to') === 'home') {
          return lroute('home').'#contact';
      }
      return lroute('contact');
  }
  ```
  Both branches (honeypot short-circuit and the real success path) use
  this. `redirect_to` is never used to build an arbitrary URL — only
  compared against the one literal string `'home'` — so there is no
  open-redirect surface regardless of what a client sends. Using
  `lroute()` for both targets also fixes the pre-existing English-locale
  redirect gap described above, for both `/contact` and the new
  homepage section.
- `app/Http/Controllers/Public/HomeController.php` — added
  `contactPhone`, `contactWhatsapp`, `contactEmail`, `contactAddress`
  to the view data, using the exact same `Setting::where('key', ...)`
  queries `ContactController::create()` already uses — same source of
  truth, not a second copy.

### Not touched (confirmed)
- `StoreContactRequest` — zero changes; `redirect_to` is intentionally
  not added to its validation rules (same treatment as the `website_url`
  honeypot — an internal field, not user-facing content).
- `AttributionService`, the `leads` table/migration, rate limiting
  (`throttle:5,1` on the shared route) — untouched, and proven to still
  apply identically regardless of entry point (see tests below).
- No other homepage section, the hero video, the login video, or the
  dashboard were read for content or modified.

### Verification
- `php artisan test --filter=ContactFormTest` — **14 tests / 58
  assertions, all passing** (was 7 before this task; +7 new):
  homepage shows the real form; homepage submission creates a
  `type='contact'` lead and redirects to `route('home').'#contact'`;
  submitting with no `redirect_to` still defaults to `/contact`
  (regression guard for the standalone page); honeypot from the
  homepage still redirects to the home anchor with zero leads created;
  attribution snapshots populate correctly when submitted from the
  homepage; and two new tests proving the English-locale redirect fix
  — `contact.store.en` now redirects to `route('contact.en')` /
  `route('home.en').'#contact'`, not the Arabic URLs.
- `php artisan test` (full suite) — **273 tests / 820 assertions, all
  passing** — zero regressions anywhere, including the original 7
  `/contact` tests (unchanged assertions, still passing against the
  refactored controller).
- `npm run build` — clean.
- **Live manual verification against the real dev database** (not just
  the test suite): started `php artisan serve`, fetched the real
  homepage, extracted the live CSRF token from the form's hidden
  `_token` field, POSTed a real submission with `redirect_to=home` —
  got back `HTTP 302` with `Location: http://127.0.0.1:8153#contact`,
  exactly as designed. Confirmed via `tinker` that a real `Lead` row
  was created: `full_name="Test Homepage Lead"`, `type="contact"`,
  `consent_given=true`. **This is a genuine test lead now sitting in
  the real leads table** — visible in the dashboard Leads list like
  any other submission; archive/delete it from there if you don't want
  it kept.

### How to verify this yourself
1. Visit `/` and scroll down — right before the final dark green CTA
   band, you should see a "تواصل معنا" section with the same contact
   info card and form as `/contact`.
2. Submit a real test message from that section — you should land back
   on the homepage (URL ending in `#contact`) with the same green
   success banner shown on `/contact`.
3. Log into the dashboard → Leads — the submission should appear there
   with type "طلب تواصل"/contact, identical in every way to a
   submission made from `/contact` directly (including UTM/attribution
   columns if you arrived via a tracked link).
4. Visit `/contact` directly and submit — confirm it still redirects
   back to `/contact` itself (not the homepage), exactly as before this
   task.
5. Switch to English (`/en`), scroll to the Contact section, submit —
   confirm you land back on `/en#contact`, not the Arabic homepage.
6. Try the honeypot: submit via a raw HTTP client with `website_url`
   filled in — confirm no lead is created but the success message still
   shows (matches the existing anti-spam behavior, now proven identical
   from both entry points by the new tests).

## 2026-08-05 — Full responsive QA and fix pass completed — docs/testing/responsive-audit-2026-08-05.md

**Summary: 9 real issues found and fixed across 17 files** — dashboard
table clipping (9 files), iOS-zoom-risk textareas (2 files), a
homepage text-overflow risk, undersized carousel touch targets, a
WhatsApp-button/footer overlap, a honeypot-positioning technique that
could expand page scroll width (3 files), a dashboard filter-grid
squeeze at the sidebar's own breakpoint, and a leads-detail overflow
risk. Full page×breakpoint checklist, methodology (no browser
available — static/structural analysis against Tailwind's actual
compiled CSS, honestly disclosed), and spot-check guidance:
`docs/testing/responsive-audit-2026-08-05.md`.

### Methodology (see the audit doc for full detail)
No browser or screenshot tool is available in this environment. Every
page's Blade source was read in full and traced against Tailwind's
confirmed default breakpoints (640/768/1024/1280/1536px — verified via
`tailwind.config.js` having no override) for all 6 requested widths.
Anything safety-critical was verified empirically against the actual
compiled CSS rather than assumed — e.g. confirmed Tailwind's Preflight
already applies `img,video{max-width:100%;height:auto}` globally
(ruling out an article-body-image overflow concern without needing a
fix), and confirmed the exact computed rule for `sr-only` before using
it to fix a real overflow bug.

### Modified — dashboard table clipping → horizontal scroll (9 files)
`overflow-hidden` on the table's rounded-corner wrapper was clipping
(not scrolling) wide tables on narrow screens. Added an inner
`<div class="overflow-x-auto">` around each `<table>`:
`dashboard/services/index.blade.php`, `dashboard/countries/index.blade.php`,
`dashboard/faqs/index.blade.php`, `dashboard/testimonials/index.blade.php`,
`dashboard/articles/index.blade.php`, `dashboard/pages/index.blade.php`,
`dashboard/pages/sections/index.blade.php`, `dashboard/leads/index.blade.php`,
`dashboard/comments/index.blade.php`.

### Modified — iOS zoom-on-focus risk (2 files)
`font-mono text-sm` (14px) on real text-entry `<textarea>` fields
triggers iOS Safari's auto-zoom. Changed to `text-base sm:text-sm` (16px
on touch widths, compact again on desktop `sm:`+) in
`dashboard/articles/_form.blade.php` and `dashboard/pages/edit.blade.php`
(4 textareas total: body_ar/body_en in each).

### Modified — homepage (2 fixes, 1 file)
`resources/views/public/home.blade.php`:
- Countries teaser: added `min-w-0` to the card, `truncate` to the
  country-name span — a long name could otherwise force the 2-column
  mobile grid wider than the viewport.
- Testimonial carousel dots: were exactly 8×8px with zero padding
  (unusable touch target). Kept the visible dot at 8×8px on an inner
  `<span>`, added `-m-2 p-2` to the outer `<button>` for a ~24×24px tap
  area with no visual change.

### Modified — WhatsApp button / footer overlap (1 file)
`resources/views/layouts/public.blade.php` — the fixed WhatsApp button
sits over whatever is at the true bottom of the viewport once scrolled
to the end of any page; the footer's copyright text (the last,
bottom-left content on every public page) had no clearance for it.
`py-12` → `pt-12 pb-24` on the footer's inner wrapper.

### Modified — honeypot overflow risk (3 files)
`absolute -left-[9999px]` positions relative to the viewport when no
ancestor is positioned (true here), which can silently expand the
page's scrollable width and produce a horizontal scrollbar site-wide.
Switched to Tailwind's `sr-only` utility (clips to 1×1px, confirmed via
compiled CSS — can never expand document width) in
`resources/views/components/contact-form.blade.php`,
`resources/views/public/consultation.blade.php`, and
`resources/views/public/articles/show.blade.php` (comment form).
`aria-hidden="true"` and the field name/detection logic are unchanged —
confirmed by every honeypot test still passing.

### Modified — dashboard Leads (2 fixes, 2 files)
- `dashboard/leads/index.blade.php` — the 5-field filter form jumped
  straight from 2 to 5 columns at exactly `lg` (1024px), the same
  breakpoint where the sidebar becomes permanently visible and takes
  288px from the content area, squeezing each field to ~130px.
  `lg:grid-cols-5` → `lg:grid-cols-3 xl:grid-cols-5` (and the matching
  button-row `col-span`).
- `dashboard/leads/show.blade.php` — label/value rows in the
  customer-data and source-data lists had no `min-w-0`/`shrink-0`; a
  long value (e.g. a long email) risked the same overflow class as the
  Countries teaser fix. Added `shrink-0` to labels, `min-w-0
  break-words` (customer data) / `min-w-0 break-all` (source data,
  already had `break-all`) to values.

### Confirmed NOT a bug (checked, ruled out)
Article-body embedded images (`{!! $page->body !!}`) have no explicit
`max-width` in the `.article-body img` component rule, but Tailwind's
Preflight base layer already applies `max-width:100%;height:auto` to
every `img`/`video` globally — confirmed in the compiled CSS. No fix
needed.

### Confirmed unchanged / not touched
- Privacy Policy and Terms and Conditions — **do not exist anywhere in
  this project**, confirmed via `route:list`. Reported honestly per
  the task's own instructions rather than built (would be a new
  feature, out of scope for a responsive-fix task).
- The RTL/LTR mirroring fix and the sidebar-visibility fix from the
  two immediately preceding tasks — re-verified present and correct in
  their source files, not rebuilt.
- The hero video and login video — not touched; both already used
  `object-cover` correctly from their respective earlier tasks.
- No content, routes, or business logic changed anywhere — every
  change in this task is a CSS class or a wrapping `<div>`.

### Verification
- `php artisan test` — **273 tests / 820 assertions, all passing** —
  identical count to before this task, confirming zero functional
  regressions from any of the 17 markup changes.
- `npm run build` — clean.

### Honesty note
This was a structural/static audit — every page was read in full and
traced against Tailwind's real, confirmed breakpoint values and
(where relevant) the actual compiled CSS, not assumed or guessed. It
is **not** a substitute for a real browser check, which was not
possible in this environment. The audit doc's "How to spot-check this
yourself" section names the 5 specific combinations most worth a real
look — prioritize those first.

## 2026-08-05 — Honeypot accessibility fixed (sr-only replaced with proper aria-hidden pattern), Privacy Policy + Terms and Conditions pages built and seeded

### Part A — Honeypot accessibility fix

**Root problem**: the previous responsive-fix task replaced an
overflow-risky `absolute -left-[9999px]` honeypot hiding technique with
Tailwind's `sr-only` utility. `sr-only`'s entire designed purpose is
the opposite of what's needed here — it keeps content **visible to
screen readers** while hiding it visually (that's what "screen-reader
only" means), whereas a honeypot must be invisible to every real human,
sighted or using assistive technology, while staying present in the DOM
for bots that blanket-fill form fields.

**Every honeypot field found** (searched the whole codebase for
`website_url`, confirmed exactly 3 real usages — a 4th `sr-only` match
in `profile/partials/delete-user-form.blade.php` is an unrelated,
correct usage — a Breeze password label meant to stay screen-reader-
visible — and was left untouched):
1. `resources/views/components/contact-form.blade.php` (used by both
   `/contact` and the homepage Contact section)
2. `resources/views/public/consultation.blade.php`
3. `resources/views/public/articles/show.blade.php` (comment form)

**Fix**: replaced `class="sr-only"` with
`class="absolute h-px w-px overflow-hidden opacity-0 pointer-events-none"`
on all 3, keeping `aria-hidden="true"` and `tabindex="-1"` on the
wrapper and `autocomplete="off"` and `tabindex="-1"` on the `<input>`
unchanged. This hides the field from sighted users (opacity-0, 1×1px)
**and** screen readers (`aria-hidden`, and no `sr-only` fighting it),
and — same as the previous task's fix — never risks page overflow: no
`absolute` offset is set, so the element stays at its normal in-flow
position (inside the form, inside the page's max-width container)
rather than escaping to the viewport edge, and `overflow-hidden` clips
it to 1×1px regardless.

### Modified — Part A
- `resources/views/components/contact-form.blade.php`
- `resources/views/public/consultation.blade.php`
- `resources/views/public/articles/show.blade.php`
- `tests/Feature/Public/ContactFormTest.php`,
  `tests/Feature/Public/ConsultationFormTest.php`,
  `tests/Feature/Public/CommentSubmissionTest.php` — added
  `test_honeypot_is_hidden_from_screen_readers_not_just_visually` to
  each, asserting the exact `aria-hidden="true"`/`tabindex="-1"`
  markup is present and `sr-only` is gone (3 new tests).

### Part B — Privacy Policy and Terms and Conditions pages

**Confirmed absent first** (per behavior rule 1): `php artisan
route:list` had zero matches for `privacy|terms` before this task —
these pages were never actually built, despite being part of the
original page plan.

**Built using the existing Page/PageSection CMS — no new rendering
mechanism**, following the same pattern as About/Why-Invest/Formation-
Process/Requirements:
- `database/seeders/PageContentSeeder.php` — two new idempotent
  `updateOrCreate`-on-slug methods, `seedPrivacyPolicy()` and
  `seedTermsAndConditions()`, both called from `run()`. Unlike the
  four existing pages (seeded Arabic-only, translated to English later
  by a separate command in an earlier task), these two are written
  **directly bilingual** — this project is fully bilingual now, so new
  content is authored in both languages from the start, with no extra
  translate-command step needed after seeding.
- `app/Http/Controllers/Public/PageController.php` — two new thin
  methods, `privacyPolicy()` and `termsAndConditions()`. Both pages
  are intro-only prose with no sections (like About), and — unlike the
  four existing pages, which each have a dedicated view because they
  need genuinely different visual treatment (prose vs. card-grid vs.
  timeline vs. checklist) — Privacy Policy and Terms share the exact
  same simple layout, so both render through one new shared view
  rather than two near-duplicate files.
- `resources/views/public/pages/legal.blade.php` — new shared view
  (dark-green title band + sanitized prose body, same as About's first
  two sections, without About's photo/video sections).
- `routes/web.php` — two new routes inside the existing
  `$registerPublicRoutes` closure, so both get the standard `ar`
  (no prefix) / `en` (`.en` suffix, `/en/` prefix) variants
  automatically, exactly like every other public route.
- `resources/views/layouts/public.blade.php` — footer links to both
  pages, added below the copyright line, using `lroute()` so they
  stay locale-correct.
- `lang/ar/site.php` / `lang/en/site.php` — added `footer.privacy_link`
  / `footer.terms_link` keys (verified paired, no new mismatches; one
  pre-existing unrelated `brand.tagline` EN-only key predates this
  task and was left alone).

**Content**: professional-sounding starter copy, written to be
factually conservative (no unverifiable statistics, no specific legal
claims presented as certain) — covers, for Privacy: what data is
collected (contact-form fields, first-party attribution cookies,
IP address on comments), how it's used, no data sale, limited sharing
with analytics/ad platforms (Google/Meta/TikTok) when enabled, cookie
disclosure, data protection/retention, user rights, and a reference to
Saudi Arabia's Personal Data Protection Law (PDPL) by name only (no
specific-article claims). For Terms: nature of services (consulting,
explicitly **not** formal legal/financial/tax advice), user
responsibilities, service-scope and no-guarantee-of-outcome disclaimer
(government approval processes are outside the company's control),
intellectual property, limitation of liability, Saudi governing
law/jurisdiction, and a changes-to-terms clause.

**⚠️ Explicit flag, per this task's own instruction**: this is
**starter content only** — professionally worded but written by an AI
assistant, not a lawyer. **It must be reviewed by a qualified Saudi
legal professional before being treated as final/relied upon in
production.** This is the same standing caveat already documented in
this seeder for the other four pages' starter copy, called out
explicitly here given the legal (not just marketing) nature of this
specific content — see the seeder's own doc comment for the same
notice in code.

**Also fixed while here**: the dashboard Pages screen's notice text
said "these **four** pages are fixed" — now factually wrong with 6
pages seeded. Reworded to drop the specific count entirely (in both
`lang/ar/dashboard.php` and `lang/en/dashboard.php`, plus a matching
doc-comment update in `app/Http/Controllers/Dashboard/PageController.php`)
rather than hardcoding "six", so it won't go stale again if more fixed
pages are ever added.

### Modified — Part B
- `database/seeders/PageContentSeeder.php`
- `app/Http/Controllers/Public/PageController.php`
- `app/Http/Controllers/Dashboard/PageController.php` (comment only)
- `routes/web.php`
- `resources/views/layouts/public.blade.php`
- `lang/ar/site.php`, `lang/en/site.php`
- `lang/ar/dashboard.php`, `lang/en/dashboard.php` (`fixed_notice` wording)
- `tests/Feature/Public/PagesTest.php` — 6 new tests: Privacy Policy
  200 + bilingual content, Privacy Policy 404 when missing, Terms 200 +
  bilingual content, Terms 404 when missing, footer links present, and
  one test that runs the **real** `PageContentSeeder` (not a hand-built
  fixture) and asserts real seeded phrases appear — proving the actual
  shipped content works, not just the rendering mechanism.

### Created
- `resources/views/public/pages/legal.blade.php`

### Verification
- `php artisan db:seed --class=PageContentSeeder` — real output: seeded
  cleanly, confirmed via `tinker` that all 6 pages now exist:
  `about, why-invest-saudi-arabia, formation-process, required-documents,
  privacy-policy, terms-and-conditions`.
- `php artisan test --filter=Contact` — **17 tests / 64 assertions, all
  passing**.
- `php artisan test --filter=Page` — **81 tests / 243 assertions, all
  passing**.
- `php artisan test` (full suite) — **282 tests / 849 assertions, all
  passing** (was 273/820 before this task; +9 new: 3 honeypot
  accessibility regression tests, 6 legal-page tests).
- `npm run build` — clean.
- Live verification against a running `php artisan serve`: confirmed
  all 4 routes (`/privacy-policy`, `/en/privacy-policy`,
  `/terms-and-conditions`, `/en/terms-and-conditions`) return 200 with
  real bilingual content; confirmed the footer links render with
  correct locale-aware `href`s on both `/` and `/en`; confirmed the
  honeypot's actual rendered HTML on `/contact` shows exactly
  `class="absolute h-px w-px overflow-hidden opacity-0 pointer-events-none" aria-hidden="true" tabindex="-1"`
  with no `sr-only` anywhere.

### How to verify this yourself
1. **Honeypot accessibility**: open dev tools on `/contact`,
   `/consultation`, or any article page, inspect the hidden
   `website_url` field's wrapping `<div>` — confirm it has
   `aria-hidden="true"` and no `sr-only` class. If you have a screen
   reader available, confirm it is never announced while tabbing/
   reading through the form (it also has `tabindex="-1"`, so keyboard
   users skip it entirely).
2. **No overflow reintroduced**: load `/contact` and the homepage,
   confirm no horizontal scrollbar appears at any width — the honeypot
   change was specifically designed to preserve the previous task's
   overflow fix.
3. Visit `/privacy-policy` and `/terms-and-conditions` in Arabic, then
   `/en/privacy-policy` and `/en/terms-and-conditions` in English —
   confirm real, readable content in both languages, not placeholders.
4. Scroll to the footer on any public page — confirm "سياسة الخصوصية" /
   "الشروط والأحكام" (or the English equivalents on `/en`) links are
   present and go to the right pages.
5. Log into the dashboard → Pages — confirm both new pages appear in
   the list alongside the original four, and that editing their
   content there updates the public page (same as the other four).
6. **Read the seeded legal content yourself** before this site goes
   live — it's professional-sounding starter copy, not something
   written or reviewed by a lawyer.

## 2026-08-05 — Full post-sprint health check completed

Full end-to-end re-verification of the entire system from a clean
bootstrap (composer/npm install, `migrate:fresh --seed`, `npm run
build`, full test suite, complete route inventory, all cross-feature
integration checks, security spot-check, housekeeping). **Overall
verdict: the application works correctly as an integrated system —
282/282 tests passing, all 113 routes checked as expected, no code
bugs found, nothing fixed.** Four process/housekeeping gaps were found
and reported (not silently patched): (A) a fresh seed alone does not
produce fully bilingual content — `content:translate-to-english` must
be run separately; (B) the project has only 1 git commit total, so
nearly the entire build history exists only as uncommitted
working-tree changes; (C) an earlier task's manual-verification test
lead was never archived and was only cleared by this task's own
database wipe; (D) no real `ADMIN_EMAIL`/`ADMIN_PASSWORD` are set, so
every fresh seed still uses placeholder dev credentials. Full detail,
evidence, and re-run instructions:
[docs/testing/health-check-2026-08-05.md](docs/testing/health-check-2026-08-05.md).

## 2026-08-08 — All work committed to git (commit c370f55), English translation now automatic during seeding

Closed the two process gaps the health check found.

**Part A — everything committed.** `.gitignore` was reviewed and needed
no changes (it already correctly excludes `.env`, `.env.backup`,
`.env.production`, `/vendor`, `/node_modules`, `/public/build`,
`/public/hot`, `/public/storage`, `.DS_Store`, editor dirs and
`/storage/*.key`). The full staged list was reviewed file-by-file
before committing: 108 files, no `.env`, no `vendor`/`node_modules`,
and no raw uncompressed video originals (the hero/login video cleanup
from the earlier tasks was confirmed to have actually happened — the
five largest staged files are all the intended compressed `.mp4`/
`.webm` site assets at ~1.5–2.1 MB each). Committed as a single commit
**`c370f55`** — 108 files changed, 6,425 insertions, 646 deletions.
Working tree is now clean; `.env` confirmed never tracked in any commit
in the repo's history. **This commit was not pushed** — the branch is
now 1 commit ahead of `origin/main`.

**Part B — bilingual seeding is now one step.**
`database/seeders/DatabaseSeeder.php` now calls
`Artisan::call('content:translate-to-english', [], $this->command?->getOutput())`
after the content seeders, so `php artisan migrate:fresh --seed` alone
produces a fully bilingual site. Verified on a genuinely fresh seed
with no separate translate command: 54 fields translated automatically,
and `/en/about`, `/en/why-invest`, `/en/formation-process`,
`/en/requirements` all return 200 with real English content (h2s read
"Our Expertise", "Aligned with Vision 2030", "Initial Consultation",
"Passport Copy") and **zero** Arabic fallback — the only Arabic left on
an English page is the navbar's "العربية" toggle label, which is
correct. Idempotency confirmed: re-seeding an already-seeded database
skips all 24 already-English Page fields; the 38 PageSection fields are
re-translated only because `PageContentSeeder` deletes and recreates
its sections each run (line 251), so those rows are genuinely new
Arabic-only records — the end state is still fully bilingual either way.
Full suite still **282 passed (849 assertions)**, no regressions,
including `SeedersTest › seeding twice does not create duplicates`.

Setup documentation created:
[docs/setup-instructions.md](docs/setup-instructions.md) — the single
correct sequence is now
`composer install && npm install && php artisan migrate:fresh --seed && npm run build`,
with nothing else required.

**Process recommendation (not enforced in code):** future tasks should
each end with their own commit, so the history stays granular and each
change is individually reviewable and revertable. This task's single
squashed commit was the right call only because the working tree
already had everything mixed together — it should not become the norm.
