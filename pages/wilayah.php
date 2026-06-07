<?php
/**
 * AMLO Dashboard - Monitoring Wilayah (HO only)
 */


require_once __DIR__ . '/../includes/auth.php';
require_role('ho');

$user = amlo_get_current_user();
$period = get_current_period();
$wilayah_data = get_wilayah_summary();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Wilayah — AMLO Dashboard</title>
    <link href="../assets/css/fonts.css" rel="stylesheet">
    <link href="../assets/css/amlo-design-system.css" rel="stylesheet">
    <style>        .wilayah-table { width: 100%; border-collapse: collapse; }
        .wilayah-table th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); padding: 10px 14px; border-bottom: 1px solid var(--hairline); }
        .wilayah-table td { padding: 13px 14px; border-bottom: 1px solid var(--hairline); font-size: 12px; }
        .wilayah-table tr:hover td { background: var(--hairline); }
        .chip-wilayah { display: inline-block; background: rgba(27,143,158,0.15); color: var(--teal-light); border-radius: 4px; padding: 2px 8px; font-size: 10px; font-weight: 600; }
        .perf-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; }
        .perf-exceed { background: rgba(46,204,113,0.15); color: var(--success); }
        .perf-good { background: rgba(52,152,219,0.15); color: #3498db; }
        .perf-below { background: rgba(224,82,82,0.15); color: var(--critical); }
        .kpi-grid { grid-template-columns: repeat(5, 1fr); }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: var(--gold-soft); border-radius: 2px; }
    </style>
</head>
<body>
<div id="app">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">Monitoring Seluruh Wilayah</div>
            <div class="topbar-date"><?= tanggal_indonesia('now', 'long') ?></div>
        </div>

        <div class="content">
            <div class="page-header">
                <h2>Monitoring Seluruh Kantor Wilayah</h2>
                <p><?= count($wilayah_data) ?> Kantor Wilayah — Kinerja AML Nasional</p>
            </div>

            <?php
            $total_officer = array_sum(array_column($wilayah_data, 'total_officer'));
            $exceed_wilayah = 0;
            $good_wilayah = 0;
            $below_wilayah = 0;
            foreach ($wilayah_data as &$w) {
                $total_perf = $w['exceed_count'] + $w['good_count'] + $w['below_count'];
                $overall = $total_perf > 0 ? round(($w['exceed_count'] * 100 + $w['good_count'] * 75 + $w['below_count'] * 50) / $total_perf) : 0;
                $w['overall'] = $overall;
                if ($overall >= 80) {
                    $exceed_wilayah++;
                } elseif ($overall >= 60) {
                    $good_wilayah++;
                } else {
                    $below_wilayah++;
                }
            }
            unset($w);
            ?>

            <div class="kpi-grid">
                <div class="kpi-card gold">
                    <span class="kpi-icon">🌐</span>
                    <div class="kpi-label">Total Kanwil</div>
                    <div class="kpi-value gold"><?= count($wilayah_data) ?></div>
                    <div class="kpi-sub">Seluruh Indonesia</div>
                </div>
                <div class="kpi-card teal">
                    <span class="kpi-icon">👤</span>
                    <div class="kpi-label">Total AMLO Officer</div>
                    <div class="kpi-value teal"><?= $total_officer ?></div>
                    <div class="kpi-sub">Kanwil aktif</div>
                </div>
                <div class="kpi-card green">
                    <span class="kpi-icon">⭐</span>
                    <div class="kpi-label">Wilayah Exceed</div>
                    <div class="kpi-value green"><?= $exceed_wilayah ?></div>
                    <div class="kpi-sub">↑ Performanya di atas target</div>
                </div>
                <div class="kpi-card blue">
                    <span class="kpi-icon">👍</span>
                    <div class="kpi-label">Wilayah Good</div>
                    <div class="kpi-value blue"><?= $good_wilayah ?></div>
                    <div class="kpi-sub">✓ Sesuai standar performa</div>
                </div>
                <div class="kpi-card red">
                    <span class="kpi-icon">⚠️</span>
                    <div class="kpi-label">Wilayah Below</div>
                    <div class="kpi-value red"><?= $below_wilayah ?></div>
                    <div class="kpi-sub">🔴 Perlu intervensi</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">📊 Scorecard Kinerja per Kantor Wilayah</div>
                </div>
                <table class="wilayah-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Kantor Wilayah</th>
                            <th>AMLO</th>
                            <th>Exceed</th>
                            <th>Good</th>
                            <th>Below</th>
                            <th>Overall</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wilayah_data as $w):
                            $overall = $w['overall'];
                            $perf_cls = $overall >= 80 ? 'perf-exceed' : ($overall >= 60 ? 'perf-good' : 'perf-below');
                            $perf_label = $overall >= 80 ? 'Exceed' : ($overall >= 60 ? 'Good' : 'Below');
                        ?>
                            <tr>
                                <td><span class="chip-wilayah"><?= e($w['kode']) ?></span></td>
                                <td><b><?= e($w['nama']) ?></b></td>
                                <td style="text-align:center"><?= $w['total_officer'] ?></td>
                                <td style="text-align:center;color:var(--success);font-weight:700"><?= $w['exceed_count'] ?></td>
                                <td style="text-align:center;color:#3498db;font-weight:700"><?= $w['good_count'] ?></td>
                                <td style="text-align:center;color:var(--critical);font-weight:700"><?= $w['below_count'] ?></td>
                                <td><span class="perf-badge <?= $perf_cls ?>"><?= $perf_label ?> (<?= $overall ?>%)</span></td>
                                <td><a href="assessment.php?kanwil=<?= $w['id'] ?>" style="color:var(--teal-light);font-size:11px">Review →</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>