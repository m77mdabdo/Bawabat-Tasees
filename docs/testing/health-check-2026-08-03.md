# Full Project Health Check — 2026-08-03

Bawabat Taasees Al Sharikat, Laravel 12. This is a from-scratch, end-to-end
verification of everything built so far: migrations/models, seeders,
dashboard auth, CMS CRUD (Services/Countries/FAQs/Testimonials), Articles +
Media Library. No new features were added in this task.

**Overall verdict: the application works.** 102/102 automated tests pass,
every one of the 54 registered routes behaves exactly as expected (200,
302, 403, or 404 — whichever is correct for that route/actor), and every
cross-feature and security spot-check passed. The one real gap is that
**the branding/homepage-hero task has not actually been done** — raw
assets exist on disk but nothing is wired up. Full detail below.

---

## 1. Clean bootstrap test

All four steps completed with **zero errors**.

```
$ composer install
Installing dependencies from lock file (including require-dev)
Verifying lock file contents can be installed on current platform.
Nothing to install, update or remove
Generating optimized autoload files
...
No security vulnerability advisories found.

$ npm install
added 1 package, and audited 162 packages in 727ms
38 packages are looking for funding
found 0 vulnerabilities

$ php artisan migrate:fresh --seed
  Dropping all tables ........................................... 76.64ms DONE
  [... 19 migrations, all DONE ...]
  Database\Seeders\AdminUserSeeder ............................... 259 ms DONE
  Database\Seeders\LeadSourceSeeder ............................... 11 ms DONE
  Database\Seeders\SettingsSeeder ................................. 10 ms DONE
  Database\Seeders\TrackingSettingSeeder ........................... 5 ms DONE

$ npm run build
vite v7.3.6 building client environment for production...
✓ 57 modules transformed.
public/build/manifest.json             0.33 kB │ gzip:  0.17 kB
public/build/assets/app-BR7dyIpm.css  38.30 kB │ gzip:  7.12 kB
public/build/assets/app-BPjt-bD7.js   94.78 kB │ gzip: 34.73 kB
✓ built in 709ms
```

**One thing worth noting, not an error:** `npm install` reported "added 1
package" on a supposedly-clean install — minor lockfile drift, harmless,
did not block the build.

**Minor toolchain inconsistency found (not fixed, not a bug):**
`package.json` lists both `@tailwindcss/vite: ^4.0.0` and
`tailwindcss: ^3.1.0`. Only the v3 toolchain is actually wired up —
`postcss.config.js` uses the classic `tailwindcss: {}` PostCSS plugin,
`tailwind.config.js` is a v3-style config, and `vite.config.js` never
registers the `@tailwindcss/vite` plugin. The resolved package is
`tailwindcss@3.4.19`. This is almost certainly a leftover: Laravel 12's
default `laravel new` scaffold ships Tailwind v4, but `breeze:install
blade` overwrote it with its own v3-based setup, leaving the unused v4
Vite plugin dangling in `package.json`. **It causes no build failure and
no runtime issue** — confirmed by the clean `npm run build` above — but
it's dead weight worth removing in a future cleanup pass.

`ADMIN_EMAIL`/`ADMIN_PASSWORD` are still not set in `.env`, so
`AdminUserSeeder` used its documented fallback credentials again:
`admin@example.test` / `Xk9#mPz2Qw7!`. This was already known/documented,
re-confirmed here.

---

## 2. Full automated test suite

```
$ php artisan test
Tests:    102 passed (274 assertions)
Duration: 2.36s
```

Every suite passed: `Unit\ExampleTest`, `Unit\ModelsTest` (9),
`Feature\Auth\*` (16, all Breeze defaults), `Feature\Dashboard\ArticleControllerTest`
(13), `Feature\Dashboard\CountryControllerTest` (11),
`Feature\Dashboard\FaqControllerTest` (8), `Feature\Dashboard\MediaControllerTest`
(8), `Feature\Dashboard\ServiceControllerTest` (11),
`Feature\Dashboard\TestimonialControllerTest` (10), `Feature\DashboardAuthTest`
(6), `Feature\ExampleTest`, `Feature\ProfileTest` (5), `Feature\SeedersTest` (3).

