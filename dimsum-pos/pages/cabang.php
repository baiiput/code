<?php
require_once '../config/config.php';
Auth::requireRole(['super_admin', 'owner']);

$pageTitle = 'Kelola Cabang';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $db->insert('cabang', [
            'nama' => $_POST['nama'],
            'alamat' => $_POST['alamat'],
            'telepon' => $_POST['telepon']
        ]);
        Helper::setFlash('success', 'Cabang berhasil ditambahkan!');
    } elseif ($action === 'update') {
        $db->update('cabang', [
            'nama' => $_POST['nama'],
            'alamat' => $_POST['alamat'],
            'telepon' => $_POST['telepon']
        ], 'id = ?', [$_POST['id']]);
        Helper::setFlash('success', 'Cabang berhasil diperbarui!');
    } elseif ($action === 'delete') {
        $db->update('cabang', ['is_active' => 0], 'id = ?', [$_POST['id']]);
        Helper::setFlash('success', 'Cabang berhasil dihapus!');
    } elseif ($action === 'toggle') {
        $cabang = $db->fetch("SELECT is_active FROM cabang WHERE id = ?", [$_POST['id']]);
        $db->update('cabang', ['is_active' => !$cabang['is_active']], 'id = ?', [$_POST['id']]);
        Helper::setFlash('success', 'Status cabang berhasil diubah!');
    }

    header('Location: cabang.php');
    exit;
}

// Get all cabang
$cabangs = $db->fetchAll("SELECT * FROM cabang ORDER BY nama ASC");

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Daftar Cabang</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCabang">
        <i class="bi bi-plus-lg me-1"></i> Tambah Cabang
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th width="50">No</th>
                    <th>Nama Cabang</th>
                    <th>Alamat</th>
                    <th>Telepon</th>
                    <th>Status</th>
                    <th width="150">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cabangs)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Belum ada data cabang</td>
                </tr>
                <?php else: ?>
                <?php foreach ($cabangs as $i => $cabang): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="fw-medium"><?= htmlspecialchars($cabang['nama']) ?></td>
                    <td><?= htmlspecialchars($cabang['alamat']) ?></td>
                    <td><?= htmlspecialchars($cabang['telepon']) ?></td>
                    <td>
                        <span class="badge bg-<?= $cabang['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $cabang['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editCabang(<?= htmlspecialchars(json_encode($cabang)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin mengubah status?')">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $cabang['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $cabang['is_active'] ? 'warning' : 'success' ?>">
                                <i class="bi bi-<?= $cabang['is_active'] ? 'x-circle' : 'check-circle' ?>"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalCabang" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formCabang">
                <input type="hidden" name="action" value="create" id="formAction">
                <input type="hidden" name="id" id="formId">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Cabang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Cabang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" id="inputNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-control" name="alamat" id="inputAlamat" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telepon</label>
                        <input type="text" class="form-control" name="telepon" id="inputTelepon">
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
function editCabang(cabang) {
    document.getElementById("formAction").value = "update";
    document.getElementById("formId").value = cabang.id;
    document.getElementById("inputNama").value = cabang.nama;
    document.getElementById("inputAlamat").value = cabang.alamat || "";
    document.getElementById("inputTelepon").value = cabang.telepon || "";
    document.getElementById("modalTitle").textContent = "Edit Cabang";
    new bootstrap.Modal(document.getElementById("modalCabang")).show();
}

document.getElementById("modalCabang").addEventListener("hidden.bs.modal", function() {
    document.getElementById("formCabang").reset();
    document.getElementById("formAction").value = "create";
    document.getElementById("formId").value = "";
    document.getElementById("modalTitle").textContent = "Tambah Cabang";
});
</script>';

include '../includes/footer.php';
?>
