<?php
require_once __DIR__ . '/includes/functions.php';

$sql = file_get_contents(__DIR__ . '/create_task_targets.sql');

try {
    // Assuming db_exec exists or we can get the PDO instance. Let's check config/database.php.
    // If db_exec doesn't work for schema creation, we might need direct PDO access.
    global $pdo;
    if (isset($pdo)) {
        $pdo->exec($sql);
        echo "Success\n";
    } else {
        echo "PDO not found globally. Trying db_exec...\n";
        db_exec($sql);
        echo "Success (db_exec)\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
