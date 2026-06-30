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
        "SELECT u.id, u.nama, COALESCE(kw.nama, 'Regional Office') as kanwil_nama FROM users u LEFT JOIN kantor_wilayah kw ON u.kanwil_id = kw.id WHERE u.kanwil_id = ? AND u.role = 'officer' AND u.aktif = 1 ORDER BY u.nama",
        [$user['kanwil_id']]
    );
} elseif ($user['role'] === 'ho') {
    $target_officers = db_fetch_all("SELECT u.id, u.nama, COALESCE(kw.nama, 'Regional Office') as kanwil_nama FROM users u LEFT JOIN kantor_wilayah kw ON u.kanwil_id = kw.id WHERE u.role = 'officer' AND u.aktif = 1 ORDER BY u.nama");
} else {
    $kw = db_fetch_one("SELECT nama FROM kantor_wilayah WHERE id = ?", [$user['kanwil_id']]);
    $target_officers = [['id' => $user['id'], 'nama' => $user['nama'], 'kanwil_nama' => $kw['nama'] ?? 'Regional Office']];
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

// 4.5 Get ALL adhoc assignments for target officers
$assignments_records = db_fetch_all(
    "SELECT to_user_id, task_name, due_date 
     FROM assignments 
     WHERE to_user_id IN ($placeholders) AND YEAR(due_date) = ?",
    $query_params
);

$assignments_map = [];
foreach ($assignments_records as $ar) {
    $assignments_map[$ar['to_user_id']][$ar['task_name']][] = $ar['due_date'];
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
    $ukanwil = $officer['kanwil_nama'] ?? 'Regional Office';

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
                $t['kanwil_nama'] = $ukanwil;
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
                $t['kanwil_nama'] = $ukanwil;
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
                $t['kanwil_nama'] = $ukanwil;
                $display_tasks[] = $t;
            }
        } else {
            // Just use the current period for harian & adhoc
            $m = $period['bulan'];
            $t = $tt;
            if ($tt['periode'] === 'adhoc') {
                $original_nama = $tt['nama'];
                
                // HANYA MUNCUL JIKA ADA ASSIGNMENT ATAU PROGRESS YANG SUDAH DIBUAT
                if (!isset($assignments_map[$uid][$original_nama]) && !isset($progress_map[$uid][$tt['id']][$m])) {
                    continue;
                }

                $t['nama'] .= ' ' . $period['tahun'];
                $t['vis_bulan'] = '1,2,3,4,5,6,7,8,9,10,11,12';
                
                if (isset($assignments_map[$uid][$original_nama])) {
                    $dates = $assignments_map[$uid][$original_nama];
                    sort($dates);
                    $formatted_dates = array_map(function($d) {
                        return date('d M Y', strtotime($d));
                    }, $dates);
                    
                    if (count($formatted_dates) == 1) {
                        $t['due_label'] = $formatted_dates[0];
                    } else {
                        $t['due_label'] = $formatted_dates[0] . ' (+ ' . (count($formatted_dates) - 1) . ' adhoc)';
                    }
                } else {
                    $t['due_label'] = 'Assignment selesai/terhapus';
                }
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
            $t['kanwil_nama'] = $ukanwil;
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
                    <?= $flash['type'] === 'success' ? '<i class="ph ph-check-circle"></i>' : '<i class="ph ph-warning-circle"></i>' ?> <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h2>To-Do List Harian AMLO</h2>
                <p>Input progress tugas secara real-time</p>
            </div>

            <div class="todo-filters-container cal-filter-form mb-xl">
                <div>
                    <label class="filter-label letter-spacing-1">Status Tugas</label>
                    <select id="filter-status" class="select-field filter-select w-150" onchange="applyFilters()">
                        <option value="all">Semua Status</option>
                        <option value="pending">Belum Dimulai</option>
                        <option value="active">Sedang Berjalan</option>
                        <option value="waiting">Waiting For Approval</option>
                        <option value="done">Selesai / Approved</option>
                    </select>
                </div>

                <div>
                    <label class="filter-label letter-spacing-1">Periode</label>
                    <select id="filter-periode" class="select-field filter-select w-150" onchange="applyFilters()">
                        <option value="all">Semua Periode</option>
                        <option value="bulanan">Bulanan</option>
                        <option value="triwulan">Triwulanan</option>
                        <option value="semesteran">Semesteran</option>
                        <option value="adhoc">Adhoc</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label letter-spacing-1">Bulan</label>
                    <?php $now_bulan = (int)date('n'); ?>
                    <select id="filter-bulan" class="select-field filter-select w-150" onchange="applyFilters()">
                        <option value="all">Semua Bulan</option>
                        <?php foreach($nama_bulan as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $num === $now_bulan ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="filter-label letter-spacing-1">Tahun</label>
                    <select id="filter-tahun" class="select-field filter-select w-120" onchange="window.location.href='?tahun=' + this.value">
                        <?php 
                        $current_req_tahun = $period['tahun'];
                        for($y = 2024; $y <= 2030; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == $current_req_tahun ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <?php if ($user['role'] !== 'officer'): ?>
                <div>
                    <label class="filter-label letter-spacing-1">AMLO Officer</label>
                    <select id="filter-officer" class="select-field filter-select w-150" onchange="applyFilters()">
                        <option value="all">Semua Officer</option>
                        <?php foreach($target_officers as $off): ?>
                            <option value="<?= $off['id'] ?>"><?= e($off['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="filtered-count-wrapper">
                    Tampil: <span id="filtered-count" class="filtered-count-num"><?= count($tasks) ?></span> tugas
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

                    $isNoApproval = stripos($t['nama'], 'E-Learning Target') !== false || stripos($t['nama'], 'Tindak Lanjut RBA Bankwide') !== false;
                    $isDone = $t['submission_status'] === 'approved' || $t['progress_status'] === 'approved' || ($isNoApproval && $vis_pct >= 100);
                    $pctClass = $vis_pct >= 100 ? 'text-success' : ($vis_pct >= 80 ? 'text-blue' : ($vis_pct >= 50 ? 'text-attention' : 'text-critical'));
                    $barClass = $vis_pct >= 100 ? 'bar-exceed' : ($vis_pct >= 80 ? 'bar-good' : 'bar-below');
                    ?>
                    <div class="todo-item <?= $isDone ? 'done' : '' ?> <?= (!$isNoApproval && $t['submission_status'] === 'pending' && $t['progress'] > 0) ? 'pending-submit' : '' ?>"
                         data-tag="<?= e($t['tag']) ?>"
                         data-status="<?= e(($isNoApproval && $vis_pct >= 100) ? 'done' : ($t['progress_status'] ?? 'pending')) ?>"
                         data-submission-status="<?= e($isNoApproval ? '' : ($t['submission_status'] ?? '')) ?>"
                         data-kategori="<?= e($t['kategori']) ?>"
                         data-bulan="<?= e($t['vis_bulan']) ?>"
                         data-officer="<?= e($t['officer_id']) ?>"
                         onclick="openTaskModal(<?= $t['id'] ?>, <?= $t['req_bulan'] ?>, <?= $t['req_tahun'] ?>, <?= $t['officer_id'] ?>)">

                        <div class="todo-row">
                            <div class="todo-check"><?= $isDone ? '✓' : '' ?></div>
                            <div class="todo-body">
                                <div class="todo-title">
                                    <?= e($t['nama']) ?>
                                    <span class="text-steel font-size-10">[<?= e($t['kategori']) ?>]</span>
                                    <?php if (!$isNoApproval && $t['submission_status'] === 'pending'): ?>
                                        <span class="perf-badge badge-waiting-approval">⌛ WAITING FOR APPROVAL</span>
                                    <?php elseif ($isDone): ?>
                                        <span class="perf-badge badge-done-approval"><i class="ph ph-check-circle"></i> SELESAI</span>
                                    <?php endif; ?>
                                </div>
                                <div class="todo-meta">
                                    <span class="todo-tag tag-<?= e($t['tag']) ?>"><?= e(ucfirst($t['periode'])) ?></span>
                                    <span class="due-badge">🕒 <?= e($t['due_label']) ?></span>
                                    <span class="due-badge officer-badge-chip">👤 <?= e($t['officer_nama']) ?></span>
                                </div>
                            </div>
                            <?php if ($vis_pct <= 0 && stripos($t['nama'], 'Sosialisasi AML CFT CPF') !== false): ?>
                                <div class="task-progress-row" style="color: var(--steel); font-size: 12px; font-style: italic;">
                                    Belum melakukan sosialisasi
                                </div>
                            <?php elseif ($vis_pct <= 0 && stripos($t['nama'], 'Report Progress AML CFT CPF') !== false): ?>
                                <div class="task-progress-row" style="color: var(--steel); font-size: 12px; font-style: italic;">
                                    Belum kirim laporan bulanan
                                </div>
                            <?php else: ?>
                                <div class="task-progress-row">
                                    <div class="mini-progress">
                                        <div class="mini-progress-bar <?= $barClass ?>" style="width:<?= $vis_pct ?>%"></div>
                                    </div>
                                    <div class="progress-pct <?= $pctClass ?>"><?= $vis_pct ?>%</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal" id="modal-box">
        <div class="modal-header d-none">
            <div class="modal-title" id="modal-title"><i class="ph ph-pencil-simple"></i> Input Progress</div>
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
        'officer_nama' => $t['officer_nama'],
        'kanwil_nama' => $t['kanwil_nama'] ?? 'Regional Office'
    ], $tasks)) ?>;

    const task = tasks.find(t => t.id === templateId && t.req_bulan === reqBulan && t.req_tahun === reqTahun && t.officer_id === officerId);
    if (!task) return;

    const targetVal = task.numeric_target > 0 ? task.numeric_target : 100;
    const completedVal = task.progress || 0;
    const remainingVal = Math.max(0, targetVal - completedVal);

    const oldHeader = document.querySelector('#modal-box .modal-header');
    if (oldHeader) oldHeader.style.display = 'none';

    const modalBox = document.getElementById('modal-box');
    if (modalBox) {
        modalBox.removeAttribute('style');
    }

    if (task.nama.includes('E-Learning Target') || task.nama.includes('Tindak Lanjut RBA Bankwide')) {
        const isELearning = task.nama.includes('E-Learning Target');
        const roName = task.kanwil_nama || 'Regional Office';
        const cardTitle = isELearning ? 'Partisipasi Pengerjaan E-Learning' : 'Tindak Lanjut RBA Bankwide';
        const deskripsiText = isELearning 
            ? `Pastikan partisipasi pengerjaan E-Learning di ${roName} mencapai 100%`
            : `Pastikan action plan tindak lanjut RBA Bankwide di ${roName} terlaksana sesuai ketentuan 100%`;
        document.getElementById('modal-body').innerHTML = `
            <div class="tm-modal">
                <div class="tm-header">
                    <div class="tm-title">${task.nama}</div>
                    <div class="tm-subtitle">Update progress tugas <span class="todo-tag tag-${task.periode}">${task.periode.charAt(0).toUpperCase() + task.periode.slice(1)}</span></div>
                    <div class="tm-close" onclick="closeModal()">×</div>
                </div>
                <div class="tm-content">
                    <div style="background: var(--surface-soft, #f7f8fa); border: 1px solid var(--border-color, #e5e5e5); border-radius: 20px; padding: 24px; margin-bottom: 24px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div style="font-size: 16px; font-weight: 700; color: var(--text-main, #171717); margin-bottom: 8px;">${cardTitle}</div>
                                <span style="display: inline-block; border: 1px solid #0097A6; border-radius: 12px; padding: 2px 12px; font-size: 12px; font-weight: 600; color: #0097A6;">${roName}</span>
                            </div>
                            <div style="font-size: 32px; font-weight: 800; color: var(--text-main, #171717);">${completedVal}%</div>
                        </div>
                        <hr style="border: none; border-top: 1px solid var(--border-color, #e5e5e5); margin: 20px 0;">
                        <div>
                            <div style="font-size: 14px; font-weight: 700; color: var(--text-main, #171717); margin-bottom: 6px;">Deskripsi</div>
                            <div style="font-size: 14px; color: var(--text-muted, #525252); line-height: 1.5;">${deskripsiText}</div>
                        </div>
                    </div>
                    <button type="button" onclick="closeModal()" style="width: 100%; padding: 12px; background: var(--card-bg, #ffffff); border: 1px solid var(--border-color, #d4d4d4); border-radius: 8px; font-size: 15px; font-weight: 600; color: var(--text-main, #171717); cursor: pointer; transition: all 0.2s;">Tutup</button>
                </div>
            </div>
        `;
        document.getElementById('modal-overlay').classList.add('open');
        return;
    }

    if (task.nama.includes('Sosialisasi AML CFT CPF') || task.nama.includes('Report Progress AML CFT CPF')) {
        const isSos = task.nama.includes('Sosialisasi');
        const deskripsiText = isSos 
            ? 'Edukasi untuk memperkuat peran setiap unit kerja dalam mendeteksi dan mencegah pencucian uang serta pendanaan ilegal sesuai standar terbaru minimal <b>1 kali dalam 1 bulan.</b>'
            : 'Penyampaian laporan progres dan pemantauan implementasi program AML, CFT, dan CPF secara berkala beserta bukti lampiran (evidence).';
        const btnAddText = isSos ? '+ Buat laporan sosialisasi' : '+ Buat laporan progress';
        const emptyText = isSos ? 'Belum ada laporan sosialisasi yang diajukan oleh Officer.' : 'Belum ada laporan progress yang diajukan oleh Officer.';
        const formTitleLabel = isSos ? 'Nama Sosialisasi' : 'Nama Laporan';
        const formTitlePlaceholder = isSos ? 'Contoh: Sosialisasi cara identifikasi Beneficial Owner' : 'Contoh: Laporan Progress Implementasi AML Bulan Ini';
        const formLinkPlaceholder = isSos ? 'Masukan link evidence sosialisasi yang dilakukan' : 'Masukan link evidence laporan progress yang dilakukan';

        const namaBulanArr = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const bulanName = namaBulanArr[reqBulan] || '';
        let sosData = null;
        if (task.progress > 0 && task.keterangan) {
            try {
                const parsed = JSON.parse(task.keterangan);
                if (parsed && (parsed.nama || parsed.link)) {
                    sosData = parsed;
                }
            } catch (e) {
                sosData = { nama: task.keterangan, link: '' };
            }
        }

        if (!sosData) {
            document.getElementById('modal-body').innerHTML = `
                <div class="tm-modal">
                    <!-- HEADER -->
                    <div class="tm-header">
                        <div class="tm-title">${task.nama}</div>
                        <div class="tm-subtitle">Update progress tugas <span class="todo-tag tag-${task.periode}">${task.periode.charAt(0).toUpperCase() + task.periode.slice(1)}</span></div>
                        <div class="tm-close" onclick="closeModal()">×</div>
                    </div>

                    <!-- STATE 1: NO ITEM CONTENT -->
                    <div class="tm-content" id="sos-state-no-item">
                        <div class="tm-form-group mb-0">
                            <div class="tm-label" style="font-size: 15px; font-weight: 600; color: #171717; margin-bottom: 8px;">Deskripsi</div>
                            <div class="tm-description" style="background: transparent; padding: 0; border: none; font-size: 14px; color: #525252; line-height: 1.5;">
                                ${deskripsiText}
                            </div>
                        </div>

                        ${userRole !== 'lead' ? `
                        <div>
                            <a onclick="showSosialisasiForm()" class="sos-btn-add">
                                <span>+</span> ${isSos ? 'Buat laporan sosialisasi' : 'Buat laporan progress'}
                            </a>
                        </div>
                        ` : `
                        <div style="margin-top: 24px; font-style: italic; color: #737373;">${emptyText}</div>
                        `}
                    </div>

                    <!-- STATE 2: ADD ITEM FORM -->
                    ${userRole !== 'lead' ? `
                    <form id="sosProgressForm" onsubmit="saveSosialisasiAjax(event, ${templateId}, ${reqBulan}, ${reqTahun}, ${officerId}, ${targetVal})" style="display: none;">
                        <div class="tm-content" style="padding-top: 0;">
                            <div class="tm-form-group mb-0">
                                <div class="tm-label" style="font-size: 15px; font-weight: 600; color: #171717; margin-bottom: 8px;">Deskripsi</div>
                                <div class="tm-description" style="background: transparent; padding: 0; border: none; font-size: 14px; color: #525252; line-height: 1.5; margin-bottom: 24px;">
                                    ${deskripsiText}
                                </div>
                            </div>

                            <div class="tm-form-group">
                                <div class="tm-label" style="font-size: 14px; font-weight: 600; color: #171717; margin-bottom: 8px;">${formTitleLabel}</div>
                                <input type="text" class="tm-input" id="sos_nama" required placeholder="${formTitlePlaceholder}" style="width: 100%; padding: 12px 14px; border: 1px solid #d4d4d4; border-radius: 8px; font-size: 14px;">
                            </div>

                            <div class="tm-form-group" style="margin-top: 16px;">
                                <div class="tm-label" style="font-size: 14px; font-weight: 600; color: #171717; margin-bottom: 8px;">Evidence (Link)</div>
                                <input type="text" class="tm-input" id="sos_link" required placeholder="${formLinkPlaceholder}" style="width: 100%; padding: 12px 14px; border: 1px solid #d4d4d4; border-radius: 8px; font-size: 14px;">
                            </div>
                        </div>

                        <div class="tm-actions" style="margin-top: 24px; padding: 16px 24px; display: flex; gap: 16px;">
                            <button type="button" onclick="hideSosialisasiForm()" class="tm-btn tm-cancel" style="flex: 1; padding: 12px; border-radius: 8px; border: 1px solid #d4d4d4; background: #fff; font-weight: 600; cursor: pointer;">Batal</button>
                            <button type="submit" class="tm-btn tm-save" style="flex: 1; padding: 12px; border-radius: 8px; background: #0052cc; color: #fff; font-weight: 600; border: none; cursor: pointer;">Simpan</button>
                        </div>
                    </form>
                    ` : ''}
                </div>
            `;
            document.getElementById('modal-overlay').classList.add('open');
            return;
        } else {
            document.getElementById('modal-body').innerHTML = `
                <div class="tm-modal">
                    <!-- HEADER -->
                    <div class="tm-header">
                        <div class="tm-title">${task.nama}</div>
                        <div class="tm-subtitle">Update progress tugas <span class="todo-tag tag-${task.periode}">${task.periode.charAt(0).toUpperCase() + task.periode.slice(1)}</span></div>
                        <div class="tm-close" onclick="closeModal()">×</div>
                    </div>

                    <!-- CONTENT -->
                    <div class="tm-content">
                        <div class="tm-form-group mb-0">
                            <div class="tm-label" style="font-size: 15px; font-weight: 600; color: #171717; margin-bottom: 8px;">Deskripsi</div>
                            <div class="tm-description" style="background: transparent; padding: 0; border: none; font-size: 14px; color: #525252; line-height: 1.5;">
                                ${deskripsiText}
                            </div>
                        </div>

                        <div class="sos-item-card">
                            <div style="display: flex; align-items: center; gap: 14px; overflow: hidden; padding-right: 12px;">
                                <div class="sos-icon-circle">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                </div>
                                <div style="overflow: hidden;">
                                    <div class="sos-item-title">${sosData.nama}</div>
                                    <div class="sos-item-link">
                                        ${sosData.link ? (sosData.link.startsWith('http') ? `<a href="${sosData.link}" target="_blank">${sosData.link}</a>` : sosData.link) : ''}
                                    </div>
                                </div>
                            </div>
                            ${userRole !== 'lead' && (!task.submission_status || task.submission_status === 'rejected') ? `
                            <a onclick="removeSosialisasiAjax(${templateId}, ${reqBulan}, ${reqTahun}, ${officerId})" class="sos-btn-remove">Remove</a>
                            ` : ''}
                        </div>

                        ${userRole === 'lead' ? `
                            ${task.submission_status === 'pending' ? `
                            <div class="sos-divider"><span>Tindakan Lead</span></div>
                            <div class="tm-actions">
                                <button type="button" onclick="closeModal()" class="tm-btn tm-cancel">Tutup</button>
                                <button type="button" onclick="approveTask(${task.submission_id})" class="tm-btn tm-save btn-approve-success"><i class="ph ph-check-circle"></i> Approve Tugas</button>
                            </div>
                            ` : `
                            <div style="margin-top: 24px;">
                                <button type="button" onclick="closeModal()" class="tm-btn tm-cancel w-100">Tutup</button>
                            </div>
                            `}
                        ` : `
                            <div class="sos-divider"><span>Silakan Lakukan</span></div>
                            <div style="padding-bottom: 8px;">
                                <button type="button" id="btn-request-approval" onclick="submitForApproval(${task.progress_id ? '\'' + task.progress_id + '\'' : 'null'})" style="background: ${task.submission_status ? '#e5e5e5' : '#61bcf7'}; color: ${task.submission_status ? '#737373' : '#fff'}; font-weight: 600; border-radius: 8px; padding: 14px; border: none; width: 100%; font-size: 15px; cursor: ${task.submission_status ? 'not-allowed' : 'pointer'};" ${task.submission_status ? 'disabled' : ''}>
                                    ${task.submission_status ? (task.submission_status === 'pending' ? '<i class="ph ph-hourglass-high"></i> WAITING FOR APPROVAL' : 'Status: ' + task.submission_status.toUpperCase()) : 'Request for approval'}
                                </button>
                            </div>
                        `}
                    </div>
                </div>
            `;
            document.getElementById('modal-overlay').classList.add('open');
            return;
        }
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
                    <div class="tm-counter new-tm-counter">
                        <div class="tm-counter-left">
                            <div class="tm-badge-card tm-badge-target">
                                <div class="tm-badge-label">Target</div>
                                <div class="tm-badge-val">${targetVal}</div>
                            </div>
                            <div class="tm-badge-card tm-badge-remaining">
                                <div class="tm-badge-label">Remaining</div>
                                <div class="tm-badge-val" id="remaining-display-value">${remainingVal}</div>
                            </div>
                        </div>
                        <div class="tm-counter-divider"></div>
                        <div class="tm-counter-right">
                            <div class="tm-completed-label">Completed</div>
                            <div class="tm-stepper">
                                <button type="button" class="tm-step-btn" id="minusBtn" onclick="adjustProgress(-1, ${targetVal})" ${userRole === 'lead' || completedVal <= 0 ? 'disabled' : ''}>−</button>
                                <div class="tm-completed-val" id="progress-display-value">${completedVal}</div>
                                <input type="hidden" name="progress" id="prog-slider" value="${completedVal}">
                                <button type="button" class="tm-step-btn" id="plusBtn" onclick="adjustProgress(1, ${targetVal})" ${userRole === 'lead' || completedVal >= targetVal ? 'disabled' : ''}>+</button>
                            </div>
                        </div>
                    </div>

                    <div class="tm-divider"></div>

                    <div class="tm-form-group tm-form-row">
                        <div class="tm-label mb-0">Due Date</div>
                        <div class="tm-value">${task.due_label}</div>
                    </div>

                    ${userRole === 'lead' ? 
                        (task.keterangan.trim() ? `
                        <div class="tm-form-group">
                            <div class="tm-label">Catatan dari AMLO</div>
                            <div class="tm-description amlo-note-box">
                                ${task.keterangan}
                            </div>
                        </div>
                        ` : '') 
                    : `
                        <div class="tm-form-group">
                            <div class="tm-label">Deskripsi</div>
                            <div class="tm-description">
                                ${task.nama.includes('Tindak Lanjut Alert STR') ? 
                                    'Silakan lakukan update tindak lanjut Alert STR pada Sistem AML, CFT &amp; CPF (<a href="https://brisim.bri.co.id/" target="_blank" style="color: var(--brand-primary); text-decoration: underline;">https://brisim.bri.co.id/</a>)' : 
                                    `Ini adalah deskripsi tugas. Kategori penugasan ini adalah [${task.kategori}]. Target yang harus dicapai adalah: ${task.target || 'Tepat Waktu'}.`}
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
                <button type="button" onclick="approveTask(${task.submission_id})" class="tm-btn tm-save btn-approve-success"><i class="ph ph-check-circle"></i> Approve Tugas</button>
            </div>
        `;
    } else if (task.submission_status === 'approved' || task.progress_status === 'approved') {
        return `
            <div class="tm-approval">
                <button type="button" onclick="closeModal()" class="tm-btn tm-cancel w-100 mb-xl">Tutup</button>
                <div class="task-approved-banner">
                    <i class="ph ph-check-circle"></i> TUGAS SELESAI (APPROVED)
                </div>
            </div>
        `;
    } else {
        if (userRole === 'lead') {
            return `
                <div class="tm-actions">
                    <button type="button" onclick="closeModal()" class="tm-btn tm-cancel w-100">Tutup</button>
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
                        ${task.submission_status ? (task.submission_status === 'pending' ? '<i class="ph ph-hourglass-high"></i> WAITING FOR APPROVAL' : 'Status: ' + task.submission_status.toUpperCase()) : '<i class="ph ph-check"></i> &nbsp; Request for approval'}
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
                btn.innerHTML = '<i class="ph ph-hourglass-high"></i> WAITING FOR APPROVAL';
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

function showSosialisasiForm() {
    document.getElementById('sos-state-no-item').style.display = 'none';
    document.getElementById('sosProgressForm').style.display = 'block';
}

function hideSosialisasiForm() {
    document.getElementById('sosProgressForm').style.display = 'none';
    document.getElementById('sos-state-no-item').style.display = 'block';
}

async function saveSosialisasiAjax(event, templateId, bulan, tahun, officerId, targetVal) {
    event.preventDefault();
    const nama = document.getElementById('sos_nama').value.trim();
    const link = document.getElementById('sos_link').value.trim();
    if (!nama || !link) {
        alert('Silakan isi Nama dan Evidence Link');
        return;
    }
    const ketJson = JSON.stringify({ nama: nama, link: link });

    try {
        const response = await fetch('../api/tasks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'update_progress',
                template_id: templateId,
                bulan: bulan,
                tahun: tahun,
                officer_id: officerId,
                progress: targetVal,
                keterangan: ketJson
            })
        });

        const result = await response.json();
        if (result.success) {
            alert('Laporan berhasil disimpan!');
            location.reload();
        } else {
            alert(result.message || 'Error saving laporan');
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan');
    }
}

async function removeSosialisasiAjax(templateId, bulan, tahun, officerId) {
    if (!confirm('Apakah Anda yakin ingin menghapus laporan ini?')) return;
    try {
        const response = await fetch('../api/tasks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'update_progress',
                template_id: templateId,
                bulan: bulan,
                tahun: tahun,
                officer_id: officerId,
                progress: 0,
                keterangan: ''
            })
        });

        const result = await response.json();
        if (result.success) {
            alert('Laporan dihapus.');
            location.reload();
        } else {
            alert(result.message || 'Error removing laporan');
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

    const remainingDisplay = document.getElementById('remaining-display-value');
    if (remainingDisplay) {
        remainingDisplay.textContent = Math.max(0, maxVal - val);
    }

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
