# Comprehensive Full-Project Health Check — 2026-08-05 (Post-Feature-Sprint)

Bawabat Taasees Al Sharikat, Laravel 12. This is a from-scratch,
end-to-end re-verification of the ENTIRE system as one coherent
product — public site (bilingual), dashboard (bilingual, per-admin
locale), Leads/attribution, Tracking Settings, RTL/LTR mirroring, the
sidebar-visibility regression fix, the honeypot accessibility fix, and
the two legal pages — after a very large number of features landed
since the last health check (2026-08-03). Nothing in this task was
trusted from earlier task reports without being re-checked live here.

**Overall verdict: the application works, and works correctly as an
integrated system.** Clean bootstrap: zero errors. Full test suite:
**282/282 passing**, 849 assertions, from a completely fresh database.
Every one of the 113 registered routes that can be directly visited
returned exactly the status it should. Every cross-feature integration
check (Consultation attribution, homepage Contact form, honeypot,
rate-limiting, both locale toggles, Meta Pixel activation, the sidebar
fix, both videos, the legal pages) passed on live re-test, not by
re-reading old task reports. Every security spot-check passed. **No
code bugs were found — nothing was fixed in this task.** What *was*
found are four process/housekeeping gaps, detailed in full below,
none of which are code defects.

---

## 1. Clean bootstrap

All four steps, real output, zero errors:

```
$ composer install
Installing dependencies from lock file (including require-dev)
Nothing to install, update or remove
Generating optimized autoload files
> @php artisan package:discover --ansi
  [8 packages discovered, all DONE]

$ npm install
up to date, audited 152 packages in 880ms
38 packages are looking for funding
found 0 vulnerabilities

$ php artisan migrate:fresh --seed
  Dropping all tables ........................................... 64.28ms DONE
  [23 migrations, all DONE]
  Database\Seeders\AdminUserSeeder ................................... RUNNING
ADMIN_EMAIL not set in .env — using fallback dev email: admin@example.test
ADMIN_PASSWORD not set in .env — using fallback dev password: Xk9#mPz2Qw7! (placeholder only, not a real credential — set ADMIN_PASSWORD in .env).
  Database\Seeders\AdminUserSeeder ............................... 255 ms DONE
  Database\Seeders\LeadSourceSeeder ................................ 8 ms DONE
  Database\Seeders\SettingsSeeder .................................. 7 ms DONE
  Database\Seeders\TrackingSettingSeeder ........................... 4 ms DONE
  Database\Seeders\PageContentSeeder ............................... 21 ms DONE

$ npm run build
vite v7.3.6 building client environment for production...
✓ 58 modules transformed.
public/build/manifest.json             0.33 kB │ gzip:  0.17 kB
public/build/assets/app-C8J61aaB.css  55.48 kB │ gzip:  9.38 kB
public/build/assets/app-DQzJ0kPq.js   95.81 kB │ gzip: 35.10 kB
✓ built in 766ms
```

**Note, not an error**: the seeder's own warning about missing
`ADMIN_EMAIL`/`ADMIN_PASSWORD` is intentional, documented fallback
behavior (see `AdminUserSeeder`) — not a bug. See Finding D below for
why this matters before real launch.

**Important sequencing note**: before running `migrate:fresh`, the
*existing* database was checked for the test lead flagged in this
task's own scope (see Finding C) — `migrate:fresh` is destructive and
would have made that check impossible afterward.

---

## 2. Full test suite

```
$ php artisan test
Tests:    282 passed (849 assertions)
Duration: 3.67s
```

**282 passed, 0 failed.** Every test file ran, from `Tests\Unit\*`
through every `Tests\Feature\*` namespace (Auth, Console, Dashboard,
Public, root-level Home/Locale/Profile/DashboardAuth/Seeders). No
skips, no incomplete tests, no warnings. This matches the exact count
reported at the end of the immediately preceding task, confirming
**zero regressions survived from a totally fresh install** — the
passing count isn't inherited from a stale environment.

---

## 3. Route inventory

