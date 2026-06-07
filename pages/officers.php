<?php
/**
 * AMLO Dashboard - Monitoring Officers (Lead/HO)
 */


require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = amlo_get_current_user();
$period = get_current_period();

$req_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : $period['bulan'];
$req_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : $period['tahun'];

// Get officers based on role
if ($user['role'] === 'lead') {
    $officers = db_fetch_all(
        "SELECT u.*, kw.kode as kanwil_kode, kw.nama as kanwil_nama
         FROM users u
         JOIN kantor_wilayah kw ON u.kanwil_id = kw.id
         WHERE u.kanwil_id = ? AND u.role = 'officer' AND u.aktif = 1
         ORDER BY u.nama",
        [$user['kanwil_id']]
    );
} else {
    $officers = db_fetch_all(
        "SELECT u.*, kw.kode as kanwil_kode, kw.nama as kanwil_nama
         FROM users u
         JOIN kantor_wilayah kw ON u.kanwil_id = kw.id
         WHERE u.role = 'officer' AND u.aktif = 1
         ORDER BY kw.kode, u.nama"
    );
}

// Calculate summary for each officer robustly
$all_templates = db_fetch_all("SELECT * FROM task_templates WHERE is_active = 1");
$officer_ids = array_column($officers, 'id');
if (empty($officer_ids)) $officer_ids = [-1];

$placeholders = implode(',', array_fill(0, count($officer_ids), '?'));
$params = array_merge($officer_ids, [$req_tahun]);
$team_prog_records = db_fetch_all(
    "SELECT user_id, template_id, bulan, progress FROM task_progress WHERE user_id IN ($placeholders) AND tahun = ?",
    $params
);

$team_prog_map = [];
foreach ($team_prog_records as $pr) {
    $team_prog_map[$pr['user_id']][$pr['template_id']][$pr['bulan']] = $pr;
}

foreach ($officers as &$o) {
    $uid = $o['id'];
    $o_visible = [];
    
    $tugas_selesai = 0;
    $tugas_progress = 0;
    $tugas_pending = 0;
    $sum_prog = 0;
    $total_tugas = 0;
    
    foreach ($all_templates as $tt) {
        if ($tt['periode'] === 'harian') continue;
        
        $prog = 0;
        $is_visible = false;
        
        if ($tt['periode'] === 'bulanan') {
            $m = $req_bulan;
            $prog = $team_prog_map[$uid][$tt['id']][$m]['progress'] ?? 0;
            $is_visible = true;
        } elseif ($tt['periode'] === 'triwulan') {
            $tw_map = [1 => 3, 2 => 6, 3 => 9, 4 => 12];
            $tw_vis = [1 => [1,2,3], 2 => [4,5,6], 3 => [7,8,9], 4 => [10,11,12]];
            for ($tw = 1; $tw <= 4; $tw++) {
                if (in_array($req_bulan, $tw_vis[$tw])) {
                    $m = $tw_map[$tw];
                    $prog = $team_prog_map[$uid][$tt['id']][$m]['progress'] ?? 0;
                    $is_visible = true;
                }
            }
        } elseif ($tt['periode'] === 'semesteran') {
            $sem_map = [1 => 6, 2 => 12];
            $sem_vis = [1 => [1,2,3,4,5,6], 2 => [7,8,9,10,11,12]];
            for ($sem = 1; $sem <= 2; $sem++) {
                if (in_array($req_bulan, $sem_vis[$sem])) {
                    $m = $sem_map[$sem];
                    $prog = $team_prog_map[$uid][$tt['id']][$m]['progress'] ?? 0;
                    $is_visible = true;
                }
            }
        } else {
            $m = $req_bulan;
            $prog = $team_prog_map[$uid][$tt['id']][$m]['progress'] ?? 0;
            $is_visible = true;
        }
        
        if ($is_visible) {
            $sum_prog += $prog;
            $total_tugas++;
            
            $count_in_total = false;
            if ($tt['periode'] === 'bulanan') {
                $count_in_total = true;
            } elseif (in_array($tt['periode'], ['triwulan', 'semesteran', 'adhoc']) && $prog > 0) {
                $count_in_total = true;
            }
            
            if ($count_in_total) {
                if ($prog >= 100) {
                    $tugas_selesai++;
                } elseif ($prog > 0 && $prog < 100) {
                    $tugas_progress++;
                }
            }
            
            if ($tt['periode'] === 'bulanan' && $prog == 0) {
                $tugas_pending++;
            }
        }
    }
    
    $o['total_tugas'] = $total_tugas;
    $o['tugas_selesai'] = $tugas_selesai;
    $o['tugas_progress'] = $tugas_progress;
    $o['tugas_pending'] = $tugas_pending;
    $o['avg_progress'] = $total_tugas > 0 ? round($sum_prog / $total_tugas) : 0;
}
unset($o);

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($user['role'] === 'lead' || $user['role'] === 'ho')) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';

        if ($action === 'send_feedback') {
            $task_progress_id = (int)$_POST['task_progress_id'];
            $isi = trim($_POST['isi'] ?? '');

            if ($task_progress_id > 0 && !empty($isi)) {
                db_insert(
                    "INSERT INTO feedbacks (task_progress_id, from_user_id, from_role, isi) VALUES (?, ?, ?, ?)",
                    [$task_progress_id, $user['id'], $user['role'], $isi]
                );
                log_activity('feedback_sent', "Feedback untuk task $task_progress_id");

                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Feedback berhasil dikirim!'];
                header('Location: officers.php');
                exit;
            }
        }
    }
}

