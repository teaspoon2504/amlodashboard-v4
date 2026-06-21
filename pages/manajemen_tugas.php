<?php
/**
 * Manajemen Target Tugas (HO & Lead)
 */

require_once __DIR__ . '/../includes/auth.php';
require_role(['ho', 'lead']);

$user = amlo_get_current_user();
$role = $user['role'];
$csrf_token = generate_csrf_token();

// Fetch templates for filter
$allowed_tasks = [
    'Adhoc Enhanced Due Diligence (EDD)',
    'Pengkinian Bad Data',
    'Pengkinian CIF ganda',
    'Pengkinian data Beneficial Owner',
    'Pengkinian data nasabah',
    'Adhoc RFI Remittance',
    'STR Proaktif',
    'Tindak Lanjut Alert STR',
    'Tindak Lanjut PEP Sistem AML CFT CPF',
    'Tindak Lanjut RBA Bankwide'
];
$placeholders = implode(',', array_fill(0, count($allowed_tasks), '?'));
$templates = db_fetch_all(
    "SELECT id, nama FROM task_templates WHERE is_active = 1 AND nama IN ($placeholders) ORDER BY nama", 
    $allowed_tasks
);

$period = get_current_period();
$tahun = $period['tahun'];
$bulan = $period['bulan'];

$page_title = 'Manajemen Target Tugas';
$topbar_title = 'Manajemen Target Tugas';
$topbar_date = tanggal_indonesia('now', 'long');
include __DIR__ . '/../includes/layout_header.php';
?>

