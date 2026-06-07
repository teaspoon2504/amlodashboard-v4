<?php
/**
 * AMLO Dashboard - Approvals API
 * For Lead/HO to review and approve/reject submissions
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = amlo_get_current_user();
$period = get_current_period();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get pending submissions for review
        $role_filter = '';
        $params = [];

        if ($user['role'] === 'lead') {
            // Lead sees submissions from their kanwil
            $role_filter = "AND u.kanwil_id = ?";
            $params[] = $user['kanwil_id'];
        }

        $submissions = db_fetch_all(
            "SELECT s.*, tp.progress, tp.status, tp.keterangan,
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

        echo json_encode([
            'success' => true,
            'data' => $submissions,
            'user_role' => $user['role']
        ]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            exit;
        }

        $action = $input['action'] ?? '';

        if ($action === 'approve' || $action === 'reject') {
            $submission_id = (int)($input['submission_id'] ?? 0);
            $catatan = trim($input['catatan'] ?? '');

            if ($submission_id === 0) {
                echo json_encode(['success' => false, 'message' => 'Submission ID required']);
                exit;
            }

            // Verify the submission exists and user has permission
            $submission = db_fetch_one(
                "SELECT s.*, u.kanwil_id FROM submissions s
                 JOIN task_progress tp ON s.task_progress_id = tp.id
                 JOIN users u ON tp.user_id = u.id
                 WHERE s.id = ?",
                [$submission_id]
            );

            if (!$submission) {
                echo json_encode(['success' => false, 'message' => 'Submission not found']);
                exit;
            }

            // Check permission
            if ($user['role'] === 'lead' && $submission['kanwil_id'] !== $user['kanwil_id']) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit;
            }

            $status = $action === 'approve' ? 'approved' : 'rejected';
            $role_approver = $user['role']; // 'lead' or 'ho'

            // Create approval record
            db_insert(
                "INSERT INTO approvals (submission_id, approver_id, role_approver, status, catatan) VALUES (?, ?, ?, ?, ?)",
                [$submission_id, $user['id'], $role_approver, $status, $catatan]
            );

            // Update submission status
            db_exec(
                "UPDATE submissions SET status = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?",
                [$status, $user['id'], $submission_id]
            );

            // If approved, update task progress to approved
            if ($status === 'approved') {
                db_exec(
                    "UPDATE task_progress SET status = 'approved', updated_at = NOW() WHERE id = ?",
                    [$submission['task_progress_id']]
                );
            }

            log_activity('approval_' . $status, "Submission $submission_id $status by {$user['role']}");

            echo json_encode([
                'success' => true,
                'message' => "Submission $status successfully",
                'data' => ['status' => $status]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}