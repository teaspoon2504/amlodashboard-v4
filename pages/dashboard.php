<?php
/**
 * AMLO Dashboard - Main Dashboard
 * Role-based overview with KPIs, alerts, calendar, scorecard
 * Refactored with Meta design system tokens (DESIGN.md)
 */


require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = amlo_get_current_user();
$csrf_token = generate_csrf_token();

// Get current period
$period = get_current_period();
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : $period['tahun'];
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : $period['bulan'];

$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$task_summary = get_task_summary($user['id'], $tahun, $bulan);
$perf = get_user_performance($user['id'], $tahun, $bulan);

// Calculate accurate average progress based on visible tasks for this month
$progress_records = db_fetch_all("
    SELECT tp.*, 
           (SELECT status FROM submissions WHERE task_progress_id = tp.id ORDER BY id DESC LIMIT 1) as submission_status 
    FROM task_progress tp 
    WHERE user_id = ? AND tahun = ?", 
    [$user['id'], $tahun]
);
$progress_map = [];
foreach ($progress_records as $pr) {
    $progress_map[$pr['template_id']][$pr['bulan']] = $pr;
}
$all_templates = db_fetch_all("SELECT * FROM task_templates WHERE is_active = 1");
$visible_tasks = [];

$kpi_gold = 0;
$kpi_green = 0;
$kpi_waiting = 0;
$kpi_teal = 0;
$kpi_red = 0;

foreach ($all_templates as $tt) {
    if ($tt['periode'] === 'harian') continue;
    $prog = 0;
    $sub_status = null;
    $is_visible = false;
    
    if ($tt['periode'] === 'bulanan') {
        $prog = $progress_map[$tt['id']][$bulan]['progress'] ?? 0;
        $sub_status = $progress_map[$tt['id']][$bulan]['submission_status'] ?? null;
        $is_visible = true;
    } elseif ($tt['periode'] === 'triwulan') {
        $tw_map = [1 => 3, 2 => 6, 3 => 9, 4 => 12];
        $tw_vis = [1 => [1,2,3], 2 => [4,5,6], 3 => [7,8,9], 4 => [10,11,12]];
        for ($tw = 1; $tw <= 4; $tw++) {
            if (in_array($bulan, $tw_vis[$tw])) {
                $prog = $progress_map[$tt['id']][$tw_map[$tw]]['progress'] ?? 0;
                $sub_status = $progress_map[$tt['id']][$tw_map[$tw]]['submission_status'] ?? null;
                $is_visible = true;
            }
        }
    } elseif ($tt['periode'] === 'semesteran') {
        $sem_map = [1 => 6, 2 => 12];
        $sem_vis = [1 => [1,2,3,4,5,6], 2 => [7,8,9,10,11,12]];
        for ($sem = 1; $sem <= 2; $sem++) {
            if (in_array($bulan, $sem_vis[$sem])) {
                $prog = $progress_map[$tt['id']][$sem_map[$sem]]['progress'] ?? 0;
                $sub_status = $progress_map[$tt['id']][$sem_map[$sem]]['submission_status'] ?? null;
                $is_visible = true;
            }
        }
    } else {
        $prog = $progress_map[$tt['id']][$bulan]['progress'] ?? 0;
        $sub_status = $progress_map[$tt['id']][$bulan]['submission_status'] ?? null;
        $is_visible = true;
    }
    
    if ($is_visible) {
        $visible_tasks[] = $prog;
        
        $count_in_total = false;
        if ($tt['periode'] === 'bulanan') {
            $count_in_total = true;
        } elseif (in_array($tt['periode'], ['triwulan', 'semesteran', 'adhoc']) && $prog > 0) {
            $count_in_total = true;
        }
        
        if ($count_in_total) {
            $kpi_gold++;
            if ($prog >= 100) {
                if ($sub_status === 'pending') {
                    $kpi_waiting++;
                } elseif ($sub_status === 'approved') {
                    $kpi_green++;
                } else {
                    $kpi_teal++; // 100% but not yet submitted
                }
            } elseif ($prog > 0 && $prog < 100) {
                $kpi_teal++;
            }
        }
        
        if ($tt['periode'] === 'bulanan' && $prog == 0) {
            $kpi_red++;
        }
    }
}
$dashboard_average_progress = count($visible_tasks) > 0 ? round(array_sum($visible_tasks) / count($visible_tasks)) : 0;

// For Lead/HO - get team data
$team_tasks = [];
$wilayah_data = [];
if ($user['role'] === 'lead') {
    $team = db_fetch_all(
        "SELECT u.*, kw.nama as kanwil_nama
         FROM users u
         JOIN kantor_wilayah kw ON u.kanwil_id = kw.id
         WHERE u.kanwil_id = ? AND u.role = 'officer' AND u.aktif = 1",
        [$user['kanwil_id']]
    );

    foreach ($team as $officer) {
        $summary = get_task_summary($officer['id'], $tahun, $bulan);
        $team_tasks[] = [
            'officer' => $officer,
            'summary' => $summary
        ];
    }
}

if ($user['role'] === 'ho') {
    $wilayah_data = get_wilayah_summary();
}

// Nav items
$nav_items = get_nav_items($user['role']);

// Greeting based on hour
$hour = date('G');
if ($hour < 12) {
    $greeting = 'Selamat Pagi';
} elseif ($hour < 17) {
    $greeting = 'Selamat Siang';
} else {
    $greeting = 'Selamat Malam';
}

// Format tanggal Indonesia untuk hero
$tanggal_hari_ini = tanggal_indonesia('now', 'long');

