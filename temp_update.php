<?php
require_once __DIR__ . '/includes/functions.php';

try {
    db_exec("UPDATE assignments SET task_name = 'Adhoc RFI Remittance' WHERE task_name = 'RFI Remittance';");
    db_exec("UPDATE assignments SET task_name = 'Adhoc Pendampingan AML' WHERE task_name = 'Pendampingan AML';");
    echo "Updated assignments table.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