`php artisan route:list --json` → **113 total registered routes.**
Every GET route that can be visited without side effects was visited
directly (guest for public routes in both `ar`/`en`, authenticated
admin — logged in live via a real POST to `/login`, not a test helper
— for dashboard routes). POST/PUT/PATCH/DELETE routes were exercised
via the integration checks in §4, not blindly.

**Public routes — Arabic (17 checked, all 200):**
`/`, `/about`, `/articles`, `/articles/{slug}`, `/consultation`,
`/contact`, `/countries`, `/faqs`, `/formation-process`,
`/privacy-policy`, `/requirements`, `/services`, `/services/{slug}`,
`/terms-and-conditions`, `/why-invest`, `/login`, `/forgot-password`,
`/up`.

**Public routes — English (15 checked, all 200):** the `/en/...`
equivalent of every one of the above except `/login`/`/forgot-password`
(single-locale auth screens by design, confirmed no `.en` variant is
registered — this is intentional, not a gap).

**Guest-blocked routes (5 checked, all correctly 302 → `/login`):**
`/dashboard`, `/dashboard/services`, `/dashboard/leads`,
`/dashboard/tracking-settings`, `/profile`.

**`/register` → 404**, confirmed genuinely unreachable (Breeze
registration was never wired up, matching the earlier auth task's
decision).

**Dashboard routes as authenticated admin (all 200):** dashboard home;
Services/Countries/Articles index+create+edit (using real slugs —
these three models bind by `slug` via `getRouteKeyName()`, not `id`,
confirmed by checking the model source after an initial 404 with a
numeric ID turned out to be a test-script mistake, not an app bug);
FAQs/Testimonials index+create+edit (these two bind by `id`, no
`getRouteKeyName()` override); Media index; Pages index+edit+sections
index+sections create+sections edit; Leads index+show; Comments index;
Tracking Settings edit; all 5 "coming soon" placeholders (Campaigns,
Lead Sources, Contact Messages, Reports, Settings — confirmed these
are still genuine placeholders with no real fields, not something to
report as broken); `/profile`.

No route returned an unexpected status.

---

## 4. Cross-feature integration checks

All of the following were done as **real live HTTP requests** against
a running `php artisan serve` instance (port 8200), not re-reads of
earlier test output.

### 4.1 Consultation form → UTM attribution → dashboard Leads
Simulated a real visitor arriving via `?utm_source=google&utm_medium=cpc&utm_campaign=health_check_test&gclid=TESTGCLID123`,
submitted the Consultation form with a matching attribution snapshot
(the same JSON shape `attribution.js` produces client-side). Result:

- `302` redirect, Lead created.
- `source_platform=google`, `utm_medium=cpc`, `utm_campaign=health_check_test`,
  `gclid=TESTGCLID123`, `landing_page_url=/consultation` — all flat
  columns correct.
- **Both** `first_touch` and `latest_touch` JSON columns populated
  correctly (identical in this single-visit simulation, as expected).
- Confirmed rendered correctly in the actual dashboard UI: visible in
  `/dashboard/leads` (name + source), and on `/dashboard/leads/1` (all
  fields, including the UTM/gclid values, render correctly in the
  Lead Source Data card).

### 4.2 Homepage embedded Contact form
Fetched `/`, confirmed the hidden `redirect_to` field is present with
value `home`, submitted with real data. Result: `302` →
`Location: http://127.0.0.1:8200#contact` (exactly the designed
redirect target), and a `type=contact` Lead was created with the
correct name/email/message.

### 4.3 Honeypot on all 3 entry points
Submitted Consultation, Contact, and the Article Comment form each
with `website_url` filled (bot simulation). All three: `302`
(success-looking response, matching the "silently accept" design), and
confirmed via direct DB query that **zero** records were created by
any of them (Lead count unchanged after the two Lead-creating forms;
Comment count stayed at 0 after the comment-form attempt).

### 4.4 Rate limiting
6 real Contact submissions in a row: requests 1–5 → `302` (all
succeeded), request 6 → **`429`**. Matches `throttle:5,1` exactly.

