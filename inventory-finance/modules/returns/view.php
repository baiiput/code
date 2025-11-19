<?php
$pageTitle = 'Detail Return';
require_once __DIR__ . '/../../templates/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ' . BASE_URL . 'modules/returns/');
    exit;
}

$db = getDB();

$return = $db->prepare("SELECT r.*, c.name as customer_name, s.name as supplier_name, u.name as created_by_name
    FROM returns r
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN suppliers s ON r.supplier_id = s.id
    LEFT JOIN users u ON r.created_by = u.id
    WHERE r.id = ?");
$return->execute([$id]);
$return = $return->fetch();

if (!$return) {
    setFlash('danger', 'Data tidak ditemukan');
    header('Location: ' . BASE_URL . 'modules/returns/');
    exit;
}

$items = $db->prepare("SELECT ri.*, p.name as product_name, p.code as product_code,
    ps.serial_number, ps.mac_address
    FROM return_items ri
    JOIN products p ON ri.product_id = p.id
    LEFT JOIN product_serials ps ON ri.serial_id = ps.id
    WHERE ri.return_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Detail Return</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Return' => BASE_URL . 'modules/returns/', 'Detail' => '']) ?>
    </div>
    <a href="<?= BASE_URL ?>modules/returns/" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Kembali
    </a>
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
                        <td class="text-muted">No. Return</td>
                        <td><strong><?= e($return['return_number']) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal</td>
                        <td><?= formatDate($return['date']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tipe</td>
                        <td><?= $return['type'] == 'customer' ? 'Dari Customer' : 'Ke Supplier' ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted"><?= $return['type'] == 'customer' ? 'Customer' : 'Supplier' ?></td>
                        <td><?= e($return['customer_name'] ?? $return['supplier_name'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td><?= getStatusBadge($return['status'], 'return') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total</td>
                        <td class="fw-bold"><?= formatCurrency($return['total_amount']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <?php if ($return['reason']): ?>
        <div class="card">
            <div class="card-header">Alasan</div>
            <div class="card-body"><?= nl2br(e($return['reason'])) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-12" style="width: 66.666%;">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-undo me-2"></i>Item Return</span>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Serial Number</th>
                            <th class="text-end">Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?= e($item['product_name']) ?>
                                <br><small class="text-muted"><?= e($item['product_code']) ?></small>
                            </td>
                            <td><?= e($item['serial_number']) ?></td>
                            <td class="text-end"><?= formatCurrency($item['subtotal']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
