# OpsVelo — Brand Direction

> **Status:** Direction document for the upcoming UI redesign. No pages are redesigned in this phase. Implementation comes after sign-off.
> **Audience:** Anyone touching OpsVelo UI — the public site, the airline dashboard, the iPad app (CrewAssist), sales decks, demo videos.
> **Foundation:** This builds on what already works in [`public/css/app.css`](../public/css/app.css), [`public/css/public.css`](../public/css/public.css), and the `sidebarIcon()` Heroicons helper in [`app/Helpers/functions.php`](../app/Helpers/functions.php). Don't reset; refine.

---

## 1. North Star

**OpsVelo is one operating system for airline operations.** Crew, flights, rosters, safety, manuals, devices, dispatch — all under one command surface.

The product replaces a thicket of paper, spreadsheets, WhatsApp threads, and disconnected vendor portals. The brand has to feel like that promise: **calm, precise, premium, command-grade**. Not a CRUD admin website. Not a startup dashboard with marketing fluff. Something a captain would trust on the line.

**Three words to design against:** *unified, precise, operational.*

If a design choice doesn't make the product feel more **unified** (one platform, not modules glued together), more **precise** (correct status, correct timestamp, correct authority), or more **operational** (built for the line, not the boardroom) — drop it.

---

## 2. Logo Concept

OpsVelo is a wordmark first. The mark exists to anchor the wordmark, never to lead.

### Wordmark

```
Ops One
```

- One word visually, two halves typographically: **Ops** carries the operational weight (slate / off-white), **One** carries the unifying accent (cyan).
- Tracking: `-0.02em` at large sizes, `0` at small sizes.
- Weight: **800** for hero/lockup uses, **700** for in-product header.
- Set in **Inter** (already loaded). No custom letterform.
- Casing: always **OpsVelo** (closed-up). Never *Ops One*, *OPSVELO*, or *opsOne*.

### Mark — three directions to choose from

The mark sits to the **left** of the wordmark, vertically centered to the cap height. Optical size: roughly equal to the cap-height of "O".

#### Direction A — Flight path node *(recommended)*

A short curved stroke representing a flight path, terminating in a solid filled node — the "one" point of command.

```
         ●          ← terminal node (cyan)
        ╱
      ╱            ← curved path stroke (slate)
    ╱
  ●                ← origin node (open / outlined)
```

- Reads as: many origins → one destination → one platform.
- 1.8px stroke, 24×24 viewBox, 4–6px node radius.
- Stroke uses `--text-primary`; terminal node uses `--accent-cyan` so the "one" colour rhymes with the wordmark.

#### Direction B — Command grid

A 2×2 dot grid with one dot lit — "many systems, one control point".

```
  ○   ○
        
  ○   ●     ← lit dot in cyan
```

- Cleanest at small sizes (favicon, app icon).
- Reads less aviation, more "ops platform".

#### Direction C — Wing chevron

An abstract upward chevron derived from an aircraft tail / wing dihedral angle.

```
  ╲   ╱
   ╲ ╱
    V         ← chevron, slate stroke + cyan inner stroke
```

- Most overtly aviation. Risks looking generic if drawn too literally — keep it geometric, not pictorial.

**Pick one direction and stick with it across all surfaces.** The current `✈` emoji in [`views/layouts/public.php:25`](../views/layouts/public.php) and the login card icon are placeholders to be replaced.

### Lockup rules

- Mark + wordmark sit on a single horizontal baseline; mark height ≤ wordmark cap height.
- Minimum size: wordmark at **14px** (with mark removed); full lockup at **20px** wordmark height.
- Clear space: at least one "O" of the wordmark on every side.
- App icon: **mark only**, on the deepest navy surface (`#050810`), with a 22% rounded square mask.

### Don't

- ❌ A literal aircraft silhouette (ATR, Q400, 737 outline) — dates fast and fights the abstract command-platform message.
- ❌ Globes, compasses, gauges, propellers, runway-numbered icons.
- ❌ Gradient inside the wordmark letters. Gradients live in the surface, not in type.
- ❌ Drop shadows on the mark.
- ❌ Tagline locked under the wordmark in the logo itself. Taglines belong in the layout, not the lockup.