// Get alerts from feedbacks & pending tasks
$alerts = [];
if ($user['role'] === 'officer') {
    $fbs = db_fetch_all("
        SELECT f.*, tt.nama as task_name
        FROM feedbacks f
        JOIN task_progress tp ON f.task_progress_id = tp.id
        JOIN task_templates tt ON tp.template_id = tt.id
        WHERE tp.user_id = ? AND f.from_role = 'ho'
        ORDER BY f.created_at DESC LIMIT 5", [$user['id']]
    );
    foreach ($fbs as $fb) {
        $alerts[] = ['type' => 'blue', 'text' => '💬 <b>Feedback HO:</b> ' . e(substr($fb['isi'], 0, 50)) . (strlen($fb['isi']) > 50 ? '...' : '') . ' pada ' . e($fb['task_name']), 'time' => date('d/m/Y H:i', strtotime($fb['created_at']))];
    }
} elseif ($user['role'] === 'lead') {
    $fbs = db_fetch_all("
        SELECT f.*, tt.nama as task_name, off.nama as off_nama
        FROM feedbacks f
        JOIN task_progress tp ON f.task_progress_id = tp.id
        JOIN users off ON tp.user_id = off.id
        JOIN task_templates tt ON tp.template_id = tt.id
        WHERE off.kanwil_id = ? AND f.from_role = 'ho'
        ORDER BY f.created_at DESC LIMIT 5", [$user['kanwil_id']]
    );
    foreach ($fbs as $fb) {
        $alerts[] = ['type' => 'blue', 'text' => '💬 <b>HO Feedback (' . e($fb['off_nama']) . '):</b> ' . e(substr($fb['isi'], 0, 50)) . (strlen($fb['isi']) > 50 ? '...' : ''), 'time' => date('d/m/Y H:i', strtotime($fb['created_at']))];
    }
}

