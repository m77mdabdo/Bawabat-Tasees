# Bawabat Taasees Al Sharikat — بوابة تأسيس الشركات

A bilingual (Arabic/English) marketing site and admin dashboard for a
Saudi company-formation consultancy.

The public site explains the firm's services and captures enquiries. Every
enquiry is stored as a **lead** enriched with marketing attribution — UTM
parameters, `gclid`/`fbclid`/`ttclid`, and first-touch vs latest-touch
cookies — so ad spend can be traced through to signed contracts. The
dashboard is where staff manage content, moderate comments, log
conversions against leads, and read the resulting ROI reports.

Arabic is the primary locale. English is a full second locale, not an
afterthought.

---

## Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12 (PHP ^8.2) |
| Frontend | Blade + Alpine.js 3 + Tailwind CSS 3, bundled by Vite 7 |
| Database | MySQL/MariaDB in dev and production; SQLite `:memory:` for tests |
| Auth | Laravel Breeze (registration removed — admins are seeded) |
| i18n | `spatie/laravel-translatable` (JSON columns) + `lang/{ar,en}` files |
| Sanitisation | `mews/purifier` (HTMLPurifier) for admin-authored HTML |

No SPA, no API layer, no Livewire/Inertia. Everything is server-rendered
Blade. Charts on the Reports screen are plain CSS — there is deliberately
no charting dependency.

---

## Setup

```bash
git clone <repo> && cd bawabat-tasees
cp .env.example .env

composer install && npm install
php artisan key:generate
# set DB_* and ADMIN_EMAIL / ADMIN_PASSWORD in .env first

php artisan migrate --seed
npm run build
php artisan storage:link      # only if you'll upload media
```

Then `php artisan serve` and log in at `/login`.

**Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` before seeding.** Outside
`local`/`testing` the seeder *aborts* rather than fall back to the
placeholder dev credentials, which are published in its own source.

`.env.example` ships production-safe defaults (`APP_ENV=production`,
`APP_DEBUG=false`). For local work set `APP_ENV=local` and
`APP_DEBUG=true` after copying it.

For a full local reset, `php artisan migrate:fresh --seed` — it drops
every table, so never point it at a database holding real leads.

More detail: [docs/setup-instructions.md](docs/setup-instructions.md).

---

## Seeding: real content vs demo data

**`migrate --seed` loads production content only** — services, countries,
FAQs, the six CMS pages, starter articles, settings, lead sources,
tracking settings and SEO meta. Nothing in it is invented.

**Fabricated data is opt-in:**

```bash
php artisan db:seed --class=DemoDataSeeder
```

That adds sample testimonials, campaigns, leads and conversion events so
the dashboard and Reports screen have something to show. They are made-up
client quotes, invented ad budgets and revenue that was never earned —
and Reports computes ROI from those figures — so **never run it against
production**. Every row is labelled `(بيانات عينة)` / `(SAMPLE DATA)`.

A test (`DemoDataSeparationTest`) asserts the default seed produces zero
testimonials, campaigns, leads and conversion events.

English translations are **not** a separate step: `DatabaseSeeder` runs
`content:translate-to-english` automatically, so one seed gives you a
fully bilingual site.

---

## The bilingual routing model

Worth understanding before touching `routes/web.php`.

Arabic has **no URL prefix**; English lives under **`/en`**:

```
/services            → Arabic    route name: services.index
/en/services         → English   route name: services.index.en
```

This is implemented by **registering the same route table twice** — a
closure in [`routes/web.php`](routes/web.php) declares 18 public routes,
called once bare and once inside a `/en` prefix with a `.en` name suffix.
A `{locale?}` prefix was rejected because Symfony cannot compile an
optional parameter followed by required literal segments, so
`{locale?}/services` would 404 on `/services`.

Locale is therefore derived from the **route name**, not by parsing the
URL: `SetLocale` middleware checks whether the matched name ends in
`.en`.

Three helpers in [`app/helpers.php`](app/helpers.php) glue this to Blade:

- `lroute($name, $params)` — like `route()`, but resolves the `.en`
  variant when the current locale is English. **Use this, not `route()`,
  for any public-facing link or redirect.**
- `current_route_base_name()` — strips the `.en` suffix.
- `route_in_locale($locale)` — the current URL in the other locale;
  drives the language toggle and the `hreflang` tags.

The **dashboard** has a second, independent locale system: each admin's
language is a `users.locale` column toggled from the top bar, applied by
`SetDashboardLocale`. A visitor's language comes from the URL; an admin's
comes from their profile.

---

## Layout

```
app/
  Http/Controllers/Public/     public site
  Http/Controllers/Dashboard/  admin
  Http/Middleware/             SetLocale, SetDashboardLocale, EnsureUserIsAdmin
  Services/Cms/                sanitising, image storage, media library
  Services/Marketing/          attribution, campaigns, conversion events
  Services/Dashboard/          dashboard stats, reporting aggregates
  Services/Seo/                SEO tag resolution + persistence
resources/views/public/        public pages
resources/views/dashboard/     admin screens
lang/{ar,en}/                  site.php (public), dashboard.php (admin)
docs/                          setup, decisions, testing audits
```

Authorisation is route-middleware based: every dashboard route sits behind
`['auth', 'admin', 'dashboardlocale']`. There is no Policy/Gate layer —
it is a single-admin-role application, and Form Requests document that
their `authorize()` is intentionally a pass-through.

---

## Day-to-day

```bash
npm run dev                          # Vite dev server
php artisan test                     # full suite
./vendor/bin/pint                    # code style
php artisan route:list               # all routes
php artisan content:translate-to-english   # safe to re-run; skips existing English
```

---

## Before launch

- Set real `ADMIN_EMAIL` / `ADMIN_PASSWORD`, and `APP_ENV=production` with
  `APP_DEBUG=false`.
- Delete any demo data that reached the environment (Testimonials,
  Campaigns, Leads).
- Replace the seeded contact/social placeholder values in Settings.
- Have the seeded legal pages (Privacy Policy, Terms) reviewed — they are
  professional-sounding starter copy, not lawyer-written.
