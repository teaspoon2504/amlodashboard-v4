<?php
/**
 * AMLO Dashboard - Performance Monitoring
 */


require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = amlo_get_current_user();
$period = get_current_period();

$req_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : $period['bulan'];
$req_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : $period['tahun'];
$req_kanwil = isset($_GET['kanwil_id']) ? (int)$_GET['kanwil_id'] : 0;

$all_kanwil = [];
$selected_kanwil_nama = '';
if ($user['role'] === 'ho') {
    $all_kanwil = db_fetch_all("SELECT id, kode, nama FROM kantor_wilayah ORDER BY kode");
    if ($req_kanwil > 0) {
        foreach ($all_kanwil as $kw) {
            if ($kw['id'] == $req_kanwil) {
                $selected_kanwil_nama = $kw['nama'];
                break;
            }
        }
    }
}

// Load progress for requested year
$progress_records = db_fetch_all(
    "SELECT * FROM task_progress WHERE user_id = ? AND tahun = ? AND periode != 'harian'",
    [$user['id'], $req_tahun]
);
$progress_map = [];
foreach ($progress_records as $pr) {
    $progress_map[$pr['template_id']][$pr['bulan']] = $pr;
}

$targets_records = db_fetch_all(
    "SELECT task_template_id, bulan, target_value 
     FROM task_targets 
     WHERE user_id = ? AND tahun = ?",
    [$user['id'], $req_tahun]
);
$targets_map = [];
foreach ($targets_records as $tg) {
    $targets_map[$tg['task_template_id']][$tg['bulan']] = $tg['target_value'];
}

// Build visible tasks for req_bulan
$all_templates = db_fetch_all("SELECT * FROM task_templates WHERE is_active = 1 ORDER BY kategori, nama");
$nama_bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

$visible_tasks = [];
foreach ($all_templates as $tt) {
    if ($tt['periode'] === 'harian') continue;

    $t = $tt;
    $prog = 0;
    $is_visible = false;

    if ($tt['periode'] === 'bulanan') {
        $m = $req_bulan;
        if (stripos($t['nama'], 'Monthly') !== false) {
            $t['nama'] = str_ireplace('Monthly', '- ' . $nama_bulan[$m] . ' ' . $req_tahun, $t['nama']);
        } else {
            $t['nama'] .= ' - ' . $nama_bulan[$m] . ' ' . $req_tahun;
        }
        $prog = $progress_map[$tt['id']][$m]['progress'] ?? 0;
        $is_visible = true;
    } elseif ($tt['periode'] === 'triwulan') {
        $tw_map = [1 => 3, 2 => 6, 3 => 9, 4 => 12];
        $tw_vis = [1 => [1,2,3], 2 => [4,5,6], 3 => [7,8,9], 4 => [10,11,12]];
        for ($tw = 1; $tw <= 4; $tw++) {
            if (in_array($req_bulan, $tw_vis[$tw])) {
                $m = $tw_map[$tw];
                $t['nama'] .= ' - TW ' . $tw . ' ' . $req_tahun;
                $prog = $progress_map[$tt['id']][$m]['progress'] ?? 0;
                $is_visible = true;
            }
        }
    } elseif ($tt['periode'] === 'semesteran') {
        $sem_map = [1 => 6, 2 => 12];
        $sem_vis = [1 => [1,2,3,4,5,6], 2 => [7,8,9,10,11,12]];
        for ($sem = 1; $sem <= 2; $sem++) {
            if (in_array($req_bulan, $sem_vis[$sem])) {
                $m = $sem_map[$sem];
                $t['nama'] .= ' - Semester ' . $sem . ' ' . $req_tahun;
                $prog = $progress_map[$tt['id']][$m]['progress'] ?? 0;
                $is_visible = true;
            }
        }
    } else {
        $m = $req_bulan;
        if ($tt['periode'] === 'adhoc') {
            $t['nama'] .= ' ' . $req_tahun;
        }
        $prog = $progress_map[$tt['id']][$m]['progress'] ?? 0;
        $is_visible = true;
    }

    if ($is_visible) {
        $target = $targets_map[$tt['id']][$m] ?? 0;
        $actual_target = $target > 0 ? $target : 100;
        $percent = $actual_target > 0 ? min(100, round(($prog / $actual_target) * 100)) : 0;
        
        $t['progress'] = $percent;
        $visible_tasks[] = $t;
    }
}

