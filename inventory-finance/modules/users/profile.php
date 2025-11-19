<?php
$pageTitle = 'Profil Saya';
require_once __DIR__ . '/../../templates/header.php';

$user = getCurrentUser();

// Handle update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    verifyCsrf();

    $data = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
    ];

    update('users', $data, $user['id']);
    $_SESSION['name'] = $data['name'];
    setFlash('success', 'Profil berhasil diupdate');
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Handle change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    verifyCsrf();

    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        setFlash('danger', 'Password baru tidak cocok');
    } elseif (strlen($newPassword) < 6) {
        setFlash('danger', 'Password minimal 6 karakter');
    } elseif (changePassword($user['id'], $oldPassword, $newPassword)) {
        setFlash('success', 'Password berhasil diubah');
    } else {
        setFlash('danger', 'Password lama salah');
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Refresh user data
$user = getCurrentUser();
?>

<div class="page-header">
    <h1>Profil Saya</h1>
    <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Profil' => '']) ?>
</div>

<div class="row">
    <div class="col-12 col-6">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-user me-2"></i>Informasi Profil</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="update_profile" value="1">

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($user['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= e($user['email']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= e($user['phone']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?= getRoleLabel($user['role']) ?>" disabled>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Profil
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-6">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-key me-2"></i>Ubah Password</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="change_password" value="1">

                    <div class="form-group">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-2"></i>Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
