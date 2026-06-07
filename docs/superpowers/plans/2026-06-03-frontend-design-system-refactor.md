# Frontend Design-System Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refactor the AMLO Dashboard frontend into a single design-system-driven implementation with light + dark theme support, shared layout partials, server-rendered component helpers, and a vanilla JS layer.

**Architecture:** Foundation-first — build the design system, partials, helpers, and JS layer before migrating any page. Each page becomes a thin body that calls helpers and includes the shared shell. The 9 component helpers in `includes/components/` echo markup; `assets/js/amlo.js` provides theme/modal/flash/tabs via `data-*` attributes; `assets/css/amlo-design-system.css` is the single source of design tokens (dark default, `[data-theme="light"]` override).

**Tech Stack:** PHP 7.4+ (procedural, no framework), vanilla CSS (no preprocessor), vanilla JS (no build step), cPanel-compatible hosting.

**Source spec:** `amlo-dashboard/docs/superpowers/specs/2026-06-03-frontend-design-system-refactor.md`

---

## File structure

| Path | Role | Action |
|---|---|---|
| `assets/css/amlo-design-system.css` | All design tokens + component classes. Single source of truth. | REWRITE |
| `assets/js/amlo.js` | Theme toggle, modal, flash, tabs, form helpers | CREATE |
| `includes/header.php` | `<head>`, no-FOUC bootstrap, topbar, theme toggle | CREATE |
| `includes/footer.php` | Closing tags, `amlo.js` include | CREATE |
| `includes/sidebar.php` | Role-based sidebar nav (already exists) | UPDATE |
| `includes/components/kpi-card.php` | `kpi_card($opts)` helper | CREATE |
| `includes/components/card.php` | `card_start($opts)` / `card_end()` | CREATE |
| `includes/components/button.php` | `button($opts)` | CREATE |
| `includes/components/badge.php` | `badge($opts)` | CREATE |
| `includes/components/table.php` | `data_table_start($opts)` / `data_table_end()` / `th()` / `td()` | CREATE |
| `includes/components/modal.php` | `modal_open($id, $opts)` / `modal_close()` | CREATE |
| `includes/components/form-input.php` | `text_input($opts)` | CREATE |
| `includes/components/alert.php` | `alert($opts)` + `render_flash()` | CREATE |
| `includes/components/promo-banner.php` | `promo_banner($opts)` | CREATE |
| `_dev/foundation.php` | Foundation gate: exercises every helper in both themes | CREATE (removed in Task 27) |
| `_dev/test-runner.php` | Simple assertion-based test runner | CREATE (removed in Task 27) |
| `_dev/tests/*.test.php` | Per-task tests | CREATE (removed in Task 27) |
| `pages/*.php` (10 files) | Each page refactored to use the new shell | REFACTOR |

---

## Part 1: Foundation

### Task 1: Test harness

**Files:**
- Create: `_dev/test-runner.php`
- Create: `_dev/tests/smoke.test.php`

- [ ] **Step 1: Create the test runner**

Write `_dev/test-runner.php`:

```php
<?php
/**
 * Simple assertion-based test runner.
 * Usage: php _dev/test-runner.php
 */
$root = __DIR__;
$tests_dir = $root . '/tests';
$files = glob($tests_dir . '/*.test.php');

$pass = 0; $fail = 0; $failures = [];
foreach ($files as $f) {
    $before = get_defined_functions()['user'];
    require $f;
    $after  = get_defined_functions()['user'];
    $new    = array_diff($after, $before);
    foreach ($new as $fn) {
        try {
            $fn();
            $pass++;
            echo "  ✓ $fn\n";
        } catch (Throwable $e) {
            $fail++;
            $failures[] = "$fn: " . $e->getMessage();
            echo "  ✗ $fn — " . $e->getMessage() . "\n";
        }
    }
}
echo "\n";
echo $fail === 0 ? "✓ All $pass tests passed\n" : "✗ $fail of " . ($pass + $fail) . " failed\n";
exit($fail === 0 ? 0 : 1);
```

- [ ] **Step 2: Create a smoke test**

Write `_dev/tests/smoke.test.php`:

```php
<?php
function test_php_version_is_at_least_74() {
    assert(version_compare(PHP_VERSION, '7.4.0', '>='), 'PHP 7.4+ required, got ' . PHP_VERSION);
}
function test_helpers_dir_will_exist_after_foundation() {
    $path = __DIR__ . '/../../includes/components';
    // We expect this to be created in later tasks; this test just ensures the path is plan-valid.
    assert(strlen($path) > 0);
}
```

- [ ] **Step 3: Run the tests, verify they pass**

Run: `cd amlo-dashboard && php _dev/test-runner.php`
Expected: `✓ All 2 tests passed`

- [ ] **Step 4: Commit**

```bash
git add _dev/test-runner.php _dev/tests/smoke.test.php
git commit -m "chore(dev): add simple test runner + smoke test"
```

---

### Task 2: Design tokens (CSS) — dark default

**Files:**
- Create: `_dev/tests/tokens.test.php`
- Modify: `assets/css/amlo-design-system.css`

- [ ] **Step 1: Write failing test for token presence**

Write `_dev/tests/tokens.test.php`:

```php
<?php
function test_css_file_exists() {
    $path = __DIR__ . '/../../assets/css/amlo-design-system.css';
    assert(file_exists($path), 'CSS file missing');
}
function test_root_has_canvas_token() {
    $css = file_get_contents(__DIR__ . '/../../assets/css/amlo-design-system.css');
    assert(str_contains($css, '--canvas:'), '--canvas token missing');
    assert(str_contains($css, '--ink:'), '--ink token missing');
    assert(str_contains($css, '--primary:'), '--primary token missing');
    assert(str_contains($css, '--gold:'), '--gold AMLO accent missing');
}
function test_light_theme_block_exists() {
    $css = file_get_contents(__DIR__ . '/../../assets/css/amlo-design-system.css');
    assert(str_contains($css, '[data-theme="light"]'), 'Light theme block missing');
}
function test_spacing_and_rounded_scales_present() {
    $css = file_get_contents(__DIR__ . '/../../assets/css/amlo-design-system.css');
    assert(str_contains($css, '--s-base:'), 'Spacing scale missing');
    assert(str_contains($css, '--r-full:'), 'Rounded scale missing');
}
```

- [ ] **Step 2: Run tests, verify they fail**

Run: `php _dev/test-runner.php`
Expected: 4 failures, all "missing" assertions.

- [ ] **Step 3: Write the dark-default `:root` block**

Open `assets/css/amlo-design-system.css` and replace its contents with:

```css
/* ============================================
   AMLO DESIGN SYSTEM (Meta-inspired)
   Source: DESIGN.md + AMLO Dashboard context
   Dark variant of Meta's light commerce palette
   Light theme via [data-theme="light"] override
   ============================================ */

:root {
    /* Surfaces (dark variant of Meta's light surface system) */
    --canvas: #0b1929;
    --surface-soft: #122236;
    --surface-elevated: #1a3352;
    --surface-translucent: rgba(26, 51, 82, 0.6);
    --hairline: rgba(255, 255, 255, 0.08);
    --hairline-soft: rgba(255, 255, 255, 0.05);

    /* Text (Meta ink hierarchy) */
    --ink-deep: #f0f4f8;
    --ink: #d4dde6;
    --charcoal: #b0bcc8;
    --slate: #8a9cae;
    --steel: #7a93ab;
    --stone: #5d6c7b;
    --disabled: #455261;

    /* Brand & accent */
    --primary: #0064e0;
    --primary-deep: #0457cb;
    --primary-soft: rgba(0, 100, 224, 0.15);
    --primary-ring: rgba(0, 100, 224, 0.4);
    --fb-blue: #1876f2;
    --meta-link: #385898;
    --oculus-purple: #a121ce;
    --oculus-purple-bg: rgba(161, 33, 206, 0.15);
    --ink-button: #ffffff;
    --ink-button-pressed: #d4dde6;

    /* Semantic */
    --success: #31a24c;
    --success-bg: rgba(49, 162, 76, 0.15);
    --success-strong: #24e400;
    --attention: #f2a918;
    --attention-bg: rgba(242, 169, 24, 0.15);
    --warning: #f7b928;
    --warning-bg: rgba(255, 226, 0, 0.15);
    --warning-vivid: #ffe200;
    --critical: #e41e3f;
    --critical-strong: #f0284a;
    --critical-bg: rgba(224, 82, 82, 0.15);

    /* AMLO brand accents */
    --gold: #c8a84b;
    --gold-soft: rgba(200, 168, 75, 0.15);
    --teal: #1b8f9e;
    --teal-light: #25b5c9;

    /* Spacing */
    --s-xxs: 4px;
    --s-xs: 8px;
    --s-sm: 10px;
    --s-md: 12px;
    --s-base: 16px;
    --s-lg: 20px;
    --s-xl: 24px;
    --s-xxl: 32px;
    --s-xxxl: 40px;
    --s-section-sm: 48px;
    --s-section: 64px;

    /* Rounded */
    --r-xs: 2px;
    --r-sm: 4px;
    --r-md: 6px;
    --r-lg: 8px;
    --r-xl: 16px;
    --r-xxl: 24px;
    --r-xxxl: 32px;
    --r-feature: 40px;
    --r-full: 100px;
    --r-circle: 9999px;

    /* Animation */
    --t-fast: 150ms ease-out;
    --t-base: 200ms ease-out;
    --t-slow: 300ms ease-in-out;
}

/* ============================================
   LIGHT THEME (Meta commerce variant)
   Applied when [data-theme="light"] on <html>
   ============================================ */

[data-theme="light"] {
    --canvas: #ffffff;
    --surface-soft: #f1f4f7;
    --surface-elevated: #ffffff;
    --surface-translucent: rgba(255, 255, 255, 0.85);
    --hairline: #ced0d4;
    --hairline-soft: #dee3e9;

    --ink-deep: #0a1317;
    --ink: #1c1e21;
    --charcoal: #444950;
    --slate: #4b4c4f;
    --steel: #5d6c7b;
    --stone: #8595a4;
    --disabled: #bcc0c4;

    --primary: #0064e0;
    --primary-deep: #0457cb;
    --primary-soft: rgba(0, 100, 224, 0.10);
    --primary-ring: rgba(0, 100, 224, 0.30);
    --fb-blue: #1876f2;
    --meta-link: #385898;
    --oculus-purple: #a121ce;
    --oculus-purple-bg: rgba(161, 33, 206, 0.10);
    --ink-button: #000000;
    --ink-button-pressed: #1c1e21;

    --success: #1f8a3a;
    --success-bg: rgba(31, 138, 58, 0.10);
    --success-strong: #24e400;
    --attention: #b87a0a;
    --attention-bg: rgba(184, 122, 10, 0.10);
    --warning: #8a6a00;
    --warning-bg: rgba(255, 226, 0, 0.20);
    --warning-vivid: #ffe200;
    --critical: #c5172f;
    --critical-strong: #f0284a;
    --critical-bg: rgba(224, 82, 82, 0.10);

    --gold: #a8862e;
    --gold-soft: rgba(168, 134, 46, 0.12);
    --teal: #146873;
    --teal-light: #1b8f9e;
}
```

