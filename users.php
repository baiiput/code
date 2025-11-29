<?php
require_once 'config.php';
requireRole('admin');

$conn = getDBConnection();
$user = getCurrentUser();

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $full_name = clean($_POST['full_name']);
        $email = clean($_POST['email']);
        $phone = clean($_POST['phone']);
        $role = $_POST['role'];
        $cabang_id = $role === 'cabang' ? intval($_POST['cabang_id']) : null;
        $warehouse_id = $role === 'staff_warehouse' ? intval($_POST['warehouse_id']) : null;
        $is_active = isset($_POST['is_active_value']) ? intval($_POST['is_active_value']) : 0;

        if ($action === 'add') {
            $username = clean($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, cabang_id, warehouse_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssiis", $username, $password, $full_name, $email, $phone, $role, $cabang_id, $warehouse_id, $is_active);
            if ($stmt->execute()) {
                logActivity('CREATE', 'user', "Created user: $username ($full_name) with role: $role");
                $success = 'User berhasil ditambahkan';
            } else {
                $success = 'Gagal menambahkan user';
            }
        } else {
            $user_id = intval($_POST['user_id']);

            // Get username and role for logging
            $stmt_username = $conn->prepare("SELECT username, role, is_active FROM users WHERE user_id = ?");
            $stmt_username->bind_param("i", $user_id);
            $stmt_username->execute();
            $result_username = $stmt_username->get_result();
            $user_data = $result_username->fetch_assoc();
            $username = $user_data['username'];
            $old_role = $user_data['role'];
            $old_is_active = $user_data['is_active'];
            $stmt_username->close();

            // Prevent admin from deactivating themselves
            if ($user_id == $user['user_id'] && $is_active == 0) {
                $_SESSION['error_message'] = 'Anda tidak dapat menonaktifkan akun Anda sendiri!';
                header('Location: users.php');
                exit;
            }

            // Check if trying to deactivate an admin
            if ($old_role === 'admin' && $old_is_active == 1 && $is_active == 0) {
                // Count active admins (excluding this one)
                $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND is_active = 1 AND user_id != ?");
                $stmt_check->bind_param("i", $user_id);
                $stmt_check->execute();
                $active_admin_count = $stmt_check->get_result()->fetch_assoc()['count'];
                $stmt_check->close();

                if ($active_admin_count == 0) {
                    $_SESSION['error_message'] = 'Tidak dapat menonaktifkan admin ini. Harus ada minimal 1 admin yang aktif!';
                    header('Location: users.php');
                    exit;
                }
            }

            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, cabang_id=?, warehouse_id=?, is_active=?, password=? WHERE user_id=?");
                $stmt->bind_param("ssssiiisi", $full_name, $email, $phone, $role, $cabang_id, $warehouse_id, $is_active, $password, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, cabang_id=?, warehouse_id=?, is_active=? WHERE user_id=?");
                $stmt->bind_param("ssssiiii", $full_name, $email, $phone, $role, $cabang_id, $warehouse_id, $is_active, $user_id);
            }
            if ($stmt->execute()) {
                logActivity('UPDATE', 'user', "Updated user: $username ($full_name)");
                $success = 'User berhasil diupdate';
            } else {
                $success = 'Gagal mengupdate user';
            }
        }
        $_SESSION['success_message'] = $success;
        header('Location: users.php');
        exit;
    }
}

$users = [];
$result = $conn->query("
    SELECT u.*, b.branch_name, w.warehouse_code, w.warehouse_name
    FROM users u
    LEFT JOIN branches b ON u.cabang_id = b.branch_id
    LEFT JOIN warehouses w ON u.warehouse_id = w.warehouse_id
    ORDER BY u.user_id
");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$branches = [];
$result = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name");
while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}

$warehouses = [];
$result = $conn->query("SELECT warehouse_id, warehouse_code, warehouse_name FROM warehouses WHERE is_active = 1 ORDER BY warehouse_name");
while ($row = $result->fetch_assoc()) {
    $warehouses[] = $row;
}

$page_title = 'Manajemen User';
include 'includes/header.php';

