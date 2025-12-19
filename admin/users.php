<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireSuperAdmin(); // Only Super Admin can access

$pageTitle = 'Kelola User Admin';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? null;
        $username = sanitize($_POST['username']);
        $full_name = sanitize($_POST['full_name']);
        $user_level = intval($_POST['user_level']);
        $password = $_POST['password'] ?? '';

        // Validate user level (1-3 only for admin users)
        if ($user_level < 1 || $user_level > 3) {
            setFlashMessage('error', 'User level tidak valid');
            header('Location: /admin/users.php');
            exit;
        }

        if ($action === 'add') {
            // Create admin user
            if (empty($password)) {
                setFlashMessage('error', 'Password harus diisi');
                header('Location: /admin/users.php');
                exit;
            }

            // Check username exists
            $check = $conn->query("SELECT COUNT(*) as total FROM users WHERE username = '$username'");
            if ($check->fetch_assoc()['total'] > 0) {
                setFlashMessage('error', 'Username sudah digunakan');
                header('Location: /admin/users.php');
                exit;
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, user_level, full_name) VALUES (?, ?, 'admin', ?, ?)");
            $stmt->bind_param("ssis", $username, $password_hash, $user_level, $full_name);

            if ($stmt->execute()) {
                setFlashMessage('success', 'User admin berhasil ditambahkan');
            } else {
                setFlashMessage('error', 'Gagal menambahkan user');
            }
            $stmt->close();
        } else {
            // Edit admin user
            if (!empty($password)) {
                // Update with new password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, user_level = ?, full_name = ? WHERE id = ? AND role = 'admin'");
                $stmt->bind_param("ssisi", $username, $password_hash, $user_level, $full_name, $id);
            } else {
                // Update without password
                $stmt = $conn->prepare("UPDATE users SET username = ?, user_level = ?, full_name = ? WHERE id = ? AND role = 'admin'");
                $stmt->bind_param("sisi", $username, $user_level, $full_name, $id);
            }

            if ($stmt->execute()) {
                setFlashMessage('success', 'User admin berhasil diupdate');
            } else {
                setFlashMessage('error', 'Gagal mengupdate user');
            }
            $stmt->close();
        }

        header('Location: /admin/users.php');
        exit;
    } elseif ($action === 'delete') {
        $id = $_POST['id'];

        // Cannot delete own account
        if ($id == getCurrentUser()['id']) {
            setFlashMessage('error', 'Tidak dapat menghapus akun sendiri');
            header('Location: /admin/users.php');
            exit;
        }

        // Cannot delete if only one super admin
        $user = $conn->query("SELECT user_level FROM users WHERE id = $id")->fetch_assoc();
        if ($user['user_level'] == USER_LEVEL_SUPERADMIN) {
            $superAdminCount = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_level = " . USER_LEVEL_SUPERADMIN)->fetch_assoc()['total'];
            if ($superAdminCount <= 1) {
                setFlashMessage('error', 'Tidak dapat menghapus satu-satunya Super Admin');
                header('Location: /admin/users.php');
                exit;
            }
        }

        if ($conn->query("DELETE FROM users WHERE id = $id AND role = 'admin'")) {
            setFlashMessage('success', 'User admin berhasil dihapus');
        } else {
            setFlashMessage('error', 'Gagal menghapus user');
        }

        header('Location: /admin/users.php');
        exit;
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'];
        $is_active = $_POST['is_active'];
        $new_status = $is_active == 1 ? 0 : 1;

        // Cannot disable own account
        if ($id == getCurrentUser()['id']) {
            setFlashMessage('error', 'Tidak dapat menonaktifkan akun sendiri');
            header('Location: /admin/users.php');
            exit;
        }

        if ($conn->query("UPDATE users SET is_active = $new_status WHERE id = $id")) {
            setFlashMessage('success', 'Status user berhasil diubah');
        } else {
            setFlashMessage('error', 'Gagal mengubah status user');
        }

        header('Location: /admin/users.php' . (isset($_GET['role']) ? '?role=' . $_GET['role'] : ''));
        exit;
    } elseif ($action === 'reset_password') {
        $id = $_POST['id'];
        $new_password = $_POST['new_password'] ?? '12345';

        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        if ($conn->query("UPDATE users SET password = '$password_hash' WHERE id = $id AND role = 'customer'")) {
            setFlashMessage('success', "Password berhasil direset menjadi: $new_password");
        } else {
            setFlashMessage('error', 'Gagal mereset password');
        }

        header('Location: /admin/users.php?role=customer');
        exit;
    }
}

// Get role filter from query parameter
$role_filter = $_GET['role'] ?? 'admin';
$role_filter = in_array($role_filter, ['admin', 'customer']) ? $role_filter : 'admin';

