<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'officer';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Mock user since we bypass amlo_require_login
$user = db_fetch_one("SELECT * FROM users WHERE id = ?", [1]);
if(!$user) {
    // create a mock one
    $user = ['id' => 1, 'role' => 'officer', 'nama_lengkap' => 'Test', 'username' => 'test'];
}

ob_start();
include __DIR__ . '/tasks.php';
$html = ob_get_clean();

file_put_contents('/tmp/tasks_output.html', $html);
echo "Output saved to /tmp/tasks_output.html\n";
