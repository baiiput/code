<?php
require_once 'config/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include 'views/navbar.php'; ?>

    <div class="main-container">
        <?php include 'views/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Dashboard</h1>
                <div class="page-actions">
                    <select id="periodSelector" class="form-select">
                        <option value="day">Hari Ini</option>
                        <option value="week">7 Hari Terakhir</option>
                        <option value="month" selected>30 Hari Terakhir</option>
                        <option value="year">1 Tahun Terakhir</option>
                    </select>
                    <button id="refreshBtn" class="btn btn-secondary">
                        <span class="icon">🔄</span> Refresh
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="cards-grid">
                <div class="card card-success">
                    <div class="card-icon">💰</div>
                    <div class="card-content">
                        <h3>Total Penjualan</h3>
                        <p class="card-value" id="totalSales">Rp 0</p>
                        <p class="card-label" id="salesCount">0 transaksi</p>
                    </div>
                </div>

                <div class="card card-danger">
                    <div class="card-icon">🛒</div>
                    <div class="card-content">
                        <h3>Total Pembelian</h3>
                        <p class="card-value" id="totalPurchases">Rp 0</p>
                        <p class="card-label" id="purchasesCount">0 transaksi</p>
                    </div>
                </div>

                <div class="card card-warning">
                    <div class="card-icon">📊</div>
                    <div class="card-content">
                        <h3>Total Pengeluaran</h3>
                        <p class="card-value" id="totalExpenses">Rp 0</p>
                        <p class="card-label" id="expensesCount">0 transaksi</p>
                    </div>
                </div>

                <div class="card card-info">
                    <div class="card-icon">📈</div>
                    <div class="card-content">
                        <h3>Profit Bersih</h3>
                        <p class="card-value" id="netProfit">Rp 0</p>
                        <p class="card-label" id="profitMargin">0% margin</p>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="charts-row">
                <div class="card chart-card">
                    <div class="card-header">
                        <h3>Tren Penjualan (12 Bulan Terakhir)</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>

                <div class="card chart-card">
                    <div class="card-header">
                        <h3>Pengeluaran per Kategori</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="expensesChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Data Tables Row -->
            <div class="tables-row">
                <div class="card table-card">
                    <div class="card-header">
                        <h3>Produk Terlaris</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Kategori</th>
                                        <th>Terjual</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody id="topProductsTable">
                                    <tr>
                                        <td colspan="4" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card table-card">
                    <div class="card-header">
                        <h3>Stok Menipis</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Produk</th>
                                        <th>Stok</th>
                                        <th>Min</th>
                                    </tr>
                                </thead>
                                <tbody id="lowStockTable">
                                    <tr>
                                        <td colspan="4" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sales & Pending Payments -->
            <div class="tables-row">
                <div class="card table-card">
                    <div class="card-header">
                        <h3>Penjualan Terbaru</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Tanggal</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="recentSalesTable">
                                    <tr>
                                        <td colspan="5" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card table-card">
                    <div class="card-header">
                        <h3>Piutang Tertunda</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Sisa</th>
                                    </tr>
                                </thead>
                                <tbody id="pendingPaymentsTable">
                                    <tr>
                                        <td colspan="4" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
