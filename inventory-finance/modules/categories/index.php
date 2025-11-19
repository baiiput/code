<?php
$pageTitle = 'Kategori';
require_once __DIR__ . '/../../templates/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    delete('categories', (int)$_GET['delete']);
    setFlash('success', 'Kategori berhasil dihapus');
    header('Location: ' . BASE_URL . 'modules/categories/');
    exit;
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $data = [
        'name' => trim($_POST['name']),
        'description' => trim($_POST['description']),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if (!empty($_POST['id'])) {
        update('categories', $data, (int)$_POST['id']);
        setFlash('success', 'Kategori berhasil diupdate');
    } else {
        insert('categories', $data);
        setFlash('success', 'Kategori berhasil ditambahkan');
    }

    header('Location: ' . BASE_URL . 'modules/categories/');
    exit;
}

// Get categories with product count
$db = getDB();
$categories = $db->query("SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.name ASC")->fetchAll();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Kategori Produk</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Kategori' => '']) ?>
    </div>
    <button class="btn btn-primary" onclick="openModal('categoryModal')">
        <i class="fas fa-plus me-2"></i>Tambah Kategori
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th class="text-center">Jumlah Produk</th>
                        <th>Status</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><strong><?= e($cat['name']) ?></strong></td>
                        <td><?= e($cat['description']) ?></td>
                        <td class="text-center"><?= $cat['product_count'] ?></td>
                        <td>
                            <?php if ($cat['is_active']): ?>
                            <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline"
                                        onclick="editCategory(<?= htmlspecialchars(json_encode($cat)) ?>)"
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($cat['product_count'] == 0): ?>
                                <a href="?delete=<?= $cat['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   data-confirm="Yakin ingin menghapus kategori ini?"
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

<!-- Modal -->
<div class="modal-backdrop"></div>
<div class="modal" id="categoryModal">
    <div class="modal-header">
        <h5 id="modalTitle">Tambah Kategori</h5>
        <button type="button" class="btn-close" onclick="closeModal('categoryModal')">&times;</button>
    </div>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="id" id="categoryId">
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                <input type="text" name="name" id="categoryName" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" id="categoryDesc" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="is_active" id="categoryActive" value="1" checked>
                    <span>Aktif</span>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('categoryModal')">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
function editCategory(cat) {
    document.getElementById('modalTitle').textContent = 'Edit Kategori';
    document.getElementById('categoryId').value = cat.id;
    document.getElementById('categoryName').value = cat.name;
    document.getElementById('categoryDesc').value = cat.description || '';
    document.getElementById('categoryActive').checked = cat.is_active == 1;
    openModal('categoryModal');
}

// Reset modal on open for new category
document.querySelector('[onclick*="openModal"]').addEventListener('click', function() {
    document.getElementById('modalTitle').textContent = 'Tambah Kategori';
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryName').value = '';
    document.getElementById('categoryDesc').value = '';
    document.getElementById('categoryActive').checked = true;
});
</script>
SCRIPT;
?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
