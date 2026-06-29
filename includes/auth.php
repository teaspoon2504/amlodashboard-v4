<?php
/**
 * AMLO Dashboard - Authentication Helper
 */

require_once __DIR__ . '/functions.php';

/**
 * Login user
 */
function login_user($username, $password) {
    $user = db_fetch_one(
        "SELECT u.*, kw.kode, kw.nama as kanwil_nama
         FROM users u
         JOIN kantor_wilayah kw ON u.kanwil_id = kw.id
         WHERE u.username = ? AND u.aktif = 1",
        [$username]
    );

    if (!$user) {
        return ['success' => false, 'message' => 'Username tidak ditemukan'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Password salah'];
    }

    // Regenerate session ID
    session_regenerate_id(true);

    // Set session
    start_secure_session();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['kanwil_id'] = $user['kanwil_id'];
    $_SESSION['kanwil_nama'] = $user['kanwil_nama'];
    $_SESSION['login_time'] = time();

    // Update last login
    db_exec(
        "UPDATE users SET last_login = NOW() WHERE id = ?",
        [$user['id']]
    );

    // Log activity
    log_activity('login', "Login sebagai {$user['role']}");

    return ['success' => true, 'user' => $user];
}

/**
 * Logout user
 */
function logout_user() {
    if (is_logged_in()) {
        log_activity('logout', 'User logout');
    }

    start_secure_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Check if AJAX request
 */
function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * API auth check - return JSON error if not logged in
 */
function require_api_auth() {
    start_secure_session();

    if (!is_logged_in()) {
        if (is_ajax()) {
            json_response(false, 'Unauthorized', ['redirect' => 'login.php']);
        }
        header('Location: ../pages/login.php');
        exit;
    }
}

/**
 * Check kanwil access (for data isolation)
 */
function can_access_kanwil($kanwil_id) {
    $user = amlo_get_current_user();
    if (!$user) return false;

    // HO can access all
    if ($user['role'] === 'ho') return true;

    // Lead can access own kanwil
    if ($user['role'] === 'lead') {
        return $user['kanwil_id'] === $kanwil_id;
    }

    // Officer can only access own kanwil
    return $user['kanwil_id'] === $kanwil_id;
}

/**
 * Get sidebar navigation based on role
 */
function get_nav_items($role) {
    $base = [
        ['id' => 'dashboard', 'icon' => '🏠', 'label' => 'Beranda']
    ];

    $role_navs = [
        'officer' => [
            ['id' => 'tasks', 'icon' => '✅', 'label' => 'To-Do Harian'],
            ['id' => 'laporan', 'icon' => '📋', 'label' => 'Tracking Laporan'],
            ['id' => 'jobdesc', 'icon' => '📌', 'label' => 'Job Description']
        ],
        'lead' => [
            ['id' => 'tasks', 'icon' => '✅', 'label' => 'To-Do Harian'],
            ['id' => 'laporan', 'icon' => '📋', 'label' => 'Tracking Laporan'],
            ['id' => 'approvals', 'icon' => '✓', 'label' => 'Approvals'],
            ['id' => 'officers', 'icon' => '👥', 'label' => 'Monitoring Officer'],
            ['id' => 'assignments', 'icon' => '📤', 'label' => 'Penugasan'],
            ['id' => 'manajemen_tugas', 'icon' => '🎯', 'label' => 'Manajemen Tugas'],
            ['id' => 'jobdesc', 'icon' => '📌', 'label' => 'Job Description']
        ],
        'ho' => [
            ['id' => 'wilayah', 'icon' => '🗺', 'label' => 'Monitoring RO'],
            ['id' => 'laporan', 'icon' => '📋', 'label' => 'Tracking Laporan'],
            ['id' => 'performa', 'icon' => '📈', 'label' => 'Performa Agregat'],
            ['id' => 'assignments', 'icon' => '📤', 'label' => 'Penugasan'],
            ['id' => 'manajemen_tugas', 'icon' => '🎯', 'label' => 'Manajemen Tugas'],
            ['id' => 'assessment', 'icon' => '📝', 'label' => 'Assessment & Feedback'],
            ['id' => 'jobdesc', 'icon' => '📌', 'label' => 'Job Description']
        ]
    ];

    return array_merge($base, $role_navs[$role] ?? []);
}

/**
 * Get page title based on section
 */
function get_page_title($section) {
    $titles = [
        'dashboard' => 'Beranda — Overview Harian',
        'tasks' => 'To-Do List Harian',
        'laporan' => 'Tracking Laporan & Progress',
        'performa' => 'Monitoring Performa',
        'officers' => 'Monitoring AMLO Officer',
        'assignments' => 'Penugasan Tugas',
        'wilayah' => 'Monitoring Seluruh Wilayah',
        'manajemen_tugas' => 'Manajemen Target Tugas',
        'assessment' => 'Assessment & Feedback Kanpus',
        'jobdesc' => 'Job Description AMLO'
    ];
    return $titles[$section] ?? $section;
}