foreach ($perf['tasks'] as $t) {
    if ($t['status'] === 'pending') {
        $alerts[] = ['type' => 'red', 'text' => '⚠️ <b>' . e($t['nama']) . '</b> belum disubmit', 'time' => '🕒 Overdue — segera submit'];
    } elseif ($t['progress'] < 50 && $t['progress'] > 0) {
        $alerts[] = ['type' => 'amber', 'text' => '📌 <b>' . e($t['nama']) . '</b> progress rendah: ' . $t['progress'] . '%', 'time' => '⏳ Perlu perhatian'];
    }
}
foreach ($perf['tasks'] as $t) {
    if ($t['status'] === 'done' || $t['status'] === 'approved') {
        $alerts[] = ['type' => 'green', 'text' => '✅ <b>' . e($t['nama']) . '</b> selesai ' . $t['progress'] . '%', 'time' => 'Selesai'];
    }
}
$alerts = array_slice($alerts, 0, 5);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda — AMLO Dashboard</title>
    <link href="../assets/css/fonts.css" rel="stylesheet">
    <style>
        /* ============================================
           META DESIGN TOKENS (DESIGN.md) — FULL COLOR PALETTE
           - Light theme → Dark variant for AMLO context
           - See DESIGN.md (colors, components sections)
           ============================================ */
        :root {
            /* ── Surfaces (dark variant of Meta's light surface system) ── */
            --canvas: #0b1929;                  /* {colors.canvas} inverted */
            --surface-soft: #122236;            /* card backgrounds */
            --surface-elevated: #1a3352;        /* elevated surface */
            --surface-translucent: rgba(26, 51, 82, 0.6);
            --hairline: rgba(255, 255, 255, 0.08);     /* {colors.hairline} */
            --hairline-soft: rgba(255, 255, 255, 0.05); /* {colors.hairline-soft} */

            /* ── Text (Meta ink hierarchy — full 7-tier scale) ── */
            --ink-deep: #f0f4f8;                /* primary headline on dark */
            --ink: #d4dde6;                      /* standard body */
            --charcoal: #b0bcc8;                /* tertiary body */
            --slate: #8a9cae;                    /* section headers */
            --steel: #7a93ab;                   /* {colors.steel} supporting */
            --stone: #5d6c7b;                    /* {colors.stone} de-emphasized */
            --disabled: #455261;                /* {colors.disabled-text} */

            /* ── Brand & Accent (Meta's signature palette) ── */
            --primary: #0064e0;                  /* {colors.primary} cobalt — buy-now CTA */
            --primary-deep: #0457cb;             /* {colors.primary-deep} pressed/active link */
            --primary-soft: rgba(0, 100, 224, 0.15);  /* {colors.primary-soft} 15% tint */
            --primary-ring: rgba(0, 100, 224, 0.4);   /* selection ring */
            --fb-blue: #1876f2;                  /* {colors.fb-blue} form-control selected */
            --meta-link: #385898;                /* {colors.meta-link} legacy nav */
            --oculus-purple: #a121ce;            /* {colors.oculus-purple} accent */
            --oculus-purple-bg: rgba(161, 33, 206, 0.15);
            --ink-button: #ffffff;               /* {colors.ink-button} marketing CTA */
            --ink-button-pressed: #d4dde6;       /* {colors.charcoal} pressed state */

            /* ── Semantic (Meta color system — full hierarchy) ── */
            --success: #31a24c;                  /* {colors.success} */
            --success-bg: rgba(49, 162, 76, 0.15);
            --success-strong: #24e400;           /* {colors.success-bg} vivid */
            --attention: #f2a918;                /* {colors.attention} mid-priority */
            --attention-bg: rgba(242, 169, 24, 0.15);
            --warning: #f7b928;                  /* {colors.warning} promo yellow */
            --warning-bg: rgba(255, 226, 0, 0.15);
            --warning-vivid: #ffe200;            /* {colors.warning-bg} vivid */
            --critical: #e41e3f;                 /* {colors.critical} */
            --critical-strong: #f0284a;          /* {colors.critical-strong} error border */
            --critical-bg: rgba(224, 82, 82, 0.15);

            /* ── AMLO brand gold (kept for brand identity, secondary accent) ── */
            --gold: #c8a84b;
            --gold-soft: rgba(200, 168, 75, 0.15);
            --teal: #1b8f9e;
            --teal-light: #25b5c9;

            /* ── Spacing tokens (DESIGN.md) ── */
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

            /* ── Rounded scale (DESIGN.md) ── */
            --r-xs: 2px;
            --r-sm: 4px;
            --r-md: 6px;
            --r-lg: 8px;
            --r-xl: 16px;      /* standard cards */
            --r-xxl: 24px;     /* warranty tiles */
            --r-xxxl: 32px;    /* feature cards */
            --r-feature: 40px; /* accessory hero panels */
            --r-full: 100px;   /* pill buttons/badges */
            --r-circle: 9999px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-feature-settings: "ss01", "ss02";
            background: var(--canvas);
            color: var(--ink-deep);
            min-height: 100vh;
            overflow-x: hidden;
            letter-spacing: -0.14px;
        }

        #app { display: flex; height: 100vh; overflow: hidden; }

        /* ============================================
           SIDEBAR — Flat chrome, pill nav items
           ============================================ */
        .sidebar {
            width: 256px;
            min-width: 256px;
            height: 100vh;
            background: var(--surface-soft);
            border-right: 1px solid var(--hairline);
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 10;
        }

        .sidebar-header {
            padding: var(--s-xl) var(--s-lg) var(--s-base);
            border-bottom: 1px solid var(--hairline-soft);
        }

        .sidebar-brand {
            font-size: 17px;
            font-weight: 700;
            color: var(--ink-deep);
            line-height: 1.2;
            letter-spacing: -0.16px;
        }

        .sidebar-brand span {
            display: block;
            font-size: 12px;
            color: var(--steel);
            font-weight: 400;
            margin-top: 4px;
            letter-spacing: 0;
        }

        .user-chip {
            margin-top: var(--s-base);
            display: flex;
            align-items: center;
            gap: var(--s-md);
            padding: var(--s-sm) 0;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: var(--r-circle);
            background: var(--gold-soft);
            border: 1px solid var(--gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: var(--gold);
            flex-shrink: 0;
        }

        .user-chip-info { min-width: 0; }
        .user-chip-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--ink-deep);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: -0.14px;
        }
        .user-chip-role {
            font-size: 12px;
            color: var(--steel);
            font-weight: 400;
            margin-top: 2px;
        }

        .sidebar-nav { flex: 1; padding: var(--s-base) var(--s-md); overflow-y: auto; }

        .nav-section-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--stone);
            padding: var(--s-md) var(--s-md) var(--s-xs);
            margin-top: var(--s-md);
            text-transform: none;
            letter-spacing: 0;
        }

        /* Pill-tab nav pattern (DESIGN.md button-pill-tab) */
        .nav-item {
            display: flex;
            align-items: center;
            gap: var(--s-md);
            padding: 10px var(--s-base);
            border-radius: var(--r-full);
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: var(--ink);
            transition: background 0.15s ease-out, color 0.15s ease-out;
            margin-bottom: 2px;
            text-decoration: none;
            letter-spacing: -0.14px;
        }

        .nav-item:hover { background: var(--hairline-soft); color: var(--ink-deep); }
        .nav-item.active {
            background: var(--ink-button);
            color: var(--canvas);
            font-weight: 700;
        }

        .nav-icon {
            font-size: 16px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: var(--s-base);
            border-top: 1px solid var(--hairline-soft);
        }

        /* button-ghost (DESIGN.md) */
        .logout-btn {
            width: 100%;
            padding: 10px var(--s-lg);
            background: transparent;
            border: 1px solid var(--hairline);
            border-radius: var(--r-full);
            color: var(--ink);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease-out;
            text-decoration: none;
            display: block;
            text-align: center;
            letter-spacing: -0.14px;
        }

        .logout-btn:hover { background: var(--hairline-soft); color: var(--ink-deep); }

        /* ============================================
           MAIN AREA + TOPBAR
           ============================================ */
        .main-area { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

        .topbar {
            height: 64px;
            background: var(--surface-soft);
            border-bottom: 1px solid var(--hairline);
            display: flex;
            align-items: center;
            padding: 0 var(--s-xxl);
            gap: var(--s-base);
            flex-shrink: 0;
        }

        .topbar-title { font-size: 14px; font-weight: 500; color: var(--ink); flex: 1; letter-spacing: -0.14px; }
        .topbar-date { font-size: 13px; color: var(--steel); font-weight: 400; }

        /* button-icon-circular (DESIGN.md) */
        .topbar-notif {
            width: 40px;
            height: 40px;
            border-radius: var(--r-circle);
            background: transparent;
            border: 1px solid var(--hairline);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            color: var(--ink);
            position: relative;
            transition: all 0.15s ease-out;
        }
        .topbar-notif:hover { background: var(--hairline-soft); }

        .notif-dot {
            position: absolute;
            top: 9px;
            right: 9px;
            width: 8px;
            height: 8px;
            background: var(--critical);
            border-radius: var(--r-circle);
            border: 2px solid var(--surface-soft);
        }

        .content { flex: 1; overflow-y: auto; padding: var(--s-xxl); }

        /* ============================================
           TYPOGRAPHY HIERARCHY (DESIGN.md)
           ============================================ */
        .page-header { margin-bottom: var(--s-xxl); }
        .page-header h1 {
            font-size: 36px;                /* {heading-lg} */
            font-weight: 500;
            line-height: 1.28;
            color: var(--ink-deep);
            letter-spacing: -0.2px;
        }
        .page-header .subhead {
            font-size: 28px;                /* {heading-md} editorial light */
            font-weight: 300;
            line-height: 1.21;
            color: var(--charcoal);
            margin-top: 6px;
            letter-spacing: -0.2px;
        }
        .page-header p {
            font-size: 14px;                /* {body-sm} */
            color: var(--steel);
            margin-top: var(--s-md);
            font-weight: 400;
            line-height: 1.43;
            letter-spacing: -0.14px;
        }

        /* ============================================
           HERO BAND — Full-bleed greeting panel
           ============================================ */
        .hero-band {
            background: var(--surface-soft);
            border: 1px solid var(--hairline-soft);
            border-radius: var(--r-xxxl);    /* 32px (DESIGN.md hero chrome) */
            padding: var(--s-xxl) var(--s-xxl);
            margin-bottom: var(--s-xl);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--s-xl);
            position: relative;
            overflow: hidden;
        }
        .hero-band::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--gold-soft) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-content { position: relative; z-index: 1; }
        .hero-eyebrow {
            font-size: 12px;
            font-weight: 700;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: var(--s-md);
        }
        .hero-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--ink-deep);
            letter-spacing: -0.4px;
            line-height: 1.2;
        }
        .hero-subtitle {
            font-size: 16px;
            color: var(--charcoal);
            margin-top: 8px;
            font-weight: 400;
            letter-spacing: -0.16px;
            line-height: 1.5;
        }
        .hero-stats {
            display: flex;
            gap: var(--s-xxl);
            position: relative;
            z-index: 1;
        }
        .hero-stat { text-align: right; }
        .hero-stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--ink-deep);
            line-height: 1;
            letter-spacing: -0.4px;
        }
        .hero-stat-label {
            font-size: 12px;
            color: var(--steel);
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* ============================================
           KPI GRID — card-icon-feature pattern
           ============================================ */
        .kpi-grid {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: minmax(0, 1fr);
            gap: var(--s-base);
            margin-bottom: var(--s-xl);
        }

        .kpi-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--surface-soft);
            border: 1px solid var(--hairline);
            border-radius: var(--r-xxl);   /* 24px (DESIGN.md card-icon-feature) */
            padding: var(--s-xl);
            position: relative;
            transition: border-color 0.15s ease-out;
        }
        .kpi-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            gap: 4px;
        }
        .kpi-card:hover { border-color: var(--hairline); }

        .kpi-card-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--r-circle);
            background: var(--hairline-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 8px;
        }
        .kpi-card.gold .kpi-card-icon { background: var(--warning-bg); color: var(--warning); }
        .kpi-card.green .kpi-card-icon { background: var(--success-bg); color: var(--success); }
        .kpi-card.teal .kpi-card-icon { background: var(--primary-soft); color: var(--teal-light); }
        .kpi-card.red .kpi-card-icon { background: var(--critical-bg); color: var(--critical-strong); }
        .kpi-card.orange .kpi-card-icon { background: rgba(245, 158, 11, 0.15); color: var(--attention); }

        .kpi-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--steel);
        }
        .kpi-value {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            color: var(--ink-deep);
            letter-spacing: -0.4px;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }
        .kpi-sub {
            font-size: 12px;
            color: var(--steel);
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: 400;
        }

        /* ============================================
           TWO-COLUMN LAYOUT + CARD CHROME
           ============================================ */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--s-base);
            margin-bottom: 8px;
        }

        /* card-product-feature / card-icon-feature (DESIGN.md) */
        .card {
            background: var(--surface-soft);
            border: 1px solid var(--hairline);
            border-radius: var(--r-xxl);   /* 24px */
            padding: var(--s-xl);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--s-lg);
        }

        .card-title {
            font-size: 16px;                /* {body-md-bold} */
            font-weight: 700;
            color: var(--ink-deep);
            letter-spacing: -0.16px;
        }

        /* link-md (DESIGN.md) */
        .card-action {
            font-size: 14px;
            font-weight: 700;
            color: var(--teal-light);
            cursor: pointer;
            text-decoration: none;
            letter-spacing: -0.14px;
            transition: opacity 0.15s ease-out;
        }
        .card-action:hover { opacity: 0.8; }

        /* ============================================
           PROGRESS BAR
           ============================================ */
        .prog-bar-wrap { margin-bottom: 8px; }
        .prog-bar-label {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 6px;
            align-items: center;
        }
        .prog-bar-label span:first-child {
            color: var(--ink);
            font-weight: 500;
            letter-spacing: -0.14px;
        }
        .prog-bar-label span:last-child {
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            font-size: 12px;
        }
        .prog-bar-track {
            height: 6px;
            background: var(--hairline);
            border-radius: var(--r-full);
            overflow: hidden;
        }
        .prog-bar-fill {
            height: 100%;
            border-radius: var(--r-full);
            transition: width 1s cubic-bezier(.4,0,.2,1);
        }
        .bar-exceed { background: var(--success); }
        .bar-good { background: #3498db; }
        .bar-below { background: var(--critical); }

        /* ============================================
           ALERTS / NOTIFICATIONS
           ============================================ */
        .alert-item {
            display: flex;
            align-items: flex-start;
            gap: var(--s-md);
            padding: var(--s-md);
            border-radius: var(--r-lg);
            background: transparent;
            border: 1px solid var(--hairline-soft);
            margin-bottom: var(--s-xs);
            transition: background 0.15s ease-out;
        }
        .alert-item:hover { background: var(--hairline-soft); }

        .alert-dot {
            width: 8px;
            height: 8px;
            border-radius: var(--r-circle);
            flex-shrink: 0;
            margin-top: 6px;
        }
        .alert-dot.red { background: var(--critical); }
        .alert-dot.amber { background: var(--attention); }
        .alert-dot.green { background: var(--success); }
        .alert-dot.blue { background: var(--primary); }
        .alert-text {
            font-size: 13px;
            line-height: 1.5;
            color: var(--ink);
            letter-spacing: -0.14px;
        }
        .alert-time {
            font-size: 11px;
            color: var(--steel);
            margin-top: 2px;
            font-weight: 400;
        }

        /* ============================================
           CALENDAR (rebuilt flat, no decorative bars)
           ============================================ */
        .cal-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            font-size: 11px;
            color: var(--stone);
            text-align: center;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }
        .cal-day {
            aspect-ratio: 1;
            border-radius: var(--r-md);
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            color: var(--charcoal);
            transition: all 0.15s ease-out;
            position: relative;
        }
        .cal-details-container {
            grid-column: 1 / -1;
            margin-top: 4px;
            margin-bottom: 8px;
            padding: 12px;
            background: var(--surface-soft);
            border-radius: var(--r-md);
            border: 1px solid var(--hairline-soft);
            display: none;
        }
        .cal-details-title {
            font-size: 11px;
            font-weight: 600;
            color: var(--steel);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .cal-day:hover { background: var(--hairline-soft); color: var(--ink-deep); }
        .cal-day.today {
            background: var(--ink-button);
            color: var(--canvas);
            font-weight: 700;
        }
        .cal-day.has-task::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 4px;
            background: var(--teal-light);
            border-radius: 2px;
        }
        .cal-day.completed { color: var(--success); }
        .cal-day.completed::after {
            background: var(--success);
        }
        .cal-day.today.has-task::after { border: 1px solid var(--canvas); }
        .cal-day.empty { visibility: hidden; }

        .cal-legend {
            display: flex;
            gap: var(--s-lg);
            margin-top: var(--s-md);
            font-size: 11px;
            color: var(--steel);
            font-weight: 500;
        }
        .cal-legend-item { display: flex; align-items: center; gap: 6px; }
        .cal-legend-dot {
            width: 8px;
            height: 8px;
            border-radius: var(--r-circle);
        }

        /* ============================================
           SCORECARD — feature-icon-row pattern
           ============================================ */
        .score-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: var(--s-sm);
        }
        .score-item {
            background: transparent;
            border: 1px solid var(--hairline-soft);
            border-radius: var(--r-xl);
            padding: var(--s-md);
            text-align: center;
            transition: border-color 0.15s ease-out;
        }
        .score-item:hover { border-color: var(--hairline); }

        .score-ring {
            width: 56px;
            height: 56px;
            border-radius: var(--r-circle);
            border: 3px solid var(--hairline);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--s-sm);
            font-size: 14px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.14px;
        }
        .score-ring.high { border-color: var(--success); color: var(--success); }
        .score-ring.mid { border-color: var(--attention); color: var(--attention); }
        .score-ring.low { border-color: var(--critical); color: var(--critical); }

        .score-label {
            font-size: 11px;
            color: var(--steel);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.3;
        }

        .separator { height: 1px; background: var(--hairline-soft); margin: var(--s-lg) 0; }

        .overall-score {
            text-align: center;
            padding: var(--s-md) 0;
        }
        .overall-label {
            font-size: 12px;
            color: var(--steel);
            margin-bottom: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .overall-value {
            font-size: 40px;
            font-weight: 700;
            color: var(--ink-deep);
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.4px;
            line-height: 1;
        }
        .overall-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: var(--r-full);
            font-size: 12px;
            font-weight: 700;
            margin-top: var(--s-md);
            letter-spacing: 0;
        }
        .overall-badge.success { background: var(--success-bg); color: var(--success); }
        .overall-badge.critical { background: var(--critical-bg); color: var(--critical-strong); }

        /* ============================================
           PROMO BANNER (DESIGN.md: promo-banner)
           Full-width strip above topbar for time-bound offers
           ============================================ */
        .promo-banner {
            background: var(--warning);
            color: var(--ink-deep);
            padding: var(--s-md) var(--s-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--s-md);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: -0.14px;
            line-height: 1.43;
            flex-shrink: 0;
        }
        .promo-banner-icon { font-size: 16px; }
        .promo-banner-cta {
            color: var(--ink-deep);
            text-decoration: underline;
            text-underline-offset: 2px;
            font-weight: 700;
        }
        .promo-banner-cta:hover { text-decoration: none; }
        .promo-banner-close {
            background: none;
            border: none;
            color: var(--ink-deep);
            cursor: pointer;
            font-size: 16px;
            padding: 0 4px;
            opacity: 0.7;
        }
        .promo-banner-close:hover { opacity: 1; }

        /* ============================================
           BADGE SYSTEM (DESIGN.md: badge-*)
           All pill-shaped ({rounded.full})
           ============================================ */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: var(--r-full);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0;
            line-height: 1.33;
            white-space: nowrap;
        }
        .badge-promo-yellow { background: var(--warning); color: var(--ink-deep); }
        .badge-attention { background: var(--attention); color: var(--canvas); }
        .badge-success { background: var(--success); color: var(--canvas); }
        .badge-critical { background: var(--critical); color: var(--canvas); }
        .badge-cobalt { background: var(--primary); color: var(--canvas); }
        .badge-purple { background: var(--oculus-purple); color: var(--canvas); }
        .badge-outline {
            background: transparent;
            color: var(--ink);
            border: 1px solid var(--hairline);
        }
        /* Tinted variants for softer status indicators */
        .badge-soft-success { background: var(--success-bg); color: var(--success); }
        .badge-soft-attention { background: var(--attention-bg); color: var(--attention); }
        .badge-soft-critical { background: var(--critical-bg); color: var(--critical-strong); }
        .badge-soft-cobalt { background: var(--primary-soft); color: var(--primary); }
        .badge-soft-purple { background: var(--oculus-purple-bg); color: var(--oculus-purple); }

        /* ============================================
           BUTTON SYSTEM (DESIGN.md: button-primary, button-buy-cta, button-secondary, button-ghost)
           Pill-shaped always ({rounded.full})
           ============================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--s-xs);
            padding: 14px 30px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.43;
            letter-spacing: -0.14px;
            border-radius: var(--r-full);
            border: none;
            cursor: pointer;
            transition: all 0.15s ease-out;
            text-decoration: none;
            white-space: nowrap;
            font-family: inherit;
        }
        /* button-primary (cobalt — for action surfaces) */
        .btn-primary {
            background: var(--primary);
            color: var(--on-primary, #ffffff);
        }
        .btn-primary:hover { background: var(--primary-deep); transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 100, 224, 0.4); }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary:disabled { background: var(--disabled); color: var(--stone); cursor: not-allowed; transform: none; box-shadow: none; }

        /* button-ink (white pill — marketing CTAs) */
        .btn-ink {
            background: var(--ink-button);
            color: var(--canvas);
        }
        .btn-ink:hover { background: var(--ink-button-pressed); }

        /* button-secondary (outlined ghost) */
        .btn-secondary {
            background: transparent;
            color: var(--ink-deep);
            border: 2px solid var(--ink-deep);
            padding: 12px 28px;
        }
        .btn-secondary:hover { background: var(--ink-deep); color: var(--canvas); }

        /* button-ghost (quieter outlined) */
        .btn-ghost {
            background: transparent;
            color: var(--ink-deep);
            border: 2px solid var(--hairline);
            padding: 10px 22px;
        }
        .btn-ghost:hover { border-color: var(--ink-deep); }

        /* button-icon-circular (40x40) */
        .btn-icon {
            width: 40px;
            height: 40px;
            padding: 0;
            border-radius: var(--r-circle);
            background: var(--ink-button);
            color: var(--canvas);
        }
        .btn-icon:hover { background: var(--ink-button-pressed); }

        /* Small variant (for inline use) */
        .btn-sm { padding: 8px 16px; font-size: 12px; }
        .btn-xs { padding: 4px 12px; font-size: 11px; }

        /* ============================================
           QUICK ACTIONS BAR
           Row of primary CTAs for the dashboard
           ============================================ */
        .quick-actions {
            display: flex;
            gap: var(--s-sm);
            margin-bottom: var(--s-xl);
            flex-wrap: wrap;
        }

        /* ============================================
           SCROLLBAR
           ============================================ */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--hairline); border-radius: var(--r-full); }
        ::-webkit-scrollbar-thumb:hover { background: var(--stone); }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 1280px) {
            .kpi-grid { grid-auto-flow: row; grid-template-columns: repeat(2, 1fr); }
            .hero-stats { gap: var(--s-lg); }
        }
        @media (max-width: 1024px) {
            .two-col { grid-template-columns: 1fr; }
            .hero-band { flex-direction: column; align-items: flex-start; }
            .hero-stats { width: 100%; justify-content: space-between; }
            .hero-stat { text-align: left; }
        }
        @media (max-width: 768px) {
            .content { padding: var(--s-lg); }
            .page-header h1 { font-size: 28px; }
            .page-header .subhead { font-size: 20px; }
            .hero-title { font-size: 24px; }
        }
    </style>
