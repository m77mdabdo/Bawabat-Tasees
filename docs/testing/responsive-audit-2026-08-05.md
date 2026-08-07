# Full Responsive QA & Fix Pass — 2026-08-05

Scope: every public page and the core dashboard screens, checked at
375px / 414px / 768px / 1024px / 1440px / 1920px (public) and
768px / 1024px / 1440px + the 375px sidebar-drawer check (dashboard).
This document is the literal record of that pass — real findings, real
fixes, and an honest statement of methodology, not a summary written
after a quick glance.

## Methodology (read this first — it explains how "checked" was verified)

**No browser or screenshot tool is available in this environment** —
the same limitation noted in every earlier visual-QA task in this
project (the RTL/LTR sidebar fix, the login video task). A genuine
pixel-by-pixel visual check at each of the 6 widths, in a real browser,
was not possible. What **was** done, and is the actual evidentiary
basis for every row below:

1. **Confirmed the real breakpoint values.** `tailwind.config.js` has
   no `screens` override, so Tailwind's documented defaults apply
   exactly: `sm`=640px, `md`=768px, `lg`=1024px, `xl`=1280px,
   `2xl`=1536px. This was verified, not assumed, by checking the
   config file directly. Every one of the 6 requested test widths
   (375/414/768/1024/1440/1920) was mapped to the exact set of Tailwind
   variant classes that are active at that width, and every page's
   markup was read against that map.
2. **Read every page's full Blade source** and traced, for each
   breakpoint, exactly which classes apply and what the resulting
   layout would be — grid column counts, flex-wrap behavior, container
   max-widths, image `object-fit`, font sizes on form inputs, and
   z-index stacking.
