<?php
/**
 * AMLO Dashboard - Job Description Reference
 */


require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = amlo_get_current_user();
$period = get_current_period();

// Job categories with descriptions
$job_categories = [
    ['code' => 'A', 'name' => 'STR Proaktif', 'tasks' => [
        'Koordinasi & analisa STR dari UKO',
        'Submit STR via GoAML',
        'Monitoring periodik STR',
        'Dokumentasi arsip STR',
        'Inisiasi pelaporan STR Online',
        'Approval STR sesuai kewenangan',
        'Verifikasi kelengkapan data mandatory',
        'Pemantauan via aplikasi STR',
        'Penegakan kepatuhan SLA'
    ]],
    ['code' => 'B', 'name' => 'Alert pada Sistem AML', 'tasks' => [
        'Koordinasi dengan Pinca/UB Leader',
        'Analisis profil & transaksi',
        'Tindak lanjut alert dengan kanca',
        'Dokumentasi hasil resolusi alert'
    ]],
    ['code' => 'C', 'name' => 'Tindak Lanjut Enterprise Risk', 'tasks' => [
        'Monitoring RBA unit kerja bina',
        'TL aksi kanca medium/high risk',
        'Monitoring sosialisasi RBA APU PPT',
        'Laporan konsolidasi tindak lanjut'
    ]],
    ['code' => 'D', 'name' => 'Monitoring Data WIC', 'tasks' => [
        'Monitoring kelengkapan data WIC',
        'Koordinasi pengisian database WIC'
    ]],
    ['code' => 'E', 'name' => 'Monitoring Data BO', 'tasks' => [
        'Monitoring catatan Beneficial Owner',
        'Koordinasi eskalasi isu sistem BO',
        'Tindak lanjut temuan monitoring BO'
    ]],
    ['code' => 'F', 'name' => 'Pelaporan APU PPT', 'tasks' => [
        'Analisa eskalasi transaksi mencurigakan',
        'Koordinasi tindak lanjut PPATK/Apgakum'
    ]],
    ['code' => 'G', 'name' => 'EDD Nasabah High Risk', 'tasks' => [
        'Monitoring CDD Remittance/Money Changer',
        'Verifikasi laporan indikasi TPPU',
        'Monitoring risiko transaksi remittance'
    ]],
    ['code' => 'H', 'name' => 'Pelaporan Program APU PPT PPPSPM', 'tasks' => [
        'Penyusunan laporan implementasi',
        'Koordinasi pengumpulan data kanca',
        'Internalisasi/pelatihan APU PPT'
    ]],
    ['code' => 'I', 'name' => 'Penundaan & Penghentian Transaksi', 'tasks' => [
        'Pastikan pelaksanaan penundaan transaksi',
        'Pastikan penghentian atas instruksi PPATK',
        'Penyusunan berita acara',
        'EDD melalui pengkinian data',
        'Kelengkapan dokumen AR01/02',
        'Penatakerjakan dokumen',
        'Tindak lanjut H+5',
        'Pelaporan STR hasil penundaan'
    ]],
    ['code' => 'J', 'name' => 'Monitoring Kualitas Data', 'tasks' => [
        'Bad data eksisting: volume & remediasi',
        'Bad data nasabah baru',
        'Progress pengkinian data %',
        'Dokumen konfirmasi pengkinian'
    ]],
    ['code' => 'K', 'name' => 'Pemadanan PEP PPATK', 'tasks' => [
        'TL 100% pemadanan PEP',
        'Input EDD ke sistem NDS',
        'Dokumentasi hasil EDD PEP',
        'Analisis pekerjaan & keluarga PEP',
        'Konfirmasi jabatan PEP'
    ]]
];

$total_jobdesc = array_sum(array_column($job_categories, 'count'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Description — AMLO Dashboard</title>
    <link href="../assets/css/fonts.css" rel="stylesheet">
    <link href="../assets/css/amlo-design-system.css" rel="stylesheet">
    <style>        .chip-wilayah { display: inline-block; background: rgba(27,143,158,0.15); color: var(--teal-light); border-radius: 4px; padding: 4px 12px; font-size: 13px; font-weight: 600; }
        .alert-item { display: flex; align-items: flex-start; gap: 12px; padding: 10px; border-radius: 8px; background: var(--hairline); margin-bottom: 6px; }
        .alert-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; background: var(--teal-light); }
        .alert-text { font-size: 12px; line-height: 1.5; }
        .regulasi-box { background: var(--gold-soft); border: 1px solid var(--gold); border-radius: 10px; padding: 16px; margin-top: 16px; }
        .regulasi-title { font-size: 12px; font-weight: 600; color: var(--gold); margin-bottom: 8px; }
        .regulasi-list { font-size: 11px; color: var(--steel); }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: var(--gold-soft); border-radius: 2px; }
    </style>
