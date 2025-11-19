<?php
$pageTitle = 'Detail Produk';
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ' . BASE_URL . 'modules/products/');
    exit;
}

$product = getById('products', $id);
if (!$product) {
    setFlash('danger', 'Produk tidak ditemukan');
    header('Location: ' . BASE_URL . 'modules/products/');
    exit;
}

// Get category
$category = $product['category_id'] ? getById('categories', $product['category_id']) : null;

// Get serial numbers
$db = getDB();
$serials = $db->prepare("SELECT ps.*, si.invoice_number as stock_in_invoice
    FROM product_serials ps
    LEFT JOIN stock_in_items sii ON ps.stock_in_item_id = sii.id
    LEFT JOIN stock_in si ON sii.stock_in_id = si.id
    WHERE ps.product_id = ?
    ORDER BY ps.created_at DESC");
$serials->execute([$id]);
$serials = $serials->fetchAll();

// Count by status
$statusCounts = [];
foreach ($serials as $serial) {
    $statusCounts[$serial['status']] = ($statusCounts[$serial['status']] ?? 0) + 1;
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><?= e($product['name']) ?></h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Produk' => BASE_URL . 'modules/products/', 'Detail' => '']) ?>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>modules/products/form.php?id=<?= $product['id'] ?>" class="btn btn-outline">
            <i class="fas fa-edit me-2"></i>Edit
        </a>
        <a href="<?= BASE_URL ?>modules/products/" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12 col-4">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-info-circle me-2"></i>Informasi Produk</span>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted" width="40%">Kode</td>
                        <td><strong><?= e($product['code']) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Barcode</td>
                        <td><?= e($product['barcode']) ?: '-' ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kategori</td>
                        <td><?= e($category['name'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Brand</td>
                        <td><?= e($product['brand']) ?: '-' ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Model</td>
                        <td><?= e($product['model']) ?: '-' ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Satuan</td>
                        <td><?= e($product['unit']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Harga Beli</td>
                        <td><?= formatCurrency($product['default_buy_price']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Harga Jual</td>
                        <td><?= formatCurrency($product['default_sell_price']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Garansi</td>
                        <td><?= $product['warranty_months'] ?> bulan</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-chart-pie me-2"></i>Status Stok</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Tersedia</span>
                    <span class="badge bg-success"><?= $statusCounts['available'] ?? 0 ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Terjual</span>
                    <span class="badge bg-primary"><?= $statusCounts['sold'] ?? 0 ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Return</span>
                    <span class="badge bg-warning"><?= $statusCounts['returned'] ?? 0 ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Rusak</span>
                    <span class="badge bg-danger"><?= $statusCounts['damaged'] ?? 0 ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12" style="width: 66.666%;">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-barcode me-2"></i>Daftar Serial Number (<?= count($serials) ?>)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Serial Number</th>
                                <th>MAC Address</th>
                                <th>Kondisi</th>
                                <th>Harga Beli</th>
                                <th>Garansi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($serials)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada serial number</td>
                            </tr>
                            <?php endif; ?>
                            <?php foreach ($serials as $serial): ?>
                            <tr>
                                <td>
                                    <strong><?= e($serial['serial_number']) ?></strong>
                                    <?php if ($serial['stock_in_invoice']): ?>
                                    <br><small class="text-muted"><?= e($serial['stock_in_invoice']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= e($serial['mac_address']) ?: '-' ?>
                                    <?php if ($serial['ip_default']): ?>
                                    <br><small class="text-muted"><?= e($serial['ip_default']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= getStatusBadge($serial['condition'], 'condition') ?></td>
                                <td><?= formatCurrency($serial['buy_price']) ?></td>
                                <td>
                                    <?php if ($serial['warranty_end']): ?>
                                    <?= formatDate($serial['warranty_start']) ?> -<br>
                                    <?= formatDate($serial['warranty_end']) ?>
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
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
