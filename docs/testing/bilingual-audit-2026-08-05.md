# Full Bilingual QA Sweep — 2026-08-05

Scope: (A) fix the dashboard top-bar RTL/LTR mirroring bug, (B) an
exhaustive, page-by-page audit of every public route and every
dashboard screen, in both `ar` and `en`, checking page title, headings,
body copy, buttons, form labels/placeholders, table headers,
empty-state text, flash/validation messages, pagination, footer, and
(dashboard) sidebar/top bar. This document is the literal record of
that sweep, not a summary written after the fact.

## Part A — Top bar RTL/LTR mirroring bug

**Root cause found**: `resources/views/layouts/app.blade.php`'s
`<header>` used `justify-between` with two children — a hamburger
button (`lg:hidden`) and the admin-controls cluster (locale toggle +
avatar dropdown). At desktop widths (`lg:` and up) the hamburger is
removed from the box tree entirely by `display: none`, leaving
**exactly one** flex child. Per the CSS Flexbox spec, `justify-content:
space-between` with a single item collapses to `flex-start` — the
cluster landed on the wrong side, and the same physical side
regardless of `dir`, instead of mirroring.

**Fix**: replaced `justify-between` on the `<header>` with `ms-auto`
(`margin-inline-start: auto`) on the controls cluster itself. This is
a logical property that pushes the cluster to the end of the row
unconditionally — independent of whether the hamburger is present in
the layout — and correctly resolves to the LEFT edge under `dir="rtl"`
and the RIGHT edge under `dir="ltr"`, exactly matching the
requirement.

While in there, re-audited the rest of the top bar and sidebar for the
same class of physical-positioning bug (this had only been fixed in
the CRUD tables in an earlier task, never explicitly in the top
bar/sidebar):
- `resources/views/components/dashboard/sidebar.blade.php` — `right-0`
  → `end-0` (logical); the closed-state transform was a single
  `translate-x-full`, which only pushes the sidebar off-screen
  correctly in RTL — changed to `rtl:translate-x-full
  ltr:-translate-x-full` so it also slides off to the correct side in
  LTR. Both were left uncaught by the earlier CRUD-table pass since
  that pass only touched `resources/views/dashboard/**/*.blade.php`,
  not `resources/views/components/dashboard/*`.
- `x-dropdown` (used for the avatar menu): already correct —
  `ltr:origin-top-left rtl:origin-top-right start-0` /
  `ltr:origin-top-right rtl:origin-top-left end-0`, confirmed by
  reading the component, not changed.

**Verification**:
- New Feature test
  `test_top_bar_controls_use_logical_end_margin_not_a_fragile_justify_between`
  in `tests/Feature/Dashboard/DashboardLocaleTest.php` — asserts
  `ms-auto` is present and `justify-between` is absent from the
  `<header>`, as a regression guard. This test **cannot** verify the
  actual visual result — no browser is available in this environment —
  see "How to spot-check this yourself" below for the manual step.
- Manually reasoned through the CSS mechanics (flexbox main-axis start/
  end is direction-aware by default; `ms-auto` behavior confirmed
  against the CSS Logical Properties spec) rather than assumed.
- **Honesty note**: I could not visually confirm the pixel result in
  an actual browser — this environment has no browser/screenshot
  tooling (the same limitation noted in an earlier task's public-site
  RTL/LTR work). The fix is verified by (a) understanding exactly why
  the old code was broken, (b) confirming the new code with a
  regression test, and (c) the same `ms-auto`/logical-property pattern
  already used correctly elsewhere in this codebase (`x-dropdown`,
  every CRUD table). A real-browser check is still recommended before
  calling this fully closed — see spot-check instructions below.

## Part B — Methodology

Real production data barely exists yet in this project's dev database
(one placeholder Service, one Country, zero Articles/FAQs/Testimonials/
Leads/Comments — content entry is the client's job via the dashboard,
not seeded). Two complementary methods were used so the audit is
genuinely exhaustive rather than "mostly empty states":

