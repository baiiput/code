<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/helpers.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/dashboard/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } elseif (login($username, $password)) {
        header('Location: ' . BASE_URL . 'modules/dashboard/');
        exit;
    } else {
        $error = 'Username atau password salah';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        .login-card {
            background: var(--bg-card);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px var(--shadow);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header .logo {
            width: 70px;
            height: 70px;
            background: var(--primary);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #fff;
            font-size: 2rem;
        }

        .login-header h1 {
            font-size: 1.5rem;
            margin: 0 0 5px;
        }

        .login-header p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 0.9rem;
        }

        .login-form .form-group {
            margin-bottom: 20px;
        }

        .login-form .form-control {
            padding: 12px 15px;
        }

        .login-form .btn {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
        }

        .theme-toggle-login {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <button class="theme-toggle-login" id="themeToggle" title="Toggle Dark/Light Mode">
        <i class="fas fa-moon"></i>
    </button>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-network-wired"></i>
                </div>
                <h1><?= APP_NAME ?></h1>
                <p>Inventory & Finance System</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username"
                           value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>

            <div class="mt-4 text-center">
                <small class="text-muted">
                    Default: admin / admin123
                </small>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
