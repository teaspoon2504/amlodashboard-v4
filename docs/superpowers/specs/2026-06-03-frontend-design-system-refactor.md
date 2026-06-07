# AMLO Dashboard — Frontend Design-System Refactor

**Date:** 2026-06-03
**Status:** Approved (awaiting implementation)
**Source spec:** `../DESIGN.md`

## Context

The AMLO Dashboard is a PHP-based internal tool for AMLODashboard Kantor Wilayah, with 10 pages and 3 API endpoints. The frontend has a partial design-system implementation (`assets/css/amlo-design-system.css`, dark Meta variant) but suffers from:

- Duplicated `:root` token blocks in `dashboard.php` and inconsistent inline `<style>` blocks across pages
- No shared layout shell — every page duplicates `<head>`, topbar, sidebar include, scripts
- No component layer — cards, buttons, badges, tables are copy-pasted HTML in each page
- Empty `assets/js/` — interactivity lives in inline `<script>` blocks per page
- No theme flexibility — the dark Meta variant is the only visual identity

## Goals

Bring the frontend to a single, design-system-driven implementation aligned with `DESIGN.md` (Meta commerce design language), with a clean foundation that can be evolved and that supports both light and dark themes.

## Non-goals

- Backend changes (PHP API endpoints remain untouched)
- Database schema changes
- New features or behavior changes
- The static HTML reference at `../AMLO Dashboard — Monitoring Aktivitas Harian.html` (older prototype, not source of truth)
- Build tooling (no Node, no bundler — keep cPanel-friendly)
- Per-user server-side theme persistence (out of scope for this refactor; `localStorage` only)

## Architecture & file structure

### New and changed files

```
amlo-dashboard/
├── assets/
│   ├── css/
│   │   ├── amlo-design-system.css   ← REWRITE (theme tokens, full DESIGN.md component set)
│   │   └── fonts.css                  (unchanged)
│   └── js/
│       └── amlo.js                    ← NEW (theme toggle, modal, flash, tabs, form helpers)
├── includes/
│   ├── header.php                     ← NEW (<head>, no-FOUC bootstrap, topbar, theme toggle)
│   ├── footer.php                     ← NEW (closing tags, amlo.js include)
│   ├── sidebar.php                    ← UPDATED (theme-aware classes, drop duplicate :root)
│   ├── auth.php                       (unchanged)
│   ├── functions.php                  (unchanged)
│   └── components/                    ← NEW DIRECTORY
│       ├── kpi-card.php
│       ├── card.php
│       ├── button.php
│       ├── badge.php
│       ├── table.php
│       ├── modal.php
│       ├── form-input.php
│       ├── alert.php
│       └── promo-banner.php
└── pages/
    ├── login.php          ← REFACTORED
    ├── dashboard.php      ← REFACTORED (1314 lines; biggest diff, pause for review after)
    ├── tasks.php          ← REFACTORED
    ├── performa.php       ← REFACTORED
    ├── officers.php       ← REFACTORED
    ├── wilayah.php        ← REFACTORED
    ├── laporan.php        ← REFACTORED
    ├── assignments.php    ← REFACTORED
    ├── assessment.php     ← REFACTORED
    └── jobdesc.php        ← REFACTORED
```

## Token system

A single CSS file declares tokens under `:root` (default = dark, matches current visual identity) and `[data-theme="light"]` (Meta commerce light variant). The toggle writes `data-theme` on `<html>` and persists to `localStorage`.

### Coverage

Every existing CSS variable gets a light-theme value:

- **Surfaces:** `--canvas`, `--surface-soft`, `--surface-elevated`, `--hairline`, `--hairline-soft`
- **Ink hierarchy:** `--ink-deep`, `--ink`, `--charcoal`, `--slate`, `--steel`, `--stone`, `--disabled`
- **Brand & accent:** `--primary`, `--primary-deep`, `--primary-soft`, `--primary-ring`, `--fb-blue`, `--meta-link`, `--oculus-purple`
- **Semantic:** `--success`, `--attention`, `--warning`, `--critical` (and bg variants)
- **AMLO accents:** `--gold`, `--gold-soft`, `--teal`, `--teal-light` (light-theme values tuned for AA contrast on white)
- **Spacing & rounded scales:** unchanged (theme-agnostic)
- **Animation timing:** unchanged

