# MatterLynk Color System

Reference palette for building the MatterLynk landing page, product dashboard, email notifications, and UI components (buttons, badges, charts). Every value below is derived from the five brand colors the client supplied — no color in this system was picked freehand, each is either one of the five source colors or a mathematically derived tint/shade/mix of them, so anything built from this doc will stay visually consistent with the approved **Chain Link** logo mark.

Built for: Sonnet‑class coding agents implementing UI. All values are hex, with RGB included for anything that needs it (email HTML, canvas/SVG, etc). Copy-paste CSS custom properties are at the bottom.

---

## 1. Brand core — the five source colors

These are the anchor colors. Nothing else in this system replaces them; everything else exists to give them room to work in text, borders, states, and data.

| Role | Name | Hex | RGB | Typical use |
|---|---|---|---|---|
| 01 | **Depth** | `#0c0636` | `rgb(12, 6, 54)` | Darkest brand color. Page background in dark mode, nav/header bars, footer bands, primary text on light surfaces. |
| 02 | **Structure** | `#095169` | `rgb(9, 81, 105)` | Secondary buttons, dividers/section bands, dark-teal UI chrome, secondary nav states. |
| 03 | **Core** | `#059b9a` | `rgb(5, 155, 154)` | Primary brand accent. Links, primary interactive elements, focus states, the color MatterLynk "is." |
| 04 | **Growth** | `#53ba83` | `rgb(83, 186, 131)` | Secondary accent. Doubles as the semantic **success** color (see §3). |
| 05 | **Signal** | `#9fd96b` | `rgb(159, 217, 107)` | Lightest, most saturated-feeling color. Use sparingly — highlight chips, "new" indicators, sparkline peaks, the lightest stop in gradients. Never as a body-text color; it fails contrast on both white and dark backgrounds (see §5). |

**Logo gradient recipe** (for hero sections, loading states, or anywhere you want to echo the mark): a single left-to-right linear gradient through all five stops in order — `#0c0636 → #095169 → #059b9a → #53ba83 → #9fd96b`. This is exactly what the Chain Link mark uses. Don't reorder the stops or drop one from the middle — the sequence is the brand signature, not just five nice colors.

---

## 2. Extended ramps (tints & shades)

Core, Depth, and Growth are the three colors that get reused constantly in UI (backgrounds, hover states, borders, chart fills), so each has a full 11-step ramp. Tints (50–400) are mixed toward white; shades (600–950) are mixed toward **Depth** (`#0c0636`) rather than plain black — this is deliberate, it means anything that goes "dark mode" using these shades still reads as MatterLynk-navy instead of generic charcoal.

### Core (teal) — primary interactive color

| Step | Hex | RGB |
|---|---|---|
| 50 | `#ebf7f7` | `235, 247, 247` |
| 100 | `#d7efef` | `215, 239, 239` |
| 200 | `#afdfdf` | `175, 223, 223` |
| 300 | `#87cfcf` | `135, 207, 207` |
| 400 | `#55bbba` | `85, 187, 186` |
| **500 (base)** | `#059b9a` | `5, 155, 154` |
| 600 | `#077a84` | `7, 122, 132` |
| 700 | `#085c70` | `8, 92, 112` |
| 800 | `#093f5c` | `9, 63, 92` |
| 900 | `#0b244a` | `11, 36, 74` |
| 950 | `#0b123e` | `11, 18, 62` |

### Depth (indigo) — darkest neutral / dark-mode base

| Step | Hex | RGB |
|---|---|---|
| 50 | `#ecebef` | `236, 235, 239` |
| 100 | `#d8d7df` | `216, 215, 223` |
| 200 | `#b1afbf` | `177, 175, 191` |
| 300 | `#8a879f` | `138, 135, 159` |
| 400 | `#5a5676` | `90, 86, 118` |
| **500 (base)** | `#0c0636` | `12, 6, 54` |
| 600 | `#09052a` | `9, 5, 42` |
| 700 | `#07031f` | `7, 3, 31` |
| 800 | `#050215` | `5, 2, 21` |
| 900 | `#02010b` | `2, 1, 11` |
| 950 | `#010004` | `1, 0, 4` |