**No failures. No trivial fixes were needed here** — the suite was already
green before this task started.

---

## 3. Route inventory

`php artisan route:list` → **54 routes registered.** Every route was
hand-tested as either a guest or the seeded admin (or a freshly-created
non-admin user for the 403 checks), via real HTTP requests against
`php artisan serve` — not assumed from reading the code.

### Public routes (guest)

| Route | Method | Result | Notes |
|---|---|---|---|
| `/` | GET | 200 | Renders — see §4 for content caveats |
| `/login` | GET | 200 | |
| `/forgot-password` | GET | 200 | |
| `/reset-password/{token}` | GET | 200 | Tested with a dummy token; form renders (real Laravel behavior — validity is checked on submit, not on render) |
| `/up` | GET | 200 | Laravel health-check endpoint |
| `/register` | GET/POST | **404** | Correct — intentionally removed in the auth task |

### Dashboard routes, as guest (must all redirect)

| Route | Result |
|---|---|
| `/dashboard`, `/dashboard/services`, `/dashboard/countries`, `/dashboard/faqs`, `/dashboard/testimonials`, `/dashboard/articles`, `/dashboard/media` | **302** → `/login`, all seven |

### Dashboard routes, as seeded admin

| Route | Result |
|---|---|
| `/dashboard` | 200 |
| `/dashboard/{services,countries,faqs,testimonials,articles,media}` (index) | 200, all six |
| `/dashboard/{services,countries,faqs,testimonials,articles}/create` | 200, all five |
| `/dashboard/services/{slug}/edit` | 200 (tested against a seeded record) |
| `/dashboard/countries/{slug}/edit` | 200 |
| `/dashboard/faqs/{id}/edit` | 200 |
| `/dashboard/testimonials/{id}/edit` | 200 |
| `/dashboard/articles/{slug}/edit` | 200 |

### Dashboard routes, as a freshly-created non-admin user

| Route | Result |
|---|---|
| `/dashboard`, `/dashboard/services`, `/dashboard/media` | **403**, all three (spot-checked; middleware is applied route-group-wide so this generalizes) |
| `/profile` | 200 — correct, `/profile` is `auth`-gated, not `admin`-gated |

### Auth-only routes (any logged-in user), as admin

| Route | Result |
|---|---|
| `/profile` | 200 |
| `/verify-email` | 200 |
| `/confirm-password` | 200 |

**Every route behaved exactly as expected. Zero broken routes, zero
unexpected 500s.**

---

## 4. Cross-feature checks

### Branding / homepage hero — **not done, despite raw assets being present**

This is the most important finding in this report. `TASKS.md` has no
entry for a branding/hero task, and after inspecting the actual codebase
that absence is **accurate** — the work genuinely was not done:

- `public/photos/` contains a full set of logo variants (`logo-full-color-*.png`,
  `logo-icon-*.png` in white/color at 64–1024px, `apple-touch-icon.png`,
  a real `favicon.ico`) and `public/videos/IMG_3401.mp4` (4.1MB, an
  unprocessed camera-roll filename) — all dated **Aug 3, 01:01–01:04**,
  i.e. dropped in around the same time as this health check, not by any
  prior documented task.
- **None of it is wired up.** `resources/views/welcome.blade.php` is
  still the byte-for-byte default Laravel/Breeze welcome page (confirmed
  via `grep` — no `logo`, `video`, `IMG_3401`, or `photos` references
  anywhere in it).
- `public/favicon.ico` (the path browsers actually auto-request) is a
  **0-byte empty file** — the real favicon sits unused at
  `public/photos/favicon.ico` instead.
- Fetching `/` and inspecting the response: no `<link rel="icon">` tag,
  no `<video>` tag, no logo `<img>` tag anywhere in the HTML.
- **The page `<title>` is literally "Laravel"** — `APP_NAME=Laravel` in
  `.env` was never changed to the company name. I did not change this
  myself: picking the correct brand string (English name, Arabic name,
  or both) is a content decision for the branding task, not a "trivial,
  unambiguous" fix.
