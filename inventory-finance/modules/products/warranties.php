<?php
$pageTitle = 'Garansi Produk';
require_once __DIR__ . '/../../templates/header.php';

// Get expiring warranties
$days = $_GET['days'] ?? 30;
$warranties = getExpiringWarranties((int)$days);
?>

<div class="page-header">
    <h1>Garansi Akan Berakhir</h1>
    <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Produk' => BASE_URL . 'modules/products/', 'Garansi' => '']) ?>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-3 align-items-center">
            <label class="form-label mb-0">Tampilkan garansi yang berakhir dalam:</label>
            <select name="days" class="form-control form-select" style="width: auto;" onchange="this.form.submit()">
                <option value="7" <?= $days == 7 ? 'selected' : '' ?>>7 hari</option>
                <option value="14" <?= $days == 14 ? 'selected' : '' ?>>14 hari</option>
                <option value="30" <?= $days == 30 ? 'selected' : '' ?>>30 hari</option>
                <option value="60" <?= $days == 60 ? 'selected' : '' ?>>60 hari</option>
                <option value="90" <?= $days == 90 ? 'selected' : '' ?>>90 hari</option>
            </select>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Serial Number</th>
                        <th>Customer</th>
                        <th>Invoice</th>
                        <th>Garansi Berakhir</th>
                        <th>Sisa Hari</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($warranties)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Tidak ada garansi yang akan berakhir dalam <?= $days ?> hari</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($warranties as $w): ?>
                    <?php $daysLeft = getDueDays($w['warranty_end']); ?>
                    <tr>
                        <td>
                            <strong><?= e($w['product_name']) ?></strong>
                            <br><small class="text-muted"><?= e($w['product_code']) ?></small>
                        </td>
                        <td><?= e($w['serial_number']) ?></td>
                        <td><?= e($w['customer_name']) ?: '-' ?></td>
                        <td><?= e($w['invoice_number']) ?: '-' ?></td>
                        <td><?= formatDate($w['warranty_end']) ?></td>
                        <td>
                            <?php if ($daysLeft <= 7): ?>
                            <span class="badge bg-danger"><?= $daysLeft ?> hari</span>
                            <?php elseif ($daysLeft <= 14): ?>
                            <span class="badge bg-warning"><?= $daysLeft ?> hari</span>
                            <?php else: ?>
                            <span class="badge bg-info"><?= $daysLeft ?> hari</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