// Calculate Skor Performa Saya
$total_progress = 0;
$task_count = count($visible_tasks);
foreach ($visible_tasks as $vt) {
    $total_progress += $vt['progress'];
}
$average_progress = $task_count > 0 ? round($total_progress / $task_count) : 0;

// For lead/ho: get team officer performance
$team_perf = [];
if ($user['role'] === 'lead' || $user['role'] === 'ho') {
    if ($user['role'] === 'lead') {
        $officers = db_fetch_all(
            "SELECT u.id, u.nama, kw.kode as kanwil_kode, kw.nama as kanwil_nama
             FROM users u
             JOIN kantor_wilayah kw ON u.kanwil_id = kw.id
             WHERE u.kanwil_id = ? AND u.role = 'officer' AND u.aktif = 1",
            [$user['kanwil_id']]
        );
    } else {
        $sql = "SELECT u.id, u.nama, kw.kode as kanwil_kode, kw.nama as kanwil_nama
             FROM users u
             JOIN kantor_wilayah kw ON u.kanwil_id = kw.id
             WHERE u.role IN ('officer', 'lead') AND u.aktif = 1";
        $params = [];
        if ($req_kanwil > 0) {
            $sql .= " AND u.kanwil_id = ?";
            $params[] = $req_kanwil;
        }
        $officers = db_fetch_all($sql, $params);
    }

    $officer_ids = array_column($officers, 'id');
    if (empty($officer_ids)) $officer_ids = [-1];
    
    $placeholders = implode(',', array_fill(0, count($officer_ids), '?'));
    $params = array_merge($officer_ids, [$req_tahun]);
    $team_prog_records = db_fetch_all(
        "SELECT user_id, template_id, bulan, progress FROM task_progress WHERE user_id IN ($placeholders) AND tahun = ? AND periode != 'harian'",
        $params
    );
    
    $team_prog_map = [];
    foreach ($team_prog_records as $pr) {
        $team_prog_map[$pr['user_id']][$pr['template_id']][$pr['bulan']] = $pr;
    }

    $team_target_records = db_fetch_all(
        "SELECT user_id, task_template_id, bulan, target_value FROM task_targets WHERE user_id IN ($placeholders) AND tahun = ?",
        $params
    );
    $team_targets_map = [];
    foreach ($team_target_records as $tg) {
        $team_targets_map[$tg['user_id']][$tg['task_template_id']][$tg['bulan']] = $tg['target_value'];
    }

    foreach ($officers as $off) {
        $uid = $off['id'];
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
                $target = $team_targets_map[$uid][$tt['id']][$m] ?? 0;
                $actual_target = $target > 0 ? $target : 100;
                $percent = $actual_target > 0 ? min(100, round(($prog / $actual_target) * 100)) : 0;
                $prog = $percent;

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
        
        $avg_progress = $total_tugas > 0 ? round($sum_prog / $total_tugas) : 0;
        
        $team_perf[] = [
            'id' => $off['id'],
            'nama' => $off['nama'],
            'kanwil_kode' => $off['kanwil_kode'],
            'kanwil_nama' => $off['kanwil_nama'],
            'total_tugas' => $total_tugas,
            'tugas_selesai' => $tugas_selesai,
            'tugas_progress' => $tugas_progress,
            'tugas_pending' => $tugas_pending,
            'avg_progress' => $avg_progress
        ];
    }
    
    usort($team_perf, function($a, $b) {
        if ($a['kanwil_kode'] === $b['kanwil_kode']) {
            return $b['avg_progress'] <=> $a['avg_progress'];
        }
        return $a['kanwil_kode'] <=> $b['kanwil_kode'];
    });
}
?>
<?php
$page_title = 'Monitoring Performa — AMLO Dashboard';
$topbar_title = 'Monitoring Performa';
include __DIR__ . '/../includes/layout_header.php';
?>

