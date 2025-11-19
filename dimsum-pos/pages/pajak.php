<?php
require_once '../config/config.php';
Auth::requireRole(['super_admin', 'owner']);

$pageTitle = 'Pengaturan Pajak';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $db->update('pengaturan_pajak', [
            'nama' => $_POST['nama'],
            'persentase' => $_POST['persentase']
        ], 'id = ?', [$_POST['id']]);
        Helper::setFlash('success', 'Pengaturan pajak berhasil diperbarui!');
    } elseif ($action === 'toggle') {
        // Disable all first, then enable selected
        $db->query("UPDATE pengaturan_pajak SET is_active = 0");
        if ($_POST['id']) {
            $db->update('pengaturan_pajak', ['is_active' => 1], 'id = ?', [$_POST['id']]);
        }
        Helper::setFlash('success', 'Status pajak berhasil diubah!');
    } elseif ($action === 'create') {
        $db->insert('pengaturan_pajak', [
            'nama' => $_POST['nama'],
            'persentase' => $_POST['persentase'],
            'is_active' => 0
        ]);
        Helper::setFlash('success', 'Pajak berhasil ditambahkan!');
    }

    header('Location: pajak.php');
    exit;
}

// Get all pajak
$pajaks = $db->fetchAll("SELECT * FROM pengaturan_pajak ORDER BY nama ASC");
$activePajak = $db->fetch("SELECT * FROM pengaturan_pajak WHERE is_active = 1");

include '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Status Pajak</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="toggle">
                    <div class="mb-3">
                        <label class="form-label">Pajak Aktif</label>
                        <select class="form-select" name="id">
                            <option value="">-- Nonaktifkan Pajak --</option>
                            <?php foreach ($pajaks as $pajak): ?>
                            <option value="<?= $pajak['id'] ?>" <?= $pajak['is_active'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pajak['nama']) ?> (<?= $pajak['persentase'] ?>%)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </form>

                <?php if ($activePajak): ?>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Pajak <strong><?= htmlspecialchars($activePajak['nama']) ?></strong> sebesar
                    <strong><?= $activePajak['persentase'] ?>%</strong> akan diterapkan pada setiap transaksi.
                </div>
                <?php else: ?>
                <div class="alert alert-secondary mt-3 mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Pajak saat ini <strong>tidak aktif</strong>. Transaksi tidak akan dikenakan pajak.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Daftar Pajak</h6>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalPajak">
                    <i class="bi bi-plus me-1"></i> Tambah
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Persentase</th>
                            <th>Status</th>
                            <th width="80">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pajaks)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Belum ada data pajak</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($pajaks as $pajak): ?>
                        <tr>
                            <td class="fw-medium"><?= htmlspecialchars($pajak['nama']) ?></td>
                            <td><?= $pajak['persentase'] ?>%</td>
                            <td>
                                <span class="badge bg-<?= $pajak['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $pajak['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editPajak(<?= htmlspecialchars(json_encode($pajak)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalPajak" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formPajak">
                <input type="hidden" name="action" value="create" id="formAction">
                <input type="hidden" name="id" id="formId">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Pajak</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Pajak <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" id="inputNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Persentase (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="persentase" id="inputPersentase" step="0.01" min="0" max="100" required>
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
function editPajak(pajak) {
    document.getElementById("formAction").value = "update";
    document.getElementById("formId").value = pajak.id;
    document.getElementById("inputNama").value = pajak.nama;
    document.getElementById("inputPersentase").value = pajak.persentase;
    document.getElementById("modalTitle").textContent = "Edit Pajak";
    new bootstrap.Modal(document.getElementById("modalPajak")).show();
}

document.getElementById("modalPajak").addEventListener("hidden.bs.modal", function() {
    document.getElementById("formPajak").reset();
    document.getElementById("formAction").value = "create";
    document.getElementById("formId").value = "";
    document.getElementById("modalTitle").textContent = "Tambah Pajak";
});
</script>';

include '../includes/footer.php';
?>
