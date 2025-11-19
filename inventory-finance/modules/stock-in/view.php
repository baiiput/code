<?php
$pageTitle = 'Detail Stok Masuk';
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ' . BASE_URL . 'modules/stock-in/');
    exit;
}

$db = getDB();

// Get stock in header
$stockIn = $db->prepare("SELECT si.*, s.name as supplier_name, s.phone as supplier_phone,
    u.name as created_by_name
    FROM stock_in si
    LEFT JOIN suppliers s ON si.supplier_id = s.id
    LEFT JOIN users u ON si.created_by = u.id
    WHERE si.id = ?");
$stockIn->execute([$id]);
$stockIn = $stockIn->fetch();

if (!$stockIn) {
    setFlash('danger', 'Data tidak ditemukan');
    header('Location: ' . BASE_URL . 'modules/stock-in/');
    exit;
}

// Get items with serials
$items = $db->prepare("SELECT sii.*, p.name as product_name, p.code as product_code
    FROM stock_in_items sii
    JOIN products p ON sii.product_id = p.id
    WHERE sii.stock_in_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

// Get serials for each item
foreach ($items as &$item) {
    $serials = $db->prepare("SELECT * FROM product_serials WHERE stock_in_item_id = ?");
    $serials->execute([$item['id']]);
    $item['serials'] = $serials->fetchAll();
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Detail Stok Masuk</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Stok Masuk' => BASE_URL . 'modules/stock-in/', 'Detail' => '']) ?>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline" onclick="window.print()">
            <i class="fas fa-print me-2"></i>Print
        </button>
        <a href="<?= BASE_URL ?>modules/stock-in/" class="btn btn-secondary">
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
                        <td><strong><?= e($stockIn['invoice_number']) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal</td>
                        <td><?= formatDate($stockIn['date']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Supplier</td>
                        <td>
                            <?= e($stockIn['supplier_name']) ?: '-' ?>
                            <?php if ($stockIn['supplier_phone']): ?>
                            <br><small class="text-muted"><?= e($stockIn['supplier_phone']) ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status Bayar</td>
                        <td><?= getStatusBadge($stockIn['payment_status'], 'payment') ?></td>
                    </tr>
                    <?php if ($stockIn['due_date']): ?>
                    <tr>
                        <td class="text-muted">Jatuh Tempo</td>
                        <td><?= formatDueDate($stockIn['due_date']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted">Created By</td>
                        <td><?= e($stockIn['created_by_name']) ?></td>
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
                    <span><?= formatCurrency($stockIn['total_amount']) ?></span>
                </div>
                <?php if ($stockIn['discount'] > 0): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Diskon</span>
                    <span class="text-danger">-<?= formatCurrency($stockIn['discount']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($stockIn['tax'] > 0): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pajak</span>
                    <span><?= formatCurrency($stockIn['tax']) ?></span>
                </div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Grand Total</span>
                    <span class="fw-bold text-primary"><?= formatCurrency($stockIn['grand_total']) ?></span>
                </div>
                <?php if ($stockIn['paid_amount'] > 0): ?>
                <div class="d-flex justify-content-between mt-2">
                    <span>Dibayar</span>
                    <span class="text-success"><?= formatCurrency($stockIn['paid_amount']) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Sisa</span>
                    <span class="text-danger"><?= formatCurrency($stockIn['grand_total'] - $stockIn['paid_amount']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12" style="width: 66.666%;">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-boxes me-2"></i>Item Produk</span>
            </div>
            <div class="card-body p-0">
                <?php foreach ($items as $item): ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong><?= e($item['product_name']) ?></strong>
                            <br><small class="text-muted"><?= e($item['product_code']) ?></small>
                        </div>
                        <div class="text-end">
                            <span><?= $item['quantity'] ?> x <?= formatCurrency($item['buy_price']) ?></span>
                            <br><strong><?= formatCurrency($item['subtotal']) ?></strong>
                        </div>
                    </div>

                    <?php if (!empty($item['serials'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size: 0.8rem;">
                            <thead>
                                <tr>
                                    <th>Serial Number</th>
                                    <th>MAC Address</th>
                                    <th>IP Default</th>
                                    <th>Kondisi</th>
                                    <th>Garansi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($item['serials'] as $serial): ?>
                                <tr>
                                    <td><strong><?= e($serial['serial_number']) ?></strong></td>
                                    <td><?= e($serial['mac_address']) ?: '-' ?></td>
                                    <td><?= e($serial['ip_default']) ?: '-' ?></td>
                                    <td><?= getStatusBadge($serial['condition'], 'condition') ?></td>
                                    <td>
                                        <?php if ($serial['warranty_end']): ?>
                                        s/d <?= formatDate($serial['warranty_end']) ?>
                                        <?php else: ?>
                                        -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= getStatusBadge($serial['status'], 'stock') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($stockIn['notes']): ?>
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-sticky-note me-2"></i>Catatan</span>
            </div>
            <div class="card-body">
                <?= nl2br(e($stockIn['notes'])) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