<div class="content">
            <div class="page-header">
                <h2>Monitoring Performa AMLO</h2>
            </div>

            <form method="GET" id="filter-form" class="todo-filters-container" style="display: flex; gap: 16px; margin-bottom: 24px; align-items: flex-end; flex-wrap: wrap;">
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Bulan</label>
                    <select name="bulan" class="select-field" style="width: 180px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--hairline); background: var(--surface-soft); color: var(--ink-deep);" onchange="document.getElementById('filter-form').submit()">
                        <?php for ($m=1; $m<=12; $m++): ?>
                            <option value="<?= $m ?>" <?= $req_bulan == $m ? 'selected' : '' ?>><?= $nama_bulan[$m] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Tahun</label>
                    <select name="tahun" class="select-field" style="width: 140px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--hairline); background: var(--surface-soft); color: var(--ink-deep);" onchange="document.getElementById('filter-form').submit()">
                        <option value="2026" <?= $req_tahun == 2026 ? 'selected' : '' ?>>2026</option>
                        <option value="2027" <?= $req_tahun == 2027 ? 'selected' : '' ?>>2027</option>
                    </select>
                </div>
                <?php if ($user['role'] === 'ho'): ?>
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block;">Regional Office</label>
                    <select name="kanwil_id" class="select-field" style="width: 250px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--hairline); background: var(--surface-soft); color: var(--ink-deep);" onchange="document.getElementById('filter-form').submit()">
                        <option value="0">Semua Regional Office</option>
                        <?php foreach ($all_kanwil as $kw): ?>
                            <option value="<?= $kw['id'] ?>" <?= $req_kanwil == $kw['id'] ? 'selected' : '' ?>>
                                [<?= e($kw['kode']) ?>] <?= e($kw['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </form>

            <div class="two-col performa-layout">
                <div class="card" style="text-align:center; display:flex; flex-direction:column; align-items:center;">
                    <div class="card-header" style="width:100%; justify-content:center; margin-bottom: 20px;">
                        <?php
                        $ho_title = 'Skor Performa Agregat';
                        if ($user['role'] === 'ho' && !empty($selected_kanwil_nama)) {
                            $ho_title .= ' ' . $selected_kanwil_nama;
                        }
                        ?>
                        <div class="card-title"><?= $user['role'] === 'ho' ? e($ho_title) : ($user['role'] === 'lead' ? 'Skor Performa Tim' : 'Skor Performa Saya') ?></div>
                    </div>
                    <?php
                    $display_avg = $average_progress;
                    if ($user['role'] !== 'officer' && isset($team_perf) && count($team_perf) > 0) {
                        $sum_team = 0;
                        foreach ($team_perf as $tp) {
                            $sum_team += $tp['avg_progress'];
                        }
                        $display_avg = round($sum_team / count($team_perf));
                    }
                    ?>
                    <div style="font-size:64px;font-weight:700;color:var(--gold);font-family:monospace"><?= $display_avg ?>%</div>
                    <?php
                    $avg = $display_avg;
                    if ($avg >= 100) {
                        $badge_class = 'perf-exceed';
                        $badge_text = '⭐ EXCEED';
                        $badge_desc = 'Luar Biasa! Semua target selesai.';
                    } elseif ($avg >= 80) {
                        $badge_class = 'perf-good';
                        $badge_text = '👍 GOOD';
                        $badge_desc = 'Kerja Bagus! Pertahankan.';
                    } else {
                        $badge_class = 'perf-below';
                        $badge_text = '⚠️ BELOW';
                        $badge_desc = 'Perlu Perbaikan. Tingkatkan effort.';
                    }
                    ?>
                    <div style="margin-top:8px">
                        <span class="perf-badge <?= $badge_class ?>" style="font-size:16px; padding:8px 24px; font-weight:800; letter-spacing:1px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            <?= $badge_text ?>
                        </span>
                    </div>
                    <div style="margin-top:12px; font-size:13px; color:var(--steel); font-weight:500;">
                        <?= $badge_desc ?>
                    </div>
                    <div style="margin-top:24px; font-size:11px; color:var(--steel); font-weight:500;">
                        Exceed ≥ 100% &nbsp;|&nbsp; Good ≥ 80% &nbsp;|&nbsp; Below &lt; 80%
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🎯 Performa per Jenis Laporan</div>
                    </div>
                    <table class="perf-table">
                        <thead>
                            <tr><th>Laporan</th><th>%</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $group_def_names = [
                                'Skor Suspicious Transaction Report' => [
                                    'STR Proaktif',
                                    'Tindak Lanjut Alert STR'
                                ],
                                'Skor Kualitas Data' => [
                                    'Pengkinian Bad Data',
                                    'Pengkinian CIF ganda',
                                    'Pengkinian data Beneficial Owner',
                                    'Pengkinian data nasabah',
                                    'Tindak Lanjut PEP Sistem AML CFT CPF',
                                    'Adhoc Enhanced Due Diligence (EDD)',
                                    'Adhoc RFI Remittance'
                                ]
                            ];

                            $grouped_tasks = [];
                            foreach ($group_def_names as $gname => $names) {
                                $grouped_tasks[$gname] = ['tasks' => [], 'total' => 0, 'count' => 0];
                            }
                            $grouped_tasks['Skor lainnya'] = ['tasks' => [], 'total' => 0, 'count' => 0];

                            foreach ($visible_tasks as $t) {
                                $found = false;
                                foreach ($group_def_names as $gname => $names) {
                                    foreach ($names as $n) {
                                        if (stripos($t['nama'], $n) !== false) {
                                            $grouped_tasks[$gname]['tasks'][] = $t;
                                            $grouped_tasks[$gname]['total'] += $t['progress'];
                                            $grouped_tasks[$gname]['count']++;
                                            $found = true;
                                            break 2;
                                        }
                                    }
                                }
                                if (!$found) {
                                    $grouped_tasks['Skor lainnya']['tasks'][] = $t;
                                    $grouped_tasks['Skor lainnya']['total'] += $t['progress'];
                                    $grouped_tasks['Skor lainnya']['count']++;
                                }
                            }
                            
                            foreach ($grouped_tasks as $gname => $gdata): 
                                if (empty($gdata['tasks'])) continue;
                                $gscore = $gdata['count'] > 0 ? round($gdata['total'] / $gdata['count']) : 0;
                            ?>
                                <tr style="background: rgba(212,175,55,0.05); border-top: 1px solid var(--gold-soft); border-bottom: 1px solid var(--gold-soft);">
                                    <td colspan="3">
                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                            <div style="font-weight: 700; font-size: 11px; color: var(--ink-deep); text-transform: uppercase; letter-spacing: 0.5px;"><?= e($gname) ?></div>
                                            <div style="font-weight: 800; font-family: monospace; color: var(--gold); font-size: 14px;"><?= $gscore ?>%</div>
                                        </div>
                                    </td>
                                </tr>
                                <?php foreach ($gdata['tasks'] as $t):
                                    $prog = $t['progress'];
                                    if ($prog == 0) {
                                        if ($t['periode'] === 'triwulan') {
                                            $perf_label = 'Belum memulai tugas triwulanan';
                                            $perf_cls = 'perf-pending';
                                        } elseif ($t['periode'] === 'semesteran') {
                                            $perf_label = 'Belum memulai tugas semesteran';
                                            $perf_cls = 'perf-pending';
                                        } elseif ($t['periode'] === 'adhoc') {
                                            $perf_label = 'Belum ada tugas adhoc';
                                            $perf_cls = 'perf-pending';
                                        } else {
                                            $perf_label = 'Not started';
                                            $perf_cls = 'perf-pending';
                                        }
                                    } else {
                                        $perf_label = $prog >= 100 ? 'Exceed' : ($prog >= 80 ? 'Good' : 'Below');
                                        $perf_cls = $perf_label === 'Exceed' ? 'perf-exceed' : ($perf_label === 'Good' ? 'perf-good' : 'perf-below');
                                    }
                                ?>
                                    <tr>
                                        <td style="font-size:11px; padding-left: 24px;"><span style="color:var(--steel); margin-right:4px;">[<?= e($t['kategori']) ?>]</span> <?= e($t['nama']) ?></td>
                                        <td style="font-family:monospace;font-weight:700"><?= $prog ?>%</td>
                                        <td><span class="perf-badge <?= $perf_cls ?>"><?= $perf_label ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>



            <?php if ($team_perf && count($team_perf) > 0): ?>
            <div class="separator"></div>
            <div class="card">
                <div class="card-header"><div class="card-title">👥 Performa Tim AMLO <?= $user['role'] === 'ho' ? 'Seluruh Wilayah' : 'Officer' ?> — <?= $nama_bulan[$req_bulan] ?> <?= $req_tahun ?></div></div>
                <table class="perf-table">
                    <thead><tr>
                        <th>Officer</th>
                        <th>Wilayah</th>
                        <th>Selesai</th>
                        <th>In Progress</th>
                        <th>Not started</th>
                        <th>Skor</th>
                        <th>Performance</th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($team_perf as $o):
                            $score = (int)($o['avg_progress'] ?? 0);
                            $perf_label = $score >= 100 ? 'Exceed' : ($score >= 80 ? 'Good' : ($score > 0 ? 'Below' : 'Not started'));
                            $perf_cls = $perf_label === 'Exceed' ? 'perf-exceed' : ($perf_label === 'Good' ? 'perf-good' : ($perf_label === 'Not started' ? 'perf-pending' : 'perf-below'));
                            $color_done = $o['tugas_selesai'] > 0 ? 'var(--success)' : 'var(--steel)';
                            $color_prog = $o['tugas_progress'] > 0 ? 'var(--attention)' : 'var(--steel)';
                            $color_pend = $o['tugas_pending'] > 0 ? 'var(--critical)' : 'var(--steel)';
                        ?>
                        <tr>
                            <td><div style="display:flex;align-items:center;gap:8px">
                                <div style="width:28px;height:28px;border-radius:50%;background:var(--gold-soft);border:1px solid var(--gold);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:var(--gold)"><?= strtoupper(substr($o['nama'],0,2)) ?></div>
                                <?= e($o['nama']) ?>
                            </div></td>
                            <td><span style="font-size:10px;background:rgba(27,143,158,0.15);color:var(--teal-light);border-radius:4px;padding:2px 8px;font-weight:600"><?= e($o['kanwil_kode']) ?></span> <?= e(substr($o['kanwil_nama'],0,25)) ?></td>
                            <td style="color:<?= $color_done ?>;font-weight:700"><?= $o['tugas_selesai'] ?></td>
                            <td style="color:<?= $color_prog ?>;font-weight:700"><?= $o['tugas_progress'] ?></td>
                            <td style="color:<?= $color_pend ?>;font-weight:700"><?= $o['tugas_pending'] ?></td>
                            <td style="font-family:monospace;font-weight:700"><?= $score ?>%</td>
                            <td><span class="perf-badge <?= $perf_cls ?>"><?= $perf_label ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    
<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