$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-users"></i> Manajemen User</h1>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-user-plus"></i> Tambah User
        </button>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="13%">Username</th>
                    <th>Nama Lengkap</th>
                    <th width="15%">Email</th>
                    <th width="11%">Role</th>
                    <th width="13%">Assignment</th>
                    <th width="8%">Status</th>
                    <th width="11%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?php echo $u['username']; ?></strong></td>
                    <td><?php echo $u['full_name']; ?></td>
                    <td><?php echo $u['email'] ?: '-'; ?></td>
                    <td>
                        <span class="badge badge-primary">
                            <?php
                            $roles = ['admin' => 'Admin', 'manager' => 'Manager', 'staff_warehouse' => 'Staff WH', 'staff_keuangan' => 'Keuangan', 'cabang' => 'Cabang'];
                            echo $roles[$u['role']] ?? $u['role'];
                            ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['role'] === 'cabang' && $u['branch_name']): ?>
                            <span style="font-size: 12px;">
                                <i class="fas fa-building" style="color: var(--text-secondary);"></i>
                                <?php echo $u['branch_name']; ?>
                            </span>
                        <?php elseif ($u['role'] === 'staff_warehouse' && $u['warehouse_name']): ?>
                            <span style="font-size: 12px;">
                                <i class="fas fa-warehouse" style="color: var(--primary-color);"></i>
                                <strong style="color: var(--primary-color);"><?php echo $u['warehouse_code']; ?></strong> -
                                <?php echo $u['warehouse_name']; ?>
                            </span>
                        <?php else: ?>
                            <span style="color: var(--text-secondary); font-size: 12px;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $u['is_active'] ? 'success' : 'danger'; ?>">
                            <?php echo $u['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn-sm btn-warning" onclick='editUser(<?php echo json_encode($u); ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-user-plus"></i> Tambah User</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" id="userForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="user_id" id="userId">
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username *</label>
                    <input type="text" name="username" id="username" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password <span id="passLabel">*</span></label>
                    <input type="password" name="password" id="password">
                    <small style="color: var(--text-secondary);">Kosongkan jika tidak ingin mengubah password</small>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-id-card"></i> Nama Lengkap *</label>
                <input type="text" name="full_name" id="fullName" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" id="email">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Telepon</label>
                    <input type="text" name="phone" id="phone">
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-user-tag"></i> Role *</label>
                <select name="role" id="role" required onchange="toggleAssignment()">
                    <option value="">Pilih Role</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="staff_warehouse">Staff Warehouse</option>
                    <option value="staff_keuangan">Staff Keuangan</option>
                    <option value="cabang">Cabang</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group" id="warehouseGroup" style="display: none;">
                    <label><i class="fas fa-warehouse"></i> Warehouse *</label>
                    <select name="warehouse_id" id="warehouseId">
                        <option value="">Pilih Warehouse</option>
                        <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo $wh['warehouse_id']; ?>">
                            <?php echo $wh['warehouse_code']; ?> - <?php echo $wh['warehouse_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="cabangGroup" style="display: none;">
                    <label><i class="fas fa-building"></i> Cabang *</label>
                    <select name="cabang_id" id="cabangId">
                        <option value="">Pilih Cabang</option>
                        <?php foreach ($branches as $b): ?>
                        <option value="<?php echo $b['branch_id']; ?>"><?php echo $b['branch_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="hidden" name="is_active_value" id="isActiveValue" value="1">
                    <input type="checkbox" id="isActive" checked onchange="updateIsActiveValue()">
                    <span>User Aktif</span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateIsActiveValue() {
    const checkbox = document.getElementById('isActive');
    const hiddenInput = document.getElementById('isActiveValue');
    hiddenInput.value = checkbox.checked ? '1' : '0';
}

function showAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Tambah User';
    document.getElementById('formAction').value = 'add';
    document.getElementById('userForm').reset();
    document.getElementById('username').disabled = false;
    document.getElementById('password').required = true;
    document.getElementById('passLabel').textContent = '*';
    document.getElementById('cabangGroup').style.display = 'none';
    document.getElementById('warehouseGroup').style.display = 'none';
    document.getElementById('isActive').checked = true;
    document.getElementById('isActiveValue').value = '1';
    document.getElementById('userModal').style.display = 'block';
}

let currentEditingUser = null;
const currentUserId = <?php echo $user['user_id']; ?>;

function editUser(user) {
    currentEditingUser = user;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Edit User';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('userId').value = user.user_id;
    document.getElementById('username').value = user.username;
    document.getElementById('username').disabled = true;
    document.getElementById('password').required = false;
    document.getElementById('password').value = '';
    document.getElementById('passLabel').textContent = '';
    document.getElementById('fullName').value = user.full_name;
    document.getElementById('email').value = user.email || '';
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('role').value = user.role;
    document.getElementById('cabangId').value = user.cabang_id || '';
    document.getElementById('warehouseId').value = user.warehouse_id || '';
    const isActive = user.is_active == 1;
    document.getElementById('isActive').checked = isActive;
    document.getElementById('isActiveValue').value = isActive ? '1' : '0';
    toggleAssignment();
    document.getElementById('userModal').style.display = 'block';
}

function toggleAssignment() {
    const role = document.getElementById('role').value;
    const cabangGroup = document.getElementById('cabangGroup');
    const warehouseGroup = document.getElementById('warehouseGroup');

    // Hide all first
    cabangGroup.style.display = 'none';
    warehouseGroup.style.display = 'none';
    document.getElementById('cabangId').required = false;
    document.getElementById('warehouseId').required = false;

    // Show based on role
    if (role === 'cabang') {
        cabangGroup.style.display = 'block';
        document.getElementById('cabangId').required = true;
    } else if (role === 'staff_warehouse') {
        warehouseGroup.style.display = 'block';
        document.getElementById('warehouseId').required = true;
    }
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('userModal')) {
        closeModal();
    }
}

// Form validation
document.getElementById('userForm').addEventListener('submit', async function(e) {
    const action = document.getElementById('formAction').value;

    if (action === 'edit' && currentEditingUser) {
        const isActive = document.getElementById('isActive').checked;
        const userId = parseInt(document.getElementById('userId').value);

        // Check if trying to deactivate self
        if (userId === currentUserId && !isActive) {
            e.preventDefault();
            alert('Anda tidak dapat menonaktifkan akun Anda sendiri!');
            return false;
        }

        // Check if trying to deactivate an admin
        if (currentEditingUser.role === 'admin' && currentEditingUser.is_active == 1 && !isActive) {
            // Count other active admins
            const allUsers = <?php echo json_encode($users); ?>;
            const activeAdmins = allUsers.filter(u =>
                u.role === 'admin' &&
                u.is_active == 1 &&
                u.user_id !== userId
            );

            if (activeAdmins.length === 0) {
                e.preventDefault();
                alert('Tidak dapat menonaktifkan admin ini.\nHarus ada minimal 1 admin yang aktif dalam sistem!');
                return false;
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
