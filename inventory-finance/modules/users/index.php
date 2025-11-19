<?php
$pageTitle = 'User Management';
require_once __DIR__ . '/../../templates/header.php';

requireRole(['admin', 'manager']);

// Handle delete
if (isset($_GET['delete']) && hasRole(['admin'])) {
    $id = (int)$_GET['delete'];
    if ($id != $_SESSION['user_id']) {
        delete('users', $id);
        logActivity('delete', 'users', $id, 'User deleted');
        setFlash('success', 'User berhasil dihapus');
    } else {
        setFlash('danger', 'Tidak dapat menghapus user sendiri');
    }
    header('Location: ' . BASE_URL . 'modules/users/');
    exit;
}

// Get users
$users = getAll('users', [], 'name ASC');
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>User Management</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Users' => '']) ?>
    </div>
    <?php if (hasRole(['admin'])): ?>
    <a href="<?= BASE_URL ?>modules/users/form.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Tambah User
    </a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= e($user['name']) ?></strong></td>
                        <td><?= e($user['username']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= getRoleLabel($user['role']) ?></td>
                        <td>
                            <?php if ($user['is_active']): ?>
                            <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td><?= formatDateTime($user['last_login']) ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>modules/users/form.php?id=<?= $user['id'] ?>"
                                   class="btn btn-sm btn-outline" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (hasRole(['admin']) && $user['id'] != $_SESSION['user_id']): ?>
                                <a href="?delete=<?= $user['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   data-confirm="Yakin ingin menghapus user ini?"
                                   title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
