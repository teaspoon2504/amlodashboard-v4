<?php
/**
 * API for Target Management (HO <-> Lead <-> AMLO)
 */

require_once __DIR__ . '/../includes/auth.php';
require_api_auth();

$user = amlo_get_current_user();
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get_targets') {
        $template_id = (int)($_GET['template_id'] ?? 0);
        $tahun = (int)($_GET['tahun'] ?? 0);
        $bulan = (int)($_GET['bulan'] ?? 0);

        if (!$template_id || !$tahun || !$bulan) {
            json_response(false, 'Parameter tidak lengkap');
        }

        if ($user['role'] === 'ho') {
            // Get all Kanwil and their HO-assigned targets
            $data = db_fetch_all(
                "SELECT kw.id as kanwil_id, kw.kode, kw.nama as kanwil_nama, 
                        COALESCE(t.target_value, 0) as target_value
                 FROM kantor_wilayah kw
                 LEFT JOIN task_targets t ON kw.id = t.kanwil_id 
                    AND t.task_template_id = ? 
                    AND t.tahun = ? AND t.bulan = ? 
                    AND t.user_id IS NULL
                 WHERE kw.aktif = 1
                 ORDER BY kw.kode",
                [$template_id, $tahun, $bulan]
            );
            json_response(true, '', ['kanwils' => $data]);
        } 
        elseif ($user['role'] === 'lead') {
            // Get HO plafon for Lead's Kanwil
            $plafon_row = db_fetch_one(
                "SELECT COALESCE(target_value, 0) as target_value 
                 FROM task_targets 
                 WHERE task_template_id = ? AND kanwil_id = ? AND tahun = ? AND bulan = ? AND user_id IS NULL",
                [$template_id, $user['kanwil_id'], $tahun, $bulan]
            );
            $plafon = $plafon_row ? (int)$plafon_row['target_value'] : 0;

            // Get Officers in Lead's Kanwil and their assigned targets
            $officers = db_fetch_all(
                "SELECT u.id as user_id, u.username, u.nama,
                        COALESCE(t.target_value, 0) as target_value
                 FROM users u
                 LEFT JOIN task_targets t ON u.id = t.user_id 
                    AND t.task_template_id = ? 
                    AND t.tahun = ? AND t.bulan = ?
                 WHERE u.kanwil_id = ? AND u.role = 'officer' AND u.aktif = 1
                 ORDER BY u.nama",
                [$template_id, $tahun, $bulan, $user['kanwil_id']]
            );

            // Calculate current total distributed
            $total_distributed = 0;
            foreach ($officers as $off) {
                $total_distributed += (int)$off['target_value'];
            }

            json_response(true, '', [
                'plafon' => $plafon,
                'total_distributed' => $total_distributed,
                'officers' => $officers
            ]);
        }
        else {
            json_response(false, 'Role tidak memiliki akses');
        }
    }
}
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'save_targets') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!verify_csrf_token($data['csrf_token'] ?? '')) {
            json_response(false, 'Invalid CSRF token');
        }

        $template_id = (int)($data['template_id'] ?? 0);
        $tahun = (int)($data['tahun'] ?? 0);
        $bulan = (int)($data['bulan'] ?? 0);
        $targets = $data['targets'] ?? [];

        if (!$template_id || !$tahun || !$bulan || !is_array($targets)) {
            json_response(false, 'Data tidak valid');
        }

        if ($user['role'] === 'ho') {
            // HO assigns targets to Kanwils
            try {
                // Should ideally use transactions here
                foreach ($targets as $kanwil_id => $target_value) {
                    $kanwil_id = (int)$kanwil_id;
                    $target_value = (int)$target_value;

                    // Insert or Update (upsert)
                    db_exec(
                        "INSERT INTO task_targets (task_template_id, kanwil_id, user_id, tahun, bulan, target_value, created_by)
                         VALUES (?, ?, NULL, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE target_value = VALUES(target_value), updated_at = NOW()",
                        [$template_id, $kanwil_id, $tahun, $bulan, $target_value, $user['id']]
                    );
                }
                log_activity('set_ho_targets', "Mengeset target plafon untuk template ID $template_id periode $tahun-$bulan");
                json_response(true, 'Target berhasil disimpan');
            } catch (Exception $e) {
                json_response(false, 'Gagal menyimpan target: ' . $e->getMessage());
            }
        }
        elseif ($user['role'] === 'lead') {
            // Lead assigns targets to Officers
            // First check plafon
            $plafon_row = db_fetch_one(
                "SELECT COALESCE(target_value, 0) as target_value 
                 FROM task_targets 
                 WHERE task_template_id = ? AND kanwil_id = ? AND tahun = ? AND bulan = ? AND user_id IS NULL",
                [$template_id, $user['kanwil_id'], $tahun, $bulan]
            );
            $plafon = $plafon_row ? (int)$plafon_row['target_value'] : 0;

            $total_requested = 0;
            foreach ($targets as $user_id => $target_value) {
                $total_requested += (int)$target_value;
            }

            if ($total_requested > $plafon) {
                json_response(false, "Total distribusi ($total_requested) melebihi plafon dari HO ($plafon)");
            }

            try {
                foreach ($targets as $officer_id => $target_value) {
                    $officer_id = (int)$officer_id;
                    $target_value = (int)$target_value;

                    // Security check: ensure officer_id belongs to lead's kanwil
                    $off_check = db_fetch_one("SELECT id FROM users WHERE id = ? AND kanwil_id = ? AND role = 'officer'", [$officer_id, $user['kanwil_id']]);
                    if (!$off_check) continue;

                    db_exec(
                        "INSERT INTO task_targets (task_template_id, kanwil_id, user_id, tahun, bulan, target_value, created_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE target_value = VALUES(target_value), updated_at = NOW()",
                        [$template_id, $user['kanwil_id'], $officer_id, $tahun, $bulan, $target_value, $user['id']]
                    );
                }
                log_activity('set_lead_targets', "Mendistribusikan target ke officer untuk template ID $template_id periode $tahun-$bulan");
                json_response(true, 'Distribusi target berhasil disimpan');
            } catch (Exception $e) {
                json_response(false, 'Gagal menyimpan distribusi: ' . $e->getMessage());
            }
        }
        else {
            json_response(false, 'Role tidak memiliki akses');
        }
    }
}

json_response(false, 'Invalid action');
