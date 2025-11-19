<?php
require_once '../config/config.php';
Auth::requireRole(['super_admin', 'owner']);

$pageTitle = 'Kelola Pengguna';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $db->insert('users', [
            'cabang_id' => $_POST['cabang_id'] ?: null,
            'username' => $_POST['username'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'nama_lengkap' => $_POST['nama_lengkap'],
            'role' => $_POST['role']
        ]);
        Helper::setFlash('success', 'Pengguna berhasil ditambahkan!');
    } elseif ($action === 'update') {
        $data = [
            'cabang_id' => $_POST['cabang_id'] ?: null,
            'username' => $_POST['username'],
            'nama_lengkap' => $_POST['nama_lengkap'],
            'role' => $_POST['role']
        ];
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        $db->update('users', $data, 'id = ?', [$_POST['id']]);
        Helper::setFlash('success', 'Pengguna berhasil diperbarui!');
    } elseif ($action === 'toggle') {
        $user = $db->fetch("SELECT is_active FROM users WHERE id = ?", [$_POST['id']]);
        $db->update('users', ['is_active' => !$user['is_active']], 'id = ?', [$_POST['id']]);
        Helper::setFlash('success', 'Status pengguna berhasil diubah!');
    }

    header('Location: users.php');
    exit;
}

// Get all users
$users = $db->fetchAll("
    SELECT u.*, c.nama as cabang_nama
    FROM users u
    LEFT JOIN cabang c ON u.cabang_id = c.id
    ORDER BY u.nama_lengkap ASC
");

// Get cabang for dropdown
$cabangs = $db->fetchAll("SELECT id, nama FROM cabang WHERE is_active = 1 ORDER BY nama ASC");

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Daftar Pengguna</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUser">
        <i class="bi bi-plus-lg me-1"></i> Tambah Pengguna
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th width="50">No</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Cabang</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">Belum ada data pengguna</td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $i => $user): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="fw-medium"><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                    <td><code><?= htmlspecialchars($user['username']) ?></code></td>
                    <td>
                        <span class="badge bg-<?= $user['role'] === 'super_admin' ? 'danger' : ($user['role'] === 'owner' ? 'warning' : 'primary') ?>">
                            <?= Helper::roleLabel($user['role']) ?>
                        </span>
                    </td>
                    <td><?= $user['cabang_nama'] ?? '<em class="text-muted">Semua</em>' ?></td>
                    <td>
                        <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $user['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </td>
                    <td>
                        <?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-' ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick='editUser(<?= json_encode($user) ?>)'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php if ($user['id'] != Auth::user('id')): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin mengubah status?')">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $user['is_active'] ? 'warning' : 'success' ?>">
                                <i class="bi bi-<?= $user['is_active'] ? 'x-circle' : 'check-circle' ?>"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formUser">
                <input type="hidden" name="action" value="create" id="formAction">
                <input type="hidden" name="id" id="formId">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_lengkap" id="inputNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" id="inputUsername" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger" id="passRequired">*</span></label>
                        <input type="password" class="form-control" name="password" id="inputPassword">
                        <small class="text-muted" id="passHint" style="display:none">Kosongkan jika tidak ingin mengubah password</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" id="inputRole" required>
                            <option value="">Pilih Role</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="owner">Owner</option>
                            <option value="admin_cabang">Admin Cabang</option>
                            <option value="kasir">Kasir</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cabang</label>
                        <select class="form-select" name="cabang_id" id="inputCabang">
                            <option value="">Semua Cabang</option>
                            <?php foreach ($cabangs as $cabang): ?>
                            <option value="<?= $cabang['id'] ?>"><?= htmlspecialchars($cabang['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Kosongkan untuk akses ke semua cabang</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraJs = '
<script>
function editUser(user) {
    document.getElementById("formAction").value = "update";
    document.getElementById("formId").value = user.id;
    document.getElementById("inputNama").value = user.nama_lengkap;
    document.getElementById("inputUsername").value = user.username;
    document.getElementById("inputPassword").value = "";
    document.getElementById("inputPassword").required = false;
    document.getElementById("passRequired").style.display = "none";
    document.getElementById("passHint").style.display = "block";
    document.getElementById("inputRole").value = user.role;
    document.getElementById("inputCabang").value = user.cabang_id || "";
    document.getElementById("modalTitle").textContent = "Edit Pengguna";
    new bootstrap.Modal(document.getElementById("modalUser")).show();
}

document.getElementById("modalUser").addEventListener("hidden.bs.modal", function() {
    document.getElementById("formUser").reset();
    document.getElementById("formAction").value = "create";
    document.getElementById("formId").value = "";
    document.getElementById("inputPassword").required = true;
    document.getElementById("passRequired").style.display = "inline";
    document.getElementById("passHint").style.display = "none";
    document.getElementById("modalTitle").textContent = "Tambah Pengguna";
});
</script>';

include '../includes/footer.php';
?>
