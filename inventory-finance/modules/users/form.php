<?php
$pageTitle = 'Form User';
require_once __DIR__ . '/../../templates/header.php';

requireRole(['admin', 'manager']);

$id = $_GET['id'] ?? null;
$user = $id ? getById('users', $id) : null;
$isEdit = $user !== null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $data = [
        'name' => trim($_POST['name']),
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'role' => $_POST['role'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    // Password handling
    if (!$isEdit || !empty($_POST['password'])) {
        if (empty($_POST['password'])) {
            setFlash('danger', 'Password harus diisi');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    // Check username unique
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$data['username'], $id ?? 0]);
    if ($stmt->fetch()) {
        setFlash('danger', 'Username sudah digunakan');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if ($isEdit) {
        update('users', $data, $id);
        logActivity('update', 'users', $id, 'User updated: ' . $data['name']);
        setFlash('success', 'User berhasil diupdate');
    } else {
        $newId = insert('users', $data);
        logActivity('create', 'users', $newId, 'User created: ' . $data['name']);
        setFlash('success', 'User berhasil ditambahkan');
    }

    header('Location: ' . BASE_URL . 'modules/users/');
    exit;
}
?>

<div class="page-header">
    <h1><?= $isEdit ? 'Edit User' : 'Tambah User' ?></h1>
    <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Users' => BASE_URL . 'modules/users/', ($isEdit ? 'Edit' : 'Tambah') => '']) ?>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body">
        <form method="POST" data-validate>
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= e($user['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Username <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control"
                       value="<?= e($user['username'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       value="<?= e($user['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control"
                       value="<?= e($user['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Password <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
                <input type="password" name="password" class="form-control"
                       <?= $isEdit ? '' : 'required' ?>>
                <?php if ($isEdit): ?>
                <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Role <span class="text-danger">*</span></label>
                <select name="role" class="form-control form-select" required>
                    <option value="admin" <?= ($user['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Administrator</option>
                    <option value="manager" <?= ($user['role'] ?? '') == 'manager' ? 'selected' : '' ?>>Manager</option>
                    <option value="kasir" <?= ($user['role'] ?? 'kasir') == 'kasir' ? 'selected' : '' ?>>Kasir</option>
                    <option value="gudang" <?= ($user['role'] ?? '') == 'gudang' ? 'selected' : '' ?>>Gudang</option>
                </select>
            </div>

            <div class="form-group">
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="is_active" value="1"
                           <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <span>Aktif</span>
                </label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Simpan
                </button>
                <a href="<?= BASE_URL ?>modules/users/" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
