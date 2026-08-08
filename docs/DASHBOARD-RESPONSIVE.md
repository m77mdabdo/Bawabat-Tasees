# Dashboard responsive pass — what changed, per screen

Breakpoints referenced throughout: `sm` 640px, `md` 768px, `lg` 1024px,
`xl` 1280px. Targets checked: **375px** (phone), **768px** (tablet),
**1440px** (desktop).

**The single most important constraint:** the sidebar stops being a
drawer and becomes permanently visible at exactly `lg` (1024px), taking
288px out of the content pane. Any grid that widened *at* `lg` was
therefore at its most cramped precisely when it added columns. Several
did. Their widest step moved to `xl`.

Everything here is layout/CSS. No route, query, validation rule or
stored value changed.

---

## Shell — `layouts/app.blade.php`, `components/dashboard/sidebar.blade.php`

| | |
|---|---|
| **Drawer, pre-Alpine paint** | The aside had no closed state in the markup, so on a phone it painted **open across the content** until Alpine booted. Fixed with `data-open="false"` in the HTML plus a CSS rule. It could *not* be fixed by adding `-translate-x-full` to the static class list: Tailwind emits `translate-x-0` before `translate-x-full`, so the closed class would win the cascade permanently and the drawer could never open. |
| **Drawer, tab order** | A drawer moved off-screen by a `transform` is still focusable and still in the accessibility tree — a keyboard user on a phone tabbed through an invisible menu. `visibility: hidden` below `lg`, delayed by the 200ms animation so the slide-out still plays. |
| **Drawer, dismissal** | The backdrop was the only way to close it without navigating. Added a visible close button in the drawer header and `Escape`. |
| **Drawer, width** | `w-72` (288px) is 77% of a 375px screen. Capped at `max-w-[85vw]`, released at `lg`. |
| **User menu** | Was `align="left"` → `start-0`. The trigger sits at the row's inline **end**, so the 12rem panel opened *outward* and rendered off-screen at 375px — off the right in `ltr`, off the left in `rtl`. Now `end-0`, anchored to the same edge as its trigger. |
| **Top bar** | 44px targets on hamburger, locale toggle and avatar; `shrink-0` on the cluster and the avatar; the user name truncates inside `max-w-[12rem]`. |
| **Shell height** | `h-screen` (100vh) → CSS `height: 100vh; height: 100dvh`. On iOS Safari 100vh exceeds the visible viewport. Not done with `h-screen h-dvh` because **Tailwind emits `.h-dvh` before `.h-screen`**, which would have left the vh rule winning and the dvh class silently dead. |
| **Sideways scroll** | `overflow-x-hidden` on the scroll pane, `min-w-0` on the pane, heading band and `<main>` so a wide child scrolls inside its own container instead of setting the pane's width. |

## Modal — `components/modal.blade.php`

The backdrop used `bg-bg-soft0`, which is **not a real class** and
generated zero rules — verified against the built CSS. The overlay
rendered fully transparent, so the modal appeared to float over live,
still-clickable content. Now `bg-text-main`. The panel also gains
`w-full` below `sm` (it had no width class at all there), and the
confirm/cancel pair wraps.

## Data tables — 12 tables

`services · countries · faqs · articles · testimonials · comments ·
pages · page sections · leads · campaigns · lead sources · campaign
linked-leads`

Five to seven columns each. Horizontal scroll alone left the **actions
column — the only reason to open the page on a phone — permanently
off-screen behind a swipe**.

Below `md` each table now collapses to one card per row: the header is
visually hidden and every cell prints its own label from `data-label`,
with the actions cell getting a full-width footer row. Implemented in
CSS keyed on `data-label` rather than a mobile-only partial, so there is
no duplicated markup, no second query, and the desktop table keeps its
existing scroll container untouched.

The overrides win on **specificity** (0,1,1 against the 0,1,0 utilities
they replace: `px-6`, `whitespace-nowrap`, `text-end`), so the desktop
classes on each cell did not have to be stripped. Tailwind's `@layer` is
a build-time construct, not a native cascade layer, so ordinary
specificity decides.

**Deliberately left as horizontal scroll:** the *revenue by campaign*
table on Reports. Five short numeric columns whose whole point is
side-by-side comparison; stacking would destroy the comparison. Its
first cell is a `<th scope="row">`, which the stacked CSS does not
target.

## Touch targets — two tiers

- **Form controls** (`input`/`select`/`textarea`): 44px at every width.
  Close to what `@tailwindcss/forms` already produces, so no desktop cost.
- **Buttons and action links**: 44px only under
  `(max-width: 767px), (pointer: coarse)`. A table row carries three or
  four of these; forcing 44px on a mouse-driven desktop table would
  inflate every row for no accessibility gain.
- **Checkboxes**: the *label* is padded to 44px, not the box. Stretching
  a 16px checkbox knocks it out of alignment with its own text.
- **File inputs**: styled `::file-selector-button` at 44px — the native
  button is otherwise ~24px and unreachable on touch.

## Dashboard home

