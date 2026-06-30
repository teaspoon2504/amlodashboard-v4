<?php
/**
 * AMLO Dashboard - Login Page
 * Refactored with Meta design system tokens (DESIGN.md)
 */

require_once __DIR__ . '/../includes/auth.php';

// Already logged in?
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $result = login_user($username, $password);
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }

    // Show flash for error
    if ($error) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => $error];
    }
}

// Check flash message
$flash = get_flash();
if ($flash) {
    if ($flash['type'] === 'error') {
        $error = $flash['message'];
    } else {
        $success = $flash['message'];
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — AMLO Dashboard</title>
    <link href="../assets/css/fonts.css" rel="stylesheet">
    <link href="../assets/css/amlo-design-system.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web@2.1.1"></script>
    <style>
        body {
            background-color: var(--canvas-alt);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body class="login-body">
    <canvas id="particles"></canvas>

    <div class="login-shell">
        <div class="theme-toggle login-theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
            <i id="theme-icon" class="ph ph-moon font-18"></i>
        </div>
        <div class="login-card">
            <div class="login-form-wrap">
                <h2 class="login-form-title">Masuk ke Akun Anda</h2>
                <p class="login-form-sub">Gunakan kredensial yang sudah terdaftar</p>

                <?php if ($error): ?>
                    <div class="login-alert login-alert-error mt-lg">
                        <i class="ph ph-warning-circle font-18"></i><span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="login-alert login-alert-success mt-lg">
                        <i class="ph ph-check-circle font-18"></i><span><?= e($success) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="mt-lg">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                    <div class="input-group">
                        <label class="input-label">Username</label>
                        <input type="text" name="username" class="input-field"
                               placeholder="Masukan username" required autofocus
                               value="<?= e($_POST['username'] ?? '') ?>">
                    </div>

                    <div class="input-group">
                        <label class="input-label">Password</label>
                        <input type="password" name="password" class="input-field"
                               placeholder="••••••••" required>
                    </div>

                    <?= render_ds_button([
                        'type' => 'submit',
                        'variant' => 'filled',
                        'size' => 'large',
                        'children' => 'Masuk Dashboard →',
                        'class' => 'w-full mt-base'
                    ]) ?>
                </form>

                <div class="login-footer">
                    © 2026 AMLODashboard · AMLO Dashboard v2.0
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

        // Subtle particle animation
        (function() {
            const canvas = document.getElementById('particles');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');

            function resize() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            resize();
            window.addEventListener('resize', resize);

            const particles = Array.from({ length: 40 }, () => ({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                vx: (Math.random() - 0.5) * 0.3,
                vy: (Math.random() - 0.5) * 0.3,
                r: Math.random() * 1.5 + 0.5,
                a: Math.random() * 0.4 + 0.05
            }));

            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                particles.forEach(p => {
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(200,168,75,${p.a})`;
                    ctx.fill();
                    p.x += p.vx;
                    p.y += p.vy;
                    if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
                    if (p.y < 0 || p.y > canvas.height) p.vy *= -1;
                });
                requestAnimationFrame(animate);
            }
            animate();
        })();
    </script>
</body>
</html>
