<?php
$pageTitle = 'Daftar - Sistem Voting RT';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nik = sanitize($_POST['nik'] ?? '');
    $nama = sanitize($_POST['nama_lengkap'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');
    $nokk = sanitize($_POST['no_kk'] ?? '');
    $nohp = sanitize($_POST['no_hp'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    // Validasi
    if (empty($nik) || empty($nama) || empty($alamat) || empty($nokk) || empty($password)) {
        $error = 'Semua field wajib harus diisi';
    } elseif (!isValidNIK($nik)) {
        $error = 'Format NIK tidak valid (harus 16 digit angka)';
    } elseif (!isValidNoKK($nokk)) {
        $error = 'Format No KK tidak valid (harus 16 digit angka)';
    } elseif (!isStrongPassword($password)) {
        $error = 'Password minimal 6 karakter';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok';
    } else {
        $db = getDB();

        // Cek NIK sudah terdaftar
        $stmt = $db->prepare("SELECT id FROM users WHERE nik = ?");
        $stmt->execute([$nik]);
        if ($stmt->fetch()) {
            $error = 'NIK sudah terdaftar dalam sistem';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user baru
            $stmt = $db->prepare("
                INSERT INTO users (nik, nama_lengkap, alamat, no_kk, no_hp, email, password)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([$nik, $nama, $alamat, $nokk, $nohp, $email, $hashedPassword])) {
                $userId = $db->lastInsertId();
                logActivity('user', $userId, 'Registrasi', 'User baru mendaftar');
                redirect('login.php', 'Pendaftaran berhasil! Silakan tunggu verifikasi dari panitia, lalu login.', 'success');
            } else {
                $error = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
            }
        }
    }
}

require_once 'includes/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="auth-card auth-card-wide">
            <h2>Daftar sebagai Pemilih</h2>
            <p>Isi data diri Anda untuk mendaftar sebagai pemilih</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nik">NIK <span class="required">*</span></label>
                        <input type="text" id="nik" name="nik" class="form-control"
                               placeholder="16 digit NIK" maxlength="16" pattern="[0-9]{16}" required
                               value="<?= isset($_POST['nik']) ? sanitize($_POST['nik']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="no_kk">No. Kartu Keluarga <span class="required">*</span></label>
                        <input type="text" id="no_kk" name="no_kk" class="form-control"
                               placeholder="16 digit No. KK" maxlength="16" pattern="[0-9]{16}" required
                               value="<?= isset($_POST['no_kk']) ? sanitize($_POST['no_kk']) : '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control"
                           placeholder="Sesuai KTP" required
                           value="<?= isset($_POST['nama_lengkap']) ? sanitize($_POST['nama_lengkap']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="alamat">Alamat Lengkap <span class="required">*</span></label>
                    <textarea id="alamat" name="alamat" class="form-control" rows="3"
                              placeholder="Alamat sesuai KTP" required><?= isset($_POST['alamat']) ? sanitize($_POST['alamat']) : '' ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="no_hp">No. HP</label>
                        <input type="tel" id="no_hp" name="no_hp" class="form-control"
                               placeholder="08xxxxxxxxxx"
                               value="<?= isset($_POST['no_hp']) ? sanitize($_POST['no_hp']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="email@contoh.com"
                               value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Minimal 6 karakter" required>
                    </div>
                    <div class="form-group">
                        <label for="konfirmasi_password">Konfirmasi Password <span class="required">*</span></label>
                        <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-control"
                               placeholder="Ulangi password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="agree" required>
                        Saya menyatakan data yang diisi adalah benar dan dapat dipertanggungjawabkan
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Daftar</button>
            </form>

            <div class="auth-links">
                <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