### Growth (green) — secondary accent / success

| Step | Hex | RGB |
|---|---|---|
| 50 | `#f1f9f5` | `241, 249, 245` |
| 100 | `#e3f4eb` | `227, 244, 235` |
| 200 | `#c8e9d7` | `200, 233, 215` |
| 300 | `#acdec3` | `172, 222, 195` |
| 400 | `#8ad0ab` | `138, 208, 171` |
| **500 (base)** | `#53ba83` | `83, 186, 131` |
| 600 | `#439272` | `67, 146, 114` |
| 700 | `#356e63` | `53, 110, 99` |
| 800 | `#274a53` | `39, 74, 83` |
| 900 | `#1a2a45` | `26, 42, 69` |
| 950 | `#12143c` | `18, 20, 60` |

### Structure & Signal — light use, base + two derived tones each

These two are used more as accents than as interactive surfaces, so they get a lighter treatment instead of a full ramp:

| Token | Hex | Use |
|---|---|---|
| `structure-soft` | `#e6eef0` | Soft badge/card background paired with Structure or Depth text |
| `structure-base` | `#095169` | As in §1 |
| `structure-dark` | `#0a3355` | Structure darkened toward Depth — hover state for Structure buttons |
| `signal-soft` | `#f2faea` | Very light wash — subtle "new/highlight" section backgrounds |
| `signal-base` | `#9fd96b` | As in §1 |
| `signal-deep` | `#6c8f58` | Signal darkened toward Depth — the only version of Signal safe to use as text (see §5) |

---

## 3. Semantic colors (status, not brand)

Keep these separate from the brand accent conceptually, even though two of them borrow brand hues — a user should be able to tell "this is a status" from "this is a brand-colored button" at a glance, which is a matter of *where* you use them (badges, alerts, form validation) more than the hue itself.

| Semantic | Base | Solid/button-safe (white text) | Soft background | Notes |
|---|---|---|---|---|
| **Success** | `#53ba83` (= Growth-500) | `#356e63` (Growth-700) | `#f1f9f5` (Growth-50) | Reuses brand Growth — intentional, keeps success feeling on-brand rather than bolted-on. |
| **Warning** | `#eaa32a` | — *(see note)* | `#faebd1` | New hue, matched in saturation/lightness to the brand family so it doesn't look foreign. **White text never reaches AA on this color at any reasonable weight** — always pair Warning backgrounds with `depth-500` (`#0c0636`) text, never white. |
| **Error** | `#da4e3e` | `#c53526` | `#f8e0dd` | New hue. Use `error-base` for badges/borders with dark text or large text; use `error-700` for solid buttons with white text. |
| **Info** | `#059b9a` (= Core-500) | `#077a84` (Core-600) | `#ebf7f7` (Core-50) | Reuses brand Core — informational callouts can just be "teal." |

---

## 4. Neutrals

A cool, slightly indigo-tinted gray scale (hue-matched to Depth, not a generic gray) for text, borders, and surfaces that shouldn't compete with the brand colors.

| Step | Hex | RGB | Typical use |
|---|---|---|---|
| 50 | `#f9f9fb` | `249, 249, 251` | Page background (light mode) |
| 100 | `#f1f0f4` | `241, 240, 244` | Card/surface background (light mode) |
| 200 | `#e3e2e9` | `227, 226, 233` | Subtle borders, dividers |
| 300 | `#cccbd8` | `204, 203, 216` | Stronger borders, disabled control borders |
| 400 | `#aaa8bd` | `170, 168, 189` | Placeholder text, disabled text |
| 500 | `#8985a3` | `137, 133, 163` | Tertiary text, icon-only buttons |
| 600 | `#696586` | `105, 101, 134` | Secondary text (light mode) |
| 700 | `#4e4b63` | `78, 75, 99` | Secondary surfaces (dark mode) |
| 800 | `#323040` | `50, 48, 64` | Card/surface background (dark mode) |
| 900 | `#201f29` | `32, 31, 41` | Elevated dark-mode surface |
| 950 | `#121217` | `18, 18, 23` | — (rarely needed; Depth-950 is darker and more on-brand) |

