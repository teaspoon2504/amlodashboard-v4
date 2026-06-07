<?php
/**
 * AMLO Dashboard - Logout Handler
 */

require_once __DIR__ . '/includes/auth.php';

logout_user();

// Redirect to login
header('Location: pages/login.php?logout=1');
exit;