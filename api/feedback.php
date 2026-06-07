<?php
/**
 * AMLO Dashboard - Feedback API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = amlo_get_current_user();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $task_progress_id = (int)($_GET['task_progress_id'] ?? 0);

        if ($task_progress_id > 0) {
            $feedbacks = db_fetch_all(
                "SELECT f.*, u.nama as from_name
                 FROM feedbacks f
                 JOIN users u ON f.from_user_id = u.id
                 WHERE f.task_progress_id = ?
                 ORDER BY f.created_at DESC",
                [$task_progress_id]
            );
        } else {
            // Get feedbacks for current user or from current user
            $feedbacks = db_fetch_all(
                "SELECT f.*, u.nama as from_name, tt.nama as task_name
                 FROM feedbacks f
                 JOIN users u ON f.from_user_id = u.id
                 JOIN task_progress tp ON f.task_progress_id = tp.id
                 JOIN task_templates tt ON tp.template_id = tt.id
                 WHERE tp.user_id = ? OR f.from_user_id = ?
                 ORDER BY f.created_at DESC
                 LIMIT 50",
                [$user['id'], $user['id']]
            );
        }

        echo json_encode([
            'success' => true,
            'data' => $feedbacks
        ]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            exit;
        }

        $action = $input['action'] ?? '';

        if ($action === 'add_feedback') {
            $task_progress_id = (int)($input['task_progress_id'] ?? 0);
            $isi = trim($input['isi'] ?? '');

            if ($task_progress_id === 0 || empty($isi)) {
                echo json_encode(['success' => false, 'message' => 'Task progress ID and feedback text required']);
                exit;
            }

            // Verify user has access to this task
            $task = db_fetch_one(
                "SELECT tp.*, u.kanwil_id FROM task_progress tp
                 JOIN users u ON tp.user_id = u.id
                 WHERE tp.id = ?",
                [$task_progress_id]
            );

            if (!$task) {
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit;
            }

            // Officer can only give feedback on own tasks
            // Lead/HO can give feedback on any task in their scope
            if ($user['role'] === 'officer' && $task['user_id'] !== $user['id']) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit;
            }

            if ($user['role'] === 'lead' && $task['kanwil_id'] !== $user['kanwil_id']) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit;
            }

            $result = db_insert(
                "INSERT INTO feedbacks (task_progress_id, from_user_id, from_role, isi) VALUES (?, ?, ?, ?)",
                [$task_progress_id, $user['id'], $user['role'], $isi]
            );

            log_activity('feedback_add', "Feedback added to task $task_progress_id");

            echo json_encode([
                'success' => true,
                'message' => 'Feedback added successfully',
                'data' => ['feedback_id' => $result['insert_id'] ?? 0]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}