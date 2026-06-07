<?php
/**
 * Application Initialization
 * Handles environment-specific settings (like error reporting)
 */

$env = [];
$env_file = __DIR__ . '/../env.php';
if (file_exists($env_file)) {
    $env = require $env_file;
}

$app_env = $env['APP_ENV'] ?? 'development';

if ($app_env === 'production') {
    // Hide all errors in production to prevent information leakage
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    // Show errors in development
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