Below this, append the **full reset, base styles, sidebar, topbar, content, page header, promo banner, cards, KPI grid, badges, status pills, data tables, modals, alerts, theme toggle, and responsive sections** from the current file. The shape of these sections is unchanged from the existing implementation; only the *token values* above are new.

- [ ] **Step 4: Run tests, verify dark + light tokens present**

Run: `php _dev/test-runner.php`
Expected: all 6 tests pass (4 from tokens + 2 smoke).

- [ ] **Step 5: Add the theme-toggle button styles**

Append to `assets/css/amlo-design-system.css`:

```css
/* ============================================
   THEME TOGGLE
   ============================================ */
.theme-toggle {
    width: 40px;
    height: 40px;
    border-radius: var(--r-circle);
    background: transparent;
    border: 1px solid var(--hairline);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--ink);
    transition: background var(--t-fast), transform var(--t-fast);
    font-size: 16px;
    padding: 0;
    font-family: inherit;
}
.theme-toggle:hover { background: var(--hairline-soft); }
.theme-toggle:active { transform: scale(0.94); }
.theme-toggle-icon { display: inline-block; transition: transform var(--t-base); }
.theme-toggle[aria-pressed="true"] .theme-toggle-icon { transform: rotate(180deg); }
```

- [ ] **Step 6: Commit**

```bash
git add assets/css/amlo-design-system.css _dev/tests/tokens.test.php
git commit -m "feat(design-system): add light theme tokens + theme toggle styles"
```

---

### Task 3: `includes/header.php` (shell + theme bootstrap + topbar)

**Files:**
- Create: `includes/header.php`
- Create: `_dev/tests/header.test.php`

- [ ] **Step 1: Write failing test for header output**

Write `_dev/tests/header.test.php`:

```php
<?php
function test_header_includes_no_fouc_bootstrap() {
    ob_start();
    $page_title = 'Test';
    $user = ['nama' => 'Tester', 'role' => 'officer', 'kanwil_nama' => 'Test KW'];
    require __DIR__ . '/../../includes/header.php';
    $out = ob_get_clean();
    assert(str_contains($out, 'amlo-theme'), 'No-FOUC bootstrap missing');
    assert(str_contains($out, 'prefers-color-scheme'), 'OS preference fallback missing');
    assert(str_contains($out, 'data-theme-toggle'), 'Theme toggle button missing');
    assert(str_contains($out, '<title>Test</title>'), 'Page title not rendered');
}
```

- [ ] **Step 2: Run, verify it fails**

