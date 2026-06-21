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
     WHERE tt.is_active = 1 AND tt.periode != 'harian'
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

// 4. Get ALL targets for target officers
$targets_records = db_fetch_all(
    "SELECT user_id, task_template_id, bulan, target_value 
     FROM task_targets 
     WHERE user_id IN ($placeholders) AND tahun = ?",
    $query_params
);

$targets_map = [];
foreach ($targets_records as $tg) {
    $targets_map[$tg['user_id']][$tg['task_template_id']][$tg['bulan']] = $tg['target_value'];
}

// 5. Build $display_tasks array
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
                if (stripos($t['nama'], 'Monthly') !== false) {
                    $t['nama'] = str_ireplace('Monthly', '- ' . $nama_bulan[$m] . ' ' . $period['tahun'], $t['nama']);
                } else {
                    $t['nama'] .= ' - ' . $nama_bulan[$m] . ' ' . $period['tahun'];
                }
                
                // Dynamic due date for bulanan (last day of the month)
                $last_day = date('t', strtotime($period['tahun'] . '-' . sprintf('%02d', $m) . '-01'));
                $t['due_label'] = $last_day . ' ' . $nama_bulan[$m] . ' ' . $period['tahun'];

                $t['req_bulan'] = $m;
                $t['req_tahun'] = $period['tahun'];
                $t['vis_bulan'] = (string)$m;
                
                // Attach progress if exists
                $prog = $progress_map[$uid][$tt['id']][$m] ?? $default_prog;
                $t = array_merge($t, $prog);
                $t['numeric_target'] = $targets_map[$uid][$tt['id']][$m] ?? 0;
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
                $t['numeric_target'] = $targets_map[$uid][$tt['id']][$m] ?? 0;
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
                $t['numeric_target'] = $targets_map[$uid][$tt['id']][$m] ?? 0;
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
            $t['numeric_target'] = $targets_map[$uid][$tt['id']][$m] ?? 0;
            $t['officer_id'] = $uid;
            $t['officer_nama'] = $unama;
            $display_tasks[] = $t;
        }
    }
}

$tasks = $display_tasks;

$nav_items = get_nav_items($user['role']);
?>
<?php
$page_title = 'To-Do Harian — AMLO Dashboard';
$topbar_title = 'To-Do List Harian';
$topbar_date = tanggal_indonesia('now', 'long');
include __DIR__ . '/../includes/layout_header.php';
?>


        <div class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= $flash['type'] === 'success' ? '✅' : '⚠️' ?> <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h2>To-Do List Harian AMLO</h2>
                <p>Input progress tugas secara real-time</p>
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
                    // Visual percentage calculation
                    $vis_pct = 0;
                    if ($t['numeric_target'] > 0) {
                        $vis_pct = round(($t['progress'] / $t['numeric_target']) * 100);
                        if ($vis_pct > 100) $vis_pct = 100;
                    } else {
                        $vis_pct = $t['progress'] >= 100 ? 100 : $t['progress']; // fallback
                    }

                    $isDone = $t['submission_status'] === 'approved' || $t['progress_status'] === 'approved';
                    $pctColor = $vis_pct >= 100 ? 'var(--success)' : ($vis_pct >= 80 ? '#3498db' : ($vis_pct >= 50 ? 'var(--attention)' : 'var(--critical)'));
                    $barClass = $vis_pct >= 100 ? 'bar-exceed' : ($vis_pct >= 80 ? 'bar-good' : 'bar-below');
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
                                    <div class="mini-progress-bar <?= $barClass ?>" style="width:<?= $vis_pct ?>%"></div>
                                </div>
                                <div class="progress-pct" style="color:<?= $pctColor ?>"><?= $vis_pct ?>%</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal" id="modal-box">
        <div class="modal-header" style="display: none;">
            <div class="modal-title" id="modal-title">✏️ Input Progress</div>
            <div class="modal-close" onclick="closeModal()">✕</div>
        </div>
        <div id="modal-body"></div>
    </div>
</div>



