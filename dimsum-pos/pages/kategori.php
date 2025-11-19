<?php
require_once '../config/config.php';
Auth::requireRole(['super_admin', 'owner', 'admin_cabang']);

$pageTitle = 'Kelola Kategori';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $db->insert('kategori', [
            'nama' => $_POST['nama'],
            'deskripsi' => $_POST['deskripsi'],
            'urutan' => $_POST['urutan'] ?: 0
        ]);
        Helper::setFlash('success', 'Kategori berhasil ditambahkan!');
    } elseif ($action === 'update') {
        $db->update('kategori', [
            'nama' => $_POST['nama'],
            'deskripsi' => $_POST['deskripsi'],
            'urutan' => $_POST['urutan'] ?: 0
        ], 'id = ?', [$_POST['id']]);
        Helper::setFlash('success', 'Kategori berhasil diperbarui!');
    } elseif ($action === 'toggle') {
        $kategori = $db->fetch("SELECT is_active FROM kategori WHERE id = ?", [$_POST['id']]);
        $db->update('kategori', ['is_active' => !$kategori['is_active']], 'id = ?', [$_POST['id']]);
        Helper::setFlash('success', 'Status kategori berhasil diubah!');
    }

    header('Location: kategori.php');
    exit;
}

// Get all kategori with menu count
$kategoris = $db->fetchAll("
    SELECT k.*, COUNT(m.id) as total_menu
    FROM kategori k
    LEFT JOIN menu m ON k.id = m.kategori_id AND m.is_active = 1
    GROUP BY k.id
    ORDER BY k.urutan ASC, k.nama ASC
");

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Daftar Kategori</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKategori">
        <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th width="80">Urutan</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Total Menu</th>
                    <th>Status</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kategoris)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Belum ada data kategori</td>
                </tr>
                <?php else: ?>
                <?php foreach ($kategoris as $kategori): ?>
                <tr>
                    <td><?= $kategori['urutan'] ?></td>
                    <td class="fw-medium"><?= htmlspecialchars($kategori['nama']) ?></td>
                    <td><?= htmlspecialchars($kategori['deskripsi']) ?: '-' ?></td>
                    <td><span class="badge bg-info"><?= $kategori['total_menu'] ?> menu</span></td>
                    <td>
                        <span class="badge bg-<?= $kategori['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $kategori['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editKategori(<?= htmlspecialchars(json_encode($kategori)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin mengubah status?')">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $kategori['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-<?= $kategori['is_active'] ? 'warning' : 'success' ?>">
                                <i class="bi bi-<?= $kategori['is_active'] ? 'x-circle' : 'check-circle' ?>"></i>
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
<div class="modal fade" id="modalKategori" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formKategori">
                <input type="hidden" name="action" value="create" id="formAction">
                <input type="hidden" name="id" id="formId">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" id="inputNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" id="inputDeskripsi" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Urutan</label>
                        <input type="number" class="form-control" name="urutan" id="inputUrutan" value="0" min="0">
                        <small class="text-muted">Angka lebih kecil ditampilkan lebih dulu</small>
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
function editKategori(kategori) {
    document.getElementById("formAction").value = "update";
    document.getElementById("formId").value = kategori.id;
    document.getElementById("inputNama").value = kategori.nama;
    document.getElementById("inputDeskripsi").value = kategori.deskripsi || "";
    document.getElementById("inputUrutan").value = kategori.urutan;
    document.getElementById("modalTitle").textContent = "Edit Kategori";
    new bootstrap.Modal(document.getElementById("modalKategori")).show();
}

document.getElementById("modalKategori").addEventListener("hidden.bs.modal", function() {
    document.getElementById("formKategori").reset();
    document.getElementById("formAction").value = "create";
    document.getElementById("formId").value = "";
    document.getElementById("modalTitle").textContent = "Tambah Kategori";
});
</script>';

include '../includes/footer.php';
?>