</head>
<body>
<div id="app">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">AMLO Dashboard<span><?= e($user['kanwil_nama']) ?></span></div>
            <div class="user-chip">
                <div class="user-avatar"><?= strtoupper(substr($user['nama'], 0, 2)) ?></div>
                <div class="user-chip-info">
                    <div class="user-chip-name"><?= e($user['nama']) ?></div>
                    <div class="user-chip-role"><?= e(get_role_label($user['role'])) ?></div>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav" id="sidebar-nav">
            <div class="nav-section-label">Menu Utama</div>
            <?php foreach ($nav_items as $item): ?>
                <a href="<?= $item['id'] ?>.php" class="nav-item <?= $item['id'] === 'dashboard' ? 'active' : '' ?>" id="nav-<?= $item['id'] ?>">
                    <span class="nav-icon"><?= $item['icon'] ?></span>
                    <?= $item['label'] ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn">⬅ Keluar</a>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main-area">

        <div class="topbar">
            <div class="topbar-title">Beranda — Overview Harian</div>
            <div class="topbar-date"><?= $tanggal_hari_ini ?></div>
            <div class="topbar-notif" onclick="document.getElementById('notifikasi-section').scrollIntoView({behavior: 'smooth', block: 'start'})" style="cursor:pointer;" title="Lihat Notifikasi">🔔<?php if (!empty($alerts)): ?><div class="notif-dot"></div><?php endif; ?></div>
        </div>

        <div class="content">
            <!-- HERO BAND (DESIGN.md: hero-band-marketing) -->
            <div class="hero-band">
                <div class="hero-content">
                    <div class="hero-eyebrow">AMLO Monitoring Activity</div>
                    <div class="hero-title"><?= $greeting ?>, <?= e(explode(' ', $user['nama'])[0]) ?> 👋</div>
                    <div class="hero-subtitle">
                        <?php if ($user['role'] === 'ho'): ?>
                            Pantauan kinerja AML nasional — 19 Kantor Wilayah se-Indonesia
                        <?php elseif ($user['role'] === 'lead'): ?>
                            Monitoring tim AMLO Officer di <?= e($user['kanwil_nama']) ?>
                        <?php else: ?>
                            Aktivitas harian Anda — tetap semangat, selesaikan target tepat waktu
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-value"><?= $dashboard_average_progress ?>%</div>
                        <div class="hero-stat-label">Skor Anda</div>
                    </div>

                </div>
            </div>


            <!-- KPI CARDS (DESIGN.md: card-icon-feature) -->
            <?php if ($user['role'] === 'officer'): ?>
                <div class="kpi-grid">
                    <div class="kpi-card gold">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">📋</div>
                            <div class="kpi-label">Total Tugas</div>
                            <div class="kpi-sub">📅 <?= date('F Y') ?></div>
                        </div>
                        <div class="kpi-value"><?= $kpi_gold ?></div>
                    </div>
                    <div class="kpi-card green">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">✅</div>
                            <div class="kpi-label">Selesai</div>
                            <div class="kpi-sub">🎯 <?= $kpi_gold > 0 ? round($kpi_green / $kpi_gold * 100) : 0 ?>% dari total</div>
                        </div>
                        <div class="kpi-value"><?= $kpi_green ?></div>
                    </div>
                    <div class="kpi-card orange">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">⌛</div>
                            <div class="kpi-label">Waiting Approval</div>
                            <div class="kpi-sub">⏳ Menunggu Lead</div>
                        </div>
                        <div class="kpi-value"><?= $kpi_waiting ?></div>
                    </div>
                    <div class="kpi-card teal">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">⚡</div>
                            <div class="kpi-label">Berjalan</div>
                            <div class="kpi-sub">🔄 In progress</div>
                        </div>
                        <div class="kpi-value"><?= $kpi_teal ?></div>
                    </div>
                    <div class="kpi-card red">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">⏳</div>
                            <div class="kpi-label">Pending</div>
                            <div class="kpi-sub">⚠️ Perlu perhatian</div>
                        </div>
                        <div class="kpi-value"><?= $kpi_red ?></div>
                    </div>
                </div>
            <?php elseif ($user['role'] === 'lead'): ?>
                <div class="kpi-grid">
                    <div class="kpi-card gold">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">👥</div>
                            <div class="kpi-label">Officer Tim</div>
                            <div class="kpi-sub">📍 <?= e($user['kanwil_nama']) ?></div>
                        </div>
                        <div class="kpi-value"><?= count($team_tasks) ?></div>
                    </div>
                    <div class="kpi-card green">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">⭐</div>
                            <div class="kpi-label">Exceed</div>
                            <div class="kpi-sub">✅ Officer berprestasi</div>
                        </div>
                        <div class="kpi-value"><?= count(array_filter($team_tasks, fn($t) => $t['summary']['done'] + $t['summary']['approved'] >= 8)) ?></div>
                    </div>
                    <div class="kpi-card teal">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">👍</div>
                            <div class="kpi-label">Good</div>
                            <div class="kpi-sub">📊 Sesuai target</div>
                        </div>
                        <div class="kpi-value"><?= count(array_filter($team_tasks, fn($t) => $t['summary']['done'] + $t['summary']['approved'] >= 5 && $t['summary']['done'] + $t['summary']['approved'] < 8)) ?></div>
                    </div>
                    <div class="kpi-card red">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">❗</div>
                            <div class="kpi-label">Below</div>
                            <div class="kpi-sub">⚡ Coaching</div>
                        </div>
                        <div class="kpi-value"><?= count(array_filter($team_tasks, fn($t) => $t['summary']['done'] + $t['summary']['approved'] < 5)) ?></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="kpi-grid">
                    <div class="kpi-card gold">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">🌐</div>
                            <div class="kpi-label">Total Kanwil</div>
                            <div class="kpi-sub">📍 Seluruh Indonesia</div>
                        </div>
                        <div class="kpi-value"><?= count($wilayah_data) ?></div>
                    </div>
                    <div class="kpi-card green">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">✅</div>
                            <div class="kpi-label">Exceed</div>
                            <div class="kpi-sub">↑ Wilayah berprestasi</div>
                        </div>
                        <div class="kpi-value"><?= count(array_filter($wilayah_data, fn($w) => $w['exceed_count'] > $w['below_count'])) ?></div>
                    </div>
                    <div class="kpi-card teal">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">👤</div>
                            <div class="kpi-label">Total Officer</div>
                            <div class="kpi-sub">📊 Kanwil se-Indonesia</div>
                        </div>
                        <div class="kpi-value"><?= array_sum(array_column($wilayah_data, 'total_officer')) ?></div>
                    </div>
                    <div class="kpi-card red">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">⚠️</div>
                            <div class="kpi-label">Butuh Perhatian</div>
                            <div class="kpi-sub">🔴 Intervensi segera</div>
                        </div>
                        <div class="kpi-value"><?= count(array_filter($wilayah_data, fn($w) => $w['below_count'] > 0)) ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- PROGRESS + ALERTS (DESIGN.md: two-col card layout) -->
            <div class="two-col">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📊 Progress Laporan</div>
                        <a href="laporan.php" class="card-action">Lihat Semua →</a>
                    </div>
                    <?php 
                    $filtered_progress = array_filter($perf['tasks'], fn($t) => $t['progress'] > 0 && $t['periode'] !== 'harian');
                    if (empty($filtered_progress)): 
                    ?>
                        <div style="padding:var(--s-xl);text-align:center;color:var(--steel);font-size:13px">
                            Belum melakukan progress laporan. ✨
                        </div>
                    <?php 
                    else:
                        foreach (array_slice($filtered_progress, 0, 5) as $t): 
                    ?>
                        <div class="prog-bar-wrap">
                            <div class="prog-bar-label">
                                <span><?= e($t['nama']) ?></span>
                                <span style="color:<?= get_progress_color($t['progress']) ?>"><?= $t['progress'] ?>%</span>
                            </div>
                            <div class="prog-bar-track">
                                <div class="prog-bar-fill <?= $t['progress'] >= 100 ? 'bar-exceed' : ($t['progress'] >= 80 ? 'bar-good' : 'bar-below') ?>"
                                     style="width:<?= $t['progress'] ?>%"></div>
                            </div>
                        </div>
                    <?php 
                        endforeach; 
                    endif; 
                    ?>
                </div>

                <div class="card">
                    <div class="card-header" style="flex-direction: column; align-items: flex-start; gap: 16px;">
                        <div class="card-title" style="width: 100%;">📅 Kalender Aktivitas — <?= $nama_bulan[$bulan] ?> <?= $tahun ?></div>
                        <form method="GET" id="cal-filter-form" class="todo-filters-container" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; width: 100%;">
                            <div>
                                <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Bulan</label>
                                <select name="bulan" class="select-field" style="width: 160px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--hairline); background: var(--surface-soft); color: var(--ink-deep);" onchange="document.getElementById('cal-filter-form').submit()">
                                    <?php for ($m=1; $m<=12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $bulan == $m ? 'selected' : '' ?>><?= $nama_bulan[$m] ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Tahun</label>
                                <select name="tahun" class="select-field" style="width: 120px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--hairline); background: var(--surface-soft); color: var(--ink-deep);" onchange="document.getElementById('cal-filter-form').submit()">
                                    <option value="2026" <?= $tahun == 2026 ? 'selected' : '' ?>>2026</option>
                                    <option value="2027" <?= $tahun == 2027 ? 'selected' : '' ?>>2027</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="cal-weekdays">
                        <div>SEN</div><div>SEL</div><div>RAB</div><div>KAM</div><div>JUM</div><div>SAB</div><div>MIN</div>
                    </div>
                    <?php
                    $num_days = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));
                    $days = range(1, $num_days);

                    $taskDaysMap = [];
                    if ($user['role'] === 'lead' || $user['role'] === 'officer') {
                        $cal_assignments = db_fetch_all(
                            "SELECT task_name, due_date, status FROM assignments 
                             WHERE YEAR(due_date) = ? AND MONTH(due_date) = ? 
                             AND (from_user_id = ? OR to_user_id = ?)
                             AND task_name IN ('RFI Remittance', 'Adhoc EDD', 'Pendampingan AML')",
                            [$tahun, $bulan, $user['id'], $user['id']]
                        );
                        foreach ($cal_assignments as $ca) {
                            $day = (int)date('j', strtotime($ca['due_date']));
                            if (!isset($taskDaysMap[$day])) {
                                $taskDaysMap[$day] = [];
                            }
                            $taskDaysMap[$day][] = $ca;
                        }
                    }

                    // Calculate first day offset (0=Mon, 6=Sun)
                    $firstDow = (int)date('N', mktime(0, 0, 0, $bulan, 1, $tahun)) - 1;

                    echo '<div class="cal-grid">';
                    // Empty cells for offset
                    for ($i = 0; $i < $firstDow; $i++) {
                        echo '<div class="cal-day empty"></div>';
                    }
                    foreach ($days as $d) {
                        $isToday = ($d == date('j') && $bulan == date('n') && $tahun == date('Y'));
                        
                        $dayTasks = $taskDaysMap[$d] ?? [];
                        $hasTask = count($dayTasks) > 0;
                        $isDone = false;
                        
                        $onclick = '';
                        if ($hasTask) {
                            $allDone = true;
                            foreach ($dayTasks as $t) {
                                if ($t['status'] !== 'selesai') $allDone = false;
                            }
                            if ($allDone) $isDone = true;
                            $onclick = " onclick=\"showCalTasks($d, this)\" style=\"cursor:pointer\" title=\"Klik untuk Menampilkan/Menyembunyikan Penugasan\"";
                        }

                        $cls = 'cal-day';
                        if ($isToday) $cls .= ' today';
                        elseif ($isDone) $cls .= ' completed';
                        elseif ($hasTask) $cls .= ' has-task';
                        
                        echo "<div class=\"$cls\"$onclick>$d</div>";
                    }
                    echo '</div>';
                    ?>
                    <div class="cal-legend">
                        <div class="cal-legend-item"><span class="cal-legend-dot" style="background:var(--ink-button)"></span>Hari Ini</div>
                        <div class="cal-legend-item"><span class="cal-legend-dot" style="background:var(--success)"></span>Selesai</div>
                        <div class="cal-legend-item"><span class="cal-legend-dot" style="background:var(--teal-light)"></span>Ada Tugas</div>
                    </div>
                    
                    <div id="cal-details-container" class="cal-details-container"></div>
                    <?php
                    $taskData = [];
                    foreach ($taskDaysMap as $day => $tasks) {
                        $html = "<div class='cal-details-title'>Penugasan Tanggal $day</div>";
                        foreach ($tasks as $t) {
                            $badge = $t['status'] === 'selesai' ? '<span style="color:var(--success); font-weight:600; font-size:10px;">✅ Selesai</span>' : '<span style="color:var(--attention); font-weight:600; font-size:10px;">⏳ Menunggu</span>';
                            $html .= "<div style='font-size:13px; margin-bottom:6px; color:var(--ink-deep);'>• " . e($t['task_name']) . " <span style='float:right'>$badge</span></div>";
                        }
                        $taskData[$day] = $html;
                    }
                    ?>
                    <script>
                        const calTaskData = <?= json_encode($taskData) ?>;
                        function showCalTasks(day, element) {
                            const container = document.getElementById('cal-details-container');
                            if (calTaskData[day]) {
                                if (container.dataset.activeDay == day && container.style.display === 'block') {
                                    container.style.display = 'none'; // toggle hide
                                } else {
                                    container.innerHTML = calTaskData[day];
                                    container.style.display = 'block';
                                    container.dataset.activeDay = day;

                                    if (element && element.parentNode.classList.contains('cal-grid')) {
                                        const grid = element.parentNode;
                                        const cells = Array.from(grid.children).filter(c => c.id !== 'cal-details-container');
                                        const cellIndex = cells.indexOf(element);
                                        const remainder = cellIndex % 7;
                                        const endOfRowIndex = cellIndex + (6 - remainder);
                                        
                                        if (endOfRowIndex >= cells.length - 1) {
                                            grid.appendChild(container);
                                        } else {
                                            grid.insertBefore(container, cells[endOfRowIndex + 1]);
                                        }
                                    }
                                }
                            }
                        }
                    </script>
                </div>
            </div>

            <!-- ALERTS + SCORECARD -->
            <div class="two-col">
                <div class="card" id="notifikasi-section">
                    <div class="card-header">
                        <div class="card-title">🔔 Notifikasi & Alert</div>
                    </div>

                    <?php
                    if (empty($alerts)) {
                        echo '<div style="padding:var(--s-xl);text-align:center;color:var(--steel);font-size:13px">Tidak ada notifikasi saat ini. ✨</div>';
                    } else {
                        foreach ($alerts as $alert):
                    ?>
                        <div class="alert-item">
                            <div class="alert-dot <?= $alert['type'] ?>"></div>
                            <div>
                                <div class="alert-text"><?= $alert['text'] ?></div>
                                <div class="alert-time"><?= $alert['time'] ?></div>
                            </div>
                        </div>
                    <?php
                        endforeach;
                    }
                    ?>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🏆 Scorecard Kinerja</div>
                    </div>
                    <div class="score-grid">
                        <div class="score-item">
                            <div class="score-ring high"><?= $perf['exceed'] ?></div>
                            <div class="score-label">Exceed<br>≥100%</div>
                        </div>
                        <div class="score-item">
                            <div class="score-ring <?= $perf['good'] >= 5 ? 'high' : 'mid' ?>"><?= $perf['good'] ?></div>
                            <div class="score-label">Good<br>≥80%</div>
                        </div>
                        <div class="score-item">
                            <div class="score-ring low"><?= $perf['below'] ?></div>
                            <div class="score-label">Below<br>&lt;80%</div>
                        </div>
                        <div class="score-item">
                            <div class="score-ring <?= $perf['pending'] > 0 ? 'low' : 'mid' ?>"><?= $perf['pending'] ?></div>
                            <div class="score-label">Pending<br>0%</div>
                        </div>
                    </div>
                    <div class="separator"></div>
                    <div class="overall-score">
                        <div class="overall-label">Skor Keseluruhan</div>
                        <div class="overall-value"><?= $perf['average_progress'] ?>%</div>
                        <div>
                            <span class="overall-badge <?= $perf['average_progress'] >= 80 ? 'success' : 'critical' ?>">
                                <?= $perf['average_progress'] >= 80 ? '👍 Good Performance' : '⚠️ Perlu Perbaikan' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Animate progress bars on load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.prog-bar-fill').forEach(bar => {
            const w = bar.style.width;
            bar.style.width = '0';
            setTimeout(() => { bar.style.width = w; }, 100);
        });

        // Sidebar nav active state
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.href === window.location.href) {
                item.classList.add('active');
            }
        });
    });
</script>
</body>
</html>