// Get users based on role filter
if ($role_filter === 'customer') {
    $users = $conn->query("
        SELECT u.*, c.nama_lengkap as customer_name, c.telepon, c.email
        FROM users u
        LEFT JOIN customers c ON u.customer_id = c.id
        WHERE u.role = 'customer'
        ORDER BY u.created_at DESC
    ");
} else {
    $users = $conn->query("
        SELECT * FROM users
        WHERE role = 'admin'
        ORDER BY user_level ASC, created_at DESC
    ");
}

include '../includes/header.php';
?>

<div class="mb-8 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Kelola User</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Manajemen user admin & customer (Super Admin only)</p>
    </div>
    <?php if ($role_filter === 'admin'): ?>
    <button onclick="openModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
        + Tambah User Admin
    </button>
    <?php endif; ?>
</div>

<!-- Tab Navigation -->
<div class="mb-6 border-b border-gray-200 dark:border-gray-700">
    <nav class="-mb-px flex space-x-8">
        <a href="?role=admin" class="<?php echo $role_filter === 'admin' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
            User Admin
        </a>
        <a href="?role=customer" class="<?php echo $role_filter === 'customer' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
            User Customer
        </a>
    </nav>
</div>

<!-- User Level Info -->
<div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4 mb-6">
    <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Informasi User Level:</h3>
    <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
        <li><strong>Level 1 - Super Admin:</strong> Full access, kelola semua user admin</li>
        <li><strong>Level 2 - Manager:</strong> Lihat semua transaksi, buat transaksi, kelola pelanggan & barang</li>
        <li><strong>Level 3 - Staff:</strong> Buat transaksi, kelola pelanggan & barang (tidak bisa hapus)</li>
        <li><strong>Level 4 - Customer:</strong> Lihat & bayar cicilan sendiri</li>
    </ul>
</div>

<!-- Users Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Lengkap</th>
                    <?php if ($role_filter === 'customer'): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kontak</th>
                    <?php else: ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User Level</th>
                    <?php endif; ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php if ($users->num_rows > 0): ?>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">ID: <?php echo $user['id']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <?php
                                $display_name = $role_filter === 'customer'
                                    ? ($user['customer_name'] ?? $user['full_name'])
                                    : $user['full_name'];
                                echo htmlspecialchars($display_name);
                                ?>
                            </td>
                            <?php if ($role_filter === 'customer'): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <div><?php echo htmlspecialchars($user['telepon'] ?? '-'); ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></div>
                            </td>
                            <?php else: ?>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $levelBadges = [
                                    1 => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                    2 => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                    3 => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                ];
                                $badgeClass = $levelBadges[$user['user_level']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded <?php echo $badgeClass; ?>">
                                    Level <?php echo $user['user_level']; ?> - <?php echo getUserLevelName($user['user_level']); ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $user['is_active']; ?>">
                                    <button type="submit" class="px-2 py-1 text-xs font-medium rounded <?php echo $user['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'; ?>">
                                        <?php echo $user['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($role_filter === 'admin'): ?>
                                <button onclick='openModal("edit", <?php echo json_encode($user); ?>)' class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mr-3">Edit</button>
                                <?php if ($user['id'] != getCurrentUser()['id']): ?>
                                <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="text-red-600 hover:text-red-900 dark:text-red-400">Hapus</button>
                                <?php endif; ?>
                                <?php else: ?>
                                <button onclick='openResetPasswordModal(<?php echo $user["id"]; ?>, "<?php echo htmlspecialchars($user["username"]); ?>")' class="text-blue-600 hover:text-blue-900 dark:text-blue-400">Reset Password</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            Belum ada data user <?php echo $role_filter === 'customer' ? 'customer' : 'admin'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add/Edit -->
<div id="userModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Tambah User Admin</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="userForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="userId">

            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Username *</label>
                    <input type="text" name="username" id="username" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Nama Lengkap *</label>
                    <input type="text" name="full_name" id="full_name" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">User Level *</label>
                    <select name="user_level" id="user_level" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="1">Level 1 - Super Admin</option>
                        <option value="2">Level 2 - Manager</option>
                        <option value="3">Level 3 - Staff</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 mb-2">Password <span id="passwordNote">(Kosongkan jika tidak ingin mengubah)</span></label>
                    <input type="password" name="password" id="password" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white rounded-lg transition duration-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<!-- Reset Password Modal (Customer Only) -->
<div id="resetPasswordModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Reset Password Customer</h3>
            <button onclick="closeResetPasswordModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="id" id="resetUserId">

            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Username: <strong id="resetUsername"></strong></p>
                <label class="block text-gray-700 dark:text-gray-300 mb-2">Password Baru *</label>
                <input type="text" name="new_password" id="new_password" required placeholder="Masukkan password baru" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Password akan di-hash secara otomatis</p>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeResetPasswordModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-white rounded-lg transition duration-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200">
                    Reset Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(action, data = null) {
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    const title = document.getElementById('modalTitle');
    const passwordNote = document.getElementById('passwordNote');
    const passwordInput = document.getElementById('password');

    form.reset();
    document.getElementById('formAction').value = action;

    if (action === 'edit' && data) {
        title.textContent = 'Edit User Admin';
        document.getElementById('userId').value = data.id;
        document.getElementById('username').value = data.username;
        document.getElementById('full_name').value = data.full_name || '';
        document.getElementById('user_level').value = data.user_level;
        passwordInput.required = false;
        passwordNote.classList.remove('hidden');
    } else {
        title.textContent = 'Tambah User Admin';
        passwordInput.required = true;
        passwordNote.classList.add('hidden');
    }

    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('userModal').classList.add('hidden');
}

function confirmDelete(id, username) {
    if (confirm(`Apakah Anda yakin ingin menghapus user "${username}"?`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function openResetPasswordModal(userId, username) {
    const modal = document.getElementById('resetPasswordModal');
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetUsername').textContent = username;
    document.getElementById('new_password').value = '12345'; // Default password
    modal.classList.remove('hidden');
}

function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').classList.add('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>
