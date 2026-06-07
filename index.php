<?php
/**
 * AMLO Dashboard - Entry Point
 * Redirects to login or dashboard based on auth state
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: pages/dashboard.php');
} else {
    header('Location: pages/login.php');
}
exit;