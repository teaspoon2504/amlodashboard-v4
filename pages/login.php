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
    <style>
        /* Login-specific overrides on top of design system */
        body {
            background:
                radial-gradient(circle at 20% 20%, var(--primary-soft) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, var(--oculus-purple-bg) 0%, transparent 50%),
                var(--canvas);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Particles background */
        #particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .login-shell {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--s-xl);
            width: 100%;
        }

        /* Single-column form panel */
        .login-card {
            display: grid;
            grid-template-columns: 1fr;
            width: 440px;
            max-width: 95vw;
            background: var(--surface-soft);
            border: 1px solid var(--hairline);
            border-radius: var(--r-xxxl);
            overflow: hidden;
            box-shadow: rgba(0, 0, 0, 0.5) 0px 24px 64px;
            animation: slideUp 500ms ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Form panel ── */
        .login-form-wrap {
            padding: var(--s-xxl);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-form-title {
            font-size: 28px;
            font-weight: 500;
            color: var(--ink-deep);
            letter-spacing: -0.2px;
            line-height: 1.21;
        }
        .login-form-sub {
            font-size: 14px;
            color: var(--steel);
            margin-top: 6px;
            font-weight: 400;
            letter-spacing: -0.14px;
        }

        .form-divider {
            display: flex;
            align-items: center;
            gap: var(--s-md);
            color: var(--stone);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: var(--s-lg) 0;
        }
        .form-divider::before, .form-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--hairline);
        }

        .form-group { margin-bottom: var(--s-base); }
        .form-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--ink);
            margin-bottom: 6px;
            display: block;
        }
        .form-input {
            width: 100%;
            background: var(--surface-elevated);
            border: 1px solid var(--hairline);
            border-radius: var(--r-lg);
            padding: 12px var(--s-md);
            color: var(--ink-deep);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color var(--t-fast), box-shadow var(--t-fast);
            letter-spacing: -0.14px;
        }
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-ring);
        }
        .form-input::placeholder { color: var(--stone); }

        .login-alert {
            display: flex;
            align-items: center;
            gap: var(--s-sm);
            padding: var(--s-md) var(--s-base);
            border-radius: var(--r-lg);
            font-size: 13px;
            margin-bottom: var(--s-base);
            font-weight: 500;
            letter-spacing: -0.14px;
        }
        .login-alert-error { background: var(--critical-bg); color: var(--critical-strong); border: 1px solid var(--critical); }
        .login-alert-success { background: var(--success-bg); color: var(--success); border: 1px solid var(--success); }

        .login-submit {
            width: 100%;
            margin-top: var(--s-sm);
        }

        .login-footer {
            text-align: center;
            margin-top: var(--s-xl);
            font-size: 12px;
            color: var(--steel);
            letter-spacing: 0;
        }

    </style>
</head>
<body>
    <canvas id="particles"></canvas>

    <div class="login-shell">
        <div class="login-card">
            <div class="login-form-wrap">
                <h2 class="login-form-title">Masuk ke Akun Anda</h2>
                <p class="login-form-sub">Gunakan kredensial AMLO yang sudah terdaftar</p>

                <?php if ($error): ?>
                    <div class="login-alert login-alert-error" style="margin-top: var(--s-lg)">
                        <span>⚠️</span><span><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="login-alert login-alert-success" style="margin-top: var(--s-lg)">
                        <span>✅</span><span><?= e($success) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" style="margin-top: var(--s-lg)">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-input"
                               placeholder="contoh: a.nugroho" required autofocus
                               value="<?= e($_POST['username'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-input"
                               placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary login-submit">Masuk Dashboard →</button>
                </form>

                <div class="login-footer">
                    © 2026 AMLODashboard · AMLO Dashboard v2.0
                </div>
            </div>
        </div>
    </div>

    <script>
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
