<?php
/**
 * AMLO Dashboard - Tasks Page (To-Do List)
 * Officer can view, update progress, submit for approval
 */


require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = amlo_get_current_user();
$csrf_token = generate_csrf_token();
$period = get_current_period();

// Override tahun jika ada filter tahun
if (isset($_GET['tahun']) && is_numeric($_GET['tahun'])) {
    $period['tahun'] = (int)$_GET['tahun'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Token keamanan tidak valid'];
        header('Location: tasks.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_progress') {
        $template_id = (int)$_POST['template_id'];
        $progress = (int)$_POST['progress'];
        $keterangan = trim($_POST['keterangan'] ?? '');
        $status = $progress >= 100 ? 'done' : ($progress > 0 ? 'active' : 'pending');

        $req_bulan = isset($_POST['bulan']) && $_POST['bulan'] ? (int)$_POST['bulan'] : $period['bulan'];
        $req_tahun = isset($_POST['tahun']) && $_POST['tahun'] ? (int)$_POST['tahun'] : $period['tahun'];
        $officer_id = isset($_POST['officer_id']) ? (int)$_POST['officer_id'] : $user['id'];

        // Upsert task progress
        $existing = db_fetch_one(
            "SELECT id, periode FROM task_progress WHERE user_id = ? AND template_id = ? AND tahun = ? AND bulan = ?",
            [$officer_id, $template_id, $req_tahun, $req_bulan]
        );

        if ($existing) {
            db_exec(
                "UPDATE task_progress SET progress = ?, status = ?, keterangan = ?, updated_at = NOW() WHERE id = ?",
                [$progress, $status, $keterangan, $existing['id']]
            );
            log_activity('task_update', "Update progress task ID $template_id to $progress% for user $officer_id");
        } else {
            // Get periode from template
            $template = db_fetch_one("SELECT periode FROM task_templates WHERE id = ?", [$template_id]);
            $periode = $template['periode'] ?? 'adhoc';
            db_insert(
                "INSERT INTO task_progress (user_id, template_id, periode, tahun, bulan, progress, status, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$officer_id, $template_id, $periode, $req_tahun, $req_bulan, $progress, $status, $keterangan]
            );
            log_activity('task_create', "Create progress task ID $template_id for bulan $req_bulan tahun $req_tahun for user $officer_id");
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Progress berhasil disimpan!'];
        header('Location: tasks.php');
        exit;
    }

    if ($action === 'submit_approval') {
        $task_progress_id = (int)$_POST['task_progress_id'];

        // Get task progress
        $tp = db_fetch_one("SELECT * FROM task_progress WHERE id = ?", [$task_progress_id]);

        if ($tp) {
            // Create submission
            db_insert(
                "INSERT INTO submissions (task_progress_id, submitted_by, status) VALUES (?, ?, 'pending')",
                [$task_progress_id, $user['id']]
            );

            // Update status to active if pending
            if ($tp['status'] === 'pending') {
                db_exec("UPDATE task_progress SET status = 'active' WHERE id = ?", [$task_progress_id]);
            }

            log_activity('submit_approval', "Submit task progress ID $task_progress_id for approval");
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Tugas berhasil disubmit untuk review!'];
        }

        header('Location: tasks.php');
        exit;
    }
}

// Get flash message
$flash = get_flash();

// 1. Get ALL active task templates
$templates = db_fetch_all(
    "SELECT tt.* FROM task_templates tt
     WHERE tt.is_active = 1
     ORDER BY FIELD(tt.periode, 'harian', 'bulanan', 'triwulan', 'semesteran', 'adhoc'), tt.kategori, tt.nama"
);

// 2. Get target officers based on role
$target_officers = [];
if ($user['role'] === 'lead') {
    $target_officers = db_fetch_all(
        "SELECT id, nama FROM users WHERE kanwil_id = ? AND role = 'officer' AND aktif = 1 ORDER BY nama",
        [$user['kanwil_id']]
    );
} elseif ($user['role'] === 'ho') {
    $target_officers = db_fetch_all("SELECT id, nama FROM users WHERE role = 'officer' AND aktif = 1 ORDER BY nama");
} else {
    $target_officers = [['id' => $user['id'], 'nama' => $user['nama']]];
}

$officer_ids = array_column($target_officers, 'id');
if (empty($officer_ids)) {
    $officer_ids = [-1]; // Fallback
}
$placeholders = implode(',', array_fill(0, count($officer_ids), '?'));
$query_params = array_merge($officer_ids, [$period['tahun']]);

// 3. Get ALL progress for target officers and current year
$progress_records = db_fetch_all(
    "SELECT tp.id as progress_id, tp.user_id, tp.template_id, tp.bulan, tp.progress, tp.status as progress_status, tp.keterangan, tp.updated_at,
            s.id as submission_id, s.status as submission_status
     FROM task_progress tp
     LEFT JOIN submissions s ON tp.id = s.task_progress_id AND s.status = 'pending'
     WHERE tp.user_id IN ($placeholders) AND tp.tahun = ?",
    $query_params
);

// Index progress by [user_id][template_id][bulan]
$progress_map = [];
foreach ($progress_records as $pr) {
    $progress_map[$pr['user_id']][$pr['template_id']][$pr['bulan']] = $pr;
}

// 4. Build $display_tasks array
$default_prog = [
    'progress_id' => null,
    'progress' => 0,
    'progress_status' => 'pending',
    'keterangan' => '',
    'updated_at' => null,
    'submission_id' => null,
    'submission_status' => null
];
$nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$display_tasks = [];

foreach ($target_officers as $officer) {
    $uid = $officer['id'];
    $unama = $officer['nama'];

    foreach ($templates as $tt) {
        if ($tt['periode'] === 'bulanan') {
            // Generate 12 items
            for ($m = 1; $m <= 12; $m++) {
                $t = $tt;
                // Rename 'Monthly' or append month
                if (stripos($t['nama'], 'Monthly') !== false) {
                    $t['nama'] = str_ireplace('Monthly', $nama_bulan[$m] . ' ' . $period['tahun'], $t['nama']);
                } else {
                    $t['nama'] .= ' - ' . $nama_bulan[$m] . ' ' . $period['tahun'];
                }
                $t['req_bulan'] = $m;
                $t['req_tahun'] = $period['tahun'];
                $t['vis_bulan'] = (string)$m;
                
                // Attach progress if exists
                $prog = $progress_map[$uid][$tt['id']][$m] ?? $default_prog;
                $t = array_merge($t, $prog);
                $t['officer_id'] = $uid;
                $t['officer_nama'] = $unama;
                $display_tasks[] = $t;
            }
        } elseif ($tt['periode'] === 'triwulan') {
            // Generate 4 items
            $tw_map = [1 => 3, 2 => 6, 3 => 9, 4 => 12];
            $tw_vis = [1 => [1,2,3], 2 => [4,5,6], 3 => [7,8,9], 4 => [10,11,12]];
            for ($tw = 1; $tw <= 4; $tw++) {
                $m = $tw_map[$tw];
                $t = $tt;
                $t['nama'] .= ' - TW ' . $tw . ' ' . $period['tahun'];
                $t['req_bulan'] = $m;
                $t['req_tahun'] = $period['tahun'];
                $t['vis_bulan'] = implode(',', $tw_vis[$tw]);
                
                // Attach progress if exists
                $prog = $progress_map[$uid][$tt['id']][$m] ?? $default_prog;
                $t = array_merge($t, $prog);
                $t['officer_id'] = $uid;
                $t['officer_nama'] = $unama;
                $display_tasks[] = $t;
            }
        } elseif ($tt['periode'] === 'semesteran') {
            // Generate 2 items
            $sem_map = [1 => 6, 2 => 12];
            $sem_vis = [1 => [1,2,3,4,5,6], 2 => [7,8,9,10,11,12]];
            for ($sem = 1; $sem <= 2; $sem++) {
                $m = $sem_map[$sem];
                $t = $tt;
                $t['nama'] .= ' - Semester ' . $sem . ' ' . $period['tahun'];
                $t['req_bulan'] = $m;
                $t['req_tahun'] = $period['tahun'];
                $t['vis_bulan'] = implode(',', $sem_vis[$sem]);
                
                // Attach progress if exists
                $prog = $progress_map[$uid][$tt['id']][$m] ?? $default_prog;
                $t = array_merge($t, $prog);
                $t['officer_id'] = $uid;
                $t['officer_nama'] = $unama;
                $display_tasks[] = $t;
            }
        } else {
            // Just use the current period for harian & adhoc
            $m = $period['bulan'];
            $t = $tt;
            if ($tt['periode'] === 'adhoc') {
                $t['nama'] .= ' ' . $period['tahun'];
                $t['vis_bulan'] = '1,2,3,4,5,6,7,8,9,10,11,12';
            } else {
                $t['vis_bulan'] = (string)$m;
            }
            $t['req_bulan'] = $m;
            $t['req_tahun'] = $period['tahun'];
            
            $prog = $progress_map[$uid][$tt['id']][$m] ?? $default_prog;
            $t = array_merge($t, $prog);
            $t['officer_id'] = $uid;
            $t['officer_nama'] = $unama;
            $display_tasks[] = $t;
        }
    }
}

$tasks = $display_tasks;

$nav_items = get_nav_items($user['role']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do Harian — AMLO Dashboard</title>
    <link href="../assets/css/fonts.css" rel="stylesheet">
    <link href="../assets/css/amlo-design-system.css" rel="stylesheet">
    <style>
        .todo-filters { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-btn { padding: 6px 14px; border-radius: 20px; border: 1px solid var(--hairline); background: transparent; color: var(--steel); font-size: 12px; font-weight: 500; cursor: pointer; font-family: 'Inter', sans-serif; transition: all 0.2s; }
        .filter-btn:hover { border-color: var(--gold); color: var(--gold); }
        .filter-btn.active { background: var(--gold-soft); border-color: var(--gold); color: var(--gold); }

        .todo-item { background: var(--hairline); border: 1px solid var(--hairline); border-radius: 12px; padding: 16px; margin-bottom: 10px; transition: all 0.2s; cursor: pointer; }
        .todo-item:hover { border-color: var(--gold-soft); background: var(--gold-soft); }
        .todo-item.done { }
        .todo-item.pending-submit { border-color: var(--attention); }

        .todo-row { display: flex; align-items: flex-start; gap: 14px; }
        .todo-check { width: 22px; height: 22px; border-radius: 6px; border: 2px solid var(--steel); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 13px; margin-top: 1px; }
        .todo-item.done .todo-check { background: var(--success); border-color: var(--success); }
        .todo-body { flex: 1; min-width: 0; }
        .todo-title { font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .todo-item.done .todo-title { color: var(--steel); }
        .todo-meta { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 6px; }
        .todo-tag { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 10px; letter-spacing: 0.5px; text-transform: uppercase; }
        .tag-harian { background: rgba(27,143,158,0.2); color: var(--teal-light); }
        .tag-bulanan { background: var(--gold-soft); color: var(--gold); }
        .tag-adhoc { background: rgba(243,156,18,0.2); color: var(--attention); }
        .tag-semesteran { background: rgba(46,204,113,0.15); color: var(--success); }
        .tag-triwulan { background: rgba(155,89,182,0.2); color: #bb8dd8; }

        .mini-progress { width: 80px; height: 6px; background: var(--hairline); border-radius: 3px; overflow: hidden; }
        .mini-progress-bar { height: 100%; border-radius: 3px; transition: width 0.5s; }
        .bar-exceed { background: var(--success); }
        .bar-good { background: #3498db; }
        .bar-below { background: var(--critical); }
        .progress-pct { font-size: 11px; font-weight: 700; font-family: monospace; min-width: 32px; }
        .due-badge { font-size: 10px; color: var(--steel); display: flex; align-items: center; gap: 4px; }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: var(--canvas); backdrop-filter: blur(6px); z-index: 200; display: none; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal { background: linear-gradient(135deg, var(--surface-soft), var(--surface-elevated)); border: 1px solid var(--hairline); border-radius: 18px; width: 550px; max-height: 80vh; overflow-y: auto; padding: 32px; animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .modal-title { font-family: 'Inter', sans-serif; font-size: 18px; max-width: 460px; }
        .modal-close { cursor: pointer; color: var(--steel); font-size: 20px; padding: 4px; }
        .input-group { margin-bottom: 16px; }
        .input-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block; }
        .input-field, .select-field, .textarea-field { width: 100%; background: var(--hairline); border: 1px solid var(--hairline); border-radius: 8px; padding: 10px 14px; color: var(--ink-deep); font-family: 'Inter', sans-serif; font-size: 13px; outline: none; transition: border-color 0.2s; }
        .input-field:focus, .select-field:focus, .textarea-field:focus { border-color: var(--gold); }
        .textarea-field { resize: vertical; min-height: 80px; }
        .select-field option { background: var(--canvas); }
        .progress-input-wrap { display: flex; gap: 12px; align-items: center; }
        .progress-input-wrap input[type="range"] { flex: 1; accent-color: var(--gold); }
        .prog-num { font-family: monospace; font-size: 18px; font-weight: 600; color: var(--gold); min-width: 50px; text-align: right; }

        /* ===== Segmented Progress Input ===== */
        .seg-progress { display: flex; flex-direction: column; gap: 10px; width: 100%; }
        .seg-progress-header { display: flex; justify-content: space-between; align-items: baseline; }
        .seg-progress-label { font-family: 'Inter', sans-serif; font-size: 11px; color: var(--steel); letter-spacing: 0.5px; text-transform: uppercase; font-weight: 600; }
        .seg-progress-value { font-family: 'Inter', sans-serif; font-size: 28px; font-weight: 700; color: var(--gold); font-variant-numeric: tabular-nums; line-height: 1; }
        .seg-progress-value.exceed { color: var(--success); }
        .seg-progress-value.good { color: #3498db; }
        .seg-progress-value.below { color: var(--critical); }
        .seg-progress-value.pending { color: var(--steel); }
        .seg-progress-track { display: grid; grid-template-columns: repeat(11, 1fr); gap: 4px; padding: 8px; background: var(--hairline); border: 1px solid var(--hairline); border-radius: 10px; }
        .seg-progress-seg { position: relative; height: 38px; background: var(--hairline); border: 1px solid transparent; border-radius: 6px; cursor: pointer; transition: all 0.15s ease; display: flex; align-items: end; justify-content: center; padding-bottom: 4px; font-size: 9px; font-weight: 600; color: var(--steel); user-select: none; }
        .seg-progress-seg:hover { background: var(--gold-soft); border-color: var(--gold-soft); color: var(--ink-deep); transform: translateY(-1px); }
        .seg-progress-seg.active { background: linear-gradient(180deg, var(--gold), #a8862a); border-color: var(--gold); color: var(--canvas); box-shadow: 0 2px 8px var(--gold-soft); font-weight: 700; }
        .seg-progress-seg.active.exceed { background: linear-gradient(180deg, var(--success), #1e8449); border-color: var(--success); color: white; box-shadow: 0 2px 8px rgba(46,204,113,0.4); }
        .seg-progress-seg.active.good { background: linear-gradient(180deg, #3498db, #1d6fa5); border-color: #3498db; color: white; box-shadow: 0 2px 8px rgba(52,152,219,0.4); }
        .seg-progress-seg.active.below { background: linear-gradient(180deg, var(--critical), #b73838); border-color: var(--critical); color: white; box-shadow: 0 2px 8px rgba(224,82,82,0.4); }
        .seg-progress-seg.zero { opacity: 0.5; font-size: 10px; }
        .seg-progress-seg.full { font-weight: 800; }
        .seg-progress-bar { height: 4px; background: var(--hairline); border-radius: 2px; overflow: hidden; margin-top: 2px; }
        .seg-progress-bar-fill { height: 100%; background: linear-gradient(90deg, var(--critical) 0%, var(--attention) 50%, #3498db 80%, var(--success) 100%); border-radius: 2px; transition: width 0.3s ease; }
        .seg-progress-quick { display: flex; gap: 6px; margin-top: 4px; }
        .seg-progress-quick-btn { flex: 1; padding: 5px 8px; background: var(--hairline); border: 1px solid var(--hairline); border-radius: 6px; color: var(--steel); font-size: 10px; font-weight: 600; cursor: pointer; transition: all 0.15s; font-family: 'Inter', sans-serif; }
        .seg-progress-quick-btn:hover { background: var(--gold-soft); border-color: var(--gold); color: var(--gold); }
        .modal-actions { display: flex; gap: 10px; margin-top: 24px; }
        .btn-primary { flex: 1; padding: 12px; background: linear-gradient(135deg, var(--gold), #b8962a); border: none; border-radius: 8px; color: var(--canvas); font-family: 'Inter', sans-serif; font-weight: 700; font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px var(--gold-soft); }
        .btn-secondary { padding: 12px 20px; background: var(--hairline); border: 1px solid var(--hairline); border-radius: 8px; color: var(--steel); font-family: 'Inter', sans-serif; font-weight: 600; font-size: 13px; cursor: pointer; }
        .btn-secondary:hover { color: var(--ink-deep); }
        .btn-submit { padding: 12px 20px; background: var(--teal); border: none; border-radius: 8px; color: white; font-family: 'Inter', sans-serif; font-weight: 700; font-size: 13px; cursor: pointer; }
        .btn-submit:hover { background: var(--teal-light); }

        .perf-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; }
        .perf-exceed { background: rgba(46,204,113,0.15); color: var(--success); }
        .perf-good { background: rgba(52,152,219,0.15); color: #3498db; }
        .perf-below { background: rgba(224,82,82,0.15); color: var(--critical); }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--gold-soft); border-radius: 2px; }
    </style>
</head>
<body>
<div id="app">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">To-Do List Harian</div>
            <div class="topbar-date"><?= tanggal_indonesia('now', 'long') ?></div>
            <div class="topbar-notif">🔔</div>
        </div>

        <div class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= $flash['type'] === 'success' ? '✅' : '⚠️' ?> <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h2>To-Do List Harian AMLO</h2>
                <p><?= count($tasks) ?> jenis laporan & tugas sesuai ketentuan — klik item untuk input progress</p>
            </div>

            <div class="todo-filters-container" style="display: flex; gap: 16px; margin-bottom: 20px; align-items: flex-end; flex-wrap: wrap;">
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Status Tugas</label>
                    <select id="filter-status" class="select-field" style="width: 150px; padding: 10px 14px;" onchange="applyFilters()">
                        <option value="all">Semua Status</option>
                        <option value="pending">Belum Dimulai</option>
                        <option value="active">Sedang Berjalan</option>
                        <option value="waiting">Waiting For Approval</option>
                        <option value="done">Selesai / Approved</option>
                    </select>
                </div>

                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Periode</label>
                    <select id="filter-periode" class="select-field" style="width: 150px; padding: 10px 14px;" onchange="applyFilters()">
                        <option value="all">Semua Periode</option>
                        <option value="harian">Harian</option>
                        <option value="bulanan">Bulanan</option>
                        <option value="triwulan">Triwulanan</option>
                        <option value="semesteran">Semesteran</option>
                        <option value="adhoc">Adhoc</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Bulan</label>
                    <?php $now_bulan = (int)date('n'); ?>
                    <select id="filter-bulan" class="select-field" style="width: 150px; padding: 10px 14px;" onchange="applyFilters()">
                        <option value="all">Semua Bulan</option>
                        <?php foreach($nama_bulan as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $num === $now_bulan ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Tahun</label>
                    <select id="filter-tahun" class="select-field" style="width: 120px; padding: 10px 14px;" onchange="window.location.href='?tahun=' + this.value">
                        <?php 
                        $current_req_tahun = $period['tahun'];
                        for($y = 2024; $y <= 2030; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == $current_req_tahun ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <?php if ($user['role'] !== 'officer'): ?>
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">AMLO Officer</label>
                    <select id="filter-officer" class="select-field" style="width: 150px; padding: 10px 14px;" onchange="applyFilters()">
                        <option value="all">Semua Officer</option>
                        <?php foreach($target_officers as $off): ?>
                            <option value="<?= $off['id'] ?>"><?= e($off['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div style="font-size: 13px; color: var(--steel); margin-bottom: 10px; margin-left: auto;">
                    Tampil: <span id="filtered-count" style="font-weight: 700; color: var(--gold);"><?= count($tasks) ?></span> tugas
                </div>
            </div>

            <div id="todo-list">
                <?php foreach ($tasks as $t): ?>
                    <?php
                    $isDone = $t['submission_status'] === 'approved' || $t['progress_status'] === 'approved';
                    $pctColor = $t['progress'] >= 100 ? 'var(--success)' : ($t['progress'] >= 80 ? '#3498db' : ($t['progress'] >= 50 ? 'var(--attention)' : 'var(--critical)'));
                    $barClass = $t['progress'] >= 100 ? 'bar-exceed' : ($t['progress'] >= 80 ? 'bar-good' : 'bar-below');
                    ?>
                    <div class="todo-item <?= $isDone ? 'done' : '' ?> <?= $t['submission_status'] === 'pending' && $t['progress'] > 0 ? 'pending-submit' : '' ?>"
                         data-tag="<?= e($t['tag']) ?>"
                         data-status="<?= e($t['progress_status'] ?? 'pending') ?>"
                         data-submission-status="<?= e($t['submission_status'] ?? '') ?>"
                         data-kategori="<?= e($t['kategori']) ?>"
                         data-bulan="<?= e($t['vis_bulan']) ?>"
                         data-officer="<?= e($t['officer_id']) ?>"
                         onclick="openTaskModal(<?= $t['id'] ?>, <?= $t['req_bulan'] ?>, <?= $t['req_tahun'] ?>, <?= $t['officer_id'] ?>)">

                        <div class="todo-row">
                            <div class="todo-check"><?= $isDone ? '✓' : '' ?></div>
                            <div class="todo-body">
                                <div class="todo-title">
                                    <?= e($t['nama']) ?>
                                    <span style="font-size:10px;color:var(--steel)">[<?= e($t['kategori']) ?>]</span>
                                    <?php if ($t['submission_status'] === 'pending'): ?>
                                        <span class="perf-badge" style="margin-left:8px; background:rgba(245, 158, 11, 0.15); color:var(--attention);">⌛ WAITING FOR APPROVAL</span>
                                    <?php elseif ($isDone): ?>
                                        <span class="perf-badge" style="margin-left:8px; background:var(--success-bg); color:var(--success);">✅ SELESAI</span>
                                    <?php endif; ?>
                                </div>
                                <div class="todo-meta">
                                    <span class="todo-tag tag-<?= e($t['tag']) ?>"><?= e(ucfirst($t['periode'])) ?></span>
                                    <span class="due-badge">🕒 <?= e($t['due_label']) ?></span>
                                    <span class="due-badge" style="margin-left:8px;color:var(--teal-light)">👤 <?= e($t['officer_nama']) ?></span>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:12px">
                                <div class="mini-progress">
                                    <div class="mini-progress-bar <?= $barClass ?>" style="width:<?= $t['progress'] ?? 0 ?>%"></div>
                                </div>
                                <div class="progress-pct" style="color:<?= $pctColor ?>"><?= $t['progress'] ?? 0 ?>%</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal" id="modal-box">
        <div class="modal-header">
            <div class="modal-title" id="modal-title">✏️ Input Progress</div>
            <div class="modal-close" onclick="closeModal()">✕</div>
        </div>
        <div id="modal-body"></div>
    </div>
</div>

<script>
const csrfToken = '<?= e($csrf_token) ?>';

function applyFilters() {
    const statusFilter = document.getElementById('filter-status').value;
    const periodeFilter = document.getElementById('filter-periode').value;
    const bulanFilter = document.getElementById('filter-bulan').value;
    const officerFilter = document.getElementById('filter-officer') ? document.getElementById('filter-officer').value : 'all';
    let visibleCount = 0;

    document.querySelectorAll('#todo-list .todo-item').forEach(el => {
        let showStatus = false;
        let showPeriode = false;
        let showBulan = false;
        let showOfficer = false;

        const elStatus = el.dataset.status || 'pending';
        const elSubStatus = el.dataset.submissionStatus || '';
        const elTag = el.dataset.tag;
        const elBulan = el.dataset.bulan;
        const elOfficer = el.dataset.officer;

        // Cek Status
        if (statusFilter === 'all') {
            showStatus = true;
        } else if (statusFilter === 'waiting') {
            showStatus = (elSubStatus === 'pending');
        } else if (statusFilter === 'done') {
            showStatus = (elStatus === 'done' || elStatus === 'approved');
        } else if (statusFilter === 'pending') {
            showStatus = (elStatus === 'pending' && elSubStatus !== 'pending');
        } else if (statusFilter === 'active') {
            showStatus = (elStatus === 'active' && elSubStatus !== 'pending');
        }

        // Cek Periode
        showPeriode = (periodeFilter === 'all' || elTag === periodeFilter);
        
        // Cek Bulan
        showBulan = (bulanFilter === 'all' || elBulan.split(',').includes(bulanFilter));
        
        // Cek Officer
        showOfficer = (officerFilter === 'all' || elOfficer == officerFilter);

        if (showStatus && showPeriode && showBulan && showOfficer) {
            el.style.display = '';
            visibleCount++;
        } else {
            el.style.display = 'none';
        }
    });

    const countEl = document.getElementById('filtered-count');
    if (countEl) countEl.textContent = visibleCount;
}

document.addEventListener('DOMContentLoaded', applyFilters);

function openTaskModal(templateId, reqBulan, reqTahun, officerId) {
    const tasks = <?= json_encode(array_map(fn($t) => [
        'id' => $t['id'],
        'nama' => $t['nama'],
        'kategori' => $t['kategori'],
        'periode' => $t['periode'],
        'req_bulan' => $t['req_bulan'],
        'req_tahun' => $t['req_tahun'],
        'due_label' => $t['due_label'],
        'target' => $t['target'],
        'progress' => $t['progress'] ?? 0,
        'progress_status' => $t['progress_status'] ?? 'pending',
        'progress_id' => $t['progress_id'],
        'keterangan' => $t['keterangan'] ?? '',
        'submission_id' => $t['submission_id'] ?? null,
        'submission_status' => $t['submission_status'],
        'officer_id' => $t['officer_id'],
        'officer_nama' => $t['officer_nama']
    ], $tasks)) ?>;

    const task = tasks.find(t => t.id === templateId && t.req_bulan === reqBulan && t.req_tahun === reqTahun && t.officer_id === officerId);
    if (!task) return;

    document.getElementById('modal-title').textContent = '✏️ Input Progress — ' + task.nama;

    document.getElementById('modal-body').innerHTML = `
        <form method="POST" action="tasks.php">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="action" value="update_progress">
            <input type="hidden" name="template_id" value="${templateId}">
            <input type="hidden" name="bulan" value="${reqBulan}">
            <input type="hidden" name="tahun" value="${reqTahun}">
            <input type="hidden" name="officer_id" value="${officerId}">

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px">
                <div>
                    <div class="input-label">Kategori</div>
                    <div style="font-size:13px">[${task.kategori}] ${task.periode}</div>
                </div>
                <div>
                    <div class="input-label">Due Date</div>
                    <div style="font-size:13px">${task.due_label}</div>
                </div>
                <div>
                    <div class="input-label">Officer</div>
                    <div style="font-size:13px">👤 ${task.officer_nama}</div>
                </div>
            </div>

            <div style="background:var(--hairline);border:1px solid var(--hairline);border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;line-height:1.6">
                <div class="input-label">Target</div>
                ${task.target || '-'}
            </div>

            <div class="input-group">
                <label class="input-label">Progress Realisasi (%)</label>
                <div class="seg-progress">
                    <div class="seg-progress-header">
                        <span class="seg-progress-label">Pilih atau klik segmen</span>
                        <span class="seg-progress-value pending" id="seg-progress-value">${task.progress}%</span>
                    </div>
                    <div class="seg-progress-track" id="seg-progress-track">
                        ${[0,10,20,30,40,50,60,70,80,90,100].map(v => {
                            const isActive = v <= task.progress;
                            const isZero = v === 0;
                            const isFull = v === 100;
                            let cls = 'seg-progress-seg';
                            if (isActive) cls += ' active';
                            if (v >= 100) cls += ' exceed';
                            else if (v >= 80) cls += ' good';
                            else if (v > 0) cls += ' below';
                            if (isZero) cls += ' zero';
                            if (isFull) cls += ' full';
                            return `<div class="${cls}" data-value="${v}" onclick="setSegProgress(${v})">${v}</div>`;
                        }).join('')}
                    </div>
                    <div class="seg-progress-bar">
                        <div class="seg-progress-bar-fill" id="seg-progress-bar-fill" style="width:${task.progress}%"></div>
                    </div>
                    <div class="seg-progress-quick">
                        <button type="button" class="seg-progress-quick-btn" onclick="setSegProgress(0)">0% Pending</button>
                        <button type="button" class="seg-progress-quick-btn" onclick="setSegProgress(50)">50% Setengah</button>
                        <button type="button" class="seg-progress-quick-btn" onclick="setSegProgress(80)">80% Good</button>
                        <button type="button" class="seg-progress-quick-btn" onclick="setSegProgress(100)">100% Exceed</button>
                    </div>
                    <input type="hidden" name="progress" id="prog-slider" value="${task.progress}">
                </div>
            </div>

            <div class="input-group">
                <label class="input-label">Keterangan / Evidence</label>
                <textarea name="keterangan" class="textarea-field" placeholder="Attach link evidence, no. dokumen, atau keterangan...">${task.keterangan}</textarea>
            </div>

            ${getActionsHtml(task)}
        </form>
    `;

    document.getElementById('modal-overlay').classList.add('open');
}

function getActionsHtml(task) {
    const userRole = '<?= e($user['role']) ?>';
    
    if (userRole === 'lead' && task.submission_status === 'pending') {
        return `
            <div style="margin-top:16px;">
                <button type="button" class="btn-submit" onclick="approveTask(${task.submission_id})" style="width: 100%; background: var(--success); color: white;">
                    ✅ APPROVE TUGAS INI
                </button>
                <button type="button" class="btn-secondary" onclick="closeModal()" style="width: 100%; margin-top: 8px;">Tutup</button>
            </div>
        `;
    } else if (task.submission_status === 'approved' || task.progress_status === 'approved') {
        return `
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()" style="width: 100%;">Tutup</button>
            </div>

            <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--hairline); text-align:center;">
                <div style="background: var(--success-bg); color: var(--success); padding: 12px; border-radius: 8px; font-weight: bold; font-size: 14px;">
                    ✅ TUGAS SELESAI (APPROVED)
                </div>
            </div>
        `;
    } else {
        return `
            <div class="modal-actions">
                <button type="submit" class="btn-primary">💾 Simpan Progress</button>
                <button type="button" class="btn-secondary" onclick="closeModal()">Tutup</button>
            </div>

            <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--hairline)">
                <button type="button" id="btn-request-approval" class="btn-submit" onclick="submitForApproval(${task.progress_id ? `'` + task.progress_id + `'` : 'null'})" style="width: 100%; ${task.progress == 100 && !task.submission_status ? '' : 'opacity: 0.5; cursor: not-allowed;'}" ${task.progress == 100 && !task.submission_status ? '' : 'disabled'}>
                    ${task.submission_status ? (task.submission_status === 'pending' ? '⏳ WAITING FOR APPROVAL' : 'Status: ' + task.submission_status.toUpperCase()) : '📤 Request for Approval'}
                </button>
            </div>
        `;
    }
}

async function approveTask(submissionId) {
    if (!confirm('Apakah Anda yakin ingin menyetujui tugas ini?')) return;

    try {
        const response = await fetch('../api/approvals.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'approve',
                submission_id: submissionId,
                catatan: 'Disetujui melalui pop-up modal'
            })
        });

        const result = await response.json();
        if (result.success) {
            alert('Tugas berhasil disetujui (Approved)!');
            location.reload();
        } else {
            alert(result.message || 'Terjadi kesalahan');
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan');
    }
}

function submitForApproval(taskProgressId) {
    if (!taskProgressId || taskProgressId === 'null' || taskProgressId === 'undefined') {
        alert('Silakan tekan tombol "💾 Simpan Progress" terlebih dahulu untuk menyimpan data baru ini ke dalam sistem sebelum melakukan Request for Approval.');
        return;
    }
    if (!confirm('Submit tugas ini untuk direview oleh Lead?')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'tasks.php';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);

    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'submit_approval';
    form.appendChild(actionInput);

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'task_progress_id';
    idInput.value = taskProgressId;
    form.appendChild(idInput);

    document.body.appendChild(form);
    form.submit();
}

function closeModal() {
    document.getElementById('modal-overlay').classList.remove('open');
}

function setSegProgress(value) {
    const hiddenInput = document.getElementById('prog-slider');
    const display = document.getElementById('seg-progress-value');
    const barFill = document.getElementById('seg-progress-bar-fill');
    const segments = document.querySelectorAll('.seg-progress-seg');

    if (hiddenInput) hiddenInput.value = value;
    if (display) {
        display.textContent = value + '%';
        display.classList.remove('exceed', 'good', 'below', 'pending');
        if (value >= 100) display.classList.add('exceed');
        else if (value >= 80) display.classList.add('good');
        else if (value > 0) display.classList.add('below');
        else display.classList.add('pending');
    }
    if (barFill) barFill.style.width = value + '%';
    
    const btnRequest = document.getElementById('btn-request-approval');
    if (btnRequest) {
        const isSubmitted = btnRequest.innerText.includes('Status:');
        if (value >= 100 && !isSubmitted) {
            btnRequest.disabled = false;
            btnRequest.style.opacity = '1';
            btnRequest.style.cursor = 'pointer';
        } else {
            btnRequest.disabled = true;
            btnRequest.style.opacity = '0.5';
            btnRequest.style.cursor = 'not-allowed';
        }
    }

    segments.forEach(seg => {
        const segVal = parseInt(seg.dataset.value);
        seg.classList.remove('active', 'exceed', 'good', 'below');
        if (segVal <= value) {
            seg.classList.add('active');
            if (value >= 100) seg.classList.add('exceed');
            else if (value >= 80) seg.classList.add('good');
            else if (value > 0) seg.classList.add('below');
        }
    });
}
</script>
</body>
</html>