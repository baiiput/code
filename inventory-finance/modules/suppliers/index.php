<?php
$pageTitle = 'Supplier';
require_once __DIR__ . '/../../templates/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    delete('suppliers', (int)$_GET['delete']);
    setFlash('success', 'Supplier berhasil dihapus');
    header('Location: ' . BASE_URL . 'modules/suppliers/');
    exit;
}

// Get suppliers
$suppliers = getAll('suppliers', [], 'name ASC');
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Supplier</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Supplier' => '']) ?>
    </div>
    <a href="<?= BASE_URL ?>modules/suppliers/form.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Tambah Supplier
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Kota</th>
                        <th>Status</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td><strong><?= e($supplier['code']) ?></strong></td>
                        <td><?= e($supplier['name']) ?></td>
                        <td><?= e($supplier['contact_person']) ?></td>
                        <td><?= e($supplier['phone']) ?></td>
                        <td><?= e($supplier['city']) ?></td>
                        <td>
                            <?php if ($supplier['is_active']): ?>
                            <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>modules/suppliers/form.php?id=<?= $supplier['id'] ?>"
                                   class="btn btn-sm btn-outline" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?= $supplier['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   data-confirm="Yakin ingin menghapus supplier ini?"
                                   title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </a>
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
