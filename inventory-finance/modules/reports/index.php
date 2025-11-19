<?php
$pageTitle = 'Laporan';
require_once __DIR__ . '/../../templates/header.php';

// Date filters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

$db = getDB();

// Sales report
$salesReport = $db->prepare("SELECT
    COUNT(*) as total_transactions,
    COALESCE(SUM(grand_total), 0) as total_sales,
    COALESCE(SUM(profit), 0) as total_profit,
    COALESCE(AVG(grand_total), 0) as avg_transaction
    FROM sales WHERE date BETWEEN ? AND ?");
$salesReport->execute([$startDate, $endDate]);
$salesReport = $salesReport->fetch();

// Expenses report
$expensesReport = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date BETWEEN ? AND ?");
$expensesReport->execute([$startDate, $endDate]);
$totalExpenses = $expensesReport->fetch()['total'];

// Stock In report
$stockInReport = $db->prepare("SELECT COALESCE(SUM(grand_total), 0) as total FROM stock_in WHERE date BETWEEN ? AND ?");
$stockInReport->execute([$startDate, $endDate]);
$totalStockIn = $stockInReport->fetch()['total'];

// Net profit
$netProfit = $salesReport['total_profit'] - $totalExpenses;

// Sales by payment method
$salesByMethod = $db->prepare("SELECT payment_method, COUNT(*) as count, SUM(grand_total) as total
    FROM sales WHERE date BETWEEN ? AND ?
    GROUP BY payment_method ORDER BY total DESC");
$salesByMethod->execute([$startDate, $endDate]);
$salesByMethod = $salesByMethod->fetchAll();

// Sales by category
$salesByCategory = $db->prepare("SELECT c.name as category, SUM(si.subtotal) as total, SUM(si.quantity) as qty
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN sales s ON si.sale_id = s.id
    WHERE s.date BETWEEN ? AND ?
    GROUP BY c.id ORDER BY total DESC LIMIT 10");
$salesByCategory->execute([$startDate, $endDate]);
$salesByCategory = $salesByCategory->fetchAll();

// Expenses by category
$expensesByCategory = $db->prepare("SELECT ec.name as category, SUM(e.amount) as total
    FROM expenses e
    LEFT JOIN expense_categories ec ON e.category_id = ec.id
    WHERE e.date BETWEEN ? AND ?
    GROUP BY e.category_id ORDER BY total DESC");
$expensesByCategory->execute([$startDate, $endDate]);
$expensesByCategory = $expensesByCategory->fetchAll();

// Daily sales for chart
$dailySales = $db->prepare("SELECT DATE(date) as day, SUM(grand_total) as sales, SUM(profit) as profit
    FROM sales WHERE date BETWEEN ? AND ?
    GROUP BY DATE(date) ORDER BY day");
$dailySales->execute([$startDate, $endDate]);
$dailySales = $dailySales->fetchAll();

$chartLabels = array_column($dailySales, 'day');
$chartSales = array_column($dailySales, 'sales');
$chartProfit = array_column($dailySales, 'profit');
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1>Laporan Keuangan</h1>
        <?= breadcrumb(['Dashboard' => BASE_URL . 'modules/dashboard/', 'Laporan' => '']) ?>
    </div>
    <a href="<?= BASE_URL ?>modules/reports/sales.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-outline">
        <i class="fas fa-file-alt me-2"></i>Detail Penjualan
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
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row">
    <div class="col-6 col-3">
        <div class="stat-card primary">
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-value"><?= formatCurrency($salesReport['total_sales']) ?></div>
            <div class="stat-label">Total Penjualan</div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card success">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value"><?= formatCurrency($salesReport['total_profit']) ?></div>
            <div class="stat-label">Total Profit</div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <div class="stat-value"><?= formatCurrency($totalExpenses) ?></div>
            <div class="stat-label">Total Pengeluaran</div>
        </div>
    </div>
    <div class="col-6 col-3">
        <div class="stat-card <?= $netProfit >= 0 ? 'info' : 'danger' ?>">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-value"><?= formatCurrency($netProfit) ?></div>
            <div class="stat-label">Net Profit</div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="row">
    <div class="col-12" style="width: 66.666%;">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-chart-area me-2"></i>Grafik Penjualan</span>
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
                <span><i class="fas fa-credit-card me-2"></i>Per Metode Bayar</span>
            </div>
            <div class="card-body">
                <?php foreach ($salesByMethod as $method): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span><?= getPaymentMethodLabel($method['payment_method']) ?></span>
                    <span class="fw-bold"><?= formatCurrency($method['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tables -->
<div class="row">
    <div class="col-12 col-6">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-tags me-2"></i>Penjualan per Kategori</span>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesByCategory as $cat): ?>
                        <tr>
                            <td><?= e($cat['category']) ?></td>
                            <td class="text-center"><?= $cat['qty'] ?></td>
                            <td class="text-end"><?= formatCurrency($cat['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-6">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-wallet me-2"></i>Pengeluaran per Kategori</span>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expensesByCategory as $cat): ?>
                        <tr>
                            <td><?= e($cat['category'] ?? 'Lainnya') ?></td>
                            <td class="text-end text-danger"><?= formatCurrency($cat['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$labelsJson = json_encode($chartLabels);
$salesJson = json_encode(array_map('floatval', $chartSales));
$profitJson = json_encode(array_map('floatval', $chartProfit));

$pageScripts = <<<SCRIPT
<script>
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: {$labelsJson},
        datasets: [
            {
                label: 'Penjualan',
                data: {$salesJson},
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Profit',
                data: {$profitJson},
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
