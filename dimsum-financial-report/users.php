<?php
/**
 * User Management - Laporan Keuangan Dimsum
 */
require_once 'config.php';

// Only admin can access this page
requireRole('admin');

$db = Database::getConnection();
$currentUser = getCurrentUser();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $name = sanitize($_POST['name']);
    $role = $_POST['role'];
    $password = $_POST['password'] ?? '';

    if (isset($_POST['id']) && $_POST['id'] > 0) {
        // Update user
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET username = ?, name = ?, role = ?, password = ? WHERE id = ?");
            $stmt->execute([$username, $name, $role, $hashedPassword, $_POST['id']]);
        } else {
            $stmt = $db->prepare("UPDATE users SET username = ?, name = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $name, $role, $_POST['id']]);
        }
        $_SESSION['message'] = ['type' => 'success', 'text' => 'User berhasil diperbarui!'];
    } else {
        // Create new user
        if (empty($password)) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Password wajib diisi untuk user baru!'];
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $name, $role]);
            $_SESSION['message'] = ['type' => 'success', 'text' => 'User berhasil ditambahkan!'];
        }
    }

    header('Location: users.php');
    exit;
}

// Get all users
$users = $db->query("SELECT * FROM users ORDER BY role, name")->fetchAll();

// Get user for editing
$user = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>Kelola User - <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-cup-hot-fill"></i> <?= APP_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <?php if (canAdd()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php?action=add"><i class="bi bi-plus-circle"></i> Transaksi</a>
                    </li>
                    <?php endif; ?>
                    <?php if (canManageBranches()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="branches.php"><i class="bi bi-shop"></i> Cabang</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php"><i class="bi bi-bar-chart-line"></i> Laporan</a>
                    </li>
                    <?php if (canManageUsers()): ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php"><i class="bi bi-people"></i> Users</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm" id="themeToggle">
                        <i class="bi bi-moon-fill"></i>
                    </button>

                    <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <button class="btn btn-link text-decoration-none p-0" data-bs-toggle="dropdown">
                            <div class="user-menu">
                                <div class="user-avatar"><?= getUserInitial($currentUser) ?></div>
                                <div class="user-info">
                                    <div class="name"><?= htmlspecialchars($currentUser['name']) ?></div>
                                    <div class="role"><?= getRoleDisplayName($currentUser['role']) ?></div>
                                </div>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text">
                                <strong><?= htmlspecialchars($currentUser['name']) ?></strong><br>
                                <small class="text-muted"><?= getRoleDisplayName($currentUser['role']) ?></small>
                            </span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show">
            <?= $_SESSION['message']['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); endif; ?>

        <div class="row g-4">
            <!-- Form -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-<?= $user ? 'pencil' : 'plus-lg' ?>"></i> <?= $user ? 'Edit' : 'Tambah' ?> User</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?php if ($user): ?>
                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required
                                       value="<?= $user ? htmlspecialchars($user['username']) : '' ?>"
                                       placeholder="username">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?= $user ? htmlspecialchars($user['name']) : '' ?>"
                                       placeholder="Nama Lengkap">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="viewer" <?= ($user && $user['role'] == 'viewer') ? 'selected' : '' ?>>Viewer (View Only)</option>
                                    <option value="editor" <?= ($user && $user['role'] == 'editor') ? 'selected' : '' ?>>Editor (Add Transaction)</option>
                                    <option value="admin" <?= ($user && $user['role'] == 'admin') ? 'selected' : '' ?>>Admin (Full Access)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password <?= $user ? '' : '<span class="text-danger">*</span>' ?></label>
                                <input type="password" name="password" class="form-control"
                                       <?= $user ? '' : 'required' ?>
                                       placeholder="<?= $user ? 'Kosongkan jika tidak ingin mengubah' : 'Password' ?>">
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Simpan
                                </button>
                                <?php if ($user): ?>
                                <a href="users.php" class="btn btn-secondary">
                                    <i class="bi bi-x-lg"></i> Batal
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-list-ul"></i> Daftar User</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Nama</th>
                                        <th>Role</th>
                                        <th>Login Terakhir</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                        <td><?= htmlspecialchars($u['name']) ?></td>
                                        <td><span class="badge <?= getRoleBadgeClass($u['role']) ?>"><?= getRoleDisplayName($u['role']) ?></span></td>
                                        <td><?= $u['last_login'] ? formatDate($u['last_login']) : '-' ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($u['id'] != $currentUser['id']): ?>
                                                <button class="btn btn-outline-danger" onclick="deleteUser(<?= $u['id'] ?>)" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'light' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
        }

        // Delete user
        function deleteUser(id) {
            Swal.fire({
                title: 'Hapus User?',
                text: 'User yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api/users.php', {
                        method: 'DELETE',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id: id})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Terhapus!', 'User berhasil dihapus.', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
