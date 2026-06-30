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
<?php
$page_title = 'Monitoring Seluruh Wilayah — AMLO Dashboard';
$topbar_title = 'Monitoring Seluruh Wilayah';
include __DIR__ . '/../includes/layout_header.php';
?>

<div class="content">
            <div class="page-header">
                <h2>Monitoring Seluruh Regional Office</h2>
                <p>Kinerja <?= count($wilayah_data) ?> Regional Office</p>
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
                <div class="kpi-card teal">
                    <div class="kpi-card-icon"><i class="ph ph-user"></i></div>
                    <div class="kpi-label">Total AMLO Officer</div>
                    <div class="kpi-value teal"><?= $total_officer ?></div>
                    <div class="kpi-sub">Regional Office aktif</div>
                </div>
                <div class="kpi-card gold">
                    <div class="kpi-card-icon"><i class="ph ph-globe"></i></div>
                    <div class="kpi-label">Total RO</div>
                    <div class="kpi-value gold"><?= count($wilayah_data) ?></div>
                    <div class="kpi-sub">Seluruh Indonesia</div>
                </div>
                <div class="kpi-card green">
                    <div class="kpi-card-icon"><i class="ph ph-star"></i></div>
                    <div class="kpi-label">RO Exceed</div>
                    <div class="kpi-value green"><?= $exceed_wilayah ?></div>
                    <div class="kpi-sub">↑ Performanya di atas target</div>
                </div>
                <div class="kpi-card blue">
                    <div class="kpi-card-icon"><i class="ph ph-thumbs-up"></i></div>
                    <div class="kpi-label">RO Good</div>
                    <div class="kpi-value blue"><?= $good_wilayah ?></div>
                    <div class="kpi-sub"><i class="ph ph-check"></i> Sesuai standar performa</div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-card-icon"><i class="ph ph-warning"></i></div>
                    <div class="kpi-label">RO Below</div>
                    <div class="kpi-value red"><?= $below_wilayah ?></div>
                    <div class="kpi-sub"><i class="ph ph-warning-circle"></i> Perlu intervensi</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title"><i class="ph ph-chart-bar"></i> Scorecard Kinerja per Regional Office</div>
                </div>
                <table class="wilayah-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Regional Office</th>
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
                                <td class="td-center"><?= $w['total_officer'] ?></td>
                                <td class="td-center text-success font-bold"><?= $w['exceed_count'] ?></td>
                                <td class="td-center text-blue font-bold"><?= $w['good_count'] ?></td>
                                <td class="td-center text-critical font-bold"><?= $w['below_count'] ?></td>
                                <td><span class="perf-badge <?= $perf_cls ?>"><?= $perf_label ?> (<?= $overall ?>%)</span></td>
                                <td><a href="assessment.php?kanwil=<?= $w['id'] ?>" class="text-teal font-11">Review →</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    
<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