In dark mode, prefer `depth-600`–`depth-900` over the neutral-800/900/950 steps where you want the UI to feel unmistakably "MatterLynk" (nav, sidebar, hero sections) — reach for plain neutrals only where you want something quieter than the brand ink (secondary cards, tooltips, code blocks).

---

## 5. Accessibility — verified contrast pairs

Computed against WCAG 2.1 (AA normal text = 4.5:1, AA large text/UI components = 3:1). Use this table instead of guessing — a couple of the "obvious" pairings below actually fail.

| Pair | Ratio | Passes |
|---|---|---|
| White text on `depth-500` | 19.18:1 | AA + AAA, any size |
| White text on `structure-500` | 8.78:1 | AA + AAA, any size |
| White text on `core-500` | 3.41:1 | **Fails AA for normal text.** Large text / icons / borders only. Use `core-600` for buttons. |
| White text on `core-600` | 5.09:1 | AA normal text |
| White text on `core-700` | 7.57:1 | AA + AAA |
| White text on `growth-500` | 2.41:1 | **Fails.** Don't put white text on base Growth. |
| White text on `growth-700` | 5.90:1 | AA normal text |
| `depth-500` text on `signal-500` | 11.52:1 | AA + AAA — this is the correct way to use Signal with text |
| White text on `signal-500` | 1.67:1 | **Fails badly.** Never use white text on Signal. |
| `depth-500` text on `warning-base` | 8.92:1 | AA + AAA — always use dark text on Warning |
| White text on `warning-base` | 2.15:1 | **Fails.** |
| White text on `error-base` | 4.09:1 | Borderline — fails strict AA normal text (needs 4.5). Use `error-700` for buttons. |
| White text on `error-700` | 5.37:1 | AA normal text |
| `neutral-600` on white | 5.52:1 | AA normal text — safe as secondary body text |
| `neutral-400` on `depth-500` | 8.04:1 | AA — safe secondary text in dark mode |

**Rule of thumb:** Core, Growth, and Signal at their *base* (500) value are accent/decorative colors, not text-on-color colors. When you need a filled button or solid badge with white text, drop one or two steps into the ramp (600 or 700) rather than using the base swatch.

---

## 6. Component guidance

### Buttons

| Variant | Background | Text | Hover | Notes |
|---|---|---|---|---|
| Primary | `core-600` `#077a84` | white | `core-700` `#085c70` | Main CTA everywhere |
| Secondary (filled) | `structure-500` `#095169` | white | `structure-dark` `#0a3355` | |
| Secondary (outline) | transparent, 1.5px `core-500` border | `core-700` | bg fills `core-50` | Good on light surfaces |
| Tertiary / ghost | transparent | `depth-500` (light) / `neutral-100` (dark) | bg `neutral-100` (light) / `depth-600` (dark) | |
| Destructive | `error-700` `#c53526` | white | darken ~8% | Confirm-delete, irreversible actions |
| Disabled | `neutral-200` | `neutral-400` | — | Same in both themes, never brand-colored |
| Focus ring (all variants) | 2px `core-400` `#55bbba` outline, 2px offset | — | — | Keep visible — don't remove focus outlines |

### Badges / status pills

Pair a `-soft` background with the matching `-700`/dark-text version of the same hue, or the base color with `depth-500` text if the base is already light enough (Growth, Signal). Rounded pill (`border-radius: 999px`), 12–13px medium-weight label, no border needed on light surfaces.

- **Success:** bg `growth-50` `#f1f9f5`, text `growth-700` `#356e63`
- **Warning:** bg `warning-soft` `#faebd1`, text `depth-500` `#0c0636`
- **Error:** bg `error-soft` `#f8e0dd`, text `error-700` `#c53526`
- **Info / neutral status:** bg `core-50` `#ebf7f7`, text `core-700` `#085c70`
- **Signal / "new" tag:** bg `signal-soft` `#f2faea`, text `signal-deep` `#6c8f58`