<script>
const userRole = '<?= e($user['role']) ?>';

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
        'numeric_target' => $t['numeric_target'],
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

    const oldHeader = document.querySelector('#modal-box .modal-header');
    if (oldHeader) oldHeader.style.display = 'none';

    const modalBox = document.getElementById('modal-box');
    if (modalBox) {
        modalBox.removeAttribute('style');
    }

    document.getElementById('modal-body').innerHTML = `
        <div class="tm-modal">
            <!-- HEADER -->
            <div class="tm-header">
                <div class="tm-title">${task.nama}</div>
                <div class="tm-subtitle">Update progress tugas <span class="todo-tag tag-${task.periode}">${task.periode.charAt(0).toUpperCase() + task.periode.slice(1)}</span></div>
                <div class="tm-close" onclick="closeModal()">×</div>
            </div>

            <!-- CONTENT -->
            <form id="progressForm" onsubmit="saveProgressAjax(event)">
                <input type="hidden" name="csrf_token" value="${csrfToken}">
                <input type="hidden" name="action" value="update_progress">
                <input type="hidden" name="template_id" value="${templateId}">
                <input type="hidden" name="bulan" value="${reqBulan}">
                <input type="hidden" name="tahun" value="${reqTahun}">
                <input type="hidden" name="officer_id" value="${officerId}">

                <div class="tm-content">
                    <div class="tm-counter">
                        <button type="button" id="minusBtn" onclick="adjustProgress(-1, ${task.numeric_target})" ${userRole === 'lead' || task.progress <= 0 ? 'disabled' : ''}>−</button>
                        <div class="tm-score">
                            <div class="tm-number" id="progress-display-value">${task.progress}</div>
                            <input type="hidden" name="progress" id="prog-slider" value="${task.progress}">
                            <div class="tm-target">Target ${task.numeric_target > 0 ? task.numeric_target : '100'}</div>
                        </div>
                        <button type="button" id="plusBtn" onclick="adjustProgress(1, ${task.numeric_target})" ${userRole === 'lead' || task.progress >= (task.numeric_target > 0 ? task.numeric_target : 100) ? 'disabled' : ''}>+</button>
                    </div>

                    <div class="tm-divider"></div>

                    <div class="tm-form-group tm-form-row">
                        <div class="tm-label" style="margin-bottom:0;">Due Date</div>
                        <div class="tm-value">${task.due_label}</div>
                    </div>

                    ${userRole === 'lead' ? 
                        (task.keterangan.trim() ? `
                        <div class="tm-form-group">
                            <div class="tm-label">Catatan dari AMLO</div>
                            <div class="tm-description" style="font-style:italic; background:var(--surface-soft); padding:10px; border-radius:8px; border:1px solid var(--hairline); color:var(--ink-deep);">
                                ${task.keterangan}
                            </div>
                        </div>
                        ` : '') 
                    : `
                        <div class="tm-form-group">
                            <div class="tm-label">Deskripsi</div>
                            <div class="tm-description">
                                Ini adalah deskripsi tugas. Kategori penugasan ini adalah [${task.kategori}]. Target yang harus dicapai adalah: ${task.target || 'Tepat Waktu'}.
                            </div>
                        </div>

                        <div class="tm-form-group">
                            <div class="tm-label">Catatan</div>
                            <textarea class="tm-textarea" name="keterangan" placeholder="tuliskan jika ada catatan disini">${task.keterangan}</textarea>
                        </div>
                    `}
                </div>

                ${getActionsHtml(task)}
            </form>
        </div>
    `;

    document.getElementById('modal-overlay').classList.add('open');
}