<div class="content">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= $user['role'] === 'ho' ? 'Assign Target Regional Office' : ($user['role'] === 'lead' ? 'Distribusi Target' : 'Filter Manajemen Target') ?></div>
        </div>
        <div class="card-body">
            <form id="filter-form" class="todo-filters-container" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--steel); margin-bottom: 6px; display: block;">Nama Tugas</label>
                    <select id="filter_template" class="select-field" style="width: 250px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--hairline); background: var(--surface-soft);">
                        <option value="">-- Pilih Tugas --</option>
                        <?php foreach($templates as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= e($t['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--steel); margin-bottom: 6px; display: block;">Bulan</label>
                    <select id="filter_bulan" class="select-field" style="width: 150px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--hairline); background: var(--surface-soft);">
                        <?php 
                        $nama_bulan = [1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'];
                        foreach($nama_bulan as $k => $v): 
                        ?>
                            <option value="<?= $k ?>" <?= $k == $bulan ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--steel); margin-bottom: 6px; display: block;">Tahun</label>
                    <select id="filter_tahun" class="select-field" style="width: 120px; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--hairline); background: var(--surface-soft);">
                        <option value="2026" <?= $tahun == 2026 ? 'selected' : '' ?>>2026</option>
                        <option value="2027" <?= $tahun == 2027 ? 'selected' : '' ?>>2027</option>
                    </select>
                </div>
                <div>
                    <?= render_ds_button([
                        'children' => 'Tampilkan Target',
                        'type' => 'button',
                        'onClick' => 'loadTargets()'
                    ]) ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Alert / Info Container -->
    <div id="target-alert-container" style="display: none; margin-top: 20px; padding: 16px; border-radius: 8px; font-weight: 500;"></div>

    <div class="card" id="target-data-card" style="display: none; margin-top: 24px;">
        <div class="card-header">
            <div class="card-title" id="data-target-title">Data Target</div>
        </div>
        <div class="card-body">
            <form id="target-form" onsubmit="saveTargets(event)">
                <table class="ds-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--hairline);">
                            <?php if ($role === 'ho'): ?>
                                <th style="text-align: left; padding: 12px; color: var(--steel);">Kode Regional Office</th>
                                <th style="text-align: left; padding: 12px; color: var(--steel);">Nama Regional Office</th>
                            <?php else: ?>
                                <th style="text-align: left; padding: 12px; color: var(--steel);">Nama Officer (AMLO)</th>
                                <th style="text-align: left; padding: 12px; color: var(--steel);">Username</th>
                            <?php endif; ?>
                            <th style="text-align: right; padding: 12px; color: var(--steel); width: 200px;">Target Value</th>
                        </tr>
                    </thead>
                    <tbody id="target-tbody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>

                <div style="margin-top: 24px; display: flex; justify-content: flex-end; align-items: center; gap: 16px;">
                    <?php if ($role === 'lead'): ?>
                        <div style="font-size: 14px; color: var(--steel);">
                            Total Distribusi: <span id="total-dist-label" style="font-weight: bold; color: var(--ink-deep);">0</span> 
                            / <span id="plafon-label" style="font-weight: bold; color: var(--ink-deep);">0</span>
                        </div>
                    <?php endif; ?>
                    <?= render_ds_button([
                        'children' => 'Simpan Target',
                        'type' => 'submit',
                        'id' => 'btn-save-target'
                    ]) ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentPlafon = 0;
const userRole = '<?= $role ?>';

function loadTargets() {
    const template_id = document.getElementById('filter_template').value;
    const tahun = document.getElementById('filter_tahun').value;
    const bulan = document.getElementById('filter_bulan').value;

    if (!template_id) {
        alert('Silakan pilih Tugas terlebih dahulu.');
        return;
    }

    const tbody = document.getElementById('target-tbody');
    const dataCard = document.getElementById('target-data-card');
    const alertBox = document.getElementById('target-alert-container');
    
    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding: 20px;">Memuat data...</td></tr>';
    dataCard.style.display = 'block';
    alertBox.style.display = 'none';

    fetch(`../api/targets.php?action=get_targets&template_id=${template_id}&tahun=${tahun}&bulan=${bulan}`)
        .then(res => res.json())
        .then(res => {
            const templateSelect = document.getElementById('filter_template');
            const taskName = templateSelect.options[templateSelect.selectedIndex].text;
            document.getElementById('data-target-title').innerText = `Data Target ${taskName}`;
            
            if (!res.success) {
                tbody.innerHTML = `<tr><td colspan="3" style="text-align:center; padding: 20px; color: var(--attention);">${res.message}</td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            
            if (userRole === 'ho') {
                res.data.kanwils.forEach(k => {
                    tbody.innerHTML += `
                        <tr style="border-bottom: 1px solid var(--hairline);">
                            <td style="padding: 12px;">${k.kode}</td>
                            <td style="padding: 12px; font-weight: 500;">${k.kanwil_nama}</td>
                            <td style="padding: 12px; text-align: right;">
                                <input type="number" name="target_${k.kanwil_id}" class="target-input select-field" data-id="${k.kanwil_id}" value="${k.target_value}" min="0" style="width: 100px; text-align: right;">
                            </td>
                        </tr>
                    `;
                });
            } else if (userRole === 'lead') {
                currentPlafon = parseInt(res.data.plafon) || 0;
                document.getElementById('plafon-label').innerText = currentPlafon;
                
                if (currentPlafon === 0) {
                    alertBox.style.display = 'block';
                    alertBox.style.background = '#fff3cd';
                    alertBox.style.color = '#856404';
                    alertBox.style.border = '1px solid #ffeeba';
                    alertBox.innerHTML = '⚠️ HO belum menentukan target (plafon) untuk Regional Office Anda pada bulan ini.';
                } else {
                    alertBox.style.display = 'block';
                    alertBox.style.background = '#d4edda';
                    alertBox.style.color = '#155724';
                    alertBox.style.border = '1px solid #c3e6cb';
                    alertBox.innerHTML = `ℹ️ Plafon target dari HO untuk Regional Office Anda adalah: <b>${currentPlafon}</b>`;
                }

                res.data.officers.forEach(o => {
                    tbody.innerHTML += `
                        <tr style="border-bottom: 1px solid var(--hairline);">
                            <td style="padding: 12px; font-weight: 500;">${o.nama}</td>
                            <td style="padding: 12px; color: var(--steel);">${o.username}</td>
                            <td style="padding: 12px; text-align: right;">
                                <input type="number" name="target_${o.user_id}" class="target-input select-field" data-id="${o.user_id}" value="${o.target_value}" min="0" style="width: 100px; text-align: right;" oninput="calculateTotal()">
                            </td>
                        </tr>
                    `;
                });
                calculateTotal();
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="3" style="text-align:center; padding: 20px; color: var(--attention);">Terjadi kesalahan koneksi.</td></tr>`;
        });
}

function calculateTotal() {
    if (userRole !== 'lead') return;
    
    const inputs = document.querySelectorAll('.target-input');
    let total = 0;
    inputs.forEach(inp => {
        total += parseInt(inp.value) || 0;
    });
    
    const distLabel = document.getElementById('total-dist-label');
    const saveBtn = document.getElementById('btn-save-target');
    distLabel.innerText = total;

    if (total > currentPlafon) {
        distLabel.style.color = 'var(--attention)';
        saveBtn.disabled = true;
        saveBtn.classList.add('ds-btn-disabled');
    } else {
        distLabel.style.color = 'var(--ink-deep)';
        saveBtn.disabled = false;
        saveBtn.classList.remove('ds-btn-disabled');
    }
}

function saveTargets(e) {
    e.preventDefault();
    
    const template_id = document.getElementById('filter_template').value;
    const tahun = document.getElementById('filter_tahun').value;
    const bulan = document.getElementById('filter_bulan').value;
    
    if (!template_id) return;

    const targets = {};
    const inputs = document.querySelectorAll('.target-input');
    let totalDist = 0;
    
    inputs.forEach(inp => {
        const id = inp.getAttribute('data-id');
        const val = parseInt(inp.value) || 0;
        targets[id] = val;
        totalDist += val;
    });

    if (userRole === 'lead' && totalDist > currentPlafon) {
        alert(`Distribusi target (${totalDist}) melebihi plafon (${currentPlafon})!`);
        return;
    }

    const payload = {
        csrf_token: '<?= $csrf_token ?>',
        template_id: template_id,
        tahun: tahun,
        bulan: bulan,
        targets: targets
    };

    const btn = document.getElementById('btn-save-target');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span>Menyimpan...</span>';
    btn.disabled = true;

    fetch('../api/targets.php?action=save_targets', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(res => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        if (res.success) {
            alert('Sukses: ' + res.message);
            loadTargets(); // Refresh
        } else {
            alert('Gagal: ' + res.message);
        }
    })
    .catch(err => {
        console.error(err);
        btn.innerHTML = originalText;
        btn.disabled = false;
        alert('Terjadi kesalahan koneksi.');
    });
}
</script>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
