<?php
/**
 * AMLO Dashboard - Database Configuration
 * Single database, multi-kanwil architecture
 */

// Require initialization (error handling, etc.)
require_once __DIR__ . '/init.php';

// Load environment variables if env.php exists
$env = [];
$env_file = __DIR__ . '/../env.php';
if (file_exists($env_file)) {
    $env = require $env_file;
}

// Fallbacks if not set in env.php
define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('DB_NAME', $env['DB_NAME'] ?? 'amlo_dashboard');
define('DB_PORT', $env['DB_PORT'] ?? '3306');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . $conn->connect_error
    ]));
}

// Set charset
$conn->set_charset('utf8mb4');

/**
 * Run prepared statement query
 */
function db_query($sql, $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'error' => $conn->error];
    }

    if (!empty($params)) {
        $types = '';
        $values = [];
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $param;
        }
        $stmt->bind_param($types, ...$values);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    return ['success' => true, 'result' => $result];
}

/**
 * Get single row
 */
function db_fetch_one($sql, $params = []) {
    $result = db_query($sql, $params);
    if (!$result['success']) return null;
    return $result['result']->fetch_assoc();
}

/**
 * Get all rows
 */
function db_fetch_all($sql, $params = []) {
    $result = db_query($sql, $params);
    if (!$result['success']) return [];
    $rows = [];
    while ($row = $result['result']->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Insert and return last insert ID
 */
function db_insert($sql, $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'error' => $conn->error];
    }

    if (!empty($params)) {
        $types = '';
        $values = [];
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $param;
        }
        $stmt->bind_param($types, ...$values);
    }

    $stmt->execute();
    $insert_id = $stmt->insert_id;
    $stmt->close();

    return ['success' => true, 'insert_id' => $insert_id];
}

/**
 * Update/Delete
 */
function db_exec($sql, $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'error' => $conn->error];
    }

    if (!empty($params)) {
        $types = '';
        $values = [];
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $param;
        }
        $stmt->bind_param($types, ...$values);
    }

    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    return ['success' => true, 'affected' => $affected];
}