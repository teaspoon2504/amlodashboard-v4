<?php
/**
 * Master Layout Header
 * Extracted from dashboard.php, tasks.php, and approvals.php
 * Handles document structure, CSS linking, sidebar, and topbar
 */
if (!isset($page_title)) {
    $page_title = 'AMLO Dashboard';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- Fonts -->
    <link href="../assets/css/fonts.css" rel="stylesheet">
    
    <!-- Design System (Global Styles) -->
    <link href="../assets/css/amlo-design-system.css?v=1780939681" rel="stylesheet">
    
    <!-- Page Specific CSS -->
    <?php if (isset($page_css)): ?>
    <link href="<?= htmlspecialchars($page_css, ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
    <?php endif; ?>

    <!-- Global Variables -->
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
</head>
<body class="page-<?= basename($_SERVER['SCRIPT_NAME'], '.php') ?>">
<div id="app">
    <!-- Sidebar Component -->
    <?php include __DIR__ . '/../pages/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-area">
        
        <!-- Topbar Component -->
        <?php include __DIR__ . '/../pages/topbar.php'; ?>