</head>
<body>
<div id="app">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">Job Description AMLO</div>
            <div class="topbar-date"><?= tanggal_indonesia('now', 'long') ?></div>
        </div>

        <div class="content">
            <div class="page-header">
                <h2>Job Description AMLO — Kantor Wilayah</h2>
                <p><?= count($job_categories) ?> Kategori · Berdasarkan POJK 8/2023</p>
            </div>

            <div style="margin-bottom: 24px;">
                <input type="text" id="searchInput" onkeyup="filterJobs()" placeholder="🔍 Cari job description atau kategori..." style="width: 100%; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--hairline); background: var(--canvas); color: var(--ink-deep); font-size: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); outline: none;">
            </div>

            <?php
            $total_tasks = 0;
            foreach ($job_categories as $cat) {
                $total_tasks += count($cat['tasks']);
            }
            ?>

            <div class="kpi-grid">
                <div class="kpi-card gold">
                    <div class="kpi-label">Total Kategori</div>
                    <div class="kpi-value gold"><?= count($job_categories) ?></div>
                    <div class="kpi-sub">11 kategori utama</div>
                </div>
                <div class="kpi-card teal">
                    <div class="kpi-label">Total Job Desc</div>
                    <div class="kpi-value teal"><?= $total_tasks ?></div>
                    <div class="kpi-sub">Deskripsi pekerjaan</div>
                </div>
                <div class="kpi-card green">
                    <div class="kpi-label">Regulasi Utama</div>
                    <div class="kpi-value green" style="font-size:18px">POJK 8/2023</div>
                    <div class="kpi-sub">+ UU TPPU, FATF</div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-label">Struktur</div>
                    <div class="kpi-value red" style="font-size:16px">3 Tier</div>
                    <div class="kpi-sub">KP → Kanwil → Kanca</div>
                </div>
            </div>

            <?php foreach ($job_categories as $cat): ?>
                <div class="card job-card" style="margin-bottom:14px" data-title="<?= strtolower(e($cat['name'])) ?>">
                    <div class="card-header" onclick="toggleCat('cat-<?= $cat['code'] ?>')">
                        <div class="card-title">
                            <span class="chip-wilayah">[<?= $cat['code'] ?>]</span>
                            <?= e($cat['name']) ?>
                        </div>
                        <div class="card-action" id="arrow-<?= $cat['code'] ?>">▾</div>
                    </div>
                    <div id="cat-<?= $cat['code'] ?>">
                        <?php foreach ($cat['tasks'] as $i => $task): ?>
                            <div class="alert-item">
                                <div class="alert-dot"></div>
                                <div class="alert-text"><b><?= $cat['code'] ?>.<?= $i + 1 ?></b> — <?= e($task) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="regulasi-box">
                <div class="regulasi-title">📜 Regulasi Pendukung</div>
                <div class="regulasi-list">
                    • POJK No. 8 Tahun 2023 tentang Penerapan Anti Pencucian Uang, Pencegahan Pendanaan Terorisme, dan Pencegahan Pendanaan Proliferasi Pemulihan dari Sumber Kekerasan<br>
                    • UU No. 8 Tahun 2010 tentang Pencegahan dan Pemantauan Transaksi Financi<br>
                    • Peraturan FATF (Financial Action Task Force)<br>
                    • SE OJK terkait implementasi APU PPT di Lembaga Perbankan
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCat(id) {
    const el = document.getElementById(id);
    const arrow = document.getElementById('arrow-' + id.split('-')[1]);

    if (el.style.display === 'none') {
        el.style.display = '';
        arrow.textContent = '▾';
    } else {
        el.style.display = 'none';
        arrow.textContent = '▸';
    }
}

function filterJobs() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const cards = document.getElementsByClassName('job-card');

    for (let i = 0; i < cards.length; i++) {
        const cardTitle = cards[i].getAttribute('data-title');
        const tasks = cards[i].getElementsByClassName('alert-item');
        let hasVisibleTask = false;

        for (let j = 0; j < tasks.length; j++) {
            const taskText = tasks[j].textContent.toLowerCase();
            // Show task if it matches filter OR if the category title matches filter
            if (taskText.includes(filter) || cardTitle.includes(filter)) {
                tasks[j].style.display = '';
                hasVisibleTask = true;
            } else {
                tasks[j].style.display = 'none';
            }
        }

        // Show card if any task matches or category title matches
        if (hasVisibleTask) {
            cards[i].style.display = '';
            
            // Auto expand if there's a search term
            if (filter !== '') {
                const catContainer = document.getElementById('cat-' + cards[i].querySelector('.chip-wilayah').innerText.replace(/[\[\]]/g, ''));
                const arrow = document.getElementById('arrow-' + cards[i].querySelector('.chip-wilayah').innerText.replace(/[\[\]]/g, ''));
                if (catContainer) catContainer.style.display = '';
                if (arrow) arrow.textContent = '▾';
            }
        } else {
            cards[i].style.display = 'none';
        }
    }
}
</script>
</body>
</html>