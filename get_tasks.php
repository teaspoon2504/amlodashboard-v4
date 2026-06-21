<?php
require_once 'includes/functions.php';
$tasks = db_fetch_all("SELECT * FROM task_templates");
echo json_encode($tasks, JSON_PRETTY_PRINT);
