<?php
$pageTitle = 'Return/RMA';
require_once __DIR__ . '/../../templates/header.php';

// Handle status update
if (isset($_GET['complete'])) {
    $id = (int)$_GET['complete'];
    $db = getDB();

    // Get return items and update serial status
    $items = $db->prepare("SELECT * FROM return_items WHERE return_id = ?");
    $items->execute([$id]);
    foreach ($items->fetchAll() as $item) {
        if ($item['serial_id']) {
            updateSerialStatus($item['serial_id'], 'available');
        }
    }

    $db->prepare("UPDATE returns SET status = 'completed' WHERE id = ?")->execute([$id]);
    setFlash('success', 'Return berhasil diselesaikan, stok dikembalikan');
    header('Location: ' . BASE_URL . 'modules/returns/');
    exit;
}

// Get returns
$db = getDB();
$returns = $db->query("SELECT r.*, c.name as customer_name, s.name as supplier_name, u.name as created_by_name
    FROM returns r
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN suppliers s ON r.supplier_id = s.id
    LEFT JOIN users u ON r.created_by = u.id
    ORDER BY r.date DESC, r.id DESC")->fetchAll();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Return/RMA</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Return' => '']) ?>
    </div>
    <a href="<?= BASE_URL ?>modules/returns/form.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Buat Return
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>No. Return</th>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Customer/Supplier</th>
                        <th>Alasan</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($returns)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Belum ada data return</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($returns as $return): ?>
                    <tr>
                        <td><strong><?= e($return['return_number']) ?></strong></td>
                        <td><?= formatDate($return['date']) ?></td>
                        <td>
                            <?php if ($return['type'] == 'customer'): ?>
                            <span class="badge bg-info">Dari Customer</span>
                            <?php else: ?>
                            <span class="badge bg-warning">Ke Supplier</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($return['customer_name'] ?? $return['supplier_name'] ?? '-') ?></td>
                        <td><?= e(truncate($return['reason'], 30)) ?></td>
                        <td class="text-end"><?= formatCurrency($return['total_amount']) ?></td>
                        <td><?= getStatusBadge($return['status'], 'return') ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>modules/returns/view.php?id=<?= $return['id'] ?>"
                                   class="btn btn-sm btn-outline" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($return['status'] == 'approved'): ?>
                                <a href="?complete=<?= $return['id'] ?>"
                                   class="btn btn-sm btn-success"
                                   data-confirm="Selesaikan return dan kembalikan stok?"
                                   title="Selesaikan">
                                    <i class="fas fa-check"></i>
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

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
