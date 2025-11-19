<?php
$pageTitle = 'Laporan Penjualan';
require_once __DIR__ . '/../../templates/header.php';

// Date filters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

$db = getDB();

// Get all sales in period
$sales = $db->prepare("SELECT s.*, c.name as customer_name, u.name as created_by_name,
    (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.created_by = u.id
    WHERE s.date BETWEEN ? AND ?
    ORDER BY s.date DESC, s.id DESC");
$sales->execute([$startDate, $endDate]);
$sales = $sales->fetchAll();

// Summary
$summary = $db->prepare("SELECT
    COUNT(*) as total_transactions,
    COALESCE(SUM(grand_total), 0) as total_sales,
    COALESCE(SUM(profit), 0) as total_profit,
    COALESCE(AVG(grand_total), 0) as avg_transaction
    FROM sales WHERE date BETWEEN ? AND ?");
$summary->execute([$startDate, $endDate]);
$summary = $summary->fetch();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Laporan Penjualan</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Laporan' => BASE_URL . 'modules/reports/', 'Penjualan' => '']) ?>
    </div>
    <a href="<?= BASE_URL ?>modules/reports/" class="btn btn-outline">
        <i class="fas fa-arrow-left me-2"></i>Kembali
    </a>
</div>

<!-- Date Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex flex-wrap gap-3 align-items-center">
            <div class="form-group mb-0">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
            </div>
            <div class="form-group mb-0">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
            </div>
            <div class="form-group mb-0" style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
                <button type="button" class="btn btn-outline" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-6 col-3">
        <div class="stat-card primary">
            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
            <div class="stat-value"><?= $summary['total_transactions'] ?></div>
            <div class="stat-label">Total Transaksi</div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card info">
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-value"><?= formatCurrency($summary['total_sales']) ?></div>
            <div class="stat-label">Total Penjualan</div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card success">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value"><?= formatCurrency($summary['total_profit']) ?></div>
            <div class="stat-label">Total Profit</div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fas fa-calculator"></i></div>
            <div class="stat-value"><?= formatCurrency($summary['avg_transaction']) ?></div>
            <div class="stat-label">Rata-rata Transaksi</div>
        </div>
    </div>
</div>

<!-- Sales Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2"></i>Detail Penjualan</span>
        <span class="badge bg-primary"><?= count($sales) ?> transaksi</span>
    </div>
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
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Tidak ada data penjualan dalam periode ini</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td>
                            <a href="<?= BASE_URL ?>modules/stock-out/view.php?id=<?= $sale['id'] ?>">
                                <strong><?= e($sale['invoice_number']) ?></strong>
                            </a>
                        </td>
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
                        <td><?= getStatusBadge($sale['payment_status'], 'payment') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if (!empty($sales)): ?>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end">TOTAL:</td>
                        <td class="text-end"><?= formatCurrency($summary['total_sales']) ?></td>
                        <td class="text-end text-success"><?= formatCurrency($summary['total_profit']) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