$csrf_token = generate_csrf_token();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Officer — AMLO Dashboard</title>
    <link href="../assets/css/fonts.css" rel="stylesheet">
    <link href="../assets/css/amlo-design-system.css" rel="stylesheet">
    <style>        .officer-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .officer-card { background: var(--surface-soft); border: 1px solid var(--hairline); border-radius: 14px; padding: 20px; cursor: pointer; transition: all 0.2s; }
        .officer-card:hover { border-color: var(--gold); transform: translateY(-2px); }
        .officer-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .officer-avatar { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; }
        .officer-info h3 { font-size: 14px; font-weight: 600; }
        .officer-info p { font-size: 11px; color: var(--steel); }
        .officer-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .officer-stat { text-align: center; background: var(--hairline); border-radius: 8px; padding: 8px; }
        .officer-stat-val { font-size: 18px; font-weight: 700; }
        .officer-stat-label { font-size: 10px; color: var(--steel); text-transform: uppercase; letter-spacing: 0.5px; }
        .perf-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; }
        .perf-exceed { background: rgba(46,204,113,0.15); color: var(--success); }
        .perf-good { background: rgba(52,152,219,0.15); color: #3498db; }
        .perf-below { background: rgba(224,82,82,0.15); color: var(--critical); }
        .card-action { font-size: 11px; color: var(--teal-light); cursor: pointer; }
        .chip-wilayah { display: inline-block; background: rgba(27,143,158,0.15); color: var(--teal-light); border-radius: 4px; padding: 2px 8px; font-size: 10px; font-weight: 600; }
        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: var(--canvas); backdrop-filter: blur(6px); z-index: 200; display: none; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal { background: linear-gradient(135deg, var(--surface-soft), var(--surface-elevated)); border: 1px solid var(--hairline); border-radius: 18px; width: 500px; max-height: 80vh; overflow-y: auto; padding: 32px; }
        .modal-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .modal-title { font-family: 'Inter', sans-serif; font-size: 18px; }
        .modal-close { cursor: pointer; color: var(--steel); font-size: 20px; }
        .input-group { margin-bottom: 16px; }
        .input-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block; }
        .select-field, .textarea-field { width: 100%; background: var(--hairline); border: 1px solid var(--hairline); border-radius: 8px; padding: 10px 14px; color: var(--ink-deep); font-family: 'Inter', sans-serif; font-size: 13px; outline: none; }
        .textarea-field { resize: vertical; min-height: 100px; }
        .select-field option { background: var(--canvas); }
        .modal-actions { display: flex; gap: 10px; margin-top: 24px; }
        .btn-primary { flex: 1; padding: 12px; background: linear-gradient(135deg, var(--gold), #b8962a); border: none; border-radius: 8px; color: var(--canvas); font-weight: 700; font-size: 13px; cursor: pointer; }
        .btn-secondary { padding: 12px 20px; background: var(--hairline); border: 1px solid var(--hairline); border-radius: 8px; color: var(--steel); font-weight: 600; font-size: 13px; cursor: pointer; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: var(--gold-soft); border-radius: 2px; }
        @media (max-width: 1100px) { .officer-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>
<div id="app">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">Monitoring AMLO Officer</div>
            <div class="topbar-date"><?= tanggal_indonesia('now', 'long') ?></div>
        </div>

        <div class="content">
            <?php if ($flash): ?>
                <div class="alert alert-success">✅ <?= e($flash['message']) ?></div>
            <?php endif; ?>

            <div class="page-header">
                <h2>Monitoring AMLO Officer</h2>
                <p><?= count($officers) ?> officer aktif — <?= $user['role'] === 'ho' ? 'Seluruh Indonesia' : e($user['kanwil_nama']) ?></p>
            </div>
            
            <?php 
            $nama_bulan_arr = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
            ?>
            <form method="GET" id="filter-form" class="todo-filters-container" style="display: flex; gap: 16px; margin-bottom: 24px; align-items: flex-end; flex-wrap: wrap;">
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Bulan</label>
                    <select name="bulan" class="select-field" style="width: 180px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--hairline); background: var(--surface-soft); color: var(--ink-deep);" onchange="document.getElementById('filter-form').submit()">
                        <?php for ($m=1; $m<=12; $m++): ?>
                            <option value="<?= $m ?>" <?= $req_bulan == $m ? 'selected' : '' ?>><?= $nama_bulan_arr[$m] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Tahun</label>
                    <select name="tahun" class="select-field" style="width: 140px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--hairline); background: var(--surface-soft); color: var(--ink-deep);" onchange="document.getElementById('filter-form').submit()">
                        <?php 
                        $current_req_tahun = $period['tahun'];
                        for($y = 2024; $y <= 2030; $y++): ?>
                            <option value="<?= $y ?>" <?= $req_tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </form>
            
            <div style="font-size: 13px; font-weight: 600; color: var(--steel); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--hairline);">
                Data Performa: <?= $nama_bulan_arr[$req_bulan] ?> <?= $req_tahun ?>
            </div>

            <div class="officer-grid">
                <?php foreach ($officers as $o):
                    $done = $o['tugas_selesai'];
                    $avg = $o['avg_progress'];
                    $perf_class = $avg >= 100 ? 'perf-exceed' : ($avg >= 80 ? 'perf-good' : 'perf-below');
                    $perf_label = $avg >= 100 ? 'Exceed' : ($avg >= 80 ? 'Good' : 'Below');
                    $colors = ['var(--teal)', 'var(--gold)', 'var(--success)', 'var(--critical)', '#bb8dd8', '#3498db'];
                    $color = $colors[$o['id'] % count($colors)];
                ?>
                    <div class="officer-card" onclick="openFeedbackModal(<?= $o['id'] ?>, '<?= e($o['nama']) ?>')">
                        <div class="officer-header">
                            <div class="officer-avatar" style="background:<?= $color ?>22;border:1px solid <?= $color ?>;color:<?= $color ?>">
                                <?= strtoupper(substr($o['nama'], 0, 2)) ?>
                            </div>
                            <div class="officer-info">
                                <h3><?= e($o['nama']) ?></h3>
                                <p><?= $user['role'] === 'ho' ? e($o['kanwil_nama']) : 'Kanwil Anda' ?></p>
                            </div>
                        </div>
                        <div class="officer-stats">
                            <div class="officer-stat">
                                <div class="officer-stat-val" style="color:var(--success)"><?= $o['tugas_selesai'] ?></div>
                                <div class="officer-stat-label">Selesai</div>
                            </div>
                            <div class="officer-stat">
                                <div class="officer-stat-val" style="color:var(--attention)"><?= $o['tugas_progress'] ?></div>
                                <div class="officer-stat-label">Progress</div>
                            </div>
                            <div class="officer-stat">
                                <div class="officer-stat-val" style="color:var(--critical)"><?= $o['tugas_pending'] ?></div>
                                <div class="officer-stat-label">Pending</div>
                            </div>
                            <div class="officer-stat">
                                <div class="officer-stat-val" style="color:var(--gold)"><?= $o['avg_progress'] ?>%</div>
                                <div class="officer-stat-label">Skor</div>
                            </div>
                        </div>
                        <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center">
                            <span class="perf-badge <?= $perf_class ?>"><?= $perf_label ?></span>
                            <span class="card-action" onclick="event.stopPropagation();openFeedbackModal(<?= $o['id'] ?>, '<?= e($o['nama']) ?>')">Beri Feedback →</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div class="modal-overlay" id="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal" id="modal-box">
        <div class="modal-header">
            <div class="modal-title" id="modal-title">💬 Feedback</div>
            <div class="modal-close" onclick="closeModal()">✕</div>
        </div>
        <div id="modal-body">
            <form method="POST" action="officers.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                <input type="hidden" name="action" value="send_feedback">
                <input type="hidden" name="task_progress_id" id="feedback-task-id" value="0">

                <div class="input-group">
                    <label class="input-label">Penilaian</label>
                    <select class="select-field" name="penilaian" id="feedback-penilaian">
                        <option>⭐ Exceed — Pertahankan</option>
                        <option>👍 Good — Terus tingkatkan</option>
                        <option>⚠️ Below — Perlu coaching intensif</option>
                    </select>
                </div>

                <div class="input-group">
                    <label class="input-label">Catatan Feedback</label>
                    <textarea name="isi" class="textarea-field" placeholder="Tulis feedback untuk officer ini..." required></textarea>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Kirim Feedback</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openFeedbackModal(officerId, officerName) {
    document.getElementById('modal-title').textContent = '💬 Feedback untuk ' + officerName;
    document.getElementById('feedback-task-id').value = officerId;
    document.getElementById('modal-overlay').classList.add('open');
}

function closeModal() {
    document.getElementById('modal-overlay').classList.remove('open');
}
</script>
</body>
</html>