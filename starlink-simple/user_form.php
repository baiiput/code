<?php
require_once 'config.php';
requireRole(['super_admin']);

$id = $_GET['id'] ?? null;
$user = null;
$errors = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        redirect('users.php');
    }
    $pageTitle = 'Edit User';
} else {
    $pageTitle = 'Tambah User';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirmation'] ?? '';
    $role = $_POST['role'] ?? 'viewer';

    // Validation
    if (empty($name)) $errors[] = 'Nama harus diisi.';
    if (empty($email)) $errors[] = 'Email harus diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';

    // Check email unique
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id ?? 0]);
    if ($stmt->fetch()) {
        $errors[] = 'Email sudah digunakan.';
    }

    if (!$id && empty($password)) {
        $errors[] = 'Password harus diisi.';
    }

    if ($password && $password !== $passwordConfirm) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    if ($password && strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }

    if (empty($errors)) {
        if ($id) {
            // Update
            if ($password) {
                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=?, role=? WHERE id=?");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
                $stmt->execute([$name, $email, $role, $id]);
            }
            setFlash('success', 'Data user berhasil diperbarui.');
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            setFlash('success', 'User berhasil ditambahkan.');
        }
        redirect('users.php');
    }

    $user = ['name' => $name, 'email' => $email, 'role' => $role];
}

require_once 'includes/header.php';
?>

<div class="max-w-xl">
    <?php if ($errors): ?>
    <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/50 p-4 text-sm text-red-800 dark:text-red-200">
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nama *</label>
                    <input type="text" name="name" value="<?= e($user['name'] ?? '') ?>" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Email *</label>
                    <input type="email" name="email" value="<?= e($user['email'] ?? '') ?>" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Password <?= $id ? '' : '*' ?></label>
                    <input type="password" name="password" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <?php if ($id): ?>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika tidak ingin mengubah password</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Role *</label>
                    <select name="role" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="viewer" <?= ($user['role'] ?? '') == 'viewer' ? 'selected' : '' ?>>Viewer (hanya lihat)</option>
                        <option value="admin" <?= ($user['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin (kelola pelanggan)</option>
                        <option value="super_admin" <?= ($user['role'] ?? '') == 'super_admin' ? 'selected' : '' ?>>Super Admin (full access)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="users.php" class="text-sm font-semibold text-gray-700 dark:text-gray-200">Batal</a>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                <?= $id ? 'Update' : 'Simpan' ?> User
            </button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
