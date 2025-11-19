<?php
$pageTitle = 'Detail Penjualan';
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ' . BASE_URL . 'modules/stock-out/');
    exit;
}

$db = getDB();

// Get sale header
$sale = $db->prepare("SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address,
    u.name as created_by_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.created_by = u.id
    WHERE s.id = ?");
$sale->execute([$id]);
$sale = $sale->fetch();

if (!$sale) {
    setFlash('danger', 'Data tidak ditemukan');
    header('Location: ' . BASE_URL . 'modules/stock-out/');
    exit;
}

// Get items
$items = $db->prepare("SELECT si.*, p.name as product_name, p.code as product_code,
    ps.serial_number, ps.mac_address
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    LEFT JOIN product_serials ps ON si.serial_id = ps.id
    WHERE si.sale_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Detail Penjualan</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Penjualan' => BASE_URL . 'modules/stock-out/', 'Detail' => '']) ?>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>modules/invoices/print.php?id=<?= $sale['id'] ?>" class="btn btn-outline" target="_blank">
            <i class="fas fa-print me-2"></i>Print Invoice
        </a>
        <a href="<?= BASE_URL ?>modules/stock-out/" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12 col-4">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-info-circle me-2"></i>Informasi</span>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted" width="40%">No. Invoice</td>
                        <td><strong><?= e($sale['invoice_number']) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal</td>
                        <td><?= formatDate($sale['date']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Customer</td>
                        <td>
                            <?= e($sale['customer_name']) ?: 'Walk-in' ?>
                            <?php if ($sale['customer_phone']): ?>
                            <br><small class="text-muted"><?= e($sale['customer_phone']) ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Pembayaran</td>
                        <td>
                            <?= getPaymentMethodLabel($sale['payment_method']) ?>
                            <?php if ($sale['marketplace_name']): ?>
                            <br><small class="text-muted"><?= e($sale['marketplace_name']) ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td><?= getStatusBadge($sale['payment_status'], 'payment') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Created By</td>
                        <td><?= e($sale['created_by_name']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-calculator me-2"></i>Total</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span><?= formatCurrency($sale['subtotal']) ?></span>
                </div>
                <?php if ($sale['discount'] > 0): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Diskon</span>
                    <span class="text-danger">-<?= formatCurrency($sale['discount']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($sale['tax'] > 0): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pajak</span>
                    <span><?= formatCurrency($sale['tax']) ?></span>
                </div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold">Grand Total</span>
                    <span class="fw-bold text-primary"><?= formatCurrency($sale['grand_total']) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="fw-semibold">Profit</span>
                    <span class="fw-bold text-success"><?= formatCurrency($sale['profit']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12" style="width: 66.666%;">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-shopping-cart me-2"></i>Item Penjualan</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Serial Number</th>
                                <th class="text-end">Harga Beli</th>
                                <th class="text-end">Harga Jual</th>
                                <th class="text-end">Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= e($item['product_name']) ?></strong>
                                    <br><small class="text-muted"><?= e($item['product_code']) ?></small>
                                </td>
                                <td>
                                    <?= e($item['serial_number']) ?>
                                    <?php if ($item['mac_address']): ?>
                                    <br><small class="text-muted"><?= e($item['mac_address']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= formatCurrency($item['buy_price']) ?></td>
                                <td class="text-end">
                                    <?= formatCurrency($item['sell_price']) ?>
                                    <?php if ($item['discount'] > 0): ?>
                                    <br><small class="text-danger">-<?= formatCurrency($item['discount']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-success"><?= formatCurrency($item['profit']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
