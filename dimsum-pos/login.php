<?php
require_once 'config/config.php';

// Redirect if already logged in
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $auth = new Auth();
    if ($auth->login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}

// Get theme preference
$theme = $_COOKIE['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
    <div class="container">
        <div class="row min-vh-100 align-items-center justify-content-center">
            <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="logo-placeholder mb-3">
                                <i class="bi bi-shop fs-1 text-primary"></i>
                            </div>
                            <h4 class="fw-bold"><?= APP_NAME ?></h4>
                            <p class="text-muted small">Silakan masuk untuk melanjutkan</p>
                        </div>

                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username"
                                           placeholder="Masukkan username" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password"
                                           placeholder="Masukkan password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                            </button>
                        </form>

                        <div class="text-center">
                            <button class="btn btn-sm btn-outline-secondary" id="themeToggle">
                                <i class="bi bi-moon-stars me-1"></i>
                                <span>Mode Gelap</span>
                            </button>
                        </div>
                    </div>
                </div>
                <p class="text-center text-muted mt-3 small">
                    &copy; <?= date('Y') ?> <?= APP_NAME ?>. v<?= APP_VERSION ?>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });

        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;

        function updateThemeButton() {
            const isDark = html.getAttribute('data-bs-theme') === 'dark';
            themeToggle.innerHTML = isDark
                ? '<i class="bi bi-sun me-1"></i><span>Mode Terang</span>'
                : '<i class="bi bi-moon-stars me-1"></i><span>Mode Gelap</span>';
        }

        themeToggle.addEventListener('click', function() {
            const isDark = html.getAttribute('data-bs-theme') === 'dark';
            const newTheme = isDark ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', newTheme);
            document.cookie = `theme=${newTheme};path=/;max-age=31536000`;
            updateThemeButton();
        });

        updateThemeButton();
    </script>
</body>
</html>
