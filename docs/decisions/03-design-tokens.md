# 03 — Design Tokens: Brand Colors & Typography

Date: 2026-08-03

## Colors

Added to `tailwind.config.js` under `theme.extend.colors`, available as
standard Tailwind utility classes (`bg-primary-green`, `text-luxury-gold`,
`border-border-default`, etc.):

| Token | Hex | Usage |
|---|---|---|
| `primary-green` | `#0B5D3B` | Primary brand color — buttons, links, accents |
| `dark-green` | `#073E2A` | Footer background, dark sections, hero overlay |
| `luxury-gold` | `#B8903A` | Secondary accent — highlights, secondary CTAs |
| `light-gold` | `#D4B76A` | Lighter gold accent — hover states, subtle highlights |
| `bg-soft` | `#F7F8F6` | Default page background (off-white, slightly green-tinted) |
| `text-main` | `#17211C` | Primary body text color |
| `text-secondary` | `#66706A` | Secondary/muted text (captions, metadata) |
| `border-default` | `#E5E9E6` | Default border color for cards, dividers, inputs |

These are additive to Tailwind's default palette (`gray-*`, `indigo-*`,
etc. are untouched) so existing Breeze-scaffolded dashboard views keep
working unchanged; new public-facing views should prefer the brand
tokens above over the generic Tailwind palette.

## Typography

**Font: IBM Plex Sans Arabic**, loaded via Bunny Fonts CDN
(`fonts.bunny.net`) — the same CDN Breeze's default layouts already used
for Figtree, so this keeps the existing `<link rel="preconnect">` /
`<link rel="stylesheet">` pattern rather than introducing a second
font-loading approach. Bunny Fonts is a GDPR-compliant, cookie-free
Google Fonts mirror; no self-hosted font files were added to the repo,
keeping the asset footprint small and updates automatic.

Registered as the default `font-sans` in `tailwind.config.js`:

```js
fontFamily: {
    sans: ['IBM Plex Sans Arabic', ...defaultTheme.fontFamily.sans],
},
```

IBM Plex Sans Arabic ships both Arabic and Latin glyph subsets from a
single family, so Arabic and Latin text share one consistent typeface
instead of falling back to a mismatched system font for Latin characters
— Tailwind's `defaultTheme.fontFamily.sans` (system UI stack) remains as
the final fallback if the CDN is unreachable.

Weights loaded: 400 (regular), 500 (medium), 600 (semibold), 700 (bold) —
covers body copy through headings without pulling the full variable-font
weight range.

## Where this is wired in

- `tailwind.config.js` — color tokens + font family.
- `resources/css/app.css` — `body` base styles (`bg-bg-soft text-text-main`)
  via a `@layer base` block, so the tokens apply globally without every
  view needing to repeat the classes.
- `resources/views/layouts/guest.blade.php`,
  `resources/views/layouts/app.blade.php`,
  `resources/views/layouts/public.blade.php` — the Bunny Fonts `<link>`
  tags (previously loading Figtree, now loading IBM Plex Sans Arabic).

## Not done in this task

- No dark mode variant of the token set.
- No public-facing Services/Countries/Articles pages restyled — this
  task only covers the homepage hero; dashboard CRUD screens
  intentionally keep Breeze's default styling per the earlier CRUD
  tasks' explicit scope (brand styling there is a separate future task).
