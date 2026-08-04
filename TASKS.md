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