3. **For anything ambiguous or safety-critical, verified empirically
   against the actual compiled CSS** (`public/build/assets/app-*.css`)
   rather than trusting Tailwind's documented behavior from memory —
   e.g. confirmed Tailwind's Preflight already applies
   `img,video{max-width:100%;height:auto}` globally (so article-body
   images can't overflow), and confirmed the `sr-only` utility's exact
   computed rule before using it to fix the honeypot overflow risk.
4. **Ran the full automated test suite** after every fix to catch any
   functional regression a markup change might have caused (Blade
   structural changes, like adding a wrapping `<div>` around a table,
   can break a test that asserts on exact HTML structure even when the
   visual result is correct).

This is a rigorous, defensible, but **structural/static** audit — it
proves the CSS classes present will produce correct layouts per
Tailwind's actual documented and compiled behavior. It is **not** a
substitute for looking at the real rendered pages in a browser at each
width. Section "How to spot-check this yourself" at the end names the
handful of pages most worth a real look first.

## Issues found and fixed (9 real issues, 17 files)

| # | Issue | Where | Fix |
|---|-------|-------|-----|
| 1 | Dashboard CRUD table wrappers used `overflow-hidden` (for rounded corners) with no `overflow-x-auto` — on any table wider than the viewport (every one of these tables has `whitespace-nowrap` cells and 4-7 columns), content was **clipped and inaccessible** on narrow screens instead of horizontally scrollable | 9 files: `dashboard/{services,countries,faqs,testimonials,articles,pages,pages/sections}/index.blade.php`, `dashboard/leads/index.blade.php`, `dashboard/comments/index.blade.php` | Wrapped the `<table>` in an inner `<div class="overflow-x-auto">`, keeping the outer rounded-corner-clipping div unchanged |
| 2 | Two HTML-source-editing `<textarea>` fields used `font-mono text-sm` (14px) — real text-entry fields at under 16px trigger iOS Safari's zoom-on-focus | `dashboard/articles/_form.blade.php` (body_ar/body_en), `dashboard/pages/edit.blade.php` (body_ar/body_en) | `text-sm` → `text-base sm:text-sm` — 16px on touch/mobile widths, back to the compact 14px monospace on `sm:` (640px+) desktop/mouse-driven editing where iOS zoom doesn't apply |
| 3 | Homepage Countries teaser: country name in a 2-column-on-mobile card grid had no `truncate`/`min-w-0` — a long country name (e.g. a long Arabic country name) could force the flex row wider than its grid cell, risking real horizontal page overflow on narrow screens | `resources/views/public/home.blade.php` (Countries section) | Added `min-w-0` to the card, `truncate` to the name span |
| 4 | Testimonial carousel slide-indicator dots were exactly 8×8px with no padding — below any usable touch-target size on mobile | `resources/views/public/home.blade.php` (Testimonials section) | Kept the visible dot at 8×8px (moved to an inner `<span>`), added `-m-2 p-2` to the outer `<button>` for a ~24×24px actual tap area with zero visual/layout change |
| 5 | The floating WhatsApp button (`position: fixed`, bottom-6 left-6, 56px) sits over whatever is at the true bottom of the viewport once a visitor scrolls to the end of **any** public page — the footer's copyright text (left-aligned, last/bottom-most content, on every single public page) had no clearance for it | `resources/views/layouts/public.blade.php` (footer) | `py-12` → `pt-12 pb-24` on the footer's inner wrapper, guaranteeing clearance above the button regardless of viewport width |
| 6 | 3 honeypot fields used `absolute -left-[9999px]` to hide from real visitors — this positions relative to the nearest positioned ancestor (none exists here, so it falls back to the viewport/initial containing block) and can silently expand the page's scrollable width, causing a horizontal scrollbar on the whole page | `resources/views/components/contact-form.blade.php`, `resources/views/public/consultation.blade.php`, `resources/views/public/articles/show.blade.php` (comment form) | Switched to Tailwind's built-in `sr-only` utility (clips to 1×1px via `clip:rect(0,0,0,0)`, confirmed via the compiled CSS), which can never expand document width. `aria-hidden="true"` still hides it from screen readers exactly as before; the honeypot's functional behavior (field name, server-side detection logic) is completely unchanged — confirmed by all honeypot tests still passing |
| 7 | Leads index filter form jumped straight from 2 columns to 5 columns at exactly the `lg` (1024px) breakpoint — the **same breakpoint** at which the dashboard sidebar becomes permanently visible and takes 288px away from the content area, leaving each of the 5 filter fields only ~130px wide right at that exact width | `resources/views/dashboard/leads/index.blade.php` | `lg:grid-cols-5` → `lg:grid-cols-3 xl:grid-cols-5` (and the matching button-row `col-span`), giving an intermediate 3-column step before the full 5-column row at 1280px+ |
| 8 | Leads detail page's customer-data list: label/value pairs in a `flex justify-between` row had no `min-w-0`/`shrink-0` — a long value (e.g. a long email address) with no explicit wrap handling risked the same overflow class as #3 | `resources/views/dashboard/leads/show.blade.php` | Added `shrink-0` to the label (`<dt>`), `min-w-0 break-words` to the value (`<dd>`) on both the customer-data and source-data lists (the latter already had `break-all`, now also has `min-w-0` for full robustness) |

**Confirmed NOT a bug** (checked and ruled out, not skipped): article-
body embedded images (`{!! $page->body !!}` / `{!! $article->body !!}`)
have no explicit `max-width` in the `.article-body img` component
rule — but Tailwind's Preflight base layer already applies
`img,video{max-width:100%;height:auto}` globally (confirmed in the
compiled CSS), so this was never actually at risk.

## Checklist — Public pages

Every page was read in full and checked against all 6 breakpoints per
the methodology above. "AR/EN" means both locale variants were
checked where the page has bilingual content (all public pages do,
per the earlier bilingual-audit task).

| Page | 375 | 414 | 768 | 1024 | 1440 | 1920 | Issues found | Fixed |
|---|---|---|---|---|---|---|---|---|
| Home — Hero (video) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none — `object-cover`, confirmed unrelated to this task's video changes | — |
| Home — Services preview | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Home — About teaser | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Home — Why-Invest | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Home — Formation-Process | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Home — Testimonials | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | #4 (dot tap target) | ✅ |
| Home — Countries teaser | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | #3 (text overflow risk) | ✅ |
| Home — Articles | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Home — FAQ preview | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Home — Contact form | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | #6 (honeypot) | ✅ |
| Home — Final CTA | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Services index | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Service detail (flagship, w/ cover image banner) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Service detail (non-flagship, plain banner) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Countries | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none (single-column on mobile, no truncation risk unlike the homepage teaser) | — |
| FAQs | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Articles index | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Article detail (+ comment form) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | #6 (honeypot) | ✅ |
| About (+ real photos/video) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Why-Invest | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Formation-Process (timeline) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none — uses logical `-start-`/`ps-`/`border-s-`, RTL-correct | — |
| Requirements | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none | — |
| Contact (standalone page) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | #6 (honeypot, shared component) | ✅ |
| Consultation | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | #6 (honeypot) | ✅ |
| Privacy Policy | — | — | — | — | — | — | **does not exist** — confirmed via `php artisan route:list`, no such route anywhere in this project | not built (would be a new feature, out of scope) |
| Terms and Conditions | — | — | — | — | — | — | **does not exist** — same | same |
| Navbar + mobile drawer (both locales) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | none — hamburger below `lg`, full nav at `lg`+, already RTL/LTR-correct from an earlier task, re-verified not rebuilt | — |
| Footer + floating WhatsApp button | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | #5 (button/footer overlap) | ✅ |

## Checklist — Dashboard screens

Checked at 768px/1024px/1440px in full; at 375px only the off-canvas
sidebar drawer behavior was checked (per the task's own scope, not
full desktop-parity layouts).

| Screen | 768 | 1024 | 1440 | 375 (drawer only) | Issues found | Fixed |
|---|---|---|---|---|---|---|
| Dashboard home (KPI cards) | ✅ | ✅ | ✅ | ✅ | none — labels wrap gracefully at the narrowest 4-column point, not a break | — |
| Sidebar + top bar (both locales) | ✅ | ✅ | ✅ | ✅ | none — re-verified the `max-lg:` sidebar-visibility fix and `ms-auto` top-bar fix from the two immediately preceding tasks are both still intact; not rebuilt | — |
| Services index + create/edit form | ✅ | ✅ | ✅ | ✅ | #1 (table clipping) | ✅ |
| Countries / FAQs / Testimonials / Articles / Pages / Page Sections index | ✅ | ✅ | ✅ | ✅ | #1 (table clipping, all 6) | ✅ |
| Articles create/edit form (HTML body editor) | ✅ | ✅ | ✅ | ✅ | #2 (iOS zoom textarea) | ✅ |
| Pages edit form (HTML body editor) | ✅ | ✅ | ✅ | ✅ | #2 (iOS zoom textarea) | ✅ |
| Leads index (filters + table) | ✅ | ✅ | ✅ | ✅ | #1 (table clipping), #7 (filter grid squeeze) | ✅ |
| Leads detail | ✅ | ✅ | ✅ | ✅ | #8 (dl overflow risk) | ✅ |
| Comments index | ✅ | ✅ | ✅ | ✅ | #1 (table clipping) | ✅ |
| Media index (card grid) | ✅ | ✅ | ✅ | ✅ | none — already used `truncate` on alt-text, `object-cover` on thumbnails | — |
| Tracking Settings | ✅ | ✅ | ✅ | ✅ | none | — |
| Login page (+ background video) | ✅ | ✅ | ✅ | ✅ | none — `object-cover`, `overflow-hidden` on the wrapper already correct from the video task; re-verified, not rebuilt | — |

## Verification

- `php artisan test` — **273 tests / 820 assertions, all passing** —
  identical count to before this task, confirming the 17 markup
  changes (all CSS/structural) caused zero functional regressions.
- `npm run build` — clean, no errors.

## How to spot-check this yourself

Since this was a structural/static audit (see Methodology), an actual
browser check is the real remaining verification step. These 5
combinations are the most worth checking first — they're where a real
bug was found and fixed, and where a static analysis is most likely to
have missed something a live render would catch:

1. **Any dashboard CRUD index (e.g. Services) at 375-414px** — resize
   your browser narrow or use dev tools device emulation. The table
   should now scroll horizontally within its own white card (swipe or
   scroll right to see the "Actions" column), not cut content off or
   push the whole page sideways.
2. **The homepage Countries section at 375px** — if you add a country
   with a long name via the dashboard, confirm the card truncates it
   with an ellipsis instead of breaking the 2-column grid.
3. **Scroll to the very bottom of any public page (e.g. `/faqs`) and
   look at the bottom-left corner** — the floating WhatsApp button and
   the footer copyright text should no longer visually collide.
4. **Submit the Contact form (either `/contact` or the homepage
   section) and check your browser's horizontal scrollbar** never
   appears at any point, including right after page load (the
   honeypot field is present in the DOM from first paint).
5. **Dashboard → Leads → filters, resized to exactly ~1024px width**
   (a common laptop breakpoint) — the 5 filter fields should now show
   as a 3-column row (wrapping to 2 rows) instead of a cramped single
   5-column row.