1. **Public routes with real content** (Home, About, Why Invest,
   Formation Process, Requirements) — checked against the actual dev
   database via `php artisan serve` + `curl`, i.e. the real content a
   visitor would see today.
2. **Every route** (public and dashboard) — a one-time Feature test
   (`tests/Feature/BilingualAuditDumpTest.php`, deleted after use, not
   a permanent part of the suite) seeded full, realistic, correctly
   bilingual data (2 Services, 1 Country, 1 FAQ, 1 Testimonial, 1
   Article, 1 Lead, 1 Comment, plus the project's real
   `PageContentSeeder` output and a real run of the
   `content:translate-to-english` command — the same command that
   produced the dev database's actual English content) and rendered
   every route in both locales via Laravel's HTTP test client,
   dumping all 92 resulting HTML files to disk. Those dumps were then:
   - Grepped for Arabic text appearing on English pages (excluding the
     deliberate `[lang]`-suffixed form fields, which correctly show
     both languages always, and the intentional "عربي" toggle label).
   - Grepped for classic untranslated-Breeze English phrases ("Save
     changes", "Cancel", "Are you sure", etc.) appearing on Arabic
     pages.
   - Grepped for literal unresolved translation keys (e.g. a raw
     `dashboard.foo.bar` string leaking into rendered output).
   - Grepped for lines mixing Arabic and Latin script, to catch
     mistranslated sentences.
   - **Manually read** a representative sample of the raw HTML
     (dashboard home, services index, leads show, comments index,
     tracking settings, public service-show, both locales) rather than
     trusting only regex, since a script cannot judge tone, wrong-field
     content, or awkward phrasing.

   One methodology pitfall was caught and corrected mid-audit: Laravel's
   test HTTP client reuses the same application container across
   sequential `$this->get()` calls in one test method, so a route with
   no locale-setting middleware (`login`, `forgot-password` — these are
   pre-authentication, so there is no user to read a locale preference
   from) can inherit locale state left over from an earlier call in the
   same test — something that can **never** happen on a real server,
   where every request boots fresh. Both flagged instances were
   re-verified directly against a running `php artisan serve` instance
   and confirmed correct (`تذكرني` / `إرسال رابط`, real `dir="rtl"`, not
   the state the test dump initially suggested). This is called out
   explicitly rather than silently discarding the discrepancy.

3 real, fixed issues were found (excluding Part A above):

| # | Issue | Where | Fix |
|---|-------|-------|-----|
| 1 | Dashboard `<title>` used `config('app.name')` (fixed to the Arabic company name via `.env`), so the browser tab title stayed Arabic even in English mode | `resources/views/layouts/app.blade.php` | Changed to `__('site.brand.name')`, the same locale-aware key the public site already uses |
| 2 | Sidebar logo `alt` text had the same `config('app.name')` bug | `resources/views/components/dashboard/sidebar.blade.php` | Same fix — `__('site.brand.name')` |
| 3 | 7 CRUD index table column headers (Services, Countries, FAQs, Testimonials, Articles, Pages, Page Sections) were labeled "Name (Arabic)" / "الاسم (عربي)" etc. even though an earlier task made the underlying cell content locale-aware (shows the record's English value when toggled to English) — the header text was never updated to match, so an English-locale admin saw an English name under a column literally saying "(Arabic)" | 7 `index.blade.php` files | Added locale-neutral `dashboard.common.name` / `dashboard.common.title` / `dashboard.faqs.question_column` / `dashboard.testimonials.quote_column` keys (both `ar`/`en`); the old `_ar`-suffixed keys are kept and still correctly used by the create/edit form field labels, which genuinely are always the Arabic-specific input |

No other issues were found. The rest of this document is the full
checklist.

## Checklist — Public routes

Data used: real dev database content (via `curl` against
`php artisan serve`) for the 5 content-bearing pages; seeded
representative data (via the test dump, see above) for the rest since
the dev DB has no real Services/Articles yet.

| Route | Locale | Checked | Issues found | Fixed |
|---|---|---|---|---|
| `/` (home) | ar | ✅ real DB | none | — |
| `/en` (home) | ar/en | ✅ real DB | none | — |
| `/services` | ar | ✅ seeded | none | — |
| `/en/services` | en | ✅ seeded | none | — |
| `/services/{slug}` | ar | ✅ seeded | none | — |
| `/en/services/{slug}` | en | ✅ seeded | none — full manual read-through, see Part B | — |
| `/countries` | ar | ✅ seeded | none | — |
| `/en/countries` | en | ✅ seeded | none | — |
| `/faqs` | ar | ✅ seeded | none | — |
| `/en/faqs` | en | ✅ seeded | none | — |
| `/articles` | ar | ✅ seeded | none | — |
| `/en/articles` | en | ✅ seeded | none | — |
| `/articles/{slug}` | ar | ✅ seeded | none | — |
| `/en/articles/{slug}` | en | ✅ seeded | none | — |
| `/about` | ar | ✅ real DB | none | — |
| `/en/about` | en | ✅ real DB | none — full English body copy confirmed present, no Arabic leakage | — |
| `/why-invest` | ar | ✅ real DB | none | — |
| `/en/why-invest` | en | ✅ real DB | none — the 6 "Vision 2030" style section cards all show correct English titles/descriptions | — |
| `/formation-process` | ar | ✅ real DB | none | — |
| `/en/formation-process` | en | ✅ real DB | none — all 7 process steps in English | — |
| `/requirements` | ar | ✅ real DB | none | — |
| `/en/requirements` | en | ✅ real DB | none — all 6 document-requirement cards in English | — |
| `/contact` | ar | ✅ seeded | none | — |
| `/en/contact` | en | ✅ seeded | none | — |
| `/contact` validation errors (empty POST) | ar | ✅ seeded | none — "حقل الاسم الكامل مطلوب." etc. | — |
| `/en/contact` validation errors (empty POST) | en | ✅ seeded | none — "The full name field is required." etc. | — |
| `/consultation` | ar | ✅ seeded | none | — |
| `/en/consultation` | en | ✅ seeded | none | — |
| `/login` | ar only (no toggle possible pre-auth) | ✅ real server | none — confirmed via direct curl after the test-dump false positive described in Part B | — |
| `/forgot-password` | ar only (no toggle possible pre-auth) | ✅ real server | none — confirmed via direct curl, same false-positive correction | — |
| Privacy Policy | — | ❌ does not exist | `php artisan route:list` confirms no such route anywhere in this project | not built — out of scope to invent (business logic), reported instead per instructions |
| Terms and Conditions | — | ❌ does not exist | same | same |

## Checklist — Dashboard screens

All dashboard screens were checked as an admin, toggling between `ar`
and `en` via `dashboard.locale.update`, using representative seeded
data (2 Services — one flagship, both fully bilingual; 1 Country; 1
FAQ; 1 Testimonial; 1 Article; 1 Lead with full attribution fields; 1
pending Comment; the real seeded Pages/Sections).

| Screen | Locale | Checked | Issues found | Fixed |
|---|---|---|---|---|
| Dashboard home (KPI cards) | ar | ✅ | none | — |
| Dashboard home (KPI cards) | en | ✅ | none | — |
| Top bar + sidebar | ar | ✅ | mirroring bug (Part A) + brand name (title/logo) | ✅ |
| Top bar + sidebar | en | ✅ | same | ✅ |
| Services — index | ar | ✅ | column header mislabel (#3 above) | ✅ |
| Services — index | en | ✅ | same | ✅ |
| Services — create | ar | ✅ | none | — |
| Services — create | en | ✅ | none | — |
| Services — create, validation errors | ar | ✅ | none — "حقل الاسم (عربي) مطلوب." | — |
| Services — create, validation errors | en | ✅ | none — "The name (Arabic) field is required." | — |
| Services — edit | ar | ✅ | none — both language inputs present, correct values | — |
| Services — edit | en | ✅ | none | — |
| Countries — index | ar | ✅ | column header mislabel (#3) | ✅ |
| Countries — index | en | ✅ | same | ✅ |
| Countries — create | ar/en | ✅ | none | — |
| Countries — edit | ar/en | ✅ | none | — |
| FAQs — index | ar | ✅ | column header mislabel (#3) | ✅ |
| FAQs — index | en | ✅ | same | ✅ |
| FAQs — create | ar/en | ✅ | none | — |
| Testimonials — index | ar | ✅ | column header mislabel (#3) | ✅ |
| Testimonials — index | en | ✅ | same | ✅ |
| Testimonials — create | ar/en | ✅ | none | — |
| Articles — index | ar | ✅ | column header mislabel (#3) | ✅ |
| Articles — index | en | ✅ | same | ✅ |
| Articles — create | ar/en | ✅ | none | — |
| Articles — edit | ar/en | ✅ | none | — |
| Media — index | ar/en | ✅ | none — empty state correct in both ("No files uploaded yet." / "لا توجد ملفات مرفوعة بعد.") | — |
| Pages — index | ar | ✅ | column header mislabel (#3) | ✅ |
| Pages — index | en | ✅ | same | ✅ |
| Pages — edit | ar/en | ✅ | none — both-language meta fields correct | — |
| Pages → Sections — index | ar | ✅ | column header mislabel (#3) | ✅ |
| Pages → Sections — index | en | ✅ | same | ✅ |
| Pages → Sections — create | ar/en | ✅ | none | — |
| Pages → Sections — edit | ar/en | ✅ | none | — |
| Leads — index (filters, table) | ar/en | ✅ | none | — |
| Leads — show (customer + source detail) | ar/en | ✅ | none — manually read in full, all ~28 labeled fields correct | — |
| Comments — index (filters, table, approve/reject/delete) | ar/en | ✅ | none | — |
| Tracking Settings — edit | ar/en | ✅ | none — including the "e.g.: ..." placeholder examples | — |
| Campaigns (coming soon) | ar/en | ✅ | none | — |
| Lead Sources (coming soon) | ar/en | ✅ | none | — |
| Contact Messages (coming soon) | ar/en | ✅ | none | — |
| Reports (coming soon) | ar/en | ✅ | none | — |
| Settings (coming soon) | ar/en | ✅ | none | — |
| Profile (info + password + delete account) | ar/en | ✅ | none | — |
| Dashboard-side login | ar only, by design (see public checklist) | ✅ | none | — |
| Dashboard-side forgot-password | ar only, by design | ✅ | none | — |

**Note on Reports/Settings/Campaigns/Lead Sources/Contact Messages**:
these five are still placeholder "coming soon" screens with no real
fields or content — confirmed via `routes/dashboard.php`. Only their
placeholder title/message chrome was checked and is correctly
bilingual; there is no real content to audit yet, and building any is
out of scope for this task.

## How to spot-check this yourself

1. **Top bar mirroring (Part A)** — log in, toggle to English in the
   top bar. The toggle button + your avatar should now sit on the
   **right** edge of the top bar, with the sidebar itself now on the
   left. Toggle back to Arabic — both should sit on the **left**, and
   the sidebar back on the right. This is the one item in this report
   that was not visually confirmed in a real browser (none available
   here) — please check it first.
2. **Services index column header (#3)** — go to Services in English.
   The first column should say "Name", not "Name (Arabic)" — and the
   row underneath should show the service's actual English name (not
   Arabic). Toggle to Arabic — column now says "الاسم", row shows the
   Arabic name.
3. **Dashboard browser tab title (#1/#2)** — toggle to English, look at
   the browser tab title and the sidebar logo's hover/alt text (or
   inspect the `<title>` tag) — should read "Bawabat Taasees Al
   Sharikat", not the Arabic company name.
4. **Public English content pages** — visit `/en/why-invest` and
   `/en/formation-process` — every section card should be full English
   with zero Arabic text, since these pages' content was specifically
   flagged as a false-positive-turned-non-issue during this audit (see
   Part B) — worth a second pair of eyes.

## Terminal output

```
php artisan test --filter=Dashboard
php artisan test --filter=Locale
npm run build
```
Real output for both, plus the full suite, is in TASKS.md under this
same date.
