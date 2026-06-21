<?php
/**
 * Shared Sidebar for all pages
 */

$nav_items = get_nav_items($user['role']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">AMLO Dashboard<span><?= $user['role'] === 'ho' ? 'Kantor Pusat' : e($user['kanwil_nama'] ?? 'Unknown Regional Office') ?></span></div>
    </div>

    <nav class="sidebar-nav" id="sidebar-nav">
        <div class="nav-section-label">Menu Utama</div>
        <?php foreach ($nav_items as $item): ?>
            <?php $is_active = basename($_SERVER['SCRIPT_NAME']) === $item['id'] . '.php'; ?>
            <a href="<?= $item['id'] ?>.php" class="nav-item <?= $is_active ? 'active' : '' ?>" id="nav-<?= $item['id'] ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>