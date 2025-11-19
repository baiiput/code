<?php
require_once '../config/config.php';
Auth::requireLogin();

$pageTitle = 'Profil Saya';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $db->update('users', [
            'nama_lengkap' => $_POST['nama_lengkap']
        ], 'id = ?', [Auth::user('id')]);

        // Update session
        $_SESSION['nama_lengkap'] = $_POST['nama_lengkap'];

        Helper::setFlash('success', 'Profil berhasil diperbarui!');
    } elseif ($action === 'change_password') {
        $user = $db->fetch("SELECT password FROM users WHERE id = ?", [Auth::user('id')]);

        if (!password_verify($_POST['current_password'], $user['password'])) {
            Helper::setFlash('danger', 'Password lama salah!');
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            Helper::setFlash('danger', 'Konfirmasi password tidak cocok!');
        } elseif (strlen($_POST['new_password']) < 6) {
            Helper::setFlash('danger', 'Password minimal 6 karakter!');
        } else {
            $db->update('users', [
                'password' => password_hash($_POST['new_password'], PASSWORD_DEFAULT)
            ], 'id = ?', [Auth::user('id')]);

            Helper::setFlash('success', 'Password berhasil diubah!');
        }
    }

    header('Location: profil.php');
    exit;
}

// Get user data
$user = $db->fetch("
    SELECT u.*, c.nama as cabang_nama
    FROM users u
    LEFT JOIN cabang c ON u.cabang_id = c.id
    WHERE u.id = ?
", [Auth::user('id')]);

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Profile Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Informasi Profil</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Username</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control-plaintext" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Nama Lengkap</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Role</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control-plaintext" value="<?= Helper::roleLabel($user['role']) ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Cabang</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control-plaintext" value="<?= $user['cabang_nama'] ?? 'Semua Cabang' ?>" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Login Terakhir</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control-plaintext" value="<?= $user['last_login'] ? Helper::tanggal($user['last_login'], true) : '-' ?>" readonly>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Ubah Password</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Password Lama</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Password Baru</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" name="new_password" required minlength="6">
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Konfirmasi</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key me-1"></i> Ubah Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
