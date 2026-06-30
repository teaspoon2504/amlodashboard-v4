<?php
/**
 * Shared Topbar Component
 * 
 * Variables to pass:
 * - $topbar_title: string (required)
 * - $topbar_subtitle: string (optional)
 * - $topbar_date: string (optional)
 * - $topbar_notif_action: string (optional JS for onClick)
 * - $alerts: array (optional, for dashboard notification dot)
 */
?>
<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-title"><?= htmlspecialchars($topbar_title ?? 'AMLO Dashboard', ENT_QUOTES, 'UTF-8') ?></div>
        <?php if (!empty($topbar_subtitle)): ?>
            <div class="topbar-subtitle"><?= htmlspecialchars($topbar_subtitle, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if (!empty($topbar_date)): ?>
            <div class="topbar-date"><?= htmlspecialchars($topbar_date, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
    <div class="topbar-right">
        <div class="topbar-notif theme-toggle cursor-pointer font-16" onclick="toggleTheme()" title="Toggle Theme">
            <i id="theme-icon" class="ph ph-moon font-18"></i>
        </div>
        <?php if (!empty($topbar_notif_action)): ?>
            <div class="topbar-notif cursor-pointer" onclick="<?= htmlspecialchars($topbar_notif_action, ENT_QUOTES, 'UTF-8') ?>" title="Lihat Notifikasi">
                <i class="ph ph-bell font-18"></i><?php if (!empty($alerts)): ?><div class="notif-dot"></div><?php endif; ?>
            </div>
        <?php else: ?>
            <div class="topbar-notif"><i class="ph ph-bell font-18"></i></div>
        <?php endif; ?>
        
        <div class="topbar-profile" tabindex="0">
            <div class="user-avatar-sm avatar-<?= htmlspecialchars($user['role'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= strtoupper(substr($user['nama'] ?? 'U', 0, 2)) ?></div>
            <div class="profile-dropdown">
                <div class="dropdown-info">
                    <div class="dropdown-name"><?= htmlspecialchars($user['nama'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="dropdown-role"><?= htmlspecialchars(get_role_label($user['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <a href="../logout.php" class="dropdown-item text-critical">
                    <i class="ph ph-sign-out font-16"></i> Keluar
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function updateThemeIcon(theme) {
    const icon = document.getElementById('theme-icon');
    if(icon) {
        icon.className = theme === 'light' ? 'ph ph-sun font-18' : 'ph ph-moon font-18';
    }
}

function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme') || 'dark';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
}

document.addEventListener('DOMContentLoaded', () => {
    updateThemeIcon(document.documentElement.getAttribute('data-theme') || 'dark');
});
</script>
