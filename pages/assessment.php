<?php
/**
 * AMLO Dashboard - Assessment & Feedback (HO)
 */


require_once __DIR__ . '/../includes/auth.php';
require_role('ho');

$user = amlo_get_current_user();
$csrf_token = generate_csrf_token();
$period = get_current_period();

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'give_feedback') {
        $to_user_id = (int)$_POST['to_user_id'];
        $isi = trim($_POST['isi'] ?? '');
        $penilaian = $_POST['penilaian'] ?? '';

        if ($to_user_id > 0 && !empty($isi)) {
            // Get user's latest task for feedback context
            $latest_task = db_fetch_one(
                "SELECT tp.id FROM task_progress tp WHERE tp.user_id = ? ORDER BY tp.id DESC LIMIT 1",
                [$to_user_id]
            );

            if ($latest_task) {
                db_insert(
                    "INSERT INTO feedbacks (task_progress_id, from_user_id, from_role, isi) VALUES (?, ?, ?, ?)",
                    [$latest_task['id'], $user['id'], 'ho', "$penilaian: $isi"
                ]);
                log_activity('ho_feedback', "HO feedback for user $to_user_id");
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Feedback berhasil dikirim ke Regional Office!'];
            }
        }

        header('Location: assessment.php');
        exit;
    }
}

$flash = get_flash();

// Get all wilayah
$wilayah_list = db_fetch_all("SELECT * FROM kantor_wilayah WHERE aktif = 1 ORDER BY kode");

// Get all officers for JS map
$all_officers = db_fetch_all("SELECT id, nama, kanwil_id FROM users WHERE role = 'officer' AND aktif = 1 ORDER BY nama");
$officers_by_kanwil = [];
foreach ($all_officers as $off) {
    $officers_by_kanwil[$off['kanwil_id']][] = $off;
}

// Get pending submissions for review
$pending_submissions = db_fetch_all(
    "SELECT s.*, tp.progress, tp.status, tp.keterangan,
            u.nama as officer_name, u.username,
            kw.nama as kanwil_nama,
            tt.nama as task_name
     FROM submissions s
     JOIN task_progress tp ON s.task_progress_id = tp.id
     JOIN users u ON s.submitted_by = u.id
     JOIN kantor_wilayah kw ON u.kanwil_id = kw.id
     JOIN task_templates tt ON tp.template_id = tt.id
     WHERE s.status = 'pending' AND tt.nama NOT LIKE '%E-Learning Target%' AND tt.nama NOT LIKE '%Tindak Lanjut RBA Bankwide%'
     ORDER BY s.submitted_at DESC"
);

// Get recent feedbacks sent
$recent_feedbacks = db_fetch_all(
    "SELECT f.*, u.nama as to_name, tt.nama as task_name
     FROM feedbacks f
     JOIN task_progress tp ON f.task_progress_id = tp.id
     JOIN users u ON tp.user_id = u.id
     JOIN task_templates tt ON tp.template_id = tt.id
     WHERE f.from_role = 'ho'
     ORDER BY f.created_at DESC LIMIT 10"
);

$page_title = 'Assessment & Feedback Kanpus — AMLO Dashboard';
$topbar_title = 'Assessment & Feedback Kanpus';
include __DIR__ . '/../includes/layout_header.php';
?>

<div class="content">
    <?php if ($flash): ?>
        <div class="alert alert-success"><i class="ph ph-check-circle"></i> <?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <h2>Assessment & Feedback Kantor Pusat</h2>
        <p>Review submissions dan berikan feedback untuk seluruh Regional Office</p>
    </div>

    <div class="two-col">
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="ph ph-note-pencil"></i> Beri Feedback ke Regional Office</div>
            </div>
            <form method="POST" action="assessment.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                <input type="hidden" name="action" value="give_feedback">

                <div class="input-group">
                    <label class="input-label">Pilih Regional Office</label>
                    <select name="kanwil_id" class="select-field" onchange="loadOfficers(this.value)" id="kanwil-select">
                        <option value="">-- Pilih Regional Office --</option>
                        <?php foreach ($wilayah_list as $kw): ?>
                            <option value="<?= $kw['id'] ?>"><?= e($kw['kode']) ?> — <?= e($kw['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label class="input-label">Pilih Officer</label>
                    <select name="to_user_id" id="officer-select" class="select-field" required>
                        <option value="">-- Pilih Officer --</option>
                    </select>
                </div>

                <div class="input-group">
                    <label class="input-label">Penilaian</label>
                    <select class="select-field" name="penilaian" id="penilaian-select">
                        <option>Lengkap — Sesuai standar</option>
                        <option>Sebagian — Perlu penyempurnaan</option>
                        <option>Belum Respon — Perlu tindakan</option>
                    </select>
                </div>

                <div class="input-group">
                    <label class="input-label">Catatan / Feedback</label>
                    <textarea name="isi" class="textarea-field" placeholder="Tulis feedback untuk Regional Office..." required></textarea>
                </div>

                <?= render_ds_button([
                    'type' => 'submit',
                    'variant' => 'filled',
                    'size' => 'large',
                    'children' => 'Kirim Feedback',
                    'leftIcon' => '<i class="ph ph-paper-plane-tilt"></i>'
                ]) ?>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="ph ph-chat-centered-text"></i> Riwayat Feedback Terkirim</div>
            </div>

            <?php if (empty($recent_feedbacks)): ?>
                <div class="empty-state-p40">
                    Belum ada feedback terkirim
                </div>
            <?php else: ?>
                <?php foreach ($recent_feedbacks as $fb): ?>
                    <div class="feedback-box">
                        <div class="feedback-header">
                            <div class="fb-avatar"><?= strtoupper(substr($user['nama'], 0, 2)) ?></div>
                            <div class="fb-meta">
                                <strong><?= e($user['nama']) ?></strong> → <?= e($fb['to_name']) ?><br>
                                <?= date('d M Y', strtotime($fb['created_at'])) ?> · <?= e($fb['task_name']) ?>
                            </div>
                        </div>
                        <div class="fb-text"><?= e($fb['isi']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($pending_submissions)): ?>
        <div class="card mb-xl">
            <div class="card-header">
                <div class="card-title"><i class="ph ph-hourglass-high"></i> Pending Submissions (<?= count($pending_submissions) ?>)</div>
            </div>
            <?php foreach ($pending_submissions as $s): ?>
                <div class="submission-item">
                    <div>
                        <strong><?= e($s['task_name']) ?></strong> — <?= e($s['officer_name']) ?>
                        <div class="font-12 text-ink-muted">Disubmit pada <?= date('d/m/Y H:i', strtotime($s['submitted_at'])) ?></div>
                    </div>
                    <a href="approvals.php?highlight=<?= $s['id'] ?>" class="ds-button ds-button--outline ds-button--small">Review</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
// Load actual officer data from PHP
const officersData = <?= json_encode($officers_by_kanwil) ?>;

document.getElementById('kanwil-select').addEventListener('change', function() {
    const kanwilId = this.value;
    const officerSelect = document.getElementById('officer-select');

    if (!kanwilId || !officersData[kanwilId]) {
        officerSelect.innerHTML = '<option value="">-- Pilih Officer --</option>';
        return;
    }

    officerSelect.innerHTML = '<option value="">-- Pilih Officer --</option>';
    officersData[kanwilId].forEach(function(officer) {
        const option = document.createElement('option');
        option.value = officer.id;
        option.textContent = officer.nama;
        officerSelect.appendChild(option);
    });
});
</script>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
