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
    'Pengkinian Bad Data',
    'Pengkinian CIF ganda',
    'Pengkinian data Beneficial Owner',
    'Pengkinian data nasabah',
    'RFI Remittance',
    'Tindak Lanjut Alert STR',
    'Tindak Lanjut PEP Sistem AML CFT CPF'
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
            <form id="filter-form" class="todo-filters-container cal-filter-form">
                <div>
                    <label class="filter-label">Nama Tugas</label>
                    <select id="filter_template" class="select-field filter-select w-250">
                        <option value="">-- Pilih Tugas --</option>
                        <?php foreach($templates as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= e($t['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="filter-label">Bulan</label>
                    <select id="filter_bulan" class="select-field filter-select w-150">
                        <?php 
                        $nama_bulan = [1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'];
                        foreach($nama_bulan as $k => $v): 
                        ?>
                            <option value="<?= $k ?>" <?= $k == $bulan ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="filter-label">Tahun</label>
                    <select id="filter_tahun" class="select-field filter-select w-120">
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
    <div id="target-alert-container" class="target-alert-box"></div>

    <div class="card mt-xl d-none" id="target-data-card">
        <div class="card-header">
            <div class="card-title" id="data-target-title">Data Target</div>
        </div>
        <div class="card-body">
            <form id="target-form" onsubmit="saveTargets(event)">
                <table class="ds-table w-full">
                    <thead>
                        <tr class="tr-border">
                            <?php if ($role === 'ho'): ?>
                                <th class="th-left-p12">Kode Regional Office</th>
                                <th class="th-left-p12">Nama Regional Office</th>
                            <?php else: ?>
                                <th class="th-left-p12">Nama Officer (AMLO)</th>
                                <th class="th-left-p12">Username</th>
                            <?php endif; ?>
                            <th class="th-right-p12 w-200">Target Value</th>
                        </tr>
                    </thead>
                    <tbody id="target-tbody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>

                <div class="target-footer-row">
                    <?php if ($role === 'lead'): ?>
                        <div class="font-14 text-steel">
                            Total Distribusi: <span id="total-dist-label" class="font-bold text-ink">0</span> 
                            / <span id="plafon-label" class="font-bold text-ink">0</span>
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
    
    tbody.innerHTML = '<tr><td colspan="3" class="td-center-p20">Memuat data...</td></tr>';
    dataCard.style.display = 'block';
    alertBox.style.display = 'none';

    fetch(`../api/targets.php?action=get_targets&template_id=${template_id}&tahun=${tahun}&bulan=${bulan}`)
        .then(res => res.json())
        .then(res => {
            const templateSelect = document.getElementById('filter_template');
            const taskName = templateSelect.options[templateSelect.selectedIndex].text;
            document.getElementById('data-target-title').innerText = `Data Target ${taskName}`;
            
            if (!res.success) {
                tbody.innerHTML = `<tr><td colspan="3" class="td-center-p20 text-attention">${res.message}</td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            
            if (userRole === 'ho') {
                res.data.kanwils.forEach(k => {
                    tbody.innerHTML += `
                        <tr class="tr-border">
                            <td class="td-p12">${k.kode}</td>
                            <td class="td-p12 font-medium">${k.kanwil_nama}</td>
                            <td class="td-right-p12">
                                <div class="adjust-group">
                                    <button type="button" onclick="adjustTarget(-1, 'target-inp-${k.kanwil_id}')" class="adjust-btn-minus">−</button>
                                    <input type="number" name="target_${k.kanwil_id}" id="target-inp-${k.kanwil_id}" class="target-input adjust-input" data-id="${k.kanwil_id}" value="${k.target_value}" min="0" oninput="calculateTotal()">
                                    <button type="button" onclick="adjustTarget(1, 'target-inp-${k.kanwil_id}')" class="adjust-btn-plus">+</button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            } else if (userRole === 'lead') {
                currentPlafon = parseInt(res.data.plafon) || 0;
                document.getElementById('plafon-label').innerText = currentPlafon;
                
                if (currentPlafon === 0) {
                    alertBox.className = 'target-alert-box login-alert-error';
                    alertBox.style.display = 'block';
                    alertBox.innerHTML = '⚠️ HO belum menentukan target untuk Regional Office Anda pada bulan ini.';
                } else {
                    alertBox.className = 'target-alert-box login-alert-success';
                    alertBox.style.display = 'block';
                    alertBox.innerHTML = `ℹ️ Plafon target dari HO untuk Regional Office Anda adalah: <b>${currentPlafon}</b>`;
                }

                res.data.officers.forEach(o => {
                    tbody.innerHTML += `
                        <tr class="tr-border">
                            <td class="td-p12 font-medium">${o.nama}</td>
                            <td class="td-p12 text-steel">${o.username}</td>
                            <td class="td-right-p12">
                                <div class="adjust-group">
                                    <button type="button" onclick="adjustTarget(-1, 'target-inp-${o.user_id}')" class="adjust-btn-minus">−</button>
                                    <input type="number" name="target_${o.user_id}" id="target-inp-${o.user_id}" class="target-input adjust-input" data-id="${o.user_id}" value="${o.target_value}" min="0" oninput="calculateTotal()">
                                    <button type="button" onclick="adjustTarget(1, 'target-inp-${o.user_id}')" class="adjust-btn-plus">+</button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                calculateTotal();
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="3" class="td-center-p20 text-attention">Terjadi kesalahan koneksi.</td></tr>`;
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

function adjustTarget(diff, inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    let val = parseInt(input.value) || 0;
    val += diff;
    if (val < 0) val = 0;
    input.value = val;
    calculateTotal();
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