### Dashboard surfaces

| Token | Light mode | Dark mode |
|---|---|---|
| Page background | `neutral-50` `#f9f9fb` | `depth-700` `#07031f` |
| Card / panel background | `#ffffff` | `depth-600` `#09052a` |
| Elevated card (modal, popover) | `#ffffff` + shadow | `depth-500` `#0c0636` + lighter border |
| Sidebar / nav background | `depth-500` `#0c0636` | `depth-800` `#050215` |
| Border, subtle | `neutral-200` `#e3e2e9` | `depth-400` `#5a5676` at 30% opacity |
| Border, strong | `neutral-300` `#cccbd8` | `depth-300` `#8a879f` at 30% opacity |
| Text, primary | `depth-500` `#0c0636` | `neutral-50` `#f9f9fb` |
| Text, secondary | `neutral-600` `#696586` | `neutral-400` `#aaa8bd` |
| Active nav item | `core-500` left-border/underline + `core-50` bg wash | `core-400` left-border/underline + `depth-400` @12% bg wash |

### Forms & inputs

Border `neutral-300` (light) / `depth-400` @30% (dark) at rest; on focus, border → `core-500` with the same 2px `core-400` focus ring used on buttons. Error state border → `error-base`, helper text → `error-700`.

---

## 7. Data visualization

The five brand colors sit on a *continuous hue ramp* (indigo → teal → green → lime), which makes them naturally suited to **sequential/ordered** data and less suited to **unordered categorical** comparisons, where you want maximum perceptual distance between neighbors, not a smooth gradient. Use them differently depending on which kind of chart you're building.

**Sequential (single-metric gradients, heatmaps, progress-by-stage):**
Use the five stops in order — `depth-500 → structure-500 → core-500 → growth-500 → signal-500`. For finer-grained heatmaps, interpolate between adjacent stops rather than jumping straight from Depth to Signal.

**Diverging (variance, before/after, positive vs. negative change):**
`error-600` (`#d63a29`) at the negative end → `neutral-200` (`#e3e2e9`) at the midpoint → `core-500` (`#059b9a`) at the positive end. Don't use Growth for this — it's too close to Core in hue and the two ends won't read as distinct enough at a glance.

**Categorical / qualitative (distinct series, e.g. a legend with 4–6 unrelated lines):**
Don't use all five brand colors back to back — adjacent ones (Structure/Core, Growth/Signal) are too close in hue to tell apart in a small multi-line chart or a legend swatch. Instead, skip every other step and reorder for maximum contrast:

1. `core-500` `#059b9a`
2. `signal-deep` `#6c8f58` *(darkened Signal — the raw `#9fd96b` is too light against white backgrounds)*
3. `depth-400` `#5a5676`
4. `warning-base` `#eaa32a`
5. `structure-500` `#095169`

If a chart genuinely needs 6 or 7 series, extend with these two off-ramp hues (matched to the palette's saturation/lightness so they don't look bolted on): `error-base` `#da4e3e` and `violet-ext` `#a246b9`.

**Chart chrome:** gridlines `neutral-200` (light) / `depth-400` @15% (dark); axis labels `neutral-500`; tooltip background `depth-500` with white text regardless of site theme (tooltips read best as a fixed dark chip).

---

## 8. Landing page

- **Hero:** the five-stop brand gradient (§1) works best as a background wash behind dark text/UI on the left portion (over Depth/Structure) transitioning to needing light text on the right (over Core/Growth/Signal) — if headline text needs to sit anywhere on the gradient, keep it inside the Depth→Structure two-thirds rather than centering it, or drop a `depth-500 @70%` scrim under the text.
- **Section alternation:** alternate `#ffffff` and `neutral-50` (or `core-50` for a section you want to feel like a "highlight" section) rather than introducing new background colors.
- **Primary CTA:** `core-600`, white text, per §6.
- **Feature icons:** fine to use base-500 brand colors here (Core, Growth, Structure) since icons at that size are decorative, not text-contrast-critical.

