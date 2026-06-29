<?php
/**
 * AMLO Dashboard - Tasks API
 * JSON endpoints for task operations
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Require auth for all API calls
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = amlo_get_current_user();
$period = get_current_period();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get all active templates and left join with user's progress for this month
        $tasks = db_fetch_all(
            "SELECT tt.id as template_id, tt.nama, tt.kategori, tt.periode, tt.tag, tt.target, tt.due_label, tt.source_link,
                    tp.id as progress_id, tp.progress, IFNULL(tp.status, 'pending') as status, tp.keterangan
             FROM task_templates tt
             LEFT JOIN task_progress tp ON tp.template_id = tt.id AND tp.user_id = ? AND tp.tahun = ? AND tp.bulan = ?
             WHERE tt.is_active = 1
             ORDER BY tt.kategori, tt.nama",
            [$user['id'], $period['tahun'], $period['bulan']]
        );

        echo json_encode([
            'success' => true,
            'data' => $tasks,
            'period' => $period
        ]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            exit;
        }

        $action = $input['action'] ?? '';

        if ($action === 'update_progress') {
            $template_id = (int)($input['template_id'] ?? 0);
            $progress = (int)($input['progress'] ?? 0);
            $keterangan = trim($input['keterangan'] ?? '');

            if ($template_id === 0) {
                echo json_encode(['success' => false, 'message' => 'Template ID required']);
                exit;
            }

            $status = $progress >= 100 ? 'done' : ($progress > 0 ? 'active' : 'pending');

            // Check existing
            $existing = db_fetch_one(
                "SELECT id FROM task_progress WHERE user_id = ? AND template_id = ? AND tahun = ? AND bulan = ?",
                [$user['id'], $template_id, $period['tahun'], $period['bulan']]
            );

            if ($existing) {
                $result = db_exec(
                    "UPDATE task_progress SET progress = ?, status = ?, keterangan = ?, updated_at = NOW() WHERE id = ?",
                    [$progress, $status, $keterangan, $existing['id']]
                );
            } else {
                // Get template periode
                $template = db_fetch_one("SELECT periode FROM task_templates WHERE id = ?", [$template_id]);
                $result = db_insert(
                    "INSERT INTO task_progress (user_id, template_id, periode, tahun, bulan, progress, status, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$user['id'], $template_id, $template['periode'], $period['tahun'], $period['bulan'], $progress, $status, $keterangan]
                );
            }

            log_activity('api_task_update', "API update task $template_id to $progress%");

            echo json_encode([
                'success' => true,
                'message' => 'Progress updated',
                'data' => ['progress' => $progress, 'status' => $status]
            ]);
        } elseif ($action === 'submit_approval') {
            $task_progress_id = (int)($input['task_progress_id'] ?? 0);

            if ($task_progress_id === 0) {
                echo json_encode(['success' => false, 'message' => 'Task progress ID required']);
                exit;
            }

            // Verify ownership
            $tp = db_fetch_one("SELECT * FROM task_progress WHERE id = ? AND user_id = ?", [$task_progress_id, $user['id']]);

            if (!$tp) {
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit;
            }

            // Create submission
            $result = db_insert(
                "INSERT INTO submissions (task_progress_id, submitted_by, status) VALUES (?, ?, 'pending')",
                [$task_progress_id, $user['id']]
            );

            // Update status if pending
            if ($tp['status'] === 'pending') {
                db_exec("UPDATE task_progress SET status = 'active' WHERE id = ?", [$task_progress_id]);
            }

            log_activity('api_submit_approval', "API submit task $task_progress_id for approval");

            echo json_encode([
                'success' => true,
                'message' => 'Submitted for approval'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}