# Setup Instructions

How to get **Bawabat Taasees Al Sharikat** running from a clean
checkout. This is the complete sequence — there are no additional
manual steps.

## The one correct setup sequence

```bash
composer install && npm install
php artisan migrate:fresh --seed
npm run build
```

That's it. Nothing else is required.

> `migrate:fresh` **drops every table** and reseeds from scratch. Use it
> for first-time setup and for local resets — never against a database
> holding real leads or edited content. For an existing database, use
> `php artisan migrate` (and `php artisan db:seed` only if you actually
> want the starter content re-applied).

## Before the first run

1. Copy the environment file and generate a key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
2. Set your database credentials in `.env`.
3. **Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env`.** If you don't,
   `AdminUserSeeder` prints a warning and falls back to placeholder dev
   credentials (`admin@example.test` / a hardcoded password visible in
   the seeder source). That fallback exists so a fresh local checkout
   works immediately — it must **never** be what a real deployment
   ships with.
4. If you'll upload media through the dashboard, link the storage disk:
   ```bash
   php artisan storage:link
   ```

## Real content vs demo data

`php artisan migrate --seed` loads **production content only**: services,
countries, FAQs, the six CMS pages, starter articles, settings, lead
sources, tracking settings and SEO meta. Nothing in it is invented.

Sample testimonials, campaigns, leads and conversion events are **not**
loaded by default. They are fabricated — invented client quotes, made-up
ad budgets, and revenue that was never earned — and the Reports screen
computes ROI from those figures, so shipping them would make the
dashboard lie.

Load them explicitly when you want a populated dashboard to work against:

```bash
php artisan db:seed --class=DemoDataSeeder
```

It prints a warning, and it is idempotent, so re-running it will not
duplicate rows. **Never run it against production.** If demo data has
already been loaded somewhere it should not be, delete those rows from
the dashboard (Testimonials, Campaigns, Leads) — every one of them is
labelled `(بيانات عينة)` / `(SAMPLE DATA)`.

## Why English content is not a separate step anymore

The content seeders author **Arabic only**. English translations come
from a hand-written dictionary in the
`content:translate-to-english` Artisan command.

That command used to be a separate manual step, which meant a fresh
environment silently ended up Arabic-only on every `/en/` page unless
someone remembered to run it. As of 2026-08-08 it is invoked
automatically at the end of `DatabaseSeeder::run()`, so
`migrate:fresh --seed` alone always produces a fully bilingual site.

You can still run it by hand — it's safe to run any number of times,
because it skips any field that already has English content:

```bash
php artisan content:translate-to-english

# Overwrite existing English content (rarely wanted):
php artisan content:translate-to-english --force
```

If you add new Arabic content that has no entry in the command's
dictionary, it is reported as *"no translation available"* rather than
being silently skipped or machine-guessed — so the gap is visible and
you know to add the English text.

## Verifying the setup worked

```bash
php artisan test          # expect: 282 passed
php artisan serve
```

Then check both locales render real content:

- `http://127.0.0.1:8000/about` → Arabic ("من نحن")
- `http://127.0.0.1:8000/en/about` → English ("About Us", "Our
  Expertise") — **not** Arabic text falling through

The only Arabic that should appear on an `/en/` page is the navbar's
locale-toggle link, which is labelled "العربية" on purpose.

Log in at `/login` with the admin credentials from your `.env` (or the
fallback ones the seeder printed).

## Day-to-day commands

```bash
npm run dev               # Vite dev server with hot reload
npm run build             # production asset build
php artisan test          # full suite
php artisan route:list    # all registered routes
```
