<?php
/**
 * AMLO Dashboard - Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

/**
 * Start secure session
 */
function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true
        ]);
    }
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    start_secure_session();
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Get current user (internal)
 */
function amlo_get_current_user() {
    if (!is_logged_in()) return null;

    return db_fetch_one(
        "SELECT u.*, kw.kode, kw.nama as kanwil_nama
         FROM users u
         JOIN kantor_wilayah kw ON u.kanwil_id = kw.id
         WHERE u.id = ? AND u.aktif = 1",
        [$_SESSION['user_id']]
    );
}

/**
 * Check user role
 */
function has_role($role) {
    $user = amlo_get_current_user();
    if (!$user) return false;

    if (is_array($role)) {
        return in_array($user['role'], $role);
    }
    return $user['role'] === $role;
}

/**
 * Require login or redirect
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require specific role or redirect
 */
function require_role($role) {
    require_login();
    if (!has_role($role)) {
        header('Location: ../pages/dashboard.php');
        exit;
    }
}

/**
 * Log activity
 */
function log_activity($action, $detail = '') {
    if (!is_logged_in()) return;

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    db_exec(
        "INSERT INTO activity_log (user_id, action, detail, ip_address) VALUES (?, ?, ?, ?)",
        [$_SESSION['user_id'], $action, $detail, $ip]
    );
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    start_secure_session();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format tanggal Indonesia
 */
function tanggal_indonesia($date, $format = 'long') {
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    $d = new DateTime($date);

    if ($format === 'long') {
        return $hari[$d->format('w')] . ', ' . $d->format('j ') . $bulan[(int)$d->format('n')] . $d->format(' Y');
    }
    return $d->format('d/m/Y');
}

/**
 * Get role label in Indonesia
 */
function get_role_label($role) {
    $labels = [
        'officer' => 'AMLO Officer',
        'lead' => 'AMLO Lead / Team Leader',
        'ho' => 'Head Office Assurance'
    ];
    return $labels[$role] ?? $role;
}

/**
 * Get status badge class
 */
function get_status_class($status) {
    $classes = [
        'pending' => 'perf-pending',
        'active' => 'perf-good',
        'done' => 'perf-exceed',
        'approved' => 'perf-exceed',
        'rejected' => 'perf-below'
    ];
    return $classes[$status] ?? 'perf-pending';
}

/**
 * Get progress color
 */
function get_progress_color($progress) {
    if ($progress >= 100) return '#2ecc71'; // green
    if ($progress >= 80) return '#3498db';  // blue/good
    if ($progress >= 50) return '#f39c12';  // amber
    return '#e05252'; // red
}

/**
 * JSON response helper
 */
function json_response($success, $message = '', $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Get task summary for user
 */
function get_task_summary($user_id, $tahun, $bulan) {
    $all = db_fetch_all(
        "SELECT status, COUNT(*) as count FROM task_progress
         WHERE user_id = ? AND tahun = ? AND bulan = ? AND periode != 'harian'
         GROUP BY status",
        [$user_id, $tahun, $bulan]
    );

    $summary = ['done' => 0, 'active' => 0, 'pending' => 0, 'approved' => 0, 'total' => 0];
    foreach ($all as $row) {
        $summary[$row['status']] = (int)$row['count'];
        $summary['total'] += (int)$row['count'];
    }
    return $summary;
}

/**
 * Get performance metrics for user
 */
function get_user_performance($user_id, $tahun, $bulan) {
    $tasks = db_fetch_all(
        "SELECT tp.*, tt.nama, tt.kategori, tt.periode, tt.tag
         FROM task_progress tp
         JOIN task_templates tt ON tp.template_id = tt.id
         WHERE tp.user_id = ? AND tp.tahun = ? AND tp.bulan = ? AND tp.periode != 'harian'
         ORDER BY tt.kategori, tt.nama",
        [$user_id, $tahun, $bulan]
    );

    $total_progress = 0;
    $count = count($tasks);
    foreach ($tasks as $t) {
        $total_progress += $t['progress'];
    }

    $avg = $count > 0 ? round($total_progress / $count) : 0;

    return [
        'tasks' => $tasks,
        'total' => $count,
        'average_progress' => $avg,
        'exceed' => count(array_filter($tasks, fn($t) => $t['progress'] >= 100)),
        'good' => count(array_filter($tasks, fn($t) => $t['progress'] >= 80 && $t['progress'] < 100)),
        'below' => count(array_filter($tasks, fn($t) => $t['progress'] > 0 && $t['progress'] < 80)),
        'pending' => count(array_filter($tasks, fn($t) => $t['progress'] === 0))
    ];
}

/**
 * Get wilayah summary for HO
 */
function get_wilayah_summary() {
    return db_fetch_all(
        "SELECT kw.*,
            (SELECT COUNT(*) FROM users WHERE kanwil_id = kw.id AND aktif = 1 AND role = 'officer') as total_officer,
            (SELECT COUNT(*) FROM users u
             JOIN task_progress tp ON u.id = tp.user_id
             WHERE u.kanwil_id = kw.id AND tp.progress >= 100 AND tp.periode != 'harian') as exceed_count,
            (SELECT COUNT(*) FROM users u
             JOIN task_progress tp ON u.id = tp.user_id
             WHERE u.kanwil_id = kw.id AND tp.progress >= 80 AND tp.progress < 100 AND tp.periode != 'harian') as good_count,
            (SELECT COUNT(*) FROM users u
             JOIN task_progress tp ON u.id = tp.user_id
             WHERE u.kanwil_id = kw.id AND tp.progress > 0 AND tp.progress < 80 AND tp.periode != 'harian') as below_count
         FROM kantor_wilayah kw
         WHERE kw.aktif = 1
         ORDER BY kw.kode"
    );
}

/**
 * Sanitize output for HTML
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get current tahun-bulan (for task context)
 */
function get_current_period() {
    return [
        'tahun' => (int)date('Y'),
        'bulan' => (int)date('n')
    ];
}

/**
 * Redirect with message
 */
function redirect_with($url, $type = 'success', $message = '') {
    if ($message) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
    header("Location: $url");
    exit;
}

/**
 * Get flash message
 */
function get_flash() {
    start_secure_session();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render Design System Button
 * 
 * @param array $props
 *   - variant: 'filled' | 'outlined'
 *   - size: 'small' | 'medium' | 'large'
 *   - isDisabled: bool
 *   - leftIcon: string (HTML)
 *   - children: string
 *   - onClick: string
 *   - type: string ('button' | 'submit' | 'reset')
 *   - class: string
 *   - style: string
 *   - id: string
 */
function render_ds_button($props) {
    $variant = $props['variant'] ?? 'filled';
    $size = $props['size'] ?? 'medium';
    $isDisabled = $props['isDisabled'] ?? false;
    $leftIcon = $props['leftIcon'] ?? '';
    $children = $props['children'] ?? '';
    $onClick = $props['onClick'] ?? '';
    $type = $props['type'] ?? 'button';
    $class = $props['class'] ?? '';
    $style = $props['style'] ?? '';
    $id = $props['id'] ?? '';
    $disabledAttr = $isDisabled ? 'disabled' : '';

    $classes = ['ds-btn', "ds-btn-{$variant}", "ds-btn-{$size}"];
    if ($isDisabled) $classes[] = 'ds-btn-disabled';
    if ($class) $classes[] = $class;

    $classStr = implode(' ', $classes);
    
    $onClickAttr = $onClick ? "onclick=\"" . htmlspecialchars($onClick, ENT_QUOTES) . "\"" : "";
    $idAttr = $id ? "id=\"" . htmlspecialchars($id, ENT_QUOTES) . "\"" : "";
    $styleAttr = $style ? "style=\"" . htmlspecialchars($style, ENT_QUOTES) . "\"" : "";

    $html = "<button type=\"$type\" class=\"$classStr\" $onClickAttr $idAttr $styleAttr $disabledAttr>";
    if ($leftIcon) {
        $html .= "<span class=\"ds-btn-icon\">$leftIcon</span>";
    }
    $html .= "<span>$children</span>";
    $html .= "</button>";

    return $html;
}