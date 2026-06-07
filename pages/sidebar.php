<?php
/**
 * Shared Sidebar for all pages
 */

$nav_items = get_nav_items($user['role']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">AMLO Dashboard<span><?= e($user['kanwil_nama']) ?></span></div>
        <div class="user-chip">
            <div class="user-avatar"><?= strtoupper(substr($user['nama'], 0, 2)) ?></div>
            <div class="user-chip-info">
                <div class="user-chip-name"><?= e($user['nama']) ?></div>
                <div class="user-chip-role"><?= e(get_role_label($user['role'])) ?></div>
            </div>
        </div>
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

    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-btn">⬅ Keluar</a>
    </div>
</div>