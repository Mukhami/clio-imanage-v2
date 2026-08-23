# MatterLynk Logo — Chain Link

Final mark, exported as true vector (SVG, type converted to outlines — no font install required to open or edit these files) plus ready-to-use PNG rasters.

## Files

**SVG (edit/scale these — they're the source of truth)**
- `matterlynk-icon-color.svg` — icon alone, full palette gradient, transparent background
- `matterlynk-icon-ink.svg` — icon alone, solid `#0c0636`, for single-color/print use
- `matterlynk-icon-white.svg` — icon alone, solid white, for dark or colored backgrounds
- `matterlynk-horizontal-light.svg` / `matterlynk-horizontal-dark.svg` — icon + wordmark, side by side
- `matterlynk-stacked-light.svg` / `matterlynk-stacked-dark.svg` — icon above wordmark, centered
- `matterlynk-appicon.svg` — icon on a rounded navy tile, for app icons / social profile images
- `matterlynk-favicon-flat.svg` — simplified flat-color version for tiny sizes (browser tabs)

**PNG** (in `/png`, transparent background except the app icon and favicon tiles) — pre-rendered at common sizes, plus `favicon.ico` (16/32/64px combined).

## Minimum size

- Chain Link icon: down to 16px (it's the one concept built for this)
- Horizontal/stacked lockups: don't go below ~120px wide — the wordmark starts to blur out before the icon does
- Below 16px or for single-color contexts (embossing, engraving, one-color print), use `matterlynk-favicon-flat.svg` or `matterlynk-icon-ink.svg`

## Clear space

Keep clear space around the mark equal to the height of one ring (roughly the icon's own height ÷ 2.7) on every side — don't let text or other UI touch the icon directly.

## Do

- Use `-color` on light or neutral backgrounds, `-white` or `-ink` when the gradient will lose contrast (very light or very dark, busy, or brand-colored backgrounds)
- Keep the gradient running left to right, indigo to lime, as drawn — it's the same 5-stop sweep as the rest of the MatterLynk color system
- Scale proportionally (all files preserve aspect ratio automatically if you just set one dimension)

## Don't

- Don't recolor the icon to a color outside the palette
- Don't rotate, skew, or flip the mark
- Don't place the full-color gradient version on a background it can't hold contrast against — use the ink or white flat version instead
- Don't restyle "MatterLynk" in a different typeface — the wordmark is Sora ExtraBold, converted to outlines in these files so it'll always render correctly even without the font installed

## Color reference

Gradient stops: `#0c0636 → #095169 → #059b9a → #53ba83 → #9fd96b`. Full system (ramps, semantic colors, component and chart guidance) is in `matterlynk-color-system.md`, delivered separately.