## 9. Email notifications

Email clients (Outlook desktop especially) don't render CSS gradients, CSS variables, or `box-shadow` reliably — **use flat hex values inlined as `style="background-color:#..."`, never gradients or `var()`.** Also assume some clients will auto-apply a dark-mode filter that can wash out light colors, so favor higher-contrast pairs even where the web app could get away with something softer.

| Element | Background | Text |
|---|---|---|
| Header band | `depth-500` `#0c0636` | white logo/wordmark |
| Body | `#ffffff` | `depth-500` `#0c0636` |
| CTA button | `core-600` `#077a84` | white, bold |
| Secondary link | — | `core-700` `#085c70`, underlined |
| Footer band | `neutral-50` `#f9f9fb` | `neutral-600` `#696586` |
| Divider rule | `neutral-200` `#e3e2e9` | — |

Avoid `signal` and `growth`-base as text or button colors in email entirely — the contrast margins are too thin to survive a client that shifts colors slightly, and there's no live CSS to correct it after the fact the way there is on the web.

---

## 10. Ready-to-use tokens

Drop into a global stylesheet. Light values live on `:root`; dark values are duplicated under both the `prefers-color-scheme` media query and an explicit `[data-theme="dark"]` attribute so a manual theme toggle overrides the OS setting correctly.

```css
:root {
  /* brand core */
  --ml-depth: #0c0636;
  --ml-structure: #095169;
  --ml-core: #059b9a;
  --ml-growth: #53ba83;
  --ml-signal: #9fd96b;

  /* core ramp */
  --ml-core-50: #ebf7f7;  --ml-core-100: #d7efef; --ml-core-200: #afdfdf;
  --ml-core-300: #87cfcf; --ml-core-400: #55bbba; --ml-core-500: #059b9a;
  --ml-core-600: #077a84; --ml-core-700: #085c70; --ml-core-800: #093f5c;
  --ml-core-900: #0b244a; --ml-core-950: #0b123e;

  /* depth ramp */
  --ml-depth-50: #ecebef;  --ml-depth-100: #d8d7df; --ml-depth-200: #b1afbf;
  --ml-depth-300: #8a879f; --ml-depth-400: #5a5676; --ml-depth-500: #0c0636;
  --ml-depth-600: #09052a; --ml-depth-700: #07031f; --ml-depth-800: #050215;
  --ml-depth-900: #02010b; --ml-depth-950: #010004;

  /* growth ramp */
  --ml-growth-50: #f1f9f5;  --ml-growth-100: #e3f4eb; --ml-growth-200: #c8e9d7;
  --ml-growth-300: #acdec3; --ml-growth-400: #8ad0ab; --ml-growth-500: #53ba83;
  --ml-growth-600: #439272; --ml-growth-700: #356e63; --ml-growth-800: #274a53;
  --ml-growth-900: #1a2a45; --ml-growth-950: #12143c;

  --ml-structure-soft: #e6eef0;
  --ml-structure-dark: #0a3355;
  --ml-signal-soft: #f2faea;
  --ml-signal-deep: #6c8f58;

  /* semantic */
  --ml-success: var(--ml-growth-500);
  --ml-success-strong: var(--ml-growth-700);
  --ml-success-soft: var(--ml-growth-50);
  --ml-warning: #eaa32a;
  --ml-warning-soft: #faebd1;
  --ml-error: #da4e3e;
  --ml-error-strong: #c53526;
  --ml-error-soft: #f8e0dd;
  --ml-info: var(--ml-core-500);
  --ml-info-strong: var(--ml-core-600);
  --ml-info-soft: var(--ml-core-50);

  /* neutrals */
  --ml-neutral-50: #f9f9fb;  --ml-neutral-100: #f1f0f4; --ml-neutral-200: #e3e2e9;
  --ml-neutral-300: #cccbd8; --ml-neutral-400: #aaa8bd; --ml-neutral-500: #8985a3;
  --ml-neutral-600: #696586; --ml-neutral-700: #4e4b63; --ml-neutral-800: #323040;
  --ml-neutral-900: #201f29; --ml-neutral-950: #121217;

  /* semantic UI roles (light) */
  --bg-page: var(--ml-neutral-50);
  --bg-surface: #ffffff;
  --bg-sidebar: var(--ml-depth-500);
  --border-subtle: var(--ml-neutral-200);
  --border-strong: var(--ml-neutral-300);
  --text-primary: var(--ml-depth-500);
  --text-secondary: var(--ml-neutral-600);
  --text-on-accent: #ffffff;
  --accent: var(--ml-core-600);
  --accent-hover: var(--ml-core-700);
  --focus-ring: var(--ml-core-400);
}

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --bg-page: var(--ml-depth-700);
    --bg-surface: var(--ml-depth-600);
    --bg-sidebar: var(--ml-depth-800);
    --border-subtle: rgba(90, 86, 118, 0.3);
    --border-strong: rgba(138, 135, 159, 0.3);
    --text-primary: var(--ml-neutral-50);
    --text-secondary: var(--ml-neutral-400);
    --text-on-accent: #ffffff;
    --accent: var(--ml-core-400);
    --accent-hover: var(--ml-core-300);
    --focus-ring: var(--ml-core-400);
  }
}

:root[data-theme="dark"] {
  --bg-page: var(--ml-depth-700);
  --bg-surface: var(--ml-depth-600);
  --bg-sidebar: var(--ml-depth-800);
  --border-subtle: rgba(90, 86, 118, 0.3);
  --border-strong: rgba(138, 135, 159, 0.3);
  --text-primary: var(--ml-neutral-50);
  --text-secondary: var(--ml-neutral-400);
  --text-on-accent: #ffffff;
  --accent: var(--ml-core-400);
  --accent-hover: var(--ml-core-300);
  --focus-ring: var(--ml-core-400);
}
```

