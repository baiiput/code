<?php
$pageTitle = 'Stok Masuk';
require_once __DIR__ . '/../../templates/header.php';

// Get stock in list
$db = getDB();
$stockIns = $db->query("SELECT si.*, s.name as supplier_name, u.name as created_by_name,
    (SELECT COUNT(*) FROM stock_in_items WHERE stock_in_id = si.id) as item_count
    FROM stock_in si
    LEFT JOIN suppliers s ON si.supplier_id = s.id
    LEFT JOIN users u ON si.created_by = u.id
    ORDER BY si.date DESC, si.id DESC")->fetchAll();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Stok Masuk</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Stok Masuk' => '']) ?>
    </div>
    <a href="<?= BASE_URL ?>modules/stock-in/form.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Tambah Stok Masuk
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>No. Invoice</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">Total</th>
                        <th>Status Bayar</th>
                        <th>Created By</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stockIns)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Belum ada data stok masuk</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($stockIns as $stockIn): ?>
                    <tr>
                        <td><strong><?= e($stockIn['invoice_number']) ?></strong></td>
                        <td><?= formatDate($stockIn['date']) ?></td>
                        <td><?= e($stockIn['supplier_name']) ?: '-' ?></td>
                        <td class="text-center"><?= $stockIn['item_count'] ?></td>
                        <td class="text-end"><?= formatCurrency($stockIn['grand_total']) ?></td>
                        <td>
                            <?= getStatusBadge($stockIn['payment_status'], 'payment') ?>
                            <?php if ($stockIn['due_date'] && $stockIn['payment_status'] != 'paid'): ?>
                            <br><small class="text-muted">Due: <?= formatDate($stockIn['due_date']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($stockIn['created_by_name']) ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>modules/stock-in/view.php?id=<?= $stockIn['id'] ?>"
                                   class="btn btn-sm btn-outline" title="Detail">
                                    <i class="fas fa-eye"></i>
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
