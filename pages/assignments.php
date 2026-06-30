<?php
/**
 * AMLO Dashboard - Penugasan (Lead & HO)
 */


require_once __DIR__ . '/../includes/auth.php';
require_role(['lead', 'ho']);

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
            'Adhoc Enhanced Due Diligence (EDD)' => 12,
            'Pendampingan Verifikasi Lapangan' => 13,
            'Adhoc Asistensi UKO' => 17
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

// Get officers & assignment history based on role
if ($user['role'] === 'ho') {
    $officers = db_fetch_all("
        SELECT u.id, u.nama, kw.nama as kanwil_nama 
        FROM users u 
        LEFT JOIN kantor_wilayah kw ON u.kanwil_id = kw.id 
        WHERE u.role = 'officer' AND u.aktif = 1 
        ORDER BY kw.nama, u.nama
    ");
    $assignments = db_fetch_all("
        SELECT a.*, u.nama as to_name, u.username as to_username, kw.nama as kanwil_nama,
                tp.status as real_status, tp.progress as real_progress
         FROM assignments a
         JOIN users u ON a.to_user_id = u.id
         LEFT JOIN kantor_wilayah kw ON u.kanwil_id = kw.id
         LEFT JOIN task_templates tt ON tt.nama = a.task_name
         LEFT JOIN task_progress tp ON tp.template_id = tt.id AND tp.user_id = a.to_user_id AND tp.tahun = YEAR(a.due_date) AND tp.bulan = MONTH(a.due_date)
         ORDER BY a.created_at DESC LIMIT 30
    ");
} else {
    $officers = db_fetch_all(
        "SELECT id, nama FROM users WHERE kanwil_id = ? AND role = 'officer' AND aktif = 1 ORDER BY nama",
        [$user['kanwil_id']]
    );
    $assignments = db_fetch_all(
        "SELECT a.*, u.nama as to_name, u.username as to_username,
                tp.status as real_status, tp.progress as real_progress
         FROM assignments a
         JOIN users u ON a.to_user_id = u.id
         LEFT JOIN task_templates tt ON tt.nama = a.task_name
         LEFT JOIN task_progress tp ON tp.template_id = tt.id AND tp.user_id = a.to_user_id AND tp.tahun = YEAR(a.due_date) AND tp.bulan = MONTH(a.due_date)
         WHERE a.from_user_id = ? OR a.to_user_id = ?
         ORDER BY a.created_at DESC LIMIT 20",
        [$user['id'], $user['id']]
    );
}
?>
<?php
$page_title = 'Penugasan Tugas — AMLO Dashboard';
$topbar_title = 'Penugasan Tugas';
include __DIR__ . '/../includes/layout_header.php';
?>

<div class="content">
            <?php if ($flash): ?>
                <div id="toast-notification" class="toast-notification <?= $flash['type'] ?? 'success' ?>">
                    <span class="toast-icon toast-icon-lg"><?= ($flash['type'] ?? 'success') === 'success' ? '<i class="ph ph-check-circle"></i>' : '<i class="ph ph-warning-circle"></i>' ?></span>
                    <span class="toast-message"><?= e($flash['message']) ?></span>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const toast = document.getElementById('toast-notification');
                        if (toast) {
                            setTimeout(() => {
                                toast.classList.add('show');
                                setTimeout(() => {
                                    toast.classList.remove('show');
                                }, 4000);
                            }, 100);
                        }
                    });
                </script>
            <?php endif; ?>

            <div class="page-header">
                <h2>Penugasan Tugas</h2>
                <p>Assign tugas adhoc kepada AMLO Officer di wilayah Anda</p>
            </div>

            <div class="two-col">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Buat Penugasan Baru</div>
                    </div>
                    <form method="POST" action="assignments.php">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                        <div class="input-group">
                            <label class="input-label">Pilih Officer</label>
                            <select name="to_user_id" class="select-field" required>
                                <option value="">-- Pilih Officer --</option>
                                <?php foreach ($officers as $o): ?>
                                    <option value="<?= $o['id'] ?>"><?= e($o['nama']) ?><?= !empty($o['kanwil_nama']) ? ' (' . e($o['kanwil_nama']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Nama Tugas</label>
                            <select class="select-field" name="task_name" required>
                                <option value="">-- Pilih Jenis Tugas --</option>
                                <option>RFI Remittance</option>
                                <option>Adhoc Enhanced Due Diligence (EDD)</option>
                                <option>Pendampingan Verifikasi Lapangan</option>
                                <option>Adhoc Asistensi UKO</option>
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

                        <?= render_ds_button([
                            'type' => 'submit',
                            'variant' => 'filled',
                            'size' => 'large',
                            'children' => 'Kirim Penugasan'
                        ]) ?>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Riwayat Penugasan</div>
                    </div>

                    <?php if (empty($assignments)): ?>
                        <div class="empty-state-p40">
                            Belum ada penugasan
                        </div>
                    <?php else: ?>
                        <?php foreach ($assignments as $a):
                            $display_status = 'Belum mulai';
                            $status_cls = 'red';
                            
                            // Determine real status based on task_progress
                            if (!empty($a['real_status'])) {
                                $rs = $a['real_status'];
                                if ($rs === 'done' || $rs === 'approved') {
                                    $display_status = 'Selesai';
                                    $status_cls = 'green';
                                } elseif ($rs === 'pending') {
                                    $display_status = 'Menunggu Review';
                                    $status_cls = 'amber';
                                } elseif ($rs === 'active') {
                                    $display_status = 'In Progress';
                                    $status_cls = 'amber';
                                }
                            } else {
                                // Fallback to assignments.status
                                $orig = $a['status'];
                                if ($orig === 'selesai') {
                                    $display_status = 'Selesai';
                                    $status_cls = 'green';
                                } elseif ($orig === 'in_progress') {
                                    $display_status = 'In Progress';
                                    $status_cls = 'amber';
                                }
                            }
                            $text_cls = $status_cls === 'green' ? 'text-success' : ($status_cls === 'amber' ? 'text-attention' : 'text-critical');
                        ?>
                            <div class="alert-item">
                                <div class="alert-dot <?= $status_cls ?>"></div>
                                <div>
                                    <div class="alert-text"><b><?= e($a['to_name']) ?></b><?= !empty($a['kanwil_nama']) ? ' (' . e($a['kanwil_nama']) . ')' : '' ?> — <?= e($a['task_name']) ?></div>
                                    <div class="alert-time">
                                        Due: <?= date('d M Y', strtotime($a['due_date'])) ?>
                                        · <span class="<?= $text_cls ?>">
                                            <?= e($display_status) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    
<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
