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
    WHERE user_id = ? AND tahun = ? AND periode != 'harian'", 
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
            if ($sub_status === 'pending') {
                $kpi_waiting++;
            } elseif ($sub_status === 'approved') {
                $kpi_green++;
            } elseif ($prog > 0) {
                $kpi_teal++; // In progress or reached target but not submitted
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
<?php
$page_title = 'Beranda — AMLO Dashboard';
$topbar_title = 'Beranda — Overview Harian';
$topbar_date = $tanggal_hari_ini;
$topbar_notif_action = "document.getElementById('notifikasi-section').scrollIntoView({behavior: 'smooth', block: 'start'})";
include __DIR__ . '/../includes/layout_header.php';
?>


        <div class="content">
            <!-- HERO BAND (DESIGN.md: hero-band-marketing) -->
            <div class="hero-band">
                <div class="hero-content">
                    <div class="hero-eyebrow">AMLO Monitoring Activity</div>
                    <div class="hero-title"><?= $greeting ?>, <?= e(explode(' ', $user['nama'])[0]) ?> 👋</div>
                    
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
                            <div class="kpi-label">Not started</div>
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
                            <div class="kpi-label">AMLO member</div>
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
                            <div class="kpi-sub">⚡ Butuh Coaching</div>
                        </div>
                        <div class="kpi-value"><?= count(array_filter($team_tasks, fn($t) => $t['summary']['done'] + $t['summary']['approved'] < 5)) ?></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="kpi-grid">
                    <div class="kpi-card gold">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">🌐</div>
                            <div class="kpi-label">Total Regional Office</div>
                            <div class="kpi-sub">📍 Seluruh Indonesia</div>
                        </div>
                        <div class="kpi-value"><?= count($wilayah_data) ?></div>
                    </div>
                    <div class="kpi-card green">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">✅</div>
                            <div class="kpi-label">RO Exceed</div>
                            <div class="kpi-sub">↑ RO berprestasi</div>
                        </div>
                        <div class="kpi-value"><?= count(array_filter($wilayah_data, fn($w) => $w['exceed_count'] > $w['below_count'])) ?></div>
                    </div>
                    <div class="kpi-card teal">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">👤</div>
                            <div class="kpi-label">Total AML Officer</div>
                            <div class="kpi-sub">📊 AMLO Aktif</div>
                        </div>
                        <div class="kpi-value"><?= array_sum(array_column($wilayah_data, 'total_officer')) ?></div>
                    </div>
                    <div class="kpi-card blue">
                        <div class="kpi-details">
                            <div class="kpi-card-icon">🌟</div>
                            <div class="kpi-label">AMLO Exceed</div>
                            <div class="kpi-sub">✅ AMLO berprestasi</div>
                        </div>
                        <div class="kpi-value"><?= array_sum(array_column($wilayah_data, 'exceed_count')) ?></div>
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
                        <div class="empty-state-p40 font-13">
                            Belum melakukan progress laporan. ✨
                        </div>
                    <?php 
                    else:
                        foreach (array_slice($filtered_progress, 0, 5) as $t): 
                    ?>
                        <div class="prog-bar-wrap">
                            <div class="prog-bar-label">
                                <span><?= e($t['nama']) ?></span>
                                <?php $pctClass = $t['progress'] >= 100 ? 'text-success' : ($t['progress'] >= 80 ? 'text-blue' : ($t['progress'] >= 50 ? 'text-attention' : 'text-critical')); ?>
                                <span class="<?= $pctClass ?>"><?= $t['progress'] ?>%</span>
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
                    <div class="card-header card-header-col">
                        <div class="card-title w-full">📅 Kalender Aktivitas — <?= $nama_bulan[$bulan] ?> <?= $tahun ?></div>
                        <form method="GET" id="cal-filter-form" class="todo-filters-container cal-filter-form">
                            <div>
                                <label class="filter-label">Bulan</label>
                                <select name="bulan" class="select-field filter-select w-160" onchange="document.getElementById('cal-filter-form').submit()">
                                    <?php for ($m=1; $m<=12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $bulan == $m ? 'selected' : '' ?>><?= $nama_bulan[$m] ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="filter-label">Tahun</label>
                                <select name="tahun" class="select-field filter-select w-120" onchange="document.getElementById('cal-filter-form').submit()">
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
                            "SELECT a.task_name, a.due_date, a.status, tp.status as real_status 
                             FROM assignments a
                             LEFT JOIN task_templates tt ON tt.nama = a.task_name
                             LEFT JOIN task_progress tp ON tp.template_id = tt.id AND tp.user_id = a.to_user_id AND tp.tahun = YEAR(a.due_date) AND tp.bulan = MONTH(a.due_date)
                             WHERE YEAR(a.due_date) = ? AND MONTH(a.due_date) = ? 
                             AND (a.from_user_id = ? OR a.to_user_id = ?)
                             AND a.task_name IN ('RFI Remittance', 'Adhoc Enhanced Due Diligence (EDD)', 'Pendampingan Verifikasi Lapangan', 'Adhoc Asistensi UKO')",
                            [$tahun, $bulan, $user['id'], $user['id']]
                        );
                        foreach ($cal_assignments as $ca) {
                            if (!empty($ca['real_status'])) {
                                $rs = $ca['real_status'];
                                if ($rs === 'done' || $rs === 'approved') {
                                    $ca['status'] = 'selesai';
                                } elseif ($rs === 'pending' || $rs === 'active') {
                                    $ca['status'] = 'in_progress';
                                }
                            }
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
                        $dayStatus = null;
                        
                        $onclick = '';
                        if ($hasTask) {
                            $hasBelumMulai = false;
                            $hasInProgress = false;
                            foreach ($dayTasks as $t) {
                                if ($t['status'] === 'belum_mulai') $hasBelumMulai = true;
                                if ($t['status'] === 'in_progress') $hasInProgress = true;
                            }
                            if ($hasBelumMulai) {
                                $dayStatus = 'belum_mulai';
                            } elseif ($hasInProgress) {
                                $dayStatus = 'in_progress';
                            } else {
                                $dayStatus = 'selesai';
                            }

                            $onclick = " onclick=\"showCalTasks($d, this)\" title=\"Klik untuk Menampilkan/Menyembunyikan Penugasan\"";
                        }

                        $cls = 'cal-day';
                        if ($hasTask) $cls .= ' has-task cursor-pointer';
                        if ($isToday) $cls .= ' today';
                        
                        if ($dayStatus === 'selesai') $cls .= ' completed';
                        elseif ($dayStatus === 'in_progress') $cls .= ' in-progress';
                        elseif ($dayStatus === 'belum_mulai') $cls .= ' belum-mulai';
                        
                        echo "<div class=\"$cls\"$onclick>$d</div>";
                    }
                    echo '</div>';
                    ?>
                    <div class="cal-legend">
                        <div class="cal-legend-item"><span class="cal-legend-dot cal-legend-dot-today"></span>Hari Ini</div>
                        <div class="cal-legend-item"><span class="cal-legend-dot cal-legend-dot-success"></span>Selesai</div>
                        <div class="cal-legend-item"><span class="cal-legend-dot cal-legend-dot-attention"></span>Berjalan</div>
                        <div class="cal-legend-item"><span class="cal-legend-dot cal-legend-dot-critical"></span>Belum Mulai</div>
                    </div>
                    
                    <div id="cal-details-container" class="cal-details-container"></div>
                    <?php
                    $taskData = [];
                    foreach ($taskDaysMap as $day => $tasks) {
                        $html = "<div class='cal-details-title'>Penugasan Tanggal $day</div>";
                        foreach ($tasks as $t) {
                            $badge = '';
                            if ($t['status'] === 'selesai') {
                                $badge = '<span class="font-semibold font-10 text-success">✅ Selesai</span>';
                            } elseif ($t['status'] === 'in_progress') {
                                $badge = '<span class="font-semibold font-10 text-attention">⏳ Berjalan</span>';
                            } else {
                                $badge = '<span class="font-semibold font-10 text-critical">⚠️ Belum Mulai</span>';
                            }
                            $html .= "<div class='font-13 mb-sm text-ink'>• " . e($t['task_name']) . " <span class='float-right'>$badge</span></div>";
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
                        echo '<div class="empty-state-p40 font-13">Tidak ada notifikasi saat ini. ✨</div>';
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
    });
</script>
<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
