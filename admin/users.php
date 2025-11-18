<?php
require_once '../config.php';
requireAdmin();

$success = '';
$error = '';

// Handle Add/Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nama = sanitize($_POST['nama']);
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $role = sanitize($_POST['role']);
        $level_karyawan = sanitize($_POST['level_karyawan'] ?? '3');
        
        if (empty($nama) || empty($username) || empty($password)) {
            $error = 'Semua field harus diisi';
        } else {
            // Check if username exists
            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($check, "s", $username);
            mysqli_stmt_execute($check);
            $result = mysqli_stmt_get_result($check);
            
            if (mysqli_num_rows($result) > 0) {
                $error = 'Username sudah digunakan';
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $query = "INSERT INTO users (nama, username, password, role, level_karyawan) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssss", $nama, $username, $hashed, $role, $level_karyawan);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'User berhasil ditambahkan';
                } else {
                    $error = 'Gagal menambahkan user';
                }
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['user_id']);
        $nama = sanitize($_POST['nama']);
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $role = sanitize($_POST['role']);
        $status = sanitize($_POST['status']);
        $level_karyawan = sanitize($_POST['level_karyawan'] ?? '3');

        if (empty($nama) || empty($username)) {
            $error = 'Nama dan username harus diisi';
        } else {
            // Check if username exists for another user
            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ?");
            mysqli_stmt_bind_param($check, "si", $username, $id);
            mysqli_stmt_execute($check);
            $result = mysqli_stmt_get_result($check);

            if (mysqli_num_rows($result) > 0) {
                $error = 'Username sudah digunakan oleh user lain';
            } else {
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $query = "UPDATE users SET nama=?, username=?, password=?, role=?, level_karyawan=?, status=? WHERE id=?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "ssssssi", $nama, $username, $hashed, $role, $level_karyawan, $status, $id);
                } else {
                    $query = "UPDATE users SET nama=?, username=?, role=?, level_karyawan=?, status=? WHERE id=?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "sssssi", $nama, $username, $role, $level_karyawan, $status, $id);
                }

                if (mysqli_stmt_execute($stmt)) {
                    $success = 'User berhasil diupdate';
                } else {
                    $error = 'Gagal mengupdate user';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['user_id']);
        
        // Check if not deleting self
        if ($id == $_SESSION['user_id']) {
            $error = 'Tidak dapat menghapus user sendiri';
        } else {
            $query = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'User berhasil dihapus';
            } else {
                $error = 'Gagal menghapus user';
            }
        }
    }
}

// Get all users
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Karyawan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #000;
        }
        .action-btns {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-menu">
            <ul>
                <li><a href="../dashboard.php">Dashboard</a></li>
                <li><a href="users.php" class="active">Kelola Karyawan</a></li>
                <li><a href="laporan.php">Laporan</a></li>
            </ul>
        </div>

        <div class="dashboard">
            <div class="dashboard-header">
                <h1>👥 Kelola Karyawan</h1>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Daftar User</h2>
                    <button onclick="openAddModal()" class="btn btn-primary">➕ Tambah User</button>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Level</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['nama']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        Level <?php echo $user['level_karyawan'] ?? '3'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['status'] == 'aktif' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <button onclick='openEditModal(<?php echo json_encode($user, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                                class="btn btn-warning btn-sm">Edit</button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button onclick="confirmDelete(<?php echo $user['id']; ?>, <?php echo json_encode($user['nama'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)"
                                                class="btn btn-danger btn-sm">Hapus</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2>➕ Tambah User Baru</h2>
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" class="form-control" required>
                        <option value="karyawan">Karyawan</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Level Karyawan *</label>
                    <select name="level_karyawan" class="form-control" required>
                        <option value="1">Level 1 (Bonus Terendah)</option>
                        <option value="2">Level 2</option>
                        <option value="3" selected>Level 3 (Default)</option>
                        <option value="4">Level 4</option>
                        <option value="5">Level 5 (Bonus Tertinggi)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success" style="width: 100%;">Simpan</button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>✏️ Edit User</h2>
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" id="edit_username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah">
                </div>

                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" id="edit_role" class="form-control" required>
                        <option value="karyawan">Karyawan</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Level Karyawan *</label>
                    <select name="level_karyawan" id="edit_level_karyawan" class="form-control" required>
                        <option value="1">Level 1 (Bonus Terendah)</option>
                        <option value="2">Level 2</option>
                        <option value="3">Level 3 (Default)</option>
                        <option value="4">Level 4</option>
                        <option value="5">Level 5 (Bonus Tertinggi)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="edit_status" class="form-control" required>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Non-Aktif</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success" style="width: 100%;">Update</button>
            </form>
        </div>
    </div>

    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="delete_user_id">
    </form>

    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_nama').value = user.nama;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_level_karyawan').value = user.level_karyawan || '3';
            document.getElementById('edit_status').value = user.status;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function confirmDelete(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus user "' + nama + '"?\\nSemua data aktivitas user ini juga akan terhapus!')) {
                document.getElementById('delete_user_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
