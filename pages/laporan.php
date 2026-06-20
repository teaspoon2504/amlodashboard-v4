<?php
/**
 * AMLO Dashboard - Tracking Laporan
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

// 1. Get ALL active task templates
$templates = db_fetch_all(
    "SELECT tt.nama, tt.kategori, tt.periode, tt.tag, tt.target, tt.due_label, tt.id
     FROM task_templates tt
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
    "SELECT tp.id as tp_id, tp.user_id, tp.template_id, tp.bulan, tp.progress, tp.status as tp_status,
            (SELECT status FROM submissions WHERE task_progress_id = tp.id ORDER BY id DESC LIMIT 1) as submission_status
     FROM task_progress tp
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
    'tp_id' => null,
    'progress' => 0,
    'tp_status' => 'pending'
];
$nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
$display_tasks = [];

foreach ($target_officers as $officer) {
    $uid = $officer['id'];
    $unama = $officer['nama'];

    foreach ($templates as $tt) {
        if ($tt['periode'] === 'bulanan') {
            for ($m = 1; $m <= 12; $m++) {
                $t = $tt;
                if (stripos($t['nama'], 'Monthly') !== false) {
                    $t['nama'] = str_ireplace('Monthly', $nama_bulan[$m] . ' ' . $period['tahun'], $t['nama']);
                } else {
                    $t['nama'] .= ' - ' . $nama_bulan[$m] . ' ' . $period['tahun'];
                }
                $t['req_bulan'] = $m;
                $t['req_tahun'] = $period['tahun'];
                $t['vis_bulan'] = (string)$m;
                
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
                
                $prog = $progress_map[$uid][$tt['id']][$m] ?? $default_prog;
                $t = array_merge($t, $prog);
                $t['officer_id'] = $uid;
                $t['officer_nama'] = $unama;
                $display_tasks[] = $t;
            }
        } else {
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

// For Lead/HO - get feedback data
$feedbacks = [];
if ($user['role'] !== 'officer') {
    $feedbacks = db_fetch_all(
        "SELECT f.*, tp.id as tp_id, u.nama as from_name
         FROM feedbacks f
         JOIN task_progress tp ON f.task_progress_id = tp.id
         JOIN users u ON f.from_user_id = u.id
         WHERE f.from_role = 'officer'
         ORDER BY f.created_at DESC LIMIT 20"
    );
}
?>
<?php
$page_title = 'Tracking Laporan & Progress — AMLO Dashboard';
$topbar_title = 'Tracking Laporan & Progress';
include __DIR__ . '/../includes/layout_header.php';
?>

<div class="content">
            <div class="page-header">
                <h2>Tracking Laporan & Progress</h2>
                <p>Status seluruh jenis laporan AMLO beserta progress realization untuk Tahun <?= $period['tahun'] ?></p>
            </div>

            <div class="todo-filters-container" style="display: flex; gap: 16px; margin-bottom: 20px; align-items: flex-end; flex-wrap: wrap;">
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Jenis Laporan</label>
                    <select id="filter-kategori" class="select-field" style="width: 180px; padding: 10px 14px;" onchange="applyFilters()">
                        <option value="all">Semua Jenis</option>
                        <?php 
                        $kategoris = array_unique(array_column($tasks, 'kategori'));
                        foreach($kategoris as $k): ?>
                            <option value="<?= e($k) ?>"><?= e($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Periode</label>
                    <select id="filter-periode" class="select-field" style="width: 180px; padding: 10px 14px;" onchange="applyFilters()">
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
                    <select id="filter-bulan" class="select-field" style="width: 180px; padding: 10px 14px;" onchange="applyFilters()">
                        <option value="all">Semua Bulan</option>
                        <?php foreach($nama_bulan as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $num === $now_bulan ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Tahun</label>
                    <select id="filter-tahun" class="select-field" style="width: 140px; padding: 10px 14px;" onchange="window.location.href='?tahun=' + this.value">
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
                    Total: <span id="filtered-count" style="font-weight: 700; color: var(--gold);"><?= count($tasks) ?></span> tugas
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">📋 Rekap Status Laporan</div>
                    <a href="tasks.php" class="card-action">Input Progress →</a>
                </div>
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Officer</th>
                            <th>Jenis Laporan</th>
                            <th>Kat.</th>
                            <th>Periode</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="8" style="text-align:center;color:var(--steel);padding:40px">
                                    Belum ada data. <a href="tasks.php" style="color:var(--gold)">Input progress pertama →</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($tasks as $t): ?>
                                <?php
                                $progress = (int)($t['progress'] ?? 0);
                                $periode = $t['periode'];
                                
                                $isTriSemZero = ($progress === 0 && ($periode === 'triwulan' || $periode === 'semesteran'));
                                $isAdhocZero = ($progress === 0 && $periode === 'adhoc');

                                $subStatus = $t['submission_status'] ?? null;
                                $perf = $progress >= 100 ? ($subStatus === 'pending' ? 'Waiting Approval' : 'Exceed') : ($progress >= 80 ? 'Good' : ($progress > 0 ? 'Below' : 'Pending'));
                                $perfClass = $perf === 'Exceed' ? 'perf-exceed' : ($perf === 'Waiting Approval' ? 'perf-waiting' : ($perf === 'Good' ? 'perf-good' : ($perf === 'Pending' ? 'perf-pending' : 'perf-below')));
                                $barClass = $progress >= 100 ? 'bar-exceed' : ($progress >= 80 ? 'bar-good' : 'bar-below');
                                ?>
                                <tr class="laporan-row" data-kategori="<?= e($t['kategori']) ?>" data-periode="<?= e($t['periode']) ?>" data-bulan="<?= e($t['vis_bulan']) ?>" data-officer="<?= e($t['officer_id']) ?>">
                                    <td style="color:var(--steel)"><?= $no++ ?></td>
                                    <td><span style="font-weight:600;color:var(--teal-light)">👤 <?= e($t['officer_nama']) ?></span></td>
                                    <td><b><?= e($t['nama']) ?></b></td>
                                    <td><span class="chip-wilayah"><?= e($t['kategori']) ?></span></td>
                                    <td><span class="todo-tag tag-<?= e($t['tag']) ?>"><?= e(ucfirst($t['periode'])) ?></span></td>
                                    <td>
                                        <?php if ($isTriSemZero): ?>
                                            <span style="font-size:11px;color:var(--steel);font-style:italic;">Belum memulai tugas <?= e($periode === 'triwulan' ? 'triwulanan' : 'semesteran') ?></span>
                                        <?php elseif ($isAdhocZero): ?>
                                            <span style="font-size:11px;color:var(--steel);font-style:italic;">Belum ada tugas adhoc</span>
                                        <?php else: ?>
                                            <div style="display:flex;align-items:center;gap:8px">
                                                <div class="mini-progress">
                                                    <div class="mini-progress-bar <?= $barClass ?>" style="width:<?= $progress ?>%"></div>
                                                </div>
                                                <span style="font-family:monospace;font-weight:700;color:<?= $progress >= 80 ? 'var(--success)' : ($progress >= 50 ? 'var(--attention)' : 'var(--critical)') ?>"><?= $progress ?>%</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="perf-badge <?= $perfClass ?>"><?= $perf ?></span></td>
                                    <td><a href="tasks.php" class="card-action">Edit →</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($user['role'] !== 'officer' && !empty($feedbacks)): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📊 Rekap Feedback Officer</div>
                </div>
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>Officer</th>
                            <th>Feedback</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedbacks as $f): ?>
                            <tr>
                                <td><?= e($f['from_name']) ?></td>
                                <td><?= e(substr($f['isi'], 0, 80)) ?><?= strlen($f['isi']) > 80 ? '...' : '' ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($f['created_at'])) ?></td>
                                <td><span class="perf-badge perf-good">Received</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    

<script>
function applyFilters() {
    const filterKategori = document.getElementById('filter-kategori').value;
    const filterPeriode = document.getElementById('filter-periode').value;
    const filterBulan = document.getElementById('filter-bulan').value;
    const filterOfficer = document.getElementById('filter-officer') ? document.getElementById('filter-officer').value : 'all';
    
    let visibleCount = 0;
    
    document.querySelectorAll('.laporan-row').forEach(row => {
        let showKategori = filterKategori === 'all' || row.dataset.kategori === filterKategori;
        let showPeriode = filterPeriode === 'all' || row.dataset.periode === filterPeriode;
        let showBulan = filterBulan === 'all' || row.dataset.bulan.split(',').includes(filterBulan);
        let showOfficer = filterOfficer === 'all' || row.dataset.officer == filterOfficer;
        
        if (showKategori && showPeriode && showBulan && showOfficer) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    const countEl = document.getElementById('filtered-count');
    if (countEl) countEl.textContent = visibleCount;
}

document.addEventListener('DOMContentLoaded', applyFilters);
</script>
<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
