<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../../templates/header.php';

// Get dashboard statistics
$stats = getDashboardStats();
$monthlySales = getMonthlySalesChart(date('Y'));
$topProducts = getTopSellingProducts(5, date('Y-m-01'), date('Y-m-t'));
$lowStockProducts = getLowStockProducts();

// Prepare chart data
$chartLabels = [];
$chartSales = [];
$chartProfit = [];
for ($i = 1; $i <= 12; $i++) {
    $chartLabels[] = getMonthName($i);
    $chartSales[] = $monthlySales[$i]['sales'];
    $chartProfit[] = $monthlySales[$i]['profit'];
}
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <p class="text-muted">Selamat datang, <?= e($currentUser['name']) ?>!</p>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-6 col-3">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-value"><?= formatNumber($stats['total_stock']) ?></div>
            <div class="stat-label">Total Stok Tersedia</div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-value"><?= formatCurrency($stats['today_sales']) ?></div>
            <div class="stat-label">Penjualan Hari Ini</div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-value"><?= formatCurrency($stats['month_sales']) ?></div>
            <div class="stat-label">Penjualan Bulan Ini</div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-coins"></i>
            </div>
            <div class="stat-value"><?= formatCurrency($stats['month_profit']) ?></div>
            <div class="stat-label">Profit Bulan Ini</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <div class="col-12" style="width: 66.666%;">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-chart-area me-2"></i>Grafik Penjualan <?= date('Y') ?></span>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12" style="width: 33.333%;">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-wallet me-2"></i>Ringkasan Keuangan</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Piutang (Belum Dibayar)</span>
                        <span class="fw-bold text-success"><?= formatCurrency($stats['total_receivables']) ?></span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Hutang (Belum Bayar)</span>
                        <span class="fw-bold text-danger"><?= formatCurrency($stats['total_payables']) ?></span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Pengeluaran Bulan Ini</span>
                        <span class="fw-bold text-warning"><?= formatCurrency($stats['month_expenses']) ?></span>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="fw-semibold">Net Profit Bulan Ini</span>
                    <span class="fw-bold <?= ($stats['month_profit'] - $stats['month_expenses']) >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= formatCurrency($stats['month_profit'] - $stats['month_expenses']) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-info-circle me-2"></i>Quick Info</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Produk</span>
                    <span class="fw-bold"><?= formatNumber($stats['total_products']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Customer</span>
                    <span class="fw-bold"><?= formatNumber($stats['total_customers']) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Total Supplier</span>
                    <span class="fw-bold"><?= formatNumber($stats['total_suppliers']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div class="row">
    <div class="col-12 col-6">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-trophy me-2"></i>Produk Terlaris Bulan Ini</span>
                <a href="<?= BASE_URL ?>modules/reports/sales.php" class="btn btn-sm btn-outline">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-center">Terjual</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topProducts)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">Belum ada data penjualan</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td>
                                    <strong><?= e($product['name']) ?></strong><br>
                                    <small class="text-muted"><?= e($product['code']) ?></small>
                                </td>
                                <td class="text-center"><?= formatNumber($product['total_sold']) ?></td>
                                <td class="text-end"><?= formatCurrency($product['total_revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-6">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Stok Menipis</span>
                <a href="<?= BASE_URL ?>modules/products/?filter=low_stock" class="btn btn-sm btn-outline">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Min</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lowStockProducts)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">Semua stok aman</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach (array_slice($lowStockProducts, 0, 5) as $product): ?>
                            <tr>
                                <td>
                                    <strong><?= e($product['name']) ?></strong><br>
                                    <small class="text-muted"><?= e($product['code']) ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $product['current_stock'] == 0 ? 'bg-danger' : 'bg-warning' ?>">
                                        <?= formatNumber($product['current_stock']) ?>
                                    </span>
                                </td>
                                <td class="text-center"><?= formatNumber($product['min_stock']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: {$labels = json_encode($chartLabels)},
        datasets: [
            {
                label: 'Penjualan',
                data: {$sales = json_encode($chartSales)},
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Profit',
                data: {$profit = json_encode($chartProfit)},
                borderColor: '#27ae60',
                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});
</script>
SCRIPT;
?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
