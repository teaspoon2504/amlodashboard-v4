<?php
/**
 * AMLO Dashboard - Approvals Dashboard
 */

require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = amlo_get_current_user();
if ($user['role'] === 'officer') {
    // Officers don't have access to this page
    header('Location: dashboard.php');
    exit;
}

$period = get_current_period();

// Fetch pending submissions
$role_filter = '';
$params = [];

if ($user['role'] === 'lead') {
    $role_filter = "AND u.kanwil_id = ?";
    $params[] = $user['kanwil_id'];
}

$submissions = db_fetch_all(
    "SELECT s.*, tp.progress, tp.status as tp_status, tp.keterangan,
            u.id as officer_id, u.nama as officer_name, u.username,
            kw.id as kanwil_id, kw.kode, kw.nama as kanwil_nama,
            tt.nama as task_name, tt.kategori, tt.periode
     FROM submissions s
     JOIN task_progress tp ON s.task_progress_id = tp.id
     JOIN users u ON s.submitted_by = u.id
     JOIN kantor_wilayah kw ON u.kanwil_id = kw.id
     JOIN task_templates tt ON tp.template_id = tt.id
     WHERE s.status = 'pending' $role_filter
     ORDER BY s.submitted_at DESC",
    $params
);

$page_title = get_page_title('approvals');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - AMLO Dashboard</title>
    <link rel="stylesheet" href="../assets/css/fonts.css">
    <link rel="stylesheet" href="../assets/css/amlo-design-system.css">
    <script>
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
</head>
<body>
<div class="layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header-bar">
            <div>
                <div class="header-title">Approvals Dashboard</div>
                <div class="header-subtitle">Review dan setujui pengajuan status Selesai dari Officer</div>
            </div>
            <div class="header-actions">
                <div class="user-chip">
                    <div class="user-avatar" style="background:var(--gold);color:white;"><?= strtoupper(substr($user['nama'], 0, 2)) ?></div>
                    <div class="user-chip-info">
                        <div class="user-chip-name"><?= e($user['nama']) ?></div>
                        <div class="user-chip-role"><?= e(get_role_label($user['role'])) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-container">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">⏳ Menunggu Persetujuan (Pending Approvals)</div>
                </div>
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Officer</th>
                            <th>Tugas</th>
                            <th>Kategori</th>
                            <th>Keterangan / Evidence</th>
                            <th>Tanggal Pengajuan</th>
                            <th style="text-align:center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="approvals-tbody">
                        <?php if (empty($submissions)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center;color:var(--steel);padding:40px">
                                    <div style="font-size:24px;margin-bottom:8px">🎉</div>
                                    Tidak ada pengajuan yang perlu di-review saat ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($submissions as $s): ?>
                                <tr id="row-<?= $s['id'] ?>">
                                    <td style="color:var(--steel)"><?= $no++ ?></td>
                                    <td>
                                        <div style="font-weight:600;color:var(--teal-light)">👤 <?= e($s['officer_name']) ?></div>
                                        <?php if($user['role']==='ho'): ?>
                                            <div style="font-size:11px;color:var(--steel);margin-top:2px"><?= e($s['kanwil_nama']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><b><?= e($s['task_name']) ?></b></td>
                                    <td><span class="chip-wilayah"><?= e($s['kategori']) ?></span></td>
                                    <td>
                                        <div style="font-size:12px;color:var(--steel);max-width:250px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= e($s['keterangan']) ?>">
                                            <?= $s['keterangan'] ? e($s['keterangan']) : '<i>Tidak ada keterangan</i>' ?>
                                        </div>
                                    </td>
                                    <td><?= date('d M Y, H:i', strtotime($s['submitted_at'])) ?></td>
                                    <td style="text-align:center">
                                        <div style="display:flex;gap:8px;justify-content:center;">
                                            <button type="button" class="btn-primary" style="padding:6px 12px;font-size:12px" onclick="openApprovalModal(<?= $s['id'] ?>, 'approve', '<?= htmlspecialchars($s['officer_name'], ENT_QUOTES) ?>')">
                                                ✅ Approve
                                            </button>
                                            <button type="button" class="btn-secondary" style="padding:6px 12px;font-size:12px;color:var(--critical);border-color:var(--critical)" onclick="openApprovalModal(<?= $s['id'] ?>, 'reject', '<?= htmlspecialchars($s['officer_name'], ENT_QUOTES) ?>')">
                                                ❌ Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Approval -->
<div class="modal-overlay" id="modal-overlay">
    <div class="modal" id="modal-box">
        <div class="modal-header">
            <div class="modal-title" id="modal-title">Konfirmasi Approval</div>
            <div class="modal-close" onclick="closeModal()">✕</div>
        </div>
        <div id="modal-body">
            <div style="margin-bottom:16px; font-size:14px; color:var(--steel)" id="modal-desc">
                Anda akan menyetujui pengajuan dari Officer.
            </div>
            
            <input type="hidden" id="modal-submission-id">
            <input type="hidden" id="modal-action">
            
            <div class="input-group">
                <label class="input-label">Catatan / Pesan Tambahan (Opsional)</label>
                <textarea id="modal-catatan" class="textarea-field" placeholder="Ketik catatan atau alasan (jika ada)..." style="min-height:80px"></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-primary" id="btn-submit-modal" onclick="submitApproval()">Proses</button>
                <button type="button" class="btn-secondary" onclick="closeModal()">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
function openApprovalModal(submissionId, action, officerName) {
    document.getElementById('modal-submission-id').value = submissionId;
    document.getElementById('modal-action').value = action;
    document.getElementById('modal-catatan').value = '';
    
    const title = document.getElementById('modal-title');
    const desc = document.getElementById('modal-desc');
    const btn = document.getElementById('btn-submit-modal');
    
    if (action === 'approve') {
        title.textContent = '✅ Konfirmasi Approve';
        desc.innerHTML = `Anda akan menyetujui pengajuan tugas dari <b>${officerName}</b>. Status tugas ini akan berubah menjadi <b>Selesai</b>.`;
        btn.textContent = 'Approve Tugas';
        btn.style.background = 'var(--success)';
    } else {
        title.textContent = '❌ Konfirmasi Reject';
        desc.innerHTML = `Anda akan menolak (reject) pengajuan tugas dari <b>${officerName}</b>.`;
        btn.textContent = 'Reject Tugas';
        btn.style.background = 'var(--critical)';
    }
    
    document.getElementById('modal-overlay').classList.add('open');
}

function closeModal() {
    document.getElementById('modal-overlay').classList.remove('open');
}

function submitApproval() {
    const submissionId = document.getElementById('modal-submission-id').value;
    const action = document.getElementById('modal-action').value;
    const catatan = document.getElementById('modal-catatan').value;
    const btn = document.getElementById('btn-submit-modal');
    
    btn.disabled = true;
    btn.textContent = 'Memproses...';
    
    fetch('../api/approvals.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: action,
            submission_id: submissionId,
            catatan: catatan
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeModal();
            // Remove row from table with a nice fade out
            const row = document.getElementById('row-' + submissionId);
            if (row) {
                row.style.transition = 'opacity 0.4s ease';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    // Check if table is empty
                    const tbody = document.getElementById('approvals-tbody');
                    if (tbody.children.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="7" style="text-align:center;color:var(--steel);padding:40px">
                                    <div style="font-size:24px;margin-bottom:8px">🎉</div>
                                    Tidak ada pengajuan yang perlu di-review saat ini.
                                </td>
                            </tr>
                        `;
                    }
                }, 400);
            }
        } else {
            alert('Gagal memproses: ' + (data.message || 'Error tidak diketahui'));
            btn.disabled = false;
            btn.textContent = action === 'approve' ? 'Approve Tugas' : 'Reject Tugas';
        }
    })
    .catch(err => {
        alert('Terjadi kesalahan koneksi jaringan.');
        console.error(err);
        btn.disabled = false;
        btn.textContent = action === 'approve' ? 'Approve Tugas' : 'Reject Tugas';
    });
}
</script>
</body>
</html>