### 4.5 Dashboard locale toggle — every section, both directions
Logged in as admin, POSTed to `dashboard.locale.update` twice (toggle
to `en`, confirmed via DB that `users.locale = 'en'`). Dumped all 26
distinct dashboard screens (every CRUD index/create/edit, Leads
index+show, Comments, Tracking Settings, all 5 coming-soon
placeholders, Profile) and grepped every one for leftover Arabic
outside the always-both-languages form fields.

**Real finding, not a code bug — see Finding A below**: the first pass
found leftover Arabic on the Pages screens (page titles, section
titles) for the 4 pages seeded by `PageContentSeeder`. This is because
those 4 pages' English translations come from a *separate* command,
`content:translate-to-english`, which `migrate:fresh --seed` does not
run. Ran that command (real output, §5 below), re-dumped, re-scanned:
**zero leftover Arabic across all 26 screens.**

Toggled back to `ar` (confirmed via DB), re-dumped and re-scanned for
classic leftover-English-Breeze phrases (`Cancel`, `Delete`, `Save`,
etc.) — **zero matches**, and `<html lang="ar" dir="rtl">` confirmed
correctly restored.

### 4.6 Public locale toggle + hreflang — 3 non-Home pages
Checked Service detail (`/services/company-formation`), Article detail
(`/articles/welcome-post`), and the Why-Invest page — not just Home.
On all 3: the 3 `hreflang` tags (`ar`, `en`, `x-default`) are present,
self-referencing, and point to the correct page-specific URL in both
directions. The locale-toggle link's actual `href` was extracted and
confirmed to point to the **same page's** other-locale URL (e.g. the
Service page's toggle points to `/en/services/company-formation`, not
generically to `/en`) — confirming the "preserve current page" behavior
genuinely works beyond the homepage.

