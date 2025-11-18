<?php
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika sudah login
if (isAdminLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_nama'] = $admin['nama_lengkap'];
            $_SESSION['admin_role'] = $admin['role'];

            // Update last login
            $stmt = $db->prepare("UPDATE admin SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$admin['id']]);

            logActivity('admin', $admin['id'], 'Login Admin', 'Admin berhasil login');

            redirect('index.php');
        } else {
            $error = 'Username atau password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Sistem Voting RT</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>&#128274; Admin Panel</h1>
                <p>Sistem Voting RT</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control"
                           placeholder="Masukkan username" required
                           value="<?= isset($_POST['username']) ? sanitize($_POST['username']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Masukkan password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div class="login-footer">
                <a href="<?= APP_URL ?>">&larr; Kembali ke halaman utama</a>
            </div>
        </div>
    </div>
</body>
</html>
