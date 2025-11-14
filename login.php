<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><?php echo APP_NAME; ?></h1>
                <p>Sistem Manajemen Keuangan Bisnis Teknologi</p>
            </div>

            <form id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="remember">
                        <span>Ingat Saya</span>
                    </label>
                </div>

                <div id="errorMessage" class="error-message" style="display: none;"></div>

                <button type="submit" class="btn btn-primary btn-block">
                    <span class="btn-text">Login</span>
                    <span class="btn-loading" style="display: none;">
                        <span class="spinner"></span> Loading...
                    </span>
                </button>
            </form>

            <div class="login-footer">
                <p>Default Login: <strong>admin</strong> / <strong>admin123</strong></p>
            </div>
        </div>
    </div>

    <script src="assets/js/login.js"></script>
</body>
</html>
