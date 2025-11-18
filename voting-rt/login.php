<?php
$pageTitle = 'Login - Sistem Voting RT';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nik = sanitize($_POST['nik'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nik) || empty($password)) {
        $error = 'NIK dan password harus diisi';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE nik = ?");
        $stmt->execute([$nik]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status_verifikasi'] == 'pending') {
                $error = 'Akun Anda belum diverifikasi. Silakan tunggu verifikasi dari panitia.';
            } elseif ($user['status_verifikasi'] == 'rejected') {
                $error = 'Akun Anda ditolak. Silakan hubungi panitia untuk informasi lebih lanjut.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nik'] = $user['nik'];
                $_SESSION['user_nama'] = $user['nama_lengkap'];

                logActivity('user', $user['id'], 'Login', 'User berhasil login');

                redirect('index.php', 'Selamat datang, ' . $user['nama_lengkap'] . '!', 'success');
            }
        } else {
            $error = 'NIK atau password salah';
        }
    }
}

require_once 'includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-card">
            <h2>Login Pemilih</h2>
            <p>Masukkan NIK dan password untuk login</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nik">NIK (Nomor Induk Kependudukan)</label>
                    <input type="text" id="nik" name="nik" class="form-control"
                           placeholder="Masukkan 16 digit NIK" maxlength="16"
                           pattern="[0-9]{16}" required
                           value="<?= isset($_POST['nik']) ? sanitize($_POST['nik']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Masukkan password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div class="auth-links">
                <p>Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
