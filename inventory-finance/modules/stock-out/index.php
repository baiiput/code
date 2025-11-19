<?php
$pageTitle = 'Penjualan';
require_once __DIR__ . '/../../templates/header.php';

// Get sales list
$db = getDB();
$sales = $db->query("SELECT s.*, c.name as customer_name, u.name as created_by_name,
    (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.created_by = u.id
    ORDER BY s.date DESC, s.id DESC")->fetchAll();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Penjualan</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Penjualan' => '']) ?>
    </div>
    <a href="<?= BASE_URL ?>modules/stock-out/form.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Penjualan Baru
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
                        <th>Customer</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Profit</th>
                        <th>Pembayaran</th>
                        <th>Status</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">Belum ada data penjualan</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><strong><?= e($sale['invoice_number']) ?></strong></td>
                        <td><?= formatDate($sale['date']) ?></td>
                        <td><?= e($sale['customer_name']) ?: 'Walk-in' ?></td>
                        <td class="text-center"><?= $sale['item_count'] ?></td>
                        <td class="text-end"><?= formatCurrency($sale['grand_total']) ?></td>
                        <td class="text-end text-success"><?= formatCurrency($sale['profit']) ?></td>
                        <td>
                            <?= getPaymentMethodLabel($sale['payment_method']) ?>
                            <?php if ($sale['marketplace_name']): ?>
                            <br><small class="text-muted"><?= e($sale['marketplace_name']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= getStatusBadge($sale['payment_status'], 'payment') ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>modules/stock-out/view.php?id=<?= $sale['id'] ?>"
                                   class="btn btn-sm btn-outline" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>modules/invoices/print.php?id=<?= $sale['id'] ?>"
                                   class="btn btn-sm btn-outline" title="Print Invoice" target="_blank">
                                    <i class="fas fa-print"></i>
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