---

## 3. Color Palette

**Dark-first.** OpsVelo is read on the line — cockpits, jump seats, crew rooms, night ops. The dashboard is dark by default and that decision is final until an airline buyer specifically requests light.

### Surfaces (dark mode — primary)

| Token | Hex | Use |
|---|---|---|
| `--bg-primary` | `#050810` | Outermost canvas, public site, login page |
| `--bg-secondary` | `#0a0f1e` / `#111827` | Section bands, sidebar |
| `--bg-card` | `#1a1f35` | Cards, modals, dropdowns |
| `--bg-card-hover` | `#222845` | Card hover, list-row hover |
| `--bg-input` | `#151b2e` | Input fields, code blocks |
| `--border-color` | `#2a3154` | All hairlines |
| `--border-glow` | `rgba(59,130,246,0.20)` | Hover/focus border on cards |

These are already defined and in use. **Don't introduce new background tokens** unless you can name the surface category they cover.

### Text

| Token | Hex | Contrast on `#1a1f35` | Use |
|---|---|---|---|
| `--text-primary` | `#e8eaf0` | 11.7:1 | Body, headlines, primary content |
| `--text-secondary` | `#8b95b0` | 5.9:1 | Labels, captions, helper text |
| `--text-tertiary` | `#7484a8` | 4.6:1 | Muted/auxiliary text. *Was `#5a6480` (2.9:1, fails AA) — fixed in Phase D.* |

### Brand accents

| Token | Hex | Role |
|---|---|---|
| `--accent-blue` | `#3b82f6` | Primary action, link, active state |
| `--accent-cyan` | `#06b6d4` | Secondary accent, "One" in lockup, gradient terminus |
| `--gradient-primary` | `linear-gradient(135deg, #3b82f6, #06b6d4)` | Hero CTAs, brand mark glow |

**Rule:** every screen has at most **one** primary action visible at a time. The blue → cyan gradient is for the most important CTA only (Request Demo, Save, Sign In). Secondary actions use ghost or outline styles.

### Operational status (the cockpit-light system)

These map to how a captain reads a system: green = nominal, amber = caution, red = warning. Use them in this order of priority and **don't repurpose them for decoration**.

| Token | Hex | Meaning | Examples |
|---|---|---|---|
| `--status-cleared` (= `--accent-green` `#10b981`) | Green | Nominal, approved, in-date, on-duty | "Active", "Approved", "Acknowledged" |
| `--status-advisory` (= `--accent-yellow` `#f59e0b`) | Amber | Action needed soon, expiring, pending review | "Expires in 30d", "Pending approval", "Review required" |
| `--status-critical` (= `--accent-red` `#ef4444`) | Red | Failure, expired, AOG, denied | "Expired", "Rejected", "AOG", "Failed login" |
| `--status-info` (= `--accent-blue` `#3b82f6`) | Blue | Neutral information, count, link | Counters, badges, "View details" |

**Don't use red for "delete" buttons**. Red is reserved for *operational* state. Use it for AOG, expired, rejected. Destructive actions should be a quiet ghost button with the red reserved for the confirm-on-modal step.

The currently-defined `--accent-purple` (`#8b5cf6`) stays available but should appear sparingly — reserve it for a single semantic role (recommendation: "data/insights" — analytics dashboards, FDM trends).

### Light mode (deferred)

A light mode is **not in scope** for the May launch. When a buyer asks, we'll spec it. Until then, do not author hex values that only exist in light mode.

### Public marketing site palette

The public site uses the same tokens but in [`public/css/public.css`](../public/css/public.css). Keep them in sync — divergence between the public hero and the in-product dashboard is the #1 risk to the "premium" feeling.

---

## 4. Typography

**One family: Inter.** It's already loaded everywhere. Adding a second display font fights for premium feel and bloats the page weight.

### Family

```css
font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
```