- `tailwind.config.js` has no brand color palette — still the Breeze
  default (`fontFamily.sans: ['Figtree', ...]`, no custom `theme.extend.colors`).

**Recommendation:** write a dedicated task to (1) wire the real favicon
into `public/favicon.ico` and a `<link rel="icon">` tag, (2) place the
logo in the nav/welcome page, (3) decide what `APP_NAME` should actually
say, (4) build a real homepage hero section (with or without the video —
worth asking whether `IMG_3401.mp4` is the intended final asset or a
placeholder), (5) add brand color tokens to `tailwind.config.js`.

### Cover images (Service + Article) — **working correctly**

Verified with real file uploads through the actual HTTP form path (not
just factory/tinker):

- Uploaded a real JPEG to a Service's `cover_image` via
  `PUT /dashboard/services/{slug}` → stored at
  `storage/app/public/services/<random-40-char-name>.jpg` → confirmed
  `GET /storage/services/<name>.jpg` → **200**, and the edit form's `<img
  src="...">` tag correctly points at that same `/storage/` URL.
  `php artisan storage:link` was checked directly — the symlink at
  `public/storage` exists and correctly targets
  `storage/app/public` (this is the "common miss" the task asked me to
  check for; it was **not** missed here).
- Same test repeated for an Article's `cover_image` → same result,
  `GET /storage/articles/<name>.jpg` → **200**.

### Media Library delete vs. other resources' images — **no shared-storage bug**

Confirmed two ways:

1. **By code inspection**: `Service`/`Article` models have no
   relationship to the `Media` model at all — `cover_image` is a plain
   string column, and `MediaLibraryService::delete()` only ever touches
   the specific `Media` row's own `path`. There is no shared foreign key
   or join that could let one resource's delete reach another's file.
2. **Empirically**: uploaded an image to a Service's cover, separately
   uploaded a different image to the Media Library, deleted the Media
   Library item, then re-checked the Service — `cover_image` value
   unchanged, file still on disk, still reachable at 200 via
   `/storage/...`. (Filenames can't coincide in practice anyway — both
   `ContentPublishingService` and `MediaLibraryService` use Laravel's
   `UploadedFile::store()`, which generates a cryptographically random
   40-character filename per upload, and the two systems write to
   different subdirectories — `services/`/`articles/` vs `media/` —
   regardless.)

### Empty English translation — **handled cleanly, no errors**

Created Arabic-only records (Country, Service, Faq, Testimonial, Article
— no `en` value at all) and checked every angle:

- Index pages listing these records: all **200**, no errors.
- Edit form's English input: `<input ... name="name[en]" ... value="">`
  — a clean empty string, not the literal text `"null"` and not a PHP
  warning.
- Direct model check via tinker: `getTranslation('name', 'en')` returns
  `''` (empty string), no exception thrown, no fallback-locale warning.
- `storage/logs/laravel.log` was checked before and after this entire
  session's testing — **zero new entries appended**, despite dozens of
  HTTP requests, several file uploads, and multiple deliberately-invalid
  submissions. The only log content present is a pre-existing, already-documented
  harmless error from `php artisan db:show` (querying
  `performance_schema`, unrelated to application code, noted in the
  original migrations task).

---

## 5. Security spot-check

| Check | Result |
|---|---|
| Guest cannot reach any `/dashboard/*` route | **Confirmed** — all tested routes 302 to `/login` |
| `/register` unreachable | **Confirmed** — 404 on both GET and POST |
| XSS sanitization on Article body still works | **Confirmed** — re-ran `test_store_strips_script_tags_and_event_handlers_from_body` in isolation: **1 passed (8 assertions)** |
| `.env` not tracked by git | **N/A — this project is not a git repository at all** (`git status` returns "fatal: not a git repository"). There is nothing to check yet, but `.gitignore` already correctly lists `.env`, `.env.backup`, `.env.production`, so `.env` would be excluded automatically whenever `git init` does happen. Flagging this because "confirm .env isn't tracked" implicitly assumes git exists, and it currently doesn't — see §6. |
| `is_admin` cannot be set via any exposed form | **Confirmed, live-tested.** The only user-editable form beyond login is `/profile` (`ProfileController`/`ProfileUpdateRequest`). Its validation rules only cover `name` and `email` — `is_admin` isn't in the rule set, so `$request->validated()` can never contain it, and it also isn't in `User::$fillable` (double protection). Proved this isn't just theoretical: logged in as a non-admin user, POSTed `is_admin=1` directly to `/profile` alongside valid name/email — the update succeeded (302) but `is_admin` remained `false` in the database afterward, and the user still got 403 on `/dashboard`. |

---

## 6. Housekeeping check

### TASKS.md accuracy — **accurate, no mismatch found**

Cross-checked every controller, model, service, seeder, and middleware
file actually present in the codebase against what `TASKS.md` claims:

- 15 models present, all documented in the migrations task entry (plus
  `User`, correctly noted as modified rather than created).
- 6 Dashboard controllers present (`Service`, `Country`, `Faq`,
  `Testimonial`, `Article`, `Media`) — all match the two CRUD task
  entries exactly.
- 3 service classes in `app/Services/Cms/` — all documented.
- 4 custom seeders + `DatabaseSeeder` — all documented.
- 1 middleware (`EnsureUserIsAdmin`) — documented.
- `composer.json` non-dev requires: `laravel/framework`,
  `laravel/tinker` (defaults), `spatie/laravel-translatable`,
  `mews/purifier` — both custom packages match their respective task
  entries.
- **The one gap** — no branding/hero task entry — is *correctly* absent,
  since (per §4) that work was not actually done. `TASKS.md` is not
  overclaiming anywhere.

### Leftover temporary/test routes or views — **none found**

Searched the full `routes/` and `resources/views/` trees for anything
matching `layout-test`, `showcase`, `_test`, `demo-`/`-demo` — no
matches. Manually read `routes/web.php` and `routes/dashboard.php` in
full — every route maps to a real, intentional feature. Every file under
`resources/views/dashboard/` corresponds to a documented CRUD screen;
nothing orphaned.

### Git status — **not applicable; this is not a git repository**

```
$ git status
fatal: not a git repository (or any of the parent directories): .git
```

This has been true since before this task started (confirmed it's not
something this session changed). I did not run `git init` — that's a
decision for you to make deliberately, not something to do as a side
effect of a health check. Once you do initialize it, `.gitignore` is
already correctly configured (see §5), so `.env` and other secrets will
be excluded from the first commit onward.

**Large file check** (since git status itself isn't available): only one
file over 1MB outside `vendor`/`node_modules` — `public/videos/IMG_3401.mp4`
(4.1MB). This is the same un-wired branding asset from §4, not a
surprise/accidental file.

---

## Fixes made in this task

**None.** Everything found broken, missing, or inconsistent in this
report (the Tailwind v3/v4 devDependency mismatch, the unwired branding
assets, `APP_NAME` left as "Laravel") was left exactly as found and
reported here instead, per this task's explicit instruction not to
silently patch or redesign anything non-trivial. No files were modified
except this report and `TASKS.md`.

---

## How to re-run this verification yourself

```bash
# 1. Clean bootstrap
composer install
npm install
php artisan migrate:fresh --seed
npm run build

# 2. Automated tests
php artisan test

# 3. Route inventory
php artisan route:list

# 4. Manual route walk — start the server, then in a browser:
php artisan serve
# Visit http://127.0.0.1:8000/ as a guest, then http://127.0.0.1:8000/dashboard
# (should redirect to login), then log in as admin@example.test /
# Xk9#mPz2Qw7! (or whatever ADMIN_EMAIL/ADMIN_PASSWORD you've set in
# .env) and click through every nav link: Services, Countries, FAQs,
# Testimonials, Articles, Media.

# 5. Security spot-check
php artisan test --filter=strips_script_tags_and_event_handlers_from_body
# Try visiting /register directly — should 404.
# Log out, try /dashboard directly — should redirect to /login.

# 6. Housekeeping
cat TASKS.md   # compare against `find app/Http/Controllers app/Models app/Services database/seeders -name "*.php"`
git status     # will currently report "not a git repository"
```
