<?php
$pageTitle = 'Customer';
require_once __DIR__ . '/../../templates/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    delete('customers', (int)$_GET['delete']);
    setFlash('success', 'Customer berhasil dihapus');
    header('Location: ' . BASE_URL . 'modules/customers/');
    exit;
}

$customers = getAll('customers', [], 'name ASC');
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Customer</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Customer' => '']) ?>
    </div>
    <a href="<?= BASE_URL ?>modules/customers/form.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Tambah Customer
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
                        <th>Perusahaan</th>
                        <th>Phone</th>
                        <th>Kota</th>
                        <th>Status</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><strong><?= e($customer['code']) ?></strong></td>
                        <td><?= e($customer['name']) ?></td>
                        <td><?= e($customer['company']) ?: '-' ?></td>
                        <td><?= e($customer['phone']) ?></td>
                        <td><?= e($customer['city']) ?></td>
                        <td>
                            <?php if ($customer['is_active']): ?>
                            <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>modules/customers/form.php?id=<?= $customer['id'] ?>"
                                   class="btn btn-sm btn-outline" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?= $customer['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   data-confirm="Yakin ingin menghapus customer ini?"
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