function getActionsHtml(task) {
    if (userRole === 'lead' && task.submission_status === 'pending') {
        return `
            <div class="tm-actions">
                <button type="button" onclick="closeModal()" class="tm-btn tm-cancel">Tutup</button>
                <button type="button" onclick="approveTask(${task.submission_id})" class="tm-btn tm-save" style="background:var(--success);">✅ Approve Tugas</button>
            </div>
        `;
    } else if (task.submission_status === 'approved' || task.progress_status === 'approved') {
        return `
            <div class="tm-approval">
                <button type="button" onclick="closeModal()" class="tm-btn tm-cancel" style="width:100%; margin-bottom:20px;">Tutup</button>
                <div style="background: var(--success-bg); color: var(--success); padding: 10px; border-radius: 8px; font-weight: bold; font-size: 14px; text-align:center;">
                    ✅ TUGAS SELESAI (APPROVED)
                </div>
            </div>
        `;
    } else {
        if (userRole === 'lead') {
            return `
                <div class="tm-actions">
                    <button type="button" onclick="closeModal()" class="tm-btn tm-cancel" style="width:100%;">Tutup</button>
                </div>
            `;
        } else {
            let targetMax = task.numeric_target > 0 ? task.numeric_target : 100;
            let showApproval = (task.progress >= targetMax || task.submission_status) ? 'block' : 'none';

            return `
                <!-- BUTTON -->
                <div class="tm-actions">
                    <button type="button" onclick="closeModal()" class="tm-btn tm-cancel">Batal</button>
                    <button type="submit" class="tm-btn tm-save">Simpan</button>
                </div>

                <!-- APPROVAL -->
                <div class="tm-approval" id="approval-section" style="display: ${showApproval};">
                    <div class="tm-approval-title">Silakan Lakukan</div>
                    <button type="button" id="btn-request-approval" class="tm-btn-approval" onclick="submitForApproval(${task.progress_id ? '\'' + task.progress_id + '\'' : 'null'})" ${task.progress >= targetMax && !task.submission_status ? '' : 'disabled'}>
                        ${task.submission_status ? (task.submission_status === 'pending' ? '⏳ WAITING FOR APPROVAL' : 'Status: ' + task.submission_status.toUpperCase()) : '✓ &nbsp; Request for approval'}
                    </button>
                </div>
            `;
        }
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

async function submitForApproval(taskProgressId) {
    if (!taskProgressId || taskProgressId === 'null' || taskProgressId === 'undefined') {
        alert('Silakan tekan tombol "💾 Simpan Progress" terlebih dahulu untuk menyimpan data baru ini ke dalam sistem sebelum melakukan Request for Approval.');
        return;
    }
    if (!confirm('Submit tugas ini untuk direview oleh Lead?')) return;

    try {
        const response = await fetch('../api/tasks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'submit_approval',
                task_progress_id: parseInt(taskProgressId)
            })
        });

        const result = await response.json();
        if (result.success) {
            alert('Tugas berhasil disubmit untuk review!');
            const btn = document.getElementById('btn-request-approval');
            if(btn) {
                btn.innerText = '⏳ WAITING FOR APPROVAL';
                btn.disabled = true;
            }
        } else {
            alert(result.message || 'Terjadi kesalahan');
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan');
    }
}

async function saveProgressAjax(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Since api/tasks.php handles 'update_progress', we can hit it
    try {
        const response = await fetch('../api/tasks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            alert('Progress berhasil disimpan!');
            // Update the task_progress_id dynamically so they can request approval immediately
            // But api/tasks.php doesn't return the ID, so we might just advise them to close and reload if needed,
            // or we assume it's fine. For now, just alert success.
        } else {
            alert(result.message || 'Error saving progress');
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan');
    }
}

function closeModal() {
    document.getElementById('modal-overlay').classList.remove('open');
    // Reload to refresh the background task list
    location.reload();
}

function adjustProgress(diff, numericTarget) {
    if (userRole === 'lead') return;
    const hiddenInput = document.getElementById('prog-slider');
    const display = document.getElementById('progress-display-value');
    if(!hiddenInput || !display) return;
    
    let maxVal = numericTarget > 0 ? numericTarget : 100;
    
    let val = parseInt(hiddenInput.value) || 0;
    val += diff;
    if(val < 0) val = 0;
    if(val > maxVal) val = maxVal;
    
    hiddenInput.value = val;
    display.textContent = val;

    const btnMinus = document.getElementById('minusBtn');
    const btnPlus = document.getElementById('plusBtn');
    if(btnMinus) btnMinus.disabled = (val <= 0);
    if(btnPlus) btnPlus.disabled = (val >= maxVal);

    const approvalSection = document.getElementById('approval-section');
    const btnRequest = document.getElementById('btn-request-approval');
    if (approvalSection && btnRequest) {
        const isSubmitted = btnRequest.innerText.includes('Status:') || btnRequest.innerText.includes('WAITING');
        if (val >= maxVal || isSubmitted) {
            approvalSection.style.display = 'block';
            btnRequest.disabled = isSubmitted;
        } else {
            approvalSection.style.display = 'none';
        }
    }
}
</script>
<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