**Tailwind (if the project uses it)** — drop into `theme.extend.colors`:

```js
colors: {
  depth:     { 50:'#ecebef',100:'#d8d7df',200:'#b1afbf',300:'#8a879f',400:'#5a5676',500:'#0c0636',600:'#09052a',700:'#07031f',800:'#050215',900:'#02010b',950:'#010004' },
  structure: { DEFAULT:'#095169', soft:'#e6eef0', dark:'#0a3355' },
  core:      { 50:'#ebf7f7',100:'#d7efef',200:'#afdfdf',300:'#87cfcf',400:'#55bbba',500:'#059b9a',600:'#077a84',700:'#085c70',800:'#093f5c',900:'#0b244a',950:'#0b123e' },
  growth:    { 50:'#f1f9f5',100:'#e3f4eb',200:'#c8e9d7',300:'#acdec3',400:'#8ad0ab',500:'#53ba83',600:'#439272',700:'#356e63',800:'#274a53',900:'#1a2a45',950:'#12143c' },
  signal:    { DEFAULT:'#9fd96b', soft:'#f2faea', deep:'#6c8f58' },
  success:   { DEFAULT:'#53ba83', strong:'#356e63', soft:'#f1f9f5' },
  warning:   { DEFAULT:'#eaa32a', soft:'#faebd1' },
  error:     { DEFAULT:'#da4e3e', strong:'#c53526', soft:'#f8e0dd' },
  info:      { DEFAULT:'#059b9a', strong:'#077a84', soft:'#ebf7f7' },
  violetExt: '#a246b9',
}
```

---

## Appendix — where each color came from

`#0c0636`, `#095169`, `#059b9a`, `#53ba83`, `#9fd96b` are the five colors sampled directly from the palette reference supplied for the MatterLynk mark, in the order given (left to right). Every other value in this document is a computed tint, shade, or contrast-adjusted derivative of those five — tints mix toward white, shades mix toward `#0c0636` (not black), and the two semantic hues without a brand equivalent (`warning`, `error`) were generated to match the source palette's saturation and lightness range rather than picked from a generic UI-kit swatch. `violet-ext` is the one fully independent hue in the system, included only as a last-resort 7th categorical color for charts.
