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
     WHERE s.status = 'pending' AND tt.periode != 'harian' $role_filter
     ORDER BY s.submitted_at DESC",
    $params
);

$page_title = get_page_title('approvals');
?>
<?php
$page_title = 'Approvals Dashboard';
$topbar_title = 'Approvals Dashboard';
include __DIR__ . '/../includes/layout_header.php';
?>


        <div class="content">
            <div class="page-header">
                <h2>Approval</h2>
                <p>Periksa dan setujui tugas yang sudah dikerjakan AMLO</p>
            </div>
            
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
                            <th class="th-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="approvals-tbody">
                        <?php if (empty($submissions)): ?>
                            <tr>
                                <td colspan="7" class="empty-state-p40">
                                    <div class="font-24 mb-sm">🎉</div>
                                    Tidak ada pengajuan yang perlu di-review saat ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($submissions as $s): ?>
                                <tr id="row-<?= $s['id'] ?>">
                                    <td class="text-steel"><?= $no++ ?></td>
                                    <td>
                                        <div class="font-semibold text-teal">👤 <?= e($s['officer_name']) ?></div>
                                        <?php if($user['role']==='ho'): ?>
                                            <div class="font-11 text-steel mt-xs"><?= e($s['kanwil_nama']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><b><?= e($s['task_name']) ?></b></td>
                                    <td><span class="chip-wilayah"><?= e($s['kategori']) ?></span></td>
                                    <td>
                                        <div class="font-12 text-steel max-w-250 text-ellipsis" title="<?= e(format_keterangan($s['keterangan'])) ?>">
                                            <?= $s['keterangan'] ? e(format_keterangan($s['keterangan'])) : '<i>Tidak ada keterangan</i>' ?>
                                        </div>
                                    </td>
                                    <td><?= date('d M Y, H:i', strtotime($s['submitted_at'])) ?></td>
                                    <td class="td-center">
                                        <div class="approval-actions-row">
                                            <?= render_ds_button([
                                                'type' => 'button',
                                                'variant' => 'filled',
                                                'size' => 'small',
                                                'children' => 'Approve',
                                                'leftIcon' => '✅',
                                                'onClick' => "openApprovalModal({$s['id']}, 'approve', '" . htmlspecialchars($s['officer_name'], ENT_QUOTES) . "')"
                                            ]) ?>
                                            <?= render_ds_button([
                                                'type' => 'button',
                                                'variant' => 'outlined',
                                                'size' => 'small',
                                                'children' => 'Reject',
                                                'leftIcon' => '❌',
                                                'onClick' => "openApprovalModal({$s['id']}, 'reject', '" . htmlspecialchars($s['officer_name'], ENT_QUOTES) . "')"
                                            ]) ?>
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
            <div class="font-14 text-steel mb-base" id="modal-desc">
                Anda akan menyetujui pengajuan dari Officer.
            </div>
            
            <input type="hidden" id="modal-submission-id">
            <input type="hidden" id="modal-action">
            
            <div class="input-group">
                <label class="input-label">Catatan / Pesan Tambahan (Opsional)</label>
                <textarea id="modal-catatan" class="textarea-field min-h-80" placeholder="Ketik catatan atau alasan (jika ada)..."></textarea>
            </div>
            
            <div class="modal-actions">
                <?= render_ds_button([
                    'type' => 'button',
                    'id' => 'btn-submit-modal',
                    'variant' => 'filled',
                    'size' => 'medium',
                    'children' => 'Proses',
                    'onClick' => 'submitApproval()'
                ]) ?>
                <?= render_ds_button([
                    'type' => 'button',
                    'variant' => 'outlined',
                    'size' => 'medium',
                    'children' => 'Batal',
                    'onClick' => 'closeModal()'
                ]) ?>
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
                                <td colspan="7" class="empty-state-p40">
                                    <div class="font-24 mb-sm">🎉</div>
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
<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