Loaded weights: **300, 400, 500, 600, 700, 800, 900**. Don't add more.

For codes, timestamps, ICAO identifiers, flight numbers, REG marks — switch to a monospace:

```css
font-family: 'JetBrains Mono', 'IBM Plex Mono', ui-monospace, 'SF Mono', monospace;
```

This is **new** — JetBrains Mono is not currently loaded. Add it only if/when needed for callouts (NOTAM-style readouts, code panels, log views).

### Type scale

| Role | Size | Weight | Line height | Letter spacing | Use |
|---|---|---|---|---|---|
| Display | `clamp(42px, 5vw, 64px)` | 900 | 1.05 | -0.04em | Public hero only |
| H1 | 32px | 800 | 1.15 | -0.03em | Page titles, public section heads |
| H2 | 24px | 700 | 1.2 | -0.02em | Card group titles, dialog titles |
| H3 | 18px | 700 | 1.3 | -0.01em | Card titles |
| Body | 14px | 400 | 1.6 | 0 | Default body, table cells |
| Body-lg | 16px | 400 | 1.65 | 0 | Public site body |
| Body-sm | 13px | 400 | 1.55 | 0 | Compact tables, sidebar links |
| Label | 12px | 600 | 1.4 | 0.06em (uppercase) | Stat labels, section heads |
| Eyebrow | 11px | 700 | 1 | 0.1em (uppercase) | Tier badges, group prefixes |
| Code | 13px | 500 | 1.5 | 0 | Codes, IDs, monospace data |

**Rules**

- **No font-size below 11px.** If you can't read the text, it doesn't belong on the screen.
- **No headline above 64px.** Premium feels confident, not loud.
- Body text is 14px in-product, 16px on the public marketing site. Don't unify them — the public site is read at arms-length, the dashboard at desk distance.
- Numerals: use `font-variant-numeric: tabular-nums` for stat values, table cells, and any column of numbers. Already partially applied — make it global on `.stat-value`, `td`, `.activity-time`.

### Premium-feel rules

- **Tighter is more premium.** Headlines below 24px with `letter-spacing: -0.01em` to `-0.04em`. Body text at 0.
- **Heavier than you think for headlines.** Display at 900, H1 at 800. Avoid 600 in headlines — it reads "blog post", not "platform".
- **Lighter than you think for tables.** 400 weight in `td`, 500 only for emphasis.
- Avoid italic. The product never asks for it.

---

## 5. Icon Style

OpsVelo icons are already correct in the dashboard — Heroicons outline, 1.6px stroke. **Codify, don't change.**

### Family