### Theme bootstrap (no FOUC)

A tiny inline `<script>` in `includes/header.php` runs synchronously before the CSS link, reads `localStorage` and `prefers-color-scheme`, sets `data-theme` on `<html>`. Eliminates the flash of the wrong theme on first paint.

```html
<script>
  (function () {
    try {
      var stored = localStorage.getItem('amlo-theme');
      var theme = stored || (matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
      document.documentElement.setAttribute('data-theme', theme);
    } catch (e) { /* localStorage blocked — fall back to CSS :root default (dark) */ }
  })();
</script>
```

## Component helper inventory

Each helper in `includes/components/` is a PHP function that echoes markup. Pages call them with an array of options. All output is `e()`-escaped for XSS safety.

| Helper | Variants | Replaces |
|---|---|---|
| `kpi_card($opts)` | default, gold, green, teal, red, cobalt, purple | `.kpi-card` blocks in dashboard/performa |
| `card_start($opts)` / `card_end()` | header, title, action slot | `.card` chrome in every page |
| `button($opts)` | primary, primary-buy, secondary, ghost, pill-tab | ad-hoc `.btn` markup |
| `badge($opts)` | success, attention, warning, critical, muted | `.status-pill` and `.perf-badge` |
| `data_table($opts)` | thead + tbody rows | `.data-table`, `.perf-table` |
| `modal($id, $opts)` | id, title, body slot | inline modal markup in tasks/assignments |
| `text_input($opts)` | default, focused, error | raw `<input>` blocks |
| `alert($opts)` | success, error, info, warning | flash messages |
| `promo_banner($opts)` | default, warning variant | manual banner block |

### Signature style

```php
function kpi_card(array $opts): void {
    $variant = $opts['variant'] ?? '';
    $icon    = $opts['icon']    ?? '';
    $label   = $opts['label']   ?? '';
    $value   = $opts['value']   ?? '';
    $sub     = $opts['sub']     ?? '';
    $variant_class = $variant ? ' ' . e($variant) : '';
    echo '<div class="kpi-card' . $variant_class . '">';
    echo '  <div class="kpi-card-icon">' . e($icon) . '</div>';
    echo '  <div class="kpi-label">' . e($label) . '</div>';
    echo '  <div class="kpi-value">' . e($value) . '</div>';
    if ($sub) echo '<div class="kpi-sub">' . e($sub) . '</div>';
    echo '</div>';
}
```

Pages become declarative:

```php
<?php kpi_card(['variant' => 'gold', 'icon' => '🎯', 'label' => 'Target', 'value' => '120', 'sub' => '↑ 5%']); ?>
```

## JS layer — `assets/js/amlo.js`

Single vanilla JS file, IIFE-wrapped, no globals. Loaded once from `includes/footer.php`.

### API (markup-driven via `data-*` attributes)

- `[data-theme-toggle]` — theme switcher button
- `[data-modal-open="id"]` / `[data-modal-close="id"]` — modal control
- `[data-flash-autohide]` + `[data-flash-dismiss]` — flash auto-dismiss after 4s
- `[data-tabs]` group with `[data-tab="key"]` — tab switching
- `[data-confirm]` — form submit guard

### Theme behavior

- Default = dark; first-time visitors get OS preference (`prefers-color-scheme`); once toggled, the choice is stored in `localStorage['amlo-theme']`
- Cross-tab sync via `storage` event
- 200ms transition on the toggle button icon; page swap is instant (CSS recolors without layout thrash)

### Topbar toggle button

```php
<button type="button" class="theme-toggle" data-theme-toggle
        aria-pressed="false" aria-label="Switch to light theme">
  <span class="theme-toggle-icon" aria-hidden="true">☀️</span>
</button>
```

CSS swaps the icon and rotates a subtle 200ms transition on the button only.

## Migration plan

### Phase 1 — Foundation