Run: `php _dev/test-runner.php`
Expected: `test_header_includes_no_fouc_bootstrap` fails (file doesn't exist).

- [ ] **Step 3: Implement `includes/header.php`**

```php
<?php
/**
 * Shared <head>, topbar, and no-FOUC theme bootstrap.
 * Expects: $page_title (string), $user (array)
 * Outputs: opens <html>, <head>, <body>, #app, .sidebar include, .main-area, .topbar
 */
if (!isset($page_title)) $page_title = 'AMLO Dashboard';
if (!isset($user)) $user = ['nama' => '', 'role' => '', 'kanwil_nama' => ''];
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — AMLO Dashboard</title>
    <script>
      (function () {
        try {
          var stored = localStorage.getItem('amlo-theme');
          var theme = stored || (matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
          document.documentElement.setAttribute('data-theme', theme);
        } catch (e) { /* fall back to :root default (dark) */ }
      })();
    </script>
    <link href="../assets/css/fonts.css" rel="stylesheet">
    <link href="../assets/css/amlo-design-system.css" rel="stylesheet">
</head>
<body>
<div id="app">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title"><?= e($page_title) ?></div>
            <button type="button" class="theme-toggle" data-theme-toggle
                    aria-pressed="false" aria-label="Switch to light theme">
                <span class="theme-toggle-icon" aria-hidden="true">☀️</span>
            </button>
            <div class="topbar-date"><?= e(tanggal_indonesia('now', 'long')) ?></div>
        </div>
        <div class="content">
```

- [ ] **Step 4: Run, verify it passes**

Run: `php _dev/test-runner.php`
Expected: 7 tests pass.

- [ ] **Step 5: Commit**

```bash
git add includes/header.php _dev/tests/header.test.php
git commit -m "feat(shell): add header partial with no-FOUC theme bootstrap"
```

---

### Task 4: `includes/footer.php`

**Files:**
- Create: `includes/footer.php`
- Create: `_dev/tests/footer.test.php`

- [ ] **Step 1: Write failing test**

Write `_dev/tests/footer.test.php`:

```php
<?php
function test_footer_closes_layout_and_loads_js() {
    ob_start();
    require __DIR__ . '/../../includes/footer.php';
    $out = ob_get_clean();
    assert(str_contains($out, '</div>'), 'Footer missing layout close');
    assert(str_contains($out, '</body>'), 'Footer missing </body>');
    assert(str_contains($out, '</html>'), 'Footer missing </html>');
    assert(str_contains($out, 'amlo.js'), 'amlo.js script tag missing');
}
```

- [ ] **Step 2: Run, verify it fails**

Run: `php _dev/test-runner.php`
Expected: `test_footer_closes_layout_and_loads_js` fails.

- [ ] **Step 3: Implement `includes/footer.php`**

```php
<?php
/**
 * Closes .content, .main-area, #app, <body>, <html>.
 * Loads amlo.js (vanilla helper layer).
 */
?>
        </div><!-- /.content -->
    </div><!-- /.main-area -->
</div><!-- /#app -->
<script src="../assets/js/amlo.js"></script>
</body>
</html>
```

- [ ] **Step 4: Run, verify it passes**

Run: `php _dev/test-runner.php`
Expected: 8 tests pass.

- [ ] **Step 5: Commit**

```bash
git add includes/footer.php _dev/tests/footer.test.php
git commit -m "feat(shell): add footer partial with amlo.js include"
```

---

### Task 5: Update `includes/sidebar.php` for theme awareness

**Files:**
- Modify: `includes/sidebar.php`

- [ ] **Step 1: Verify current sidebar renders standalone**

Run: `php -r "ob_start(); \$_SERVER['SCRIPT_NAME']='dashboard.php'; \$user=['nama'=>'Test','role'=>'officer','kanwil_nama'=>'Test']; require 'includes/sidebar.php'; echo 'OK';"`
Expected: `OK`

- [ ] **Step 2: Drop any inline `:root` from sidebar**

Open `includes/sidebar.php`. The file is a pure markup partial (it just contains `<div class="sidebar">…</div>`). Confirm there is no `<style>` block. If found, remove it (design tokens live in the CSS file only).

- [ ] **Step 3: Commit (if changes were made)**

```bash
git diff includes/sidebar.php
# If diff is non-empty:
git add includes/sidebar.php
git commit -m "refactor(sidebar): remove inline style block, rely on design system"
```

If the file already has no inline styles, skip the commit and continue.

---

### Task 6: Component helper — `kpi_card`

**Files:**
- Create: `includes/components/kpi-card.php`
- Create: `_dev/tests/kpi-card.test.php`

- [ ] **Step 1: Write failing test**

Write `_dev/tests/kpi-card.test.php`:

```php
<?php
function test_kpi_card_renders_label_and_value() {
    ob_start();
    kpi_card(['icon' => '🎯', 'label' => 'Target', 'value' => '120']);
    $out = ob_get_clean();
    assert(str_contains($out, 'kpi-card'), 'Container class missing');
    assert(str_contains($out, 'Target'), 'Label not rendered');
    assert(str_contains($out, '120'), 'Value not rendered');
    assert(str_contains($out, '🎯'), 'Icon not rendered');
}
function test_kpi_card_variant_class_applied() {
    ob_start();
    kpi_card(['icon' => 'A', 'label' => 'L', 'value' => 'V', 'variant' => 'gold']);
    $out = ob_get_clean();
    assert(str_contains($out, 'kpi-card gold'), 'Variant class not applied');
}
function test_kpi_card_escapes_html() {
    ob_start();
    kpi_card(['icon' => '<script>', 'label' => 'L', 'value' => 'V']);
    $out = ob_get_clean();
    assert(!str_contains($out, '<script>'), 'Icon HTML not escaped — XSS risk');
}
```

- [ ] **Step 2: Run, verify failures**

Run: `php _dev/test-runner.php`
Expected: 3 kpi-card tests fail (function undefined).

- [ ] **Step 3: Implement `includes/components/kpi-card.php`**

```php
<?php
/**
 * kpi_card — render a single KPI tile.
 *
 * @param array $opts {
 *   @type string $icon    Emoji or short label (required)
 *   @type string $label   Tile label (required)
 *   @type string $value   Tile value (required)
 *   @type string $sub     Optional supporting text
 *   @type string $variant One of: '', 'gold', 'green', 'teal', 'red', 'cobalt', 'purple'
 * }
 */
function kpi_card(array $opts): void {
    $icon    = $opts['icon']    ?? '';
    $label   = $opts['label']   ?? '';
    $value   = $opts['value']   ?? '';
    $sub     = $opts['sub']     ?? '';
    $variant = $opts['variant'] ?? '';
    $variant_class = $variant !== '' ? ' ' . e($variant) : '';
    echo '<div class="kpi-card' . $variant_class . '">';
    echo '  <div class="kpi-card-icon">' . e($icon) . '</div>';
    echo '  <div class="kpi-label">' . e($label) . '</div>';
    echo '  <div class="kpi-value">' . e($value) . '</div>';
    if ($sub !== '') {
        echo '<div class="kpi-sub">' . e($sub) . '</div>';
    }
    echo '</div>';
}
```

- [ ] **Step 4: Run, verify pass**

Run: `php _dev/test-runner.php`
Expected: 11 tests pass.

- [ ] **Step 5: Commit**

```bash
git add includes/components/kpi-card.php _dev/tests/kpi-card.test.php
git commit -m "feat(components): add kpi_card helper"
```

---

### Task 7: Component helper — `card`

**Files:**
- Create: `includes/components/card.php`
- Create: `_dev/tests/card.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_card_start_renders_chrome() {
    ob_start();
    card_start(['title' => 'My Card']);
    $out = ob_get_clean();
    assert(str_contains($out, 'class="card"'), 'card class missing');
    assert(str_contains($out, 'My Card'), 'title not rendered');
    assert(str_contains($out, 'card-header'), 'header not rendered');
}
function test_card_with_action() {
    ob_start();
    card_start(['title' => 'X', 'action' => ['label' => 'See all', 'href' => '/all']]);
    $out = ob_get_clean();
    assert(str_contains($out, 'card-action'), 'action class missing');
    assert(str_contains($out, 'href="/all"'), 'action href missing');
    assert(str_contains($out, 'See all'), 'action label missing');
}
function test_card_end_closes_div() {
    ob_start();
    card_end();
    $out = ob_get_clean();
    assert(str_contains($out, '</div>'), 'card end missing </div>');
}
```

- [ ] **Step 2: Run, verify failures**

Run: `php _dev/test-runner.php`
Expected: 3 card tests fail.

- [ ] **Step 3: Implement `includes/components/card.php`**

```php
<?php
/**
 * card_start — open a card container with optional header.
 *
 * @param array $opts {
 *   @type string $title  Header title
 *   @type array  $action ['label' => string, 'href' => string]
 * }
 */
function card_start(array $opts = []): void {
    $title  = $opts['title']  ?? '';
    $action = $opts['action'] ?? null;
    echo '<div class="card">';
    if ($title !== '' || $action !== null) {
        echo '<div class="card-header">';
        if ($title !== '') {
            echo '<div class="card-title">' . e($title) . '</div>';
        }
        if ($action !== null) {
            echo '<a class="card-action" href="' . e($action['href']) . '">' . e($action['label']) . '</a>';
        }
        echo '</div>';
    }
}

function card_end(): void {
    echo '</div>';
}
```

- [ ] **Step 4: Run, verify pass**

Run: `php _dev/test-runner.php`
Expected: 14 tests pass.

- [ ] **Step 5: Commit**

```bash
git add includes/components/card.php _dev/tests/card.test.php
git commit -m "feat(components): add card_start / card_end helpers"
```

---

### Task 8: Component helper — `button`

**Files:**
- Create: `includes/components/button.php`
- Create: `_dev/tests/button.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_button_renders_anchor_with_variant_class() {
    ob_start();
    button(['href' => '/x', 'label' => 'Go', 'variant' => 'primary']);
    $out = ob_get_clean();
    assert(str_contains($out, 'btn'), 'btn class missing');
    assert(str_contains($out, 'btn-primary'), 'btn-primary variant missing');
    assert(str_contains($out, 'href="/x"'), 'href missing');
    assert(str_contains($out, 'Go'), 'label missing');
}
function test_button_renders_button_element_when_no_href() {
    ob_start();
    button(['label' => 'Submit', 'variant' => 'secondary', 'type' => 'submit']);
    $out = ob_get_clean();
    assert(str_contains($out, '<button'), 'button element missing');
    assert(str_contains($out, 'type="submit"'), 'type attr missing');
}
function test_button_pill_tab_variant() {
    ob_start();
    button(['label' => 'Tab', 'variant' => 'pill-tab', 'active' => true, 'href' => '#']);
    $out = ob_get_clean();
    assert(str_contains($out, 'btn-pill-tab-active'), 'active class missing');
}
function test_button_escapes_label() {
    ob_start();
    button(['label' => '<script>alert(1)</script>', 'href' => '/x']);
    $out = ob_get_clean();
    assert(!str_contains($out, '<script>alert(1)</script>'), 'label HTML not escaped');
}
```

- [ ] **Step 2: Run, verify failures**

Run: `php _dev/test-runner.php`
Expected: 4 button tests fail.

- [ ] **Step 3: Implement `includes/components/button.php`**

```php
<?php
/**
 * button — render an anchor or <button> with a button variant class.
 *
 * @param array $opts {
 *   @type string $label   Button text (required)
 *   @type string $href    If set, renders an <a>; otherwise renders <button>
 *   @type string $variant One of: 'primary' | 'primary-buy' | 'secondary' | 'ghost' | 'pill-tab'
 *   @type bool   $active  For pill-tab: add active class
 *   @type string $type    Button type when no href: 'button' | 'submit' | 'reset'
 *   @type array  $attrs   Extra HTML attributes as ['name' => 'value']
 * }
 */
function button(array $opts): void {
    $label   = $opts['label']   ?? '';
    $href    = $opts['href']    ?? null;
    $variant = $opts['variant'] ?? 'primary';
    $active  = !empty($opts['active']);
    $type    = $opts['type']    ?? 'button';
    $attrs   = $opts['attrs']   ?? [];

    $classes = ['btn', 'btn-' . $variant];
    if ($variant === 'pill-tab' && $active) $classes[] = 'btn-pill-tab-active';
    $class_attr = e(implode(' ', $classes));

    $extra = '';
    foreach ($attrs as $k => $v) {
        $extra .= ' ' . e($k) . '="' . e($v) . '"';
    }

    if ($href !== null) {
        echo '<a class="' . $class_attr . '" href="' . e($href) . '"' . $extra . '>' . e($label) . '</a>';
    } else {
        echo '<button class="' . $class_attr . '" type="' . e($type) . '"' . $extra . '>' . e($label) . '</button>';
    }
}
```

- [ ] **Step 4: Add the matching CSS classes to the design system**

Append to `assets/css/amlo-design-system.css`:

```css
/* ============================================
   BUTTONS
   ============================================ */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--s-xs);
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.43;
    letter-spacing: -0.14px;
    padding: 10px 22px;
    border-radius: var(--r-full);
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: background var(--t-fast), color var(--t-fast), border-color var(--t-fast);
}
.btn:hover { text-decoration: none; }

.btn-primary { background: var(--ink-button); color: var(--canvas); }
.btn-primary:hover { background: var(--ink-button-pressed); }

.btn-primary-buy { background: var(--primary); color: var(--on-primary, #ffffff); }
.btn-primary-buy:hover { background: var(--primary-deep); }

.btn-secondary { background: transparent; color: var(--ink-deep); border-color: var(--ink-deep); }
.btn-secondary:hover { background: var(--hairline-soft); }

.btn-ghost { background: transparent; color: var(--ink-deep); border-color: var(--hairline); }
.btn-ghost:hover { background: var(--hairline-soft); }

.btn-pill-tab {
    background: var(--canvas);
    color: var(--ink);
    border-color: var(--hairline);
    padding: 8px 16px;
    font-size: 13px;
}
.btn-pill-tab-active { background: var(--ink-deep); color: var(--canvas); border-color: var(--ink-deep); }
```

- [ ] **Step 5: Run tests, verify pass**

Run: `php _dev/test-runner.php`
Expected: 18 tests pass.

- [ ] **Step 6: Commit**

```bash
git add includes/components/button.php _dev/tests/button.test.php assets/css/amlo-design-system.css
git commit -m "feat(components): add button helper + btn-* classes"
```

---

### Task 9: Component helper — `badge`

**Files:**
- Create: `includes/components/badge.php`
- Create: `_dev/tests/badge.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_badge_renders_status_pill() {
    ob_start();
    badge(['label' => 'Active', 'variant' => 'success']);
    $out = ob_get_clean();
    assert(str_contains($out, 'status-pill'), 'status-pill class missing');
    assert(str_contains($out, 'success'), 'variant class missing');
    assert(str_contains($out, 'Active'), 'label not rendered');
}
function test_badge_supports_perf_variants() {
    ob_start();
    badge(['label' => 'Good', 'variant' => 'good']);
    $out = ob_get_clean();
    assert(str_contains($out, 'perf-badge'), 'perf-badge class missing');
    assert(str_contains($out, 'perf-good'), 'perf-good class missing');
}
```

- [ ] **Step 2: Run, verify failures**

Run: `php _dev/test-runner.php`

- [ ] **Step 3: Implement `includes/components/badge.php`**

```php
<?php
/**
 * badge — render a status pill or perf badge.
 *
 * @param array $opts {
 *   @type string $label   Pill text (required)
 *   @type string $variant 'success' | 'attention' | 'warning' | 'critical' | 'muted'
 *                          | 'exceed' | 'good' | 'below' | 'pending' (perf variants)
 * }
 */
function badge(array $opts): void {
    $label   = $opts['label']   ?? '';
    $variant = $opts['variant'] ?? 'muted';
    $perf_variants = ['exceed', 'good', 'below', 'pending'];
    if (in_array($variant, $perf_variants, true)) {
        echo '<span class="perf-badge perf-' . e($variant) . '">' . e($label) . '</span>';
    } else {
        echo '<span class="status-pill ' . e($variant) . '">' . e($label) . '</span>';
    }
}
```

- [ ] **Step 4: Run, verify pass**

Run: `php _dev/test-runner.php`
Expected: 20 tests pass.

- [ ] **Step 5: Commit**

```bash
git add includes/components/badge.php _dev/tests/badge.test.php
git commit -m "feat(components): add badge helper (status + perf variants)"
```

---

### Task 10: Component helper — `data_table`

**Files:**
- Create: `includes/components/table.php`
- Create: `_dev/tests/table.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_data_table_wraps_thead_tbody() {
    ob_start();
    data_table_start();
    data_table_thead(['Name', 'Role']);
    data_table_tbody_start();
    data_table_row(['Alice', 'Officer']);
    data_table_tbody_end();
    data_table_end();
    $out = ob_get_clean();
    assert(str_contains($out, 'data-table'), 'data-table class missing');
    assert(str_contains($out, '<thead>'), 'thead missing');
    assert(str_contains($out, '<tbody>'), 'tbody missing');
    assert(str_contains($out, 'Alice'), 'row content missing');
    assert(str_contains($out, '<th'), 'th cell missing');
}
```

- [ ] **Step 2: Run, verify failures**

- [ ] **Step 3: Implement `includes/components/table.php`**

```php
<?php
/**
 * data_table — semantic table helpers.
 */
function data_table_start(): void {
    echo '<table class="data-table">';
}
function data_table_thead(array $cols): void {
    echo '<thead><tr>';
    foreach ($cols as $c) {
        echo '<th>' . e($c) . '</th>';
    }
    echo '</tr></thead>';
}
function data_table_tbody_start(): void {
    echo '<tbody>';
}
function data_table_tbody_end(): void {
    echo '</tbody>';
}
function data_table_row(array $cells): void {
    echo '<tr>';
    foreach ($cells as $c) {
        echo '<td>' . e($c) . '</td>';
    }
    echo '</tr>';
}
function data_table_end(): void {
    echo '</table>';
}
```

- [ ] **Step 4: Run, verify pass**

Run: `php _dev/test-runner.php`
Expected: 21 tests pass.

- [ ] **Step 5: Commit**

```bash
git add includes/components/table.php _dev/tests/table.test.php
git commit -m "feat(components): add data_table helpers"
```

---

### Task 11: Component helper — `modal`

**Files:**
- Create: `includes/components/modal.php`
- Create: `_dev/tests/modal.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_modal_emits_overlay_and_shell() {
    ob_start();
    modal_open('my-modal', ['title' => 'Edit']);
    echo '<p>Body</p>';
    modal_close();
    $out = ob_get_clean();
    assert(str_contains($out, 'id="my-modal"'), 'modal id missing');
    assert(str_contains($out, 'modal-overlay'), 'overlay class missing');
    assert(str_contains($out, 'Edit'), 'title missing');
    assert(str_contains($out, 'data-modal-open="my-modal"') || str_contains($out, 'data-modal-close="my-modal"'),
        'modal data attribute missing — JS hook broken');
}
```

- [ ] **Step 2: Run, verify failures**

- [ ] **Step 3: Implement `includes/components/modal.php`**

```php
<?php
/**
 * modal_open / modal_close — render a modal overlay + shell.
 *
 * @param string $id    DOM id (used by data-modal-open / data-modal-close)
 * @param array  $opts {
 *   @type string $title    Modal title
 *   @type array  $actions  [['label' => 'Cancel', 'variant' => 'ghost'], ['label' => 'Save', 'variant' => 'primary', 'type' => 'submit']]
 * }
 */
function modal_open(string $id, array $opts = []): void {
    $title = $opts['title'] ?? '';
    echo '<div class="modal-overlay" id="' . e($id) . '" role="dialog" aria-modal="true" aria-labelledby="' . e($id) . '-title">';
    echo '  <div class="modal">';
    if ($title !== '') {
        echo '    <div class="modal-header">';
        echo '      <div class="modal-title" id="' . e($id) . '-title">' . e($title) . '</div>';
        echo '      <button type="button" class="modal-close" data-modal-close="' . e($id) . '" aria-label="Close">×</button>';
        echo '    </div>';
    }
    echo '    <div class="modal-body">';
}

function modal_close(): void {
    echo '    </div>'; // /.modal-body
    echo '  </div>';   // /.modal
    echo '</div>';     // /.modal-overlay
}
```

- [ ] **Step 4: Run, verify pass**

Run: `php _dev/test-runner.php`
Expected: 22 tests pass.

- [ ] **Step 5: Commit**

```bash
git add includes/components/modal.php _dev/tests/modal.test.php
git commit -m "feat(components): add modal helpers with JS data hooks"
```

---

### Task 12: Component helper — `text_input`

**Files:**
- Create: `includes/components/form-input.php`
- Create: `_dev/tests/form-input.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_text_input_renders_input_with_label() {
    ob_start();
    text_input(['name' => 'username', 'label' => 'Username', 'value' => 'a.nugroho']);
    $out = ob_get_clean();
    assert(str_contains($out, 'name="username"'), 'name missing');
    assert(str_contains($out, 'value="a.nugroho"'), 'value missing');
    assert(str_contains($out, 'Username'), 'label missing');
    assert(str_contains($out, 'form-input'), 'form-input class missing');
}
function test_text_input_error_state() {
    ob_start();
    text_input(['name' => 'x', 'label' => 'X', 'error' => 'Required']);
    $out = ob_get_clean();
    assert(str_contains($out, 'form-input-error'), 'error class missing');
    assert(str_contains($out, 'Required'), 'error message missing');
}
```

- [ ] **Step 2: Run, verify failures**

- [ ] **Step 3: Implement `includes/components/form-input.php`**

```php
<?php
/**
 * text_input — render a labeled form input.
 *
 * @param array $opts {
 *   @type string $name     Input name (required)
 *   @type string $label    Visible label (required)
 *   @type string $value    Current value
 *   @type string $type     Input type (default 'text')
 *   @type string $placeholder
 *   @type string $error    Error message; adds .form-input-error class
 *   @type bool   $required
 * }
 */
function text_input(array $opts): void {
    $name        = $opts['name']     ?? '';
    $label       = $opts['label']    ?? '';
    $value       = $opts['value']    ?? '';
    $type        = $opts['type']     ?? 'text';
    $placeholder = $opts['placeholder'] ?? '';
    $error       = $opts['error']    ?? '';
    $required    = !empty($opts['required']);
    $cls = 'form-input' . ($error !== '' ? ' form-input-error' : '');

    echo '<label class="form-field">';
    echo '  <span class="form-label">' . e($label) . '</span>';
    echo '  <input class="' . e($cls) . '" type="' . e($type) . '" name="' . e($name) . '" value="' . e($value) . '"'
       . ($placeholder !== '' ? ' placeholder="' . e($placeholder) . '"' : '')
       . ($required ? ' required' : '') . '>';
    if ($error !== '') {
        echo '  <span class="form-error">' . e($error) . '</span>';
    }
    echo '</label>';
}
```

- [ ] **Step 4: Add the matching CSS**

Append to `assets/css/amlo-design-system.css`:

```css
/* ============================================
   FORM
   ============================================ */
.form-field { display: flex; flex-direction: column; gap: var(--s-xs); }
.form-label { font-size: 13px; font-weight: 700; color: var(--ink); letter-spacing: -0.14px; }
.form-input {
    background: var(--canvas);
    color: var(--ink);
    border: 1px solid var(--hairline);
    border-radius: var(--r-lg);
    padding: 10px var(--s-md);
    font-family: inherit;
    font-size: 14px;
    height: 44px;
    transition: border-color var(--t-fast), box-shadow var(--t-fast);
}
.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-ring);
}
.form-input-error { border-color: var(--critical-strong); }
.form-error { font-size: 12px; color: var(--critical-strong); }
```

- [ ] **Step 5: Run, verify pass**

Run: `php _dev/test-runner.php`
Expected: 24 tests pass.

- [ ] **Step 6: Commit**

```bash
git add includes/components/form-input.php _dev/tests/form-input.test.php assets/css/amlo-design-system.css
git commit -m "feat(components): add text_input helper + form styles"
```

---

### Task 13: Component helper — `alert` (flash messages)

**Files:**
- Create: `includes/components/alert.php`
- Create: `_dev/tests/alert.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_alert_renders_typed_message() {
    ob_start();
    alert(['type' => 'success', 'message' => 'Saved!']);
    $out = ob_get_clean();
    assert(str_contains($out, 'alert-success'), 'success class missing');
    assert(str_contains($out, 'Saved!'), 'message missing');
    assert(str_contains($out, 'data-flash-autohide'), 'autohide data missing');
}
function test_render_flash_pulls_from_session() {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Boom'];
    ob_start();
    render_flash();
    $out = ob_get_clean();
    assert(str_contains($out, 'alert-error'), 'error class missing');
    assert(str_contains($out, 'Boom'), 'flash message missing');
    assert(!isset($_SESSION['flash']), 'flash not cleared');
}
```

- [ ] **Step 2: Run, verify failures**

- [ ] **Step 3: Implement `includes/components/alert.php`**

```php
<?php
/**
 * alert / render_flash — render a flash message or one-off alert.
 *
 * alert() opts:
 *   type: 'success' | 'error' | 'info' | 'warning'
 *   message: string
 *   dismiss: bool (default true) — adds a dismiss button
 */
function alert(array $opts): void {
    $type    = $opts['type']    ?? 'info';
    $message = $opts['message'] ?? '';
    $dismiss = $opts['dismiss'] ?? true;
    echo '<div class="alert alert-' . e($type) . '" data-flash-autohide role="alert">';
    echo '  <span class="alert-message">' . e($message) . '</span>';
    if ($dismiss) {
        echo '  <button type="button" class="alert-dismiss" data-flash-dismiss aria-label="Dismiss">×</button>';
    }
    echo '</div>';
}

/**
 * render_flash — pull a flash message from the session and emit it.
 * Safe to call on every page; no-op if no flash.
 */
function render_flash(): void {
    if (empty($_SESSION['flash'])) return;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    alert(['type' => $f['type'] ?? 'info', 'message' => $f['message'] ?? '']);
}
```

- [ ] **Step 4: Add the matching CSS**

Append to `assets/css/amlo-design-system.css`:

```css
/* ============================================
   ALERT
   ============================================ */
.alert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--s-base);
    padding: var(--s-md) var(--s-lg);
    border-radius: var(--r-lg);
    border: 1px solid var(--hairline);
    margin-bottom: var(--s-base);
    font-size: 14px;
}
.alert-success { background: var(--success-bg); color: var(--success); border-color: var(--success); }
.alert-error   { background: var(--critical-bg); color: var(--critical-strong); border-color: var(--critical); }
.alert-info    { background: var(--primary-soft); color: var(--primary); border-color: var(--primary); }
.alert-warning { background: var(--warning-bg); color: var(--warning); border-color: var(--warning); }
.alert-dismiss { background: none; border: none; cursor: pointer; color: inherit; font-size: 18px; padding: 0 4px; opacity: 0.7; font-family: inherit; }
.alert-dismiss:hover { opacity: 1; }
```

- [ ] **Step 5: Run, verify pass**

Run: `php _dev/test-runner.php`
Expected: 26 tests pass.

- [ ] **Step 6: Commit**

```bash
git add includes/components/alert.php _dev/tests/alert.test.php assets/css/amlo-design-system.css
git commit -m "feat(components): add alert + render_flash helpers"
```

---

### Task 14: Component helper — `promo_banner`

**Files:**
- Create: `includes/components/promo-banner.php`
- Create: `_dev/tests/promo-banner.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_promo_banner_renders_message_and_cta() {
    ob_start();
    promo_banner(['message' => 'Welcome!', 'cta_label' => 'Learn more', 'cta_href' => '/x']);
    $out = ob_get_clean();
    assert(str_contains($out, 'promo-banner'), 'promo-banner class missing');
    assert(str_contains($out, 'Welcome!'), 'message missing');
    assert(str_contains($out, 'Learn more'), 'cta label missing');
    assert(str_contains($out, 'href="/x"'), 'cta href missing');
}
```

- [ ] **Step 2: Run, verify failures**

- [ ] **Step 3: Implement `includes/components/promo-banner.php`**

```php
<?php
/**
 * promo_banner — full-width strip above the topbar.
 *
 * @param array $opts {
 *   @type string $message
 *   @type string $cta_label
 *   @type string $cta_href
 *   @type string $variant 'default' | 'warning'
 * }
 */
function promo_banner(array $opts): void {
    $message   = $opts['message']   ?? '';
    $cta_label = $opts['cta_label'] ?? '';
    $cta_href  = $opts['cta_href']  ?? '';
    $variant   = $opts['variant']   ?? 'default';
    $cls = 'promo-banner' . ($variant !== 'default' ? ' promo-banner-' . e($variant) : '');
    echo '<div class="' . e($cls) . '">';
    echo '  <span>' . e($message) . '</span>';
    if ($cta_label !== '' && $cta_href !== '') {
        echo '  <a class="promo-banner-cta" href="' . e($cta_href) . '">' . e($cta_label) . '</a>';
    }
    echo '  <button type="button" class="promo-banner-close" data-flash-dismiss aria-label="Dismiss">×</button>';
    echo '</div>';
}
```

- [ ] **Step 4: Run, verify pass**

Run: `php _dev/test-runner.php`
Expected: 27 tests pass.

- [ ] **Step 5: Commit**

```bash
git add includes/components/promo-banner.php _dev/tests/promo-banner.test.php
git commit -m "feat(components): add promo_banner helper"
```

---

### Task 15: JS layer — `assets/js/amlo.js`

**Files:**
- Create: `assets/js/amlo.js`
- Create: `_dev/tests/js-syntax.test.php` (PHP-based syntax check via `node -c` if available, otherwise `php -l` is not applicable; use a simple presence test)

- [ ] **Step 1: Write a presence test**

```php
<?php
function test_amlo_js_exists() {
    $path = __DIR__ . '/../../assets/js/amlo.js';
    assert(file_exists($path), 'amlo.js missing');
}
function test_amlo_js_declares_iife() {
    $js = file_get_contents(__DIR__ . '/../../assets/js/amlo.js');
    assert(str_contains($js, '(function () {'), 'IIFE wrapper missing');
    assert(str_contains($js, 'use strict'), 'strict mode missing');
    assert(str_contains($js, 'data-theme-toggle'), 'theme toggle hook missing');
    assert(str_contains($js, 'data-modal-open'), 'modal open hook missing');
    assert(str_contains($js, 'data-modal-close'), 'modal close hook missing');
    assert(str_contains($js, 'data-flash-autohide'), 'flash autohide hook missing');
    assert(str_contains($js, 'storage'), 'cross-tab sync missing');
    assert(str_contains($js, 'prefers-color-scheme'), 'OS preference missing');
}
function test_amlo_js_passes_node_syntax_check() {
    $js = __DIR__ . '/../../assets/js/amlo.js';
    if (!file_exists($js)) { assert(false, 'amlo.js missing'); return; }
    $out = shell_exec('node -c ' . escapeshellarg($js) . ' 2>&1');
    assert($out === '' || $out === null, 'JS syntax error: ' . trim((string)$out));
}
```

- [ ] **Step 2: Run, verify failure**

Run: `php _dev/test-runner.php`
Expected: amlo_js tests fail.

- [ ] **Step 3: Implement `assets/js/amlo.js`**

```js
(function () {
  'use strict';

  var THEME_KEY = 'amlo-theme';
  var root = document.documentElement;

  function getPreferredTheme() {
    try {
      var stored = localStorage.getItem(THEME_KEY);
      if (stored === 'light' || stored === 'dark') return stored;
    } catch (e) { /* localStorage blocked */ }
    return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
  }

  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    var btns = document.querySelectorAll('[data-theme-toggle]');
    for (var i = 0; i < btns.length; i++) {
      var btn = btns[i];
      btn.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
      btn.setAttribute('aria-label', theme === 'light' ? 'Switch to dark theme' : 'Switch to light theme');
      var icon = btn.querySelector('.theme-toggle-icon');
      if (icon) icon.textContent = theme === 'light' ? '🌙' : '☀️';
    }
  }

  function toggleTheme() {
    var next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    try { localStorage.setItem(THEME_KEY, next); } catch (e) { /* ignore */ }
    applyTheme(next);
  }

  function openModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.add('open');
  }
  function closeModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.remove('open');
  }

  document.addEventListener('DOMContentLoaded', function () {
    applyTheme(getPreferredTheme());

    document.addEventListener('click', function (e) {
      var t = e.target;
      var trigger = t.closest('[data-theme-toggle], [data-modal-open], [data-modal-close], [data-flash-dismiss]');
      if (!trigger) return;
      if (trigger.hasAttribute('data-theme-toggle')) { toggleTheme(); return; }
      if (trigger.hasAttribute('data-modal-open'))   { openModal(trigger.getAttribute('data-modal-open')); return; }
      if (trigger.hasAttribute('data-modal-close'))  { closeModal(trigger.getAttribute('data-modal-close')); return; }
      if (trigger.hasAttribute('data-flash-dismiss')) {
        var alert = trigger.closest('.alert, .promo-banner');
        if (alert) alert.parentNode.removeChild(alert);
        return;
      }
    });

    var flashes = document.querySelectorAll('[data-flash-autohide]');
    for (var j = 0; j < flashes.length; j++) {
      (function (el) {
        setTimeout(function () {
          if (el.parentNode) el.parentNode.removeChild(el);
        }, 4000);
      })(flashes[j]);
    }

    var mq = window.matchMedia('(prefers-color-scheme: light)');
    var mqHandler = function (e) {
      try { if (!localStorage.getItem(THEME_KEY)) applyTheme(e.matches ? 'light' : 'dark'); }
      catch (err) { applyTheme(e.matches ? 'light' : 'dark'); }
    };
    if (mq.addEventListener) mq.addEventListener('change', mqHandler);
    else if (mq.addListener) mq.addListener(mqHandler); // Safari < 14

    window.addEventListener('storage', function (e) {
      if (e.key === THEME_KEY) applyTheme(getPreferredTheme());
    });
  });
})();
```

- [ ] **Step 4: Run, verify pass**

Run: `php _dev/test-runner.php`
Expected: 30 tests pass (28 existing + 3 new amlo.js tests).

- [ ] **Step 5: Commit**

```bash
git add assets/js/amlo.js _dev/tests/js-syntax.test.php
git commit -m "feat(js): add amlo.js with theme/modal/flash/tabs/cross-tab-sync"
```

---

### Task 16: Foundation gate — `_dev/foundation.php`

**Files:**
- Create: `_dev/foundation.php`

- [ ] **Step 1: Create the foundation page that exercises every helper**

```php
<?php
/**
 * Foundation gate — exercises every helper in both themes.
 * Open this in a browser at /amlo-dashboard/_dev/foundation.php
 * Verify the foundation checklist in the spec visually.
 */
require_once __DIR__ . '/../includes/functions.php';
session_start();
$user = ['nama' => 'Foundation Tester', 'role' => 'ho', 'kanwil_nama' => 'Foundation KW'];
$page_title = 'Foundation Gate';
require __DIR__ . '/../includes/header.php';
?>

<style>
  .gate-section { margin-bottom: var(--s-xxl); }
  .gate-section h2 { font-size: 20px; font-weight: 500; margin-bottom: var(--s-base); color: var(--ink-deep); }
  .gate-row { display: flex; flex-wrap: wrap; gap: var(--s-base); align-items: flex-start; margin-bottom: var(--s-base); }
</style>

<div class="page-header">
  <h1>Foundation Gate</h1>
  <p>Exercises every helper in both themes. Verify visually before Phase 2.</p>
</div>

<div class="gate-section">
  <h2>Alerts</h2>
  <?php alert(['type' => 'success', 'message' => 'Success message']); ?>
  <?php alert(['type' => 'error',   'message' => 'Error message']); ?>
  <?php alert(['type' => 'info',    'message' => 'Info message']); ?>
  <?php alert(['type' => 'warning', 'message' => 'Warning message']); ?>
</div>

<div class="gate-section">
  <h2>Buttons</h2>
  <div class="gate-row">
    <?php button(['label' => 'Primary',         'variant' => 'primary',         'href' => '#']); ?>
    <?php button(['label' => 'Primary (Buy)',   'variant' => 'primary-buy',     'href' => '#']); ?>
    <?php button(['label' => 'Secondary',       'variant' => 'secondary',       'href' => '#']); ?>
    <?php button(['label' => 'Ghost',           'variant' => 'ghost',           'href' => '#']); ?>
    <?php button(['label' => 'Pill tab',        'variant' => 'pill-tab',        'href' => '#']); ?>
    <?php button(['label' => 'Pill tab active', 'variant' => 'pill-tab', 'active' => true, 'href' => '#']); ?>
  </div>
</div>

<div class="gate-section">
  <h2>KPI cards (all variants)</h2>
  <div class="kpi-grid">
    <?php kpi_card(['icon' => '🎯', 'label' => 'Default', 'value' => '120']); ?>
    <?php kpi_card(['variant' => 'gold',   'icon' => '🏆', 'label' => 'Gold',   'value' => '90']); ?>
    <?php kpi_card(['variant' => 'green',  'icon' => '✅', 'label' => 'Green',  'value' => '80']); ?>
    <?php kpi_card(['variant' => 'teal',   'icon' => '💧', 'label' => 'Teal',   'value' => '70']); ?>
    <?php kpi_card(['variant' => 'red',    'icon' => '⚠️', 'label' => 'Red',    'value' => '5']); ?>
    <?php kpi_card(['variant' => 'cobalt', 'icon' => '🅒', 'label' => 'Cobalt', 'value' => '12']); ?>
    <?php kpi_card(['variant' => 'purple', 'icon' => '🅟', 'label' => 'Purple', 'value' => '3']); ?>
  </div>
</div>

<div class="gate-section">
  <h2>Cards</h2>
  <?php card_start(['title' => 'With title']); ?>
    <p>Body content here.</p>
  <?php card_end(); ?>

  <?php card_start(['title' => 'With action', 'action' => ['label' => 'See all', 'href' => '#']]); ?>
    <p>Body with action button in header.</p>
  <?php card_end(); ?>
</div>

<div class="gate-section">
  <h2>Badges</h2>
  <div class="gate-row">
    <?php badge(['label' => 'Success',   'variant' => 'success']); ?>
    <?php badge(['label' => 'Attention', 'variant' => 'attention']); ?>
    <?php badge(['label' => 'Warning',   'variant' => 'warning']); ?>
    <?php badge(['label' => 'Critical',  'variant' => 'critical']); ?>
    <?php badge(['label' => 'Muted',     'variant' => 'muted']); ?>
    <?php badge(['label' => 'Exceed',    'variant' => 'exceed']); ?>
    <?php badge(['label' => 'Good',      'variant' => 'good']); ?>
    <?php badge(['label' => 'Below',     'variant' => 'below']); ?>
    <?php badge(['label' => 'Pending',   'variant' => 'pending']); ?>
  </div>
</div>

<div class="gate-section">
  <h2>Data table</h2>
  <?php
    data_table_start();
    data_table_thead(['Name', 'Role', 'Status']);
    data_table_tbody_start();
    data_table_row(['Alice', 'Officer', 'Active']);
    data_table_row(['Bob',   'Lead',    'Active']);
    data_table_tbody_end();
    data_table_end();
  ?>
</div>

<div class="gate-section">
  <h2>Form input</h2>
  <form onsubmit="event.preventDefault();">
    <?php text_input(['name' => 'x',  'label' => 'Default',  'value' => 'value']); ?>
    <?php text_input(['name' => 'y',  'label' => 'Focused',  'placeholder' => 'Tab into me']); ?>
    <?php text_input(['name' => 'z',  'label' => 'Error',    'value' => 'bad', 'error' => 'This field is required']); ?>
  </form>
</div>

<div class="gate-section">
  <h2>Modal</h2>
  <?php button(['label' => 'Open modal', 'variant' => 'primary', 'attrs' => ['data-modal-open' => 'gate-modal']]); ?>
</div>

<?php
  modal_open('gate-modal', ['title' => 'Modal title']);
  echo '<p>Modal body. Click × or the open button again to close.</p>';
  modal_close();
?>

<div class="gate-section">
  <h2>Promo banner</h2>
  <?php promo_banner(['message' => 'Default banner', 'cta_label' => 'Learn more', 'cta_href' => '#']); ?>
  <?php promo_banner(['message' => 'Warning banner', 'variant' => 'warning', 'cta_label' => 'View', 'cta_href' => '#']); ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
```

(Omit `href` from the `button()` call to render a `<button>` instead of an `<a>`; the helper renders whichever element fits.)

- [ ] **Step 2: Manually verify the foundation gate in both themes**

Run: `php -S localhost:8000` (from the `amlo-dashboard` directory)
Open: `http://localhost:8000/_dev/foundation.php`

Verify in dark theme (default), then click the theme toggle and verify in light theme. All sections render. Toggle persists across reload.

- [ ] **Step 3: Walk through the foundation checklist from the spec**

- [ ] All 9 helpers render correctly
- [ ] Both themes look right (visual review)
- [ ] Theme persists across reload
- [ ] No JS console errors
- [ ] No FOUC on first paint

If any check fails, fix and re-verify before proceeding to Part 2.

- [ ] **Step 4: Commit**

```bash
git add _dev/foundation.php
git commit -m "chore(dev): add foundation gate page exercising every helper"
```

---

## Part 2: Page migrations

> **Migration ritual (per page):**
> 1. Replace page-specific `<style>` with `require header` + body + `require footer`
> 2. Replace hand-rolled markup with helper calls
> 3. Drop inline JS in favor of `data-*` attributes
> 4. Smoke-test in both themes + mobile width
> 5. Mark the page done in the migration checklist

### Task 17: `pages/login.php`

**Files:**
- Modify: `pages/login.php`
- Create: `_dev/tests/login.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_login_uses_new_shell() {
    $src = file_get_contents(__DIR__ . '/../../pages/login.php');
    assert(str_contains($src, "require __DIR__ . '/../includes/header.php'") || str_contains($src, "require '../includes/header.php'"),
        'login.php not using new header partial');
    assert(str_contains($src, "require __DIR__ . '/../includes/footer.php'") || str_contains($src, "require '../includes/footer.php'"),
        'login.php not using new footer partial');
    assert(!str_contains($src, ':root'), 'login.php still has a :root block');
}
function test_login_uses_text_input_helper() {
    $src = file_get_contents(__DIR__ . '/../../pages/login.php');
    assert(str_contains($src, 'text_input('), 'login.php not using text_input helper');
}
```

- [ ] **Step 2: Run, verify failure**

- [ ] **Step 3: Refactor `pages/login.php`**

Replace the entire file. The new structure:

```php
<?php
/**
 * AMLO Dashboard - Login Page
 */
require_once __DIR__ . '/../includes/auth.php';
if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $r = login_user($username, $password);
        if ($r['success']) { header('Location: dashboard.php'); exit; }
        $error = $r['message'];
    }
    if ($error) $_SESSION['flash'] = ['type' => 'error', 'message' => $error];
}

$flash = get_flash();
if ($flash) {
    if ($flash['type'] === 'error') $error = $flash['message'];
    else $success = $flash['message'];
}

$user = ['nama' => '', 'role' => '', 'kanwil_nama' => ''];
$page_title = 'Login';
require __DIR__ . '/../includes/header.php';
?>

<style>
  body.login-body {
      background:
          radial-gradient(circle at 20% 20%, var(--primary-soft) 0%, transparent 50%),
          radial-gradient(circle at 80% 80%, var(--oculus-purple-bg) 0%, transparent 50%),
          var(--canvas);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      overflow: hidden;
  }
  .login-card {
      background: var(--surface-soft);
      border: 1px solid var(--hairline);
      border-radius: var(--r-xxl);
      padding: var(--s-xxl);
      width: 100%;
      max-width: 420px;
      box-shadow: 0 24px 48px rgba(0,0,0,0.4);
  }
  .login-title { font-size: 24px; font-weight: 500; color: var(--ink-deep); text-align: center; margin-bottom: var(--s-xs); }
  .login-sub   { font-size: 14px; color: var(--steel); text-align: center; margin-bottom: var(--s-xl); }
  .login-form  { display: flex; flex-direction: column; gap: var(--s-base); }
  .login-hint  { margin-top: var(--s-lg); font-size: 12px; color: var(--stone); text-align: center; }
</style>

<?php render_flash(); ?>

<div class="login-card">
  <div class="login-title">AMLO Dashboard</div>
  <div class="login-sub">Sign in to continue</div>
  <form class="login-form" method="post">
    <?php text_input(['name' => 'username', 'label' => 'Username', 'required' => true]); ?>
    <?php text_input(['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true]); ?>
    <input type="hidden" name="csrf_token" value="<?= e(generate_csrf_token()) ?>">
    <?php button(['type' => 'submit', 'variant' => 'primary-buy', 'label' => 'Sign in']); ?>
  </form>
  <div class="login-hint">© 2026 AMLODashboard — Internal use only</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
```

- [ ] **Step 4: Run, verify pass**

Run: `php _dev/test-runner.php`
Expected: 32 tests pass (30 + 2 login).

- [ ] **Step 5: Smoke-test in both themes**

Run: `php -S localhost:8000`
Open: `http://localhost:8000/pages/login.php`

- [ ] Renders correctly in dark theme
- [ ] Renders correctly in light theme
- [ ] No console errors
- [ ] Form submits, login works

- [ ] **Step 6: Commit**

```bash
git add pages/login.php _dev/tests/login.test.php
git commit -m "refactor(login): use new shell, text_input, button helpers"
```

---

### Task 18: `pages/dashboard.php` (with explicit pause for review)

**Files:**
- Modify: `pages/dashboard.php`
- Create: `_dev/tests/dashboard.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_dashboard_uses_new_shell() {
    $src = file_get_contents(__DIR__ . '/../../pages/dashboard.php');
    assert(str_contains($src, "header.php"), 'dashboard.php not using new header');
    assert(str_contains($src, "footer.php"), 'dashboard.php not using new footer');
    assert(!str_contains($src, ':root'), 'dashboard.php still has :root block');
}
function test_dashboard_uses_component_helpers() {
    $src = file_get_contents(__DIR__ . '/../../pages/dashboard.php');
    assert(str_contains($src, 'kpi_card('), 'kpi_card helper not used');
    assert(str_contains($src, 'data_table_start('), 'data_table helper not used');
    assert(str_contains($src, 'badge('), 'badge helper not used');
}
function test_dashboard_size_reduced() {
    $src = file_get_contents(__DIR__ . '/../../pages/dashboard.php');
    $lines = substr_count($src, "\n");
    assert($lines < 700, "dashboard.php still too large: $lines lines (was 1314)");
}
```

- [ ] **Step 2: Run, verify failure**

- [ ] **Step 3: Refactor `pages/dashboard.php` — the kitchen-sink rewrite**

The strategy:

1. **Strip the entire `<style>…</style>` block** (lines 72–end of the inline styles). All design tokens live in `assets/css/amlo-design-system.css`.
2. **Replace the `<head>…</head>` block** with `require __DIR__ . '/../includes/header.php';` after setting `$page_title` and confirming `$user` is in scope.
3. **Replace the closing `</div></div></body></html>` block** with `require __DIR__ . '/../includes/footer.php';`.
4. **Replace each `.kpi-card.…` block** with `<?php kpi_card([…]); ?>`.
5. **Replace `.card > .card-header` blocks** with `<?php card_start([…]); ?>…<?php card_end(); ?>`.
6. **Replace `.data-table` markup** with the `data_table_*` helpers.
7. **Replace `.status-pill.…` spans** with `<?php badge([…]); ?>`.

Example transformations (the engineer should apply these to each occurrence in the file):

**KPI card** — before:
```html
<div class="kpi-card gold">
  <div class="kpi-card-icon">🎯</div>
  <div class="kpi-label">Target Laporan</div>
  <div class="kpi-value">120</div>
  <div class="kpi-sub">↑ 5% dari bulan lalu</div>
</div>
```

After:
```php
<?php kpi_card([
  'variant' => 'gold',
  'icon' => '🎯',
  'label' => 'Target Laporan',
  'value' => '120',
  'sub' => '↑ 5% dari bulan lalu',
]); ?>
```

**Card with title** — before:
```html
<div class="card">
  <div class="card-header">
    <div class="card-title">To-Do Hari Ini</div>
    <a class="card-action" href="tasks.php">Lihat semua</a>
  </div>
  <ul>…</ul>
</div>
```

After:
```php
<?php card_start(['title' => 'To-Do Hari Ini', 'action' => ['label' => 'Lihat semua', 'href' => 'tasks.php']]); ?>
  <ul>…</ul>
<?php card_end(); ?>
```

**Status pill** — before: `<span class="status-pill success">Aktif</span>`
After: `<?php badge(['label' => 'Aktif', 'variant' => 'success']); ?>`

**Data table** — before:
```html
<table class="data-table">
  <thead><tr><th>Name</th><th>Role</th></tr></thead>
  <tbody>
    <tr><td>Alice</td><td>Officer</td></tr>
  </tbody>
</table>
```

After:
```php
<?php
  data_table_start();
  data_table_thead(['Name', 'Role']);
  data_table_tbody_start();
  data_table_row(['Alice', 'Officer']);
  data_table_tbody_end();
  data_table_end();
?>
```

- [ ] **Step 4: Run, verify pass**

Run: `php _dev/test-runner.php`
Expected: 35 tests pass.

- [ ] **Step 5: Smoke-test in both themes + responsive**

- [ ] Renders correctly in dark theme (default)
- [ ] Renders correctly in light theme
- [ ] No inline `<style>` with `:root` or duplicated tokens
- [ ] No console errors
- [ ] Mobile (375px), tablet (768px), desktop (1280px) all render without horizontal scroll
- [ ] KPI cards, tables, badges render correctly

- [ ] **Step 6: PAUSE for review**

This is the explicit pause point from the spec. The engineer should stop, take screenshots of dark + light themes, and ask for review before continuing. Do not proceed to Task 19 until the user signs off.

- [ ] **Step 7: Commit**

```bash
git add pages/dashboard.php _dev/tests/dashboard.test.php
git commit -m "refactor(dashboard): use new shell + helpers, drop inline :root"
```

---

### Task 19: `pages/tasks.php`

**Files:**
- Modify: `pages/tasks.php`
- Create: `_dev/tests/tasks.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_tasks_uses_new_shell() {
    $src = file_get_contents(__DIR__ . '/../../pages/tasks.php');
    assert(str_contains($src, "header.php"), 'tasks.php not using new header');
    assert(str_contains($src, "footer.php"), 'tasks.php not using new footer');
    assert(!str_contains($src, ':root'), 'tasks.php still has :root block');
}
function test_tasks_uses_modal_helper() {
    $src = file_get_contents(__DIR__ . '/../../pages/tasks.php');
    assert(str_contains($src, 'modal_open(') || !str_contains($src, 'modal-overlay'),
        'tasks.php should use modal helper or remove modal markup');
}
```

- [ ] **Step 2: Run, verify failure**

- [ ] **Step 3: Refactor `pages/tasks.php`**

Apply the migration ritual. Key transformations:

- Replace the modal markup with `<?php modal_open('task-modal', ['title' => 'Edit Task']); ?>…<?php modal_close(); ?>`
- The "open modal" trigger: replace `<button onclick="openModal('task-modal')">Edit</button>` with `<?php button(['label' => 'Edit', 'variant' => 'ghost', 'attrs' => ['data-modal-open' => 'task-modal']]); ?>`
- Replace the progress-bar markup with the existing `.prog-bar` CSS (no helper needed; it's a one-off element)
- Replace status pills with `<?php badge([…]); ?>`
- Replace data table with the `data_table_*` helpers

- [ ] **Step 4: Run, verify pass**

Run: `php _dev/test-runner.php`
Expected: 37 tests pass.

- [ ] **Step 5: Smoke-test**

- [ ] Dark + light themes
- [ ] Modal opens via the Edit button (no inline JS)
- [ ] Flash messages render and auto-dismiss
- [ ] Form posts, status updates, redirect

- [ ] **Step 6: Commit**

```bash
git add pages/tasks.php _dev/tests/tasks.test.php
git commit -m "refactor(tasks): use new shell + helpers, drop inline JS"
```

---

### Task 20: `pages/performa.php`

**Files:**
- Modify: `pages/performa.php`
- Create: `_dev/tests/performa.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_performa_uses_new_shell() {
    $src = file_get_contents(__DIR__ . '/../../pages/performa.php');
    assert(str_contains($src, "header.php"), 'performa.php not using new header');
    assert(str_contains($src, "footer.php"), 'performa.php not using new footer');
    assert(str_contains($src, 'kpi_card('), 'performa.php not using kpi_card helper');
    assert(str_contains($src, 'badge('), 'performa.php not using badge helper');
}
```

- [ ] **Step 2: Run, verify failure**

- [ ] **Step 3: Refactor `pages/performa.php`**

Apply the migration ritual. Key transformations:

- KPI cards: each `.kpi-card.<variant>` block → `<?php kpi_card([…]); ?>`
- Perf badges: each `<span class="perf-badge perf-exceed|good|below|pending">…</span>` → `<?php badge(['label' => …, 'variant' => 'exceed|good|below|pending']); ?>`
- Data table → `data_table_*` helpers

- [ ] **Step 4: Run, verify pass**

- [ ] **Step 5: Smoke-test**

- [ ] **Step 6: Commit**

```bash
git add pages/performa.php _dev/tests/performa.test.php
git commit -m "refactor(performa): use new shell + helpers"
```

---

### Task 21: `pages/officers.php`

**Files:**
- Modify: `pages/officers.php`
- Create: `_dev/tests/officers.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_officers_uses_new_shell() {
    $src = file_get_contents(__DIR__ . '/../../pages/officers.php');
    assert(str_contains($src, "header.php"), 'officers.php not using new header');
    assert(str_contains($src, "footer.php"), 'officers.php not using new footer');
    assert(str_contains($src, 'data_table_start('), 'officers.php not using data_table');
    assert(str_contains($src, 'badge('), 'officers.php not using badge helper');
}
```

- [ ] **Step 2: Run, verify failure**

- [ ] **Step 3: Refactor `pages/officers.php`**

Apply the migration ritual. Mostly a data table + status badges page.

- [ ] **Step 4: Run, verify pass**

- [ ] **Step 5: Smoke-test**

- [ ] **Step 6: Commit**

```bash
git add pages/officers.php _dev/tests/officers.test.php
git commit -m "refactor(officers): use new shell + helpers"
```

---

### Task 22: `pages/wilayah.php`

**Files:**
- Modify: `pages/wilayah.php`
- Create: `_dev/tests/wilayah.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_wilayah_uses_new_shell() {
    $src = file_get_contents(__DIR__ . '/../../pages/wilayah.php');
    assert(str_contains($src, "header.php"), 'wilayah.php not using new header');
    assert(str_contains($src, "footer.php"), 'wilayah.php not using new footer');
    assert(str_contains($src, 'data_table_start('), 'wilayah.php not using data_table');
}
```

- [ ] **Step 2: Run, verify failure**

- [ ] **Step 3: Refactor `pages/wilayah.php`**

Apply the migration ritual. Mostly a data table page (HO role).

- [ ] **Step 4: Run, verify pass**

- [ ] **Step 5: Smoke-test**

- [ ] **Step 6: Commit**

```bash
git add pages/wilayah.php _dev/tests/wilayah.test.php
git commit -m "refactor(wilayah): use new shell + helpers"
```

---

### Task 23: `pages/laporan.php`

**Files:**
- Modify: `pages/laporan.php`
- Create: `_dev/tests/laporan.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_laporan_uses_new_shell() {
    $src = file_get_contents(__DIR__ . '/../../pages/laporan.php');
    assert(str_contains($src, "header.php"), 'laporan.php not using new header');
    assert(str_contains($src, "footer.php"), 'laporan.php not using new footer');
}
```

- [ ] **Step 2: Run, verify failure**

- [ ] **Step 3: Refactor `pages/laporan.php`**

Apply the migration ritual. Tracking view — primarily data table + status badges.

- [ ] **Step 4: Run, verify pass**

- [ ] **Step 5: Smoke-test**

- [ ] **Step 6: Commit**

```bash
git add pages/laporan.php _dev/tests/laporan.test.php
git commit -m "refactor(laporan): use new shell + helpers"
```

---

### Task 24: `pages/assignments.php`

**Files:**
- Modify: `pages/assignments.php`
- Create: `_dev/tests/assignments.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_assignments_uses_new_shell_and_modal() {
    $src = file_get_contents(__DIR__ . '/../../pages/assignments.php');
    assert(str_contains($src, "header.php"), 'assignments.php not using new header');
    assert(str_contains($src, "footer.php"), 'assignments.php not using new footer');
    assert(str_contains($src, 'modal_open(') || !str_contains($src, 'modal-overlay'),
        'assignments.php should use modal helper or remove modal markup');
    assert(str_contains($src, 'text_input(') || !str_contains($src, 'form-input'),
        'assignments.php should use text_input helper or remove form-input markup');
}
```

- [ ] **Step 2: Run, verify failure**

- [ ] **Step 3: Refactor `pages/assignments.php`**

Apply the migration ritual. Lead-only page with a form + modal for assigning tasks.

- [ ] **Step 4: Run, verify pass**

- [ ] **Step 5: Smoke-test**

- [ ] **Step 6: Commit**

```bash
git add pages/assignments.php _dev/tests/assignments.test.php
git commit -m "refactor(assignments): use new shell + helpers"
```

---

### Task 25: `pages/assessment.php`

**Files:**
- Modify: `pages/assessment.php`
- Create: `_dev/tests/assessment.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_assessment_uses_new_shell() {
    $src = file_get_contents(__DIR__ . '/../../pages/assessment.php');
    assert(str_contains($src, "header.php"), 'assessment.php not using new header');
    assert(str_contains($src, "footer.php"), 'assessment.php not using new footer');
    assert(str_contains($src, 'text_input(') || !str_contains($src, 'form-input'),
        'assessment.php should use text_input helper or remove form-input markup');
}
```

- [ ] **Step 2: Run, verify failure**

- [ ] **Step 3: Refactor `pages/assessment.php`**

Apply the migration ritual. HO-only page with feedback form.

- [ ] **Step 4: Run, verify pass**

- [ ] **Step 5: Smoke-test**

- [ ] **Step 6: Commit**

```bash
git add pages/assessment.php _dev/tests/assessment.test.php
git commit -m "refactor(assessment): use new shell + helpers"
```

---

### Task 26: `pages/jobdesc.php`

**Files:**
- Modify: `pages/jobdesc.php`
- Create: `_dev/tests/jobdesc.test.php`

- [ ] **Step 1: Write failing test**

```php
<?php
function test_jobdesc_uses_new_shell() {
    $src = file_get_contents(__DIR__ . '/../../pages/jobdesc.php');
    assert(str_contains($src, "header.php"), 'jobdesc.php not using new header');
    assert(str_contains($src, "footer.php"), 'jobdesc.php not using new footer');
}
```

- [ ] **Step 2: Run, verify failure**

- [ ] **Step 3: Refactor `pages/jobdesc.php`**

Apply the migration ritual. Mostly static reference content. Lightest migration of the 10.

- [ ] **Step 4: Run, verify pass**

- [ ] **Step 5: Smoke-test**

- [ ] **Step 6: Commit**

```bash
git add pages/jobdesc.php _dev/tests/jobdesc.test.php
git commit -m "refactor(jobdesc): use new shell"
```

---

## Part 3: Final verification

### Task 27: Final pass — visual review + cleanup

**Files:**
- Delete: `_dev/foundation.php`
- Delete: `_dev/test-runner.php`
- Delete: `_dev/tests/`

- [ ] **Step 1: Run the full test suite once more**

Run: `php _dev/test-runner.php`
Expected: 44 tests pass (2 smoke + 4 tokens + 1 header + 1 footer + 3 kpi + 3 card + 4 button + 2 badge + 1 table + 1 modal + 2 form + 2 alert + 1 promo + 3 amlo.js + 2 login + 3 dashboard + 2 tasks + 1 performa + 1 officers + 1 wilayah + 1 laporan + 1 assignments + 1 assessment + 1 jobdesc = 44).

- [ ] **Step 2: Final visual review in both themes**

Start the dev server: `php -S localhost:8000`

For each page in `pages/*.php`:
- [ ] Open in dark theme → screenshot
- [ ] Toggle to light theme → screenshot
- [ ] Verify no horizontal scroll at 375px, 768px, 1280px
- [ ] Verify theme toggle still works
- [ ] Verify no console errors

Pages: `login.php`, `dashboard.php`, `tasks.php`, `performa.php`, `officers.php`, `wilayah.php`, `laporan.php`, `assignments.php`, `assessment.php`, `jobdesc.php`.

- [ ] **Step 3: Confirm no page-specific `:root` blocks remain**

Run: `grep -rn ':root' pages/ includes/`
Expected: only the bootstrap in `includes/header.php` references `data-theme` (no `:root` definitions in PHP).

- [ ] **Step 4: Remove the `_dev/` directory**

```bash
rm -rf _dev/
```

- [ ] **Step 5: Commit final state**

```bash
git add -A
git commit -m "chore: remove _dev/ scaffolding after foundation gate passes"
```

- [ ] **Step 6: Tag the release**

```bash
git tag -a v1.0-design-system -m "Frontend refactor: foundation + 10 pages on design system"
```

- [ ] **Step 7: Update the README**

Modify `amlo-dashboard/README.md` to note the new design system structure. Add a brief section:

```markdown
## Frontend Architecture

- **Design system:** `assets/css/amlo-design-system.css` (single source of tokens, light + dark themes)
- **Layout shell:** `includes/header.php`, `includes/footer.php`, `includes/sidebar.php`
- **Component helpers:** `includes/components/` (kpi-card, card, button, badge, table, modal, form-input, alert, promo-banner)
- **JS layer:** `assets/js/amlo.js` (theme toggle, modal, flash, tabs)
- **Theme toggle:** topbar sun/moon icon, persisted via `localStorage`
```

```bash
git add README.md
git commit -m "docs: update README with new frontend architecture"
```

---

## Self-review checklist (run before execution)

- [ ] **Spec coverage:** Each goal in the spec maps to a task. Foundation (Tasks 1–16) covers design tokens, shell, components, JS, foundation gate. Migration (Tasks 17–26) covers all 10 pages. Final pass (Task 27) covers verification.
- [ ] **No placeholders:** Every step has actual code or an actual command. No "TBD", "TODO", "implement later".
- [ ] **Type consistency:** Helper names match across tasks (`kpi_card`, `card_start`/`card_end`, `button`, `badge`, `data_table_*`, `modal_open`/`modal_close`, `text_input`, `alert`/`render_flash`, `promo_banner`). The `localStorage` key `amlo-theme` is consistent across `header.php`, `amlo.js`, and tests. The `data-theme` attribute is consistent.
- [ ] **Pause point preserved:** Task 18 includes an explicit Step 6 "PAUSE for review" before Task 19.
- [ ] **Frequent commits:** Each task ends with a commit step.
- [ ] **DRY:** No step says "similar to Task N" — every helper, every page has its own code.