### 4.7 Tracking Settings — Meta Pixel activate/deactivate
Confirmed `connect.facebook.net` absent from the homepage source
before any activation. Submitted a real `PUT` to
`dashboard.tracking-settings.update` with `meta_pixel_id=999999999999999`
and `is_active=1`: homepage source afterward contains
`connect.facebook.net` **and** `fbq('init', '999999999999999'`.
Submitted again with the checkbox omitted (unchecked): homepage source
afterward has **zero** `connect.facebook.net` references. (3 remaining
`fbq(` matches on the deactivated page are the pre-existing, always-
present `onclick="if (typeof fbq === 'function') {...}"` defensive
guards on the 3 WhatsApp buttons — correctly inert since `fbq` is
undefined when the base script never loads; verified this is the exact
same behavior the passing test "tracking scripts render nothing when
all settings inactive" already covers.)

### 4.8 Sidebar visibility + top-bar mirroring fixes
Re-confirmed **in the output of this task's own fresh `npm run build`**
(not the previous task's build) that the compiled CSS still has
`.max-lg\:rtl\:translate-x-full` wrapped in
`@media not all and (min-width:1024px)` and `.lg\:translate-x-0`
wrapped in `@media (min-width:1024px)` — the mutually-exclusive media
queries that fixed the regression. Also confirmed the exact markup
(`max-lg:rtl:translate-x-full max-lg:ltr:-translate-x-full`,
`ms-auto flex items-center gap-4`, `inset-y-0 end-0`) is present in
the live-served dashboard HTML in both locale dumps, and that
`dir="rtl"`/`dir="ltr"` correctly resolve per the admin's own locale.

### 4.9 Hero video and login video
```
hero-bg.mp4:  2,097,481 bytes   hero-bg.webm:  1,926,379 bytes
login-bg.mp4: 1,987,752 bytes   login-bg.webm: 1,554,161 bytes
```
Both pairs are **byte-for-byte identical** to the sizes documented at
the time each was created (hero: 2026-08-03 task; login: 2026-08-05
task) — proof neither was touched by any of the several tasks that ran
in between. `ffmpeg -v error -i ... -f null -` exit code `0` for all 4
files (still valid, decodable video). Cross-contamination check:
homepage source contains `hero-bg.*` and never `login-bg.*`; login
page source contains `login-bg.*` and never `hero-bg.*`. `IMG_3416.mp4`
(the original uncompressed source) confirmed still absent from disk.

### 4.10 Privacy Policy and Terms and Conditions
Both routes, both locales, confirmed real content (not placeholder
text): Arabic "البيانات التي نجمعها" / English "Data We Collect" on
Privacy; Arabic "القانون الواجب التطبيق" / English "Governing Law" on
Terms. Footer links present on the homepage and point to the correct
URLs.

---

## 5. Finding A — `migrate:fresh --seed` alone does not produce a fully bilingual site

**This is a process gap, not a code bug — nothing was changed to
"fix" it, per this task's instruction not to silently patch anything
non-trivial.**

`database/seeders/PageContentSeeder.php` seeds Arabic-only content for
the four original pages (About, Why Invest, Formation Process,
Requirements). Their English translations are produced by a *separate*
Artisan command:

```
$ php artisan content:translate-to-english
+-------------+------------+---------------------------+------------------------------------+
| Model       | Translated | Skipped (already English) | Skipped (no translation available) |
+-------------+------------+---------------------------+------------------------------------+
| Page        | 16         | 8                         | 0                                  |
| PageSection | 38         | 0                         | 0                                  |
| Service     | 0          | 5                         | 0                                  |
| Country     | 0          | 2                         | 0                                  |
| Faq         | 0          | 2                         | 0                                  |
| Article     | 0          | 3                         | 0                                  |
| Testimonial | 0          | 1                         | 0                                  |
| SeoMeta     | 0          | 0                         | 0                                  |
+-------------+------------+---------------------------+------------------------------------+
Total fields translated: 54
```

Neither `DatabaseSeeder` nor any documentation calls this command
automatically. A fresh production deploy that only runs the documented
`migrate:fresh --seed` step would show About/Why-Invest/Formation-
Process/Requirements entirely in Arabic even when a visitor or admin
switches to English — this was directly reproduced in §4.5 before I
ran the command myself to complete the verification.

**Recommendation** (not applied — this is a product/deployment
decision, not a trivial fix): either call
`content:translate-to-english` from `DatabaseSeeder::run()`, or add an
explicit line to the deployment checklist/README stating both commands
are required for a fully bilingual fresh install.

## Finding B — the project has essentially never been committed to git

```
$ git log --oneline
0cc1de4 Initial Laravel 12 project setup
```

**One commit, dated 2026-08-04.** Every feature and fix built across
this entire multi-task history — dozens of tasks, the full bilingual
system, Leads/attribution, both videos, everything checked in this
report — exists **only as uncommitted working-tree changes**
(`git status` shows ~80 modified + ~25 untracked files right now).
There is no commit history, no recovery point, and no way to diff
"what changed in task N" after the fact. A `git checkout .`, a disk
issue, or an environment reset would lose all of it with no way back.

**Recommendation**: commit the current state as soon as possible, then
adopt per-task (or per-session) commits going forward. I did not
commit anything myself — this is a consequential, irreversible-feeling
action (once committed, it's the new baseline) that wasn't explicitly
requested by this task, so it's reported here rather than done
silently.

## Finding C — the earlier task's manual-verification test lead was never archived

Checked the database **before** running `migrate:fresh` (see §1): the
`Lead` row created during the "Homepage Contact Us section" task's own
manual verification (`full_name = "Test Homepage Lead"`,
`email = homepagetest@example.com`, created 2026-08-04 23:44:38) was
**still present and unarchived**. It no longer exists now, but only
because this task's own required `migrate:fresh --seed` step wiped the
entire database as part of the clean-bootstrap check — nobody had
actually gone back and archived it in the interim.

**Recommendation**: when a task's manual-verification step creates
real data for demonstration purposes, that task's own "stop" checklist
should include archiving/deleting it, not leave it for a later,
unrelated task's incidental wipe.

## Finding D — no real admin credentials configured

`.env` has no `ADMIN_EMAIL`/`ADMIN_PASSWORD`, so every fresh
seed uses the hardcoded fallback (`admin@example.test` /
`Xk9#mPz2Qw7!` — visible in this very report and in the seeder source).
This is intentional, documented dev-fallback behavior, not a bug — but
it must not be the credential in a real launch. **Recommendation**: set
real values in the production `.env` before going live.

## Housekeeping — confirmed clean

- **No leftover temporary/test artifacts**: `tests/Feature/BilingualAuditDumpTest.php`
  (explicitly created-then-deleted per its own task's report) confirmed
  actually absent. No other stray `*test*`/`*debug*`/`*scratch*` views
  or routes found.
- **`.env` not tracked**: `git ls-files | grep '^\.env$'` — zero
  matches — and correctly listed in `.gitignore`.
- **`is_admin` not mass-assignable**: confirmed in `User::$fillable`
  (only `name`, `email`, `password`, `locale`) and by the passing Unit
  test `user is admin is cast but not mass assignable`.
- **TASKS.md spot-checked against reality**: the most recent entry's
  claimed test count (282/849) matches this task's own fresh run
  exactly; spot-checked 3 specific fix claims from the last 2 entries
  (`overflow-x-auto` on dashboard tables, `pb-24` on the footer, the
  honeypot's `sr-only` class actually removed from live markup, only
  surviving in explanatory code comments) — all confirmed accurate,
  not stale.
- **Test data created by this health check itself, now in the
  database** (transparently disclosed, not hidden): 1 Service
  (`company-formation`), 1 Country (`saudi-arabia`), 1 FAQ, 1
  Testimonial, 2 Articles (`welcome-post`, and `xss-health-check` — the
  XSS test article, body already confirmed sanitized/safe), 7 Leads (2
  intentional integration-test submissions + 5 legitimate submissions
  from the rate-limit test batch, since only the 6th request is
  supposed to be blocked), 1 Comment (pending, from the moderation
  check). None of this is fake/misleading data left disguised as real
  — it's disclosed here so it can be cleared from the dashboard if a
  pristine demo state is wanted.

## Security spot-check — all passed

| Check | Result |
|---|---|
| Guest blocked from `/dashboard/*` | ✅ 302 → `/login` on all 5 tested dashboard routes |
| `/register` unreachable | ✅ genuine 404 |
| XSS sanitization on Article body | ✅ live-tested: `<script>alert(1)</script>` fully stripped, `onerror` handler stripped from `<img>`, safe `<p>`/`<img>` tags preserved |
| Comment moderation defaults to pending | ✅ live-tested: new comment status `pending`, correctly absent from the public article page until approved |
| `.env` not tracked by git | ✅ confirmed absent from `git ls-files`, present in `.gitignore` |
| `is_admin` not mass-assignable | ✅ confirmed via model + passing Unit test |

---

## How to re-run these checks yourself

```bash
# Clean bootstrap + full test suite
composer install && npm install
php artisan migrate:fresh --seed   # NOTE: destroys existing data — see Finding C
npm run build
php artisan test

# Route list
php artisan route:list

# The one command a fresh seed is missing for full bilingual content
php artisan content:translate-to-english

# git housekeeping
git status
git ls-files | grep '^\.env$'   # should print nothing
git log --oneline               # currently just 1 commit — see Finding B
```

For the integration checks (§4), the fastest way to re-verify by hand:
log in at `/login` with the admin credentials shown in your
`migrate:fresh --seed` output (or your real `.env` values), then:
1. Submit `/consultation` with a `?utm_source=...` URL, check
   Dashboard → Leads for correct attribution.
2. Scroll to the homepage Contact section, submit it, confirm you land
   on `/#contact` and the lead appears in the dashboard.
3. Toggle your dashboard locale (top bar) to English, click through
   every sidebar section, toggle back.
4. In Dashboard → Tracking Settings, paste any digits into Meta Pixel
   ID, check it, save, view page source on `/`, confirm
   `connect.facebook.net` appears; uncheck it, save, confirm it's gone.
5. Resize your browser to under 1024px and back — the sidebar should
   go off-canvas below that width and be permanently visible above it,
   in both locales.