| Step | Deliverable | Verification |
|---|---|---|
| 1.1 | Rewrite `assets/css/amlo-design-system.css` with theme tokens | Token-by-token diff against current CSS; all variables in both themes; light values hit AA on white |
| 1.2 | `includes/header.php` — `<head>`, no-FOUC bootstrap, topbar, theme toggle | Loads standalone; empty topbar renders with working toggle |
| 1.3 | `includes/footer.php` | Loads standalone; fires `DOMContentLoaded` |
| 1.4 | Update `includes/sidebar.php` — drop duplicate `:root`, theme-aware classes | Renders in both themes; active-state contrast holds |
| 1.5 | `includes/components/*` — all 9 helpers | Unit smoke test via `_dev/foundation.php` |
| 1.6 | `assets/js/amlo.js` | No console errors; toggle persists across reload and across tabs; modals open/close |
| 1.7 | **Foundation gate** — `_dev/foundation.php` checklist passes | Sign off before Phase 2 |

**Foundation gate (must pass before Phase 2):**

- [ ] All 9 helpers render correctly
- [ ] Both themes look right (visual review)
- [ ] Theme persists across reload
- [ ] No JS console errors
- [ ] No FOUC on first paint

### Phase 2 — Page migration (in order)

| Order | Page | Notes |
|---|---|---|
| 2.1 | `pages/login.php` | Smallest, no sidebar/topbar. Validates shell + form-input + alert. Risk: low. |
| 2.2 | `pages/dashboard.php` | **Largest diff (1314 lines, drops inline `:root` and ~600 lines of page-specific CSS). Pause for review after.** |
| 2.3 | `pages/tasks.php` | Forms, modal, badge/status pills, data table |
| 2.4 | `pages/performa.php` | KPI cards, perf-badges, data table |
| 2.5 | `pages/officers.php` | Data table + filters |
| 2.6 | `pages/wilayah.php` | Data table |
| 2.7 | `pages/laporan.php` | Tracking view |
| 2.8 | `pages/assignments.php` | Form + modal |
| 2.9 | `pages/assessment.php` | Form + feedback |
| 2.10 | `pages/jobdesc.php` | Reference page, mostly static |

### Per-page migration ritual

1. Replace page-specific `<style>` with the new shell (`require header`, body, `require footer`)
2. Replace hand-rolled card/button/badge/table markup with helper calls
3. Drop inline JS in favor of `data-*` attributes
4. Smoke-test in both themes + mobile width
5. Mark the page done in the migration checklist

## Verification (per page, must pass)

- [ ] Renders correctly in **dark** theme (default)
- [ ] Renders correctly in **light** theme
- [ ] **No inline `<style>`** with `:root` or duplicated design tokens remains
- [ ] **No inline `<script>`** for things now in `amlo.js`
- [ ] All status/badge values come from the `badge()` helper, not ad-hoc `<span>` styling
- [ ] Mobile (375px), tablet (768px), desktop (1280px) render without horizontal scroll
- [ ] Theme toggle still works and persists
- [ ] No new console errors

After the last page, run a final pass:

- Visual review of every page in both themes, side-by-side
- Remove `_dev/foundation.php`
- Confirm `assets/css/amlo-design-system.css` is the only source of design tokens
- Confirm no page-specific `:root` blocks remain anywhere

## Risk callouts

1. **`dashboard.php` is 1314 lines of inline CSS** — port reusable bits into the design system; drop the rest; rename to DESIGN.md-aligned class names (do not try to keep the old class names).
2. **AMLO gold + teal in light theme** — needs a contrast pass. Light values likely deepen gold (`#c8a84b` → `#a8862e`) and darken teal for AA on white. Step 1.1 includes this pass.
3. **Inline JS audit** — confirm no page is doing a critical job in inline JS that would silently get dropped in Step 1.6. Audit during the foundation phase.
4. **No build step** — keep CSS/JS hand-written, PHP includes only. cPanel-friendly.
5. **Static HTML reference is out of scope** — older prototype at `../AMLO Dashboard — Monitoring Aktivitas Harian.html`; the current `amlo-design-system.css` is the live visual identity.
6. **Theme persistence is client-side only** — `localStorage` doesn't sync across devices. A `theme_pref` column on the `users` table is a future enhancement, out of scope here.

## Out of scope

- The static HTML reference file (`../AMLO Dashboard — Monitoring Aktivitas Harian.html`)
- New pages or features
- Backend / API changes
- Build tooling (Node, bundlers, etc.)
- Database schema changes
- Server-side theme persistence per user