[Heroicons](https://heroicons.com) — outline variant only.

Already wired through `sidebarIcon($name, $size)` in [`app/Helpers/functions.php:321`](../app/Helpers/functions.php). 5 icons added in Phase A: `moon`, `arrow-down-tray`, `cloud-arrow-up`, `sparkles`, `globe`.

### Size scale

| Context | Size | Stroke | Notes |
|---|---|---|---|
| Inline body | 14px | 1.6 | Sits in a `<span>` aligned to text baseline |
| Sidebar / menu item | 18px | 1.6 | Default in `sidebarIcon()` |
| Stat card / feature card | 22px | 1.6 | Inside a 48×48 tinted square |
| Module grid (public) | 28px | 1.6 | Inside an icon container |
| Hero badge | 16px | 1.6 | With text |

**Stroke weight is locked at 1.6px** at any size. Heroicons ships at 1.5px native — we render slightly heavier for screen legibility on dark surfaces. Don't mix weights.

### Color rules

- Default icon color: `currentColor` (inherits from text).
- **Branded** (primary action, key metric): `var(--accent-blue)` or `var(--accent-cyan)`.
- **Status**: use the matching status color from the cockpit-light system above — never invent (e.g. don't make a "warning" icon orange-pink to be friendly; it's amber `#f59e0b`).

### Don't

- ❌ Mix outline and filled icons in the same view. Filled icons are reserved for the **single** active state in a list (e.g. the active sidebar item could swap to filled — currently we use a background highlight instead, which is fine).
- ❌ Use FontAwesome 4/5 (drops the precision look).
- ❌ Use emoji as an icon. Emoji render differently per OS, don't accept color tokens, and don't scale crisply. *(All public-site emoji were swapped to SVG in Phase A.)*
- ❌ Custom one-off SVGs unless the concept genuinely doesn't exist in Heroicons. If you draw a custom icon, match the 1.6px stroke and 24×24 viewBox.

### Aviation-specific icons we may need

Heroicons doesn't ship aviation-specific marks. Hold off on importing a second icon family until we identify a real need. Likely candidates if/when needed:
- Aircraft (paper-airplane is a placeholder — fine for now)
- Runway / taxiway
- Sectional / chart
- Headset
- Throttle
- Fuel pump

If we add them, **draw them in Heroicons style** (1.6px outline, 24×24 viewBox, `stroke-linecap: round`, `stroke-linejoin: round`) so they read as one family. Don't pull from a different aviation icon set with a different stroke style.

---

## 6. Product Visual Style

The "command center" feel comes from a small set of repeated moves. Use them; don't multiply them.

### Surfaces

- **Card**: `var(--bg-card)`, 1px border `var(--border-color)`, `border-radius: 14px` (large) or `10px` (medium), `padding: 24-32px`. Already canonical in [app.css:338](../public/css/app.css).
- **Stat card with top accent**: 3px top border colored by status (`var(--stat-color)`). Already in use. Keep.
- **Modal / dropdown**: same as card, `box-shadow: 0 24px 64px rgba(0,0,0,0.55)` for the deepest stack level.

### Shadows

| Token | Value | When |
|---|---|---|
| `--shadow-card` | `0 4px 24px rgba(0,0,0,0.30)` | Cards on hover |
| `--shadow-lg` | `0 8px 40px rgba(0,0,0,0.40)` | Stat cards on hover, dropdowns |
| `--shadow-glow` | `0 0 60px rgba(59,130,246,0.10)` | Hero device, focused brand element |
| `--shadow-modal` | `0 24px 64px rgba(0,0,0,0.55)` | Modal backdrop, command palette |

**Rule:** never apply two of these to the same element. One shadow per surface.

### Gradients

- **Surface gradient (subtle)**: a 135deg sweep at 5–10% alpha for the hero glow. Already in use in [`public.css`](../public/css/public.css) `.hero::before`. Fine.
- **Brand gradient**: `linear-gradient(135deg, #3b82f6, #06b6d4)`. **Buttons only**, plus the brand mark accent. Never apply to large surfaces.
- **Text gradient**: only the wordmark or a single hero word. Never on body copy. Already defined in `.gradient-text`.

### Glassmorphism

Allowed only at one level of subtlety: `backdrop-filter: blur(20px)` + `background: rgba(5,8,16,0.9)` on the sticky public navbar. Already implemented. Don't add a second glass layer on top.

### Whitespace

- Public marketing: **generous** — section padding 100px vertical, max-width 1200px, gutter 32px.
- Dashboard: **dense but breathable** — section gap 24px, card padding 24-32px, card-internal gap 16-20px.
- Don't centre-align long-form text. Centre is for hero blocks only.

### Device mockups

- **iPad**: real device frame (the `.ipad-frame` style added in Phase A) wrapping a real screenshot. No CSS-drawn fake screens.
- **Desktop / browser**: a clean rounded-corner container with a subtle top border. Don't render a fake macOS traffic-light row anymore (the old `.hero-screen-dot` pattern, now removed).
- **iPhone**: when needed, same approach — real frame, real screenshot.
- Screenshots: capture at native iPad resolution, dark mode, with realistic data (the demo airline tenant). No Lorem Ipsum, no placeholder names.

### Imagery

- Minimal. Aviation photography is risky — generic stock kills the premium feel faster than anything else.
- If used: dawn/dusk ramp, cockpit instruments at low light, aircraft tail close-ups. Avoid flight-attendant smiles, full-aircraft beauty shots, runway approach photos.
- Treat images at 70-80% opacity over the navy background so they read as ambient, not as content.

### Motion

- Default transition: `0.2s ease`. Already canonical.
- Hover lifts: `translateY(-2px)` to `translateY(-4px)`. Anything bigger feels gimmicky.
- Avoid: parallax, scroll-triggered animations, lottie loops in marketing, spinning logos.

---

## 7. Voice (brief)

Captain-grade. Direct, factual, no hype. Numbers over adjectives.

- ✅ "16 roles. 15 modules. Every action audited."
- ✅ "Crew see their full month at a glance."
- ❌ "Revolutionize your operations."
- ❌ "The world's most innovative airline platform."

Section labels in marketing use a single noun phrase ("Product Overview", "How Sync Works") — no leading bullet (`✦`) decoration. Already removed in Phase A.

---

## 8. The "Don't" List

Quick-reference for what kills the premium feel:

| ❌ Avoid | ✅ Instead |
|---|---|
| Cartoon airplane illustrations | Real iPad screenshots in real device frames |
| Emoji icons (🛡️ 🔔 ⚙️) | Heroicons outline SVGs at 1.6px stroke |
| Stock photography of smiling crew | Real product screens or no image |
| Centered long-form copy | Left-aligned blocks, max ~70ch |
| Section labels with `✦` or other ornaments | Plain uppercase eyebrow text |
| Multiple primary CTAs on one screen | One primary, the rest ghost/outline |
| Gradient body text | Gradient only on the wordmark and the single most important hero word |
| Drop shadow on every element | One shadow per surface, from the four-token system |
| Heavy borders (>1px) | 1px hairlines in `--border-color` |
| Mixed icon families | Heroicons outline only, 1.6px stroke |
| Pink, mint, lavender accents | Stick to the cockpit-light status system |
| `font-weight: 600` headlines | 700 minimum, 800 for H1, 900 for hero display |

---

## 9. Adoption — How This Maps to Existing Code

This direction is mostly **already compatible** with what's in the repo. Phases A–E moved the public site and dashboard substantially toward this brand. The redesign phase that follows will:

1. **Pick a logo direction** (A / B / C above) and produce the SVG mark, replacing the `✈` emoji in:
   - [`views/layouts/public.php:25`](../views/layouts/public.php) — public navbar
   - [`views/layouts/public.php:55`](../views/layouts/public.php) — public footer
   - [`views/auth/login.php`](../views/auth/login.php) — login card logo (use the mark, plus the airline logo when tenant context is present)
   - [`public/favicon.svg`](../public/favicon.svg) — replace
   - Sidebar brand block (search for `sidebar-brand-icon` in `partials/sidebar.php`)
2. **Status pills** — refactor existing `statusBadge()` in `app/Helpers/functions.php` so its color choices map to the cockpit-light system above. Today they're roughly correct but inconsistent.
3. **Buttons** — audit every button in the dashboard for the "one primary per view" rule. Likely several views have two filled blue buttons; demote one to ghost.
4. **Type scale** — codify the table in §4 as CSS custom properties in `app.css`:
   ```css
   --type-display: 64px / 900 / 1.05 / -0.04em;
   --type-h1: 32px / 800 / 1.15 / -0.03em;
   /* … */
   ```
   So redesign work doesn't drift back to ad-hoc sizes.
5. **Icon audit** — grep for any remaining emoji in `views/**/*.php` (the dashboard partial `header_bar.php` still has `🛡️` and `🔔` on the bell buttons — replace those next).
6. **Replace remaining device mockup placeholders** — the public hero now uses a real iPad. The login page and `/install-info` page still have CSS-drawn placeholder elements that should swap to real product shots once we have polished captures.

**What stays as-is, unchanged by this direction:**
- The Heroicons stroke and size system.
- Inter as the only typeface.
- The dark-only product theme.
- The 6-group sidebar from Phase C.
- The `--accent-*` color tokens.

This document is the spec the redesign phase implements against. If a redesign decision conflicts with what's written here, **update this doc first**, then build.