| Group | Before | After |
|---|---|---|
| Needs attention (4) | `1 / sm:2 / lg:4` | `1 / sm:2 / xl:4` |
| Content overview (6) | `1 / sm:2 / lg:3` | `1 / sm:2 / xl:3` |
| Leads overview (5) | `1 / sm:3` — no `lg` step at all | `1 / sm:2 / lg:3 / xl:5` |

Figures get `break-words`, `tabular-nums` and `text-2xl sm:text-3xl`:
`1,234,567.00` at `text-3xl` is wider than a two-up tile at 375px.

## Reports

- Funnel tiles `2 / lg:5` → `2 / sm:3 / xl:5`, values `text-xl sm:text-2xl`.
- **Every `<x-card>` on this page and on campaign show had no padding
  at all** — `x-card` carries none of its own and those eleven usages
  never added any, so content sat flush against the border. Now
  `p-4 sm:p-6`.
- **Spark chart**: the 90-day range packed 90 bars into ~340px — under
  3px each once the gaps are removed, which reads as noise. Bars hold a
  `min-w-[3px]` floor and the strip scrolls, with the axis labels inside
  the same scroll box so the end label stays over the last bar. The
  `sr-only` data table is unchanged.
- **Bar charts**: label `min-w-0 flex-1 truncate` with a `title`, value
  `shrink-0 tabular-nums` — a long source name can no longer push the
  number out of the row.

## Menu manager

The reorder row carries four 44px buttons (188px) plus an edit link and
two badges. Its label was `min-w-0 flex-1` — and **a `flex-1` item has
`flex-basis: 0`, so it contributes nothing to the wrap calculation**: it
never forced a wrap, it just shrank toward zero while the controls
stayed on the line. The label now takes the full row below `sm`
(`w-full sm:w-auto sm:flex-1`) and the four reorder controls stay
together as one `shrink-0` unit. Same treatment on the
visibility/delete list. Buttons were already 44px from the earlier
accessibility batch.

## Forms — all CRUD `_form`s, settings, tracking, profile

Already `grid-cols-1 md:grid-cols-2|3`, so single-column on mobile was
correct to begin with. Changed:

- Footers `mt-6 flex flex-wrap items-center justify-end gap-4`; the
  cancel link is now a real target rather than inline text.
- Checkbox groups `flex-wrap gap-x-6 gap-y-1` — two toggles side by side
  collided at 375px.
- Conversions form `1 / md:4` → `1 / sm:2 / xl:4`: at `lg` a
  `datetime-local` control had ~184px and needs ~180px.
- Tracking settings was already `flex-col sm:flex-row`; its toggle got
  the 44px label.
- Profile: password field `w-full sm:w-3/4`, confirm/cancel wrap.

## Detail pages — leads/show, campaigns/show

Definition-list rows had `shrink-0` on the `<dt>`, so a long Arabic
label (`هل يملك شركة خارجية؟`) kept its full width and squeezed the
value into a narrow ragged column. Now stacked label-over-value below
`sm`, side-by-side above. `break-all` retained for URLs.

## Page headers — 40 files

`flex items-center justify-between` with no wrapping crushed the action
button at 375px. Now wraps with `gap-3`; buttons are `shrink-0
whitespace-nowrap` at 44px; titles get `min-w-0 break-words` because
they interpolate records (a lead's name, a page title) whose unbroken
value would otherwise set the flex item's width.

## Media library

Grid `2 / md:4 / lg:6` → `2 / sm:3 / lg:4 / xl:6`.

## RTL / LTR

Everything uses logical properties (`ms-`/`me-`, `ps-`/`pe-`, `start-`/
`end-`, `inset-y-0 end-0`, `text-align: start`). The one direction-aware
pair is the drawer's `max-lg:rtl:translate-x-full` /
`max-lg:ltr:-translate-x-full`, which was already correct. Palette
unchanged; no indigo anywhere (verified).

---

## Worth eyeballing in a real browser

A headless suite can assert that the hooks are present; it cannot
measure a layout. These are the spots where I reasoned from the CSS
rather than observed the result:

1. **Stacked-table label column.** The `::before` label is
   `flex: 0 0 40%`. A long Arabic header (`تاريخ الاستلام`, `الخدمة
   المطلوبة`) may wrap to two lines inside that 40% at 375px. If it
   looks cramped, 45% is the knob — `resources/css/app.css`.
2. **Drawer slide-out.** The `visibility` delay is hardcoded to 200ms to
   match `duration-200`. If the animation ever changes, the drawer will
   vanish early or linger. Watch one close.
3. **Spark chart at 90 days on a phone.** It should scroll horizontally
   *inside its card* without the page moving. Confirm the axis labels
   still sit under the first and last bars after scrolling.
4. **Revenue-by-campaign table** (Reports, 375px) — the one table left
   scrolling. Confirm the swipe feels reasonable inside the padded card.
5. **`100dvh` on a real iOS device.** Simulators and desktop devtools do
   not reproduce the collapsing-toolbar behaviour this fixes.
6. **Reports/campaign cards now have padding** where they previously had
   none. This is a visible change to those pages at every width — worth
   a glance to confirm it reads as intended rather than as too much.
7. **Two toggles side by side** (services form: flagship + active) at
   exactly 375px — they should wrap onto separate lines, not sit tight.
