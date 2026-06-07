<?php
/**
 * AMLO Dashboard - Penugasan (Lead only)
 */


require_once __DIR__ . '/../includes/auth.php';
require_role('lead');

$user = amlo_get_current_user();
$csrf_token = generate_csrf_token();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $to_user_id = (int)$_POST['to_user_id'];
    $task_name = trim($_POST['task_name'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $due_date = $_POST['due_date'] ?? '';
    $dokumen = trim($_POST['dokumen_pendukung'] ?? '');

    if ($to_user_id > 0 && !empty($task_name) && !empty($due_date)) {
        db_insert(
            "INSERT INTO assignments (from_user_id, to_user_id, task_name, deskripsi, due_date, dokumen_pendukung) VALUES (?, ?, ?, ?, ?, ?)",
            [$user['id'], $to_user_id, $task_name, $deskripsi, $due_date, $dokumen]
        );
        log_activity('assignment_create', "Create assignment for user $to_user_id");

        $adhoc_map = [
            'RFI Remittance' => 10,
            'Adhoc EDD' => 12,
            'Pendampingan AML' => 13
        ];

        if (isset($adhoc_map[$task_name])) {
            $template_id = $adhoc_map[$task_name];
            $tahun = (int)date('Y', strtotime($due_date));
            $bulan = (int)date('n', strtotime($due_date));
            $ket = "Due Date: " . date('d M Y', strtotime($due_date)) . "\n\nInstruksi Lead:\n" . $deskripsi;
            
            $existing = db_fetch_one("SELECT id FROM task_progress WHERE user_id = ? AND template_id = ? AND tahun = ? AND bulan = ?", [$to_user_id, $template_id, $tahun, $bulan]);
            
            if ($existing) {
                db_exec("UPDATE task_progress SET progress = 1, status = 'active', keterangan = ? WHERE id = ?", [$ket, $existing['id']]);
            } else {
                db_insert(
                    "INSERT INTO task_progress (user_id, template_id, periode, tahun, bulan, progress, status, keterangan) VALUES (?, ?, 'adhoc', ?, ?, 1, 'active', ?)",
                    [$to_user_id, $template_id, $tahun, $bulan, $ket]
                );
            }
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Penugasan berhasil dikirim!'];
        header('Location: assignments.php');
        exit;
    }
}

$flash = get_flash();

// Get officers in same kanwil
$officers = db_fetch_all(
    "SELECT id, nama FROM users WHERE kanwil_id = ? AND role = 'officer' AND aktif = 1 ORDER BY nama",
    [$user['kanwil_id']]
);

// Get assignment history
$assignments = db_fetch_all(
    "SELECT a.*, u.nama as to_name, u.username as to_username
     FROM assignments a
     JOIN users u ON a.to_user_id = u.id
     WHERE a.from_user_id = ? OR a.to_user_id = ?
     ORDER BY a.created_at DESC LIMIT 20",
    [$user['id'], $user['id']]
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penugasan — AMLO Dashboard</title>
    <link href="../assets/css/fonts.css" rel="stylesheet">
    <link href="../assets/css/amlo-design-system.css" rel="stylesheet">
    <style>
        .btn-primary { width: 100%; padding: 14px; background: linear-gradient(135deg, var(--gold), #b8962a); border: none; border-radius: 8px; color: var(--canvas); font-weight: 700; font-size: 14px; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; margin-top: 10px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(212,175,55,0.3); }
        .btn-primary:active { transform: translateY(0); box-shadow: none; }
        .input-group { margin-bottom: 16px; }
        .input-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--steel); margin-bottom: 6px; display: block; }
        .input-field, .select-field, .textarea-field { width: 100%; background: var(--surface-soft); border: 1px solid var(--hairline); border-radius: 8px; padding: 10px 14px; color: var(--ink-deep); font-family: 'Inter', sans-serif; font-size: 13px; outline: none; transition: border-color 0.2s; }
        .input-field:focus, .select-field:focus, .textarea-field:focus { border-color: var(--gold); }
        .textarea-field { resize: vertical; min-height: 100px; }
    </style>
</head>
<body>
<div id="app">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">Penugasan Tugas</div>
            <div class="topbar-date"><?= tanggal_indonesia('now', 'long') ?></div>
        </div>

        <div class="content">
            <?php if ($flash): ?>
                <div class="alert alert-success">✅ <?= e($flash['message']) ?></div>
            <?php endif; ?>

            <div class="page-header">
                <h2>Penugasan Tugas</h2>
                <p>Assign tugas adhoc kepada AMLO Officer di wilayah Anda</p>
            </div>

            <div class="two-col">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📤 Buat Penugasan Baru</div>
                    </div>
                    <form method="POST" action="assignments.php">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="input-group">
                            <label class="input-label">Pilih Officer</label>
                            <select name="to_user_id" class="select-field" required>
                                <option value="">-- Pilih Officer --</option>
                                <?php foreach ($officers as $o): ?>
                                    <option value="<?= $o['id'] ?>"><?= e($o['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Nama Tugas</label>
                            <select class="select-field" name="task_name" required>
                                <option value="">-- Pilih Jenis Tugas --</option>
                                <option>RFI Remittance</option>
                                <option>Adhoc EDD</option>
                                <option>Pendampingan AML</option>
                                <option>STR Proaktif</option>
                                <option>PEP Target</option>
                                <option>Bad Data Monitoring</option>
                                <option>Lainnya</option>
                            </select>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Due Date</label>
                            <input type="date" name="due_date" class="input-field" value="<?= date('Y-m-d', strtotime('+5 days')) ?>" required>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Deskripsi Tugas</label>
                            <textarea name="deskripsi" class="textarea-field" placeholder="Isi detail penugasan..."></textarea>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Dokumen Pendukung</label>
                            <input type="text" name="dokumen_pendukung" class="input-field" placeholder="Link atau nomor dokumen">
                        </div>

                        <button type="submit" class="btn-primary">📤 Kirim Penugasan</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📋 Riwayat Penugasan</div>
                    </div>

                    <?php if (empty($assignments)): ?>
                        <div style="text-align:center;color:var(--steel);padding:40px">
                            Belum ada penugasan
                        </div>
                    <?php else: ?>
                        <?php foreach ($assignments as $a):
                            $status_colors = ['selesai' => 'green', 'in_progress' => 'amber', 'belum_mulai' => 'red'];
                            $status_cls = $status_colors[$a['status']] ?? 'amber';
                        ?>
                            <div class="alert-item">
                                <div class="alert-dot <?= $status_cls ?>"></div>
                                <div>
                                    <div class="alert-text"><b><?= e($a['to_name']) ?></b> — <?= e($a['task_name']) ?></div>
                                    <div class="alert-time">
                                        Due: <?= date('d M Y', strtotime($a['due_date'])) ?>
                                        · <span style="color:<?= $status_cls === 'green' ? 'var(--success)' : ($status_cls === 'amber' ? 'var(--attention)' : 'var(--critical)') ?>">
                                            <?= str_replace('_', ' ', ucfirst($a['status'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>