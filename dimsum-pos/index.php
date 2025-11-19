<?php
require_once 'config/config.php';
Auth::requireLogin();

$pageTitle = 'Dashboard';

// Get user's cabang filter
$cabangId = Auth::cabangId();
$cabangFilter = $cabangId ? "AND t.cabang_id = $cabangId" : "";

// Today's statistics
$today = date('Y-m-d');

// Total transaksi hari ini
$todayStats = $db->fetch("
    SELECT
        COUNT(*) as total_transaksi,
        COALESCE(SUM(total), 0) as total_penjualan
    FROM transaksi t
    WHERE DATE(t.created_at) = ? AND t.status = 'completed' $cabangFilter
", [$today]);

// Total transaksi bulan ini
$monthStats = $db->fetch("
    SELECT
        COUNT(*) as total_transaksi,
        COALESCE(SUM(total), 0) as total_penjualan
    FROM transaksi t
    WHERE MONTH(t.created_at) = MONTH(CURRENT_DATE())
    AND YEAR(t.created_at) = YEAR(CURRENT_DATE())
    AND t.status = 'completed' $cabangFilter
");

// Total menu
$totalMenu = $db->count('menu', 'is_active = 1');

// Total cabang (for super admin/owner)
$totalCabang = $db->count('cabang', 'is_active = 1');

// Recent transactions
$recentTransactions = $db->fetchAll("
    SELECT t.*, c.nama as cabang_nama, u.nama_lengkap as kasir
    FROM transaksi t
    JOIN cabang c ON t.cabang_id = c.id
    JOIN users u ON t.user_id = u.id
    WHERE t.status = 'completed' $cabangFilter
    ORDER BY t.created_at DESC
    LIMIT 10
");

// Best selling products this month
$bestSellers = $db->fetchAll("
    SELECT
        td.nama_menu,
        td.nama_variasi,
        SUM(td.qty) as total_qty,
        SUM(td.subtotal) as total_sales
    FROM transaksi_detail td
    JOIN transaksi t ON td.transaksi_id = t.id
    WHERE MONTH(t.created_at) = MONTH(CURRENT_DATE())
    AND YEAR(t.created_at) = YEAR(CURRENT_DATE())
    AND t.status = 'completed' $cabangFilter
    GROUP BY td.nama_menu, td.nama_variasi
    ORDER BY total_qty DESC
    LIMIT 5
");

// Sales data for chart (last 7 days)
$salesChart = $db->fetchAll("
    SELECT
        DATE(created_at) as tanggal,
        SUM(total) as total
    FROM transaksi t
    WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
    AND status = 'completed' $cabangFilter
    GROUP BY DATE(created_at)
    ORDER BY tanggal ASC
");

include 'includes/header.php';
?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary-subtle text-primary me-3">
                        <i class="bi bi-cart-check"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Transaksi Hari Ini</div>
                        <div class="fs-4 fw-bold"><?= $todayStats['total_transaksi'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success-subtle text-success me-3">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Penjualan Hari Ini</div>
                        <div class="fs-5 fw-bold"><?= Helper::rupiah($todayStats['total_penjualan']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-info-subtle text-info me-3">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Penjualan Bulan Ini</div>
                        <div class="fs-5 fw-bold"><?= Helper::rupiah($monthStats['total_penjualan']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-warning-subtle text-warning me-3">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Menu Aktif</div>
                        <div class="fs-4 fw-bold"><?= $totalMenu ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Sales Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Grafik Penjualan 7 Hari Terakhir</h6>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Best Sellers -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Menu Terlaris Bulan Ini</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($bestSellers)): ?>
                <div class="p-3 text-center text-muted">
                    <i class="bi bi-inbox fs-1"></i>
                    <p class="mb-0 mt-2">Belum ada data</p>
                </div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($bestSellers as $index => $item): ?>
                    <li class="list-group-item d-flex align-items-center">
                        <span class="badge bg-primary me-3"><?= $index + 1 ?></span>
                        <div class="flex-grow-1">
                            <div class="fw-medium"><?= htmlspecialchars($item['nama_menu']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($item['nama_variasi']) ?></small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold"><?= $item['total_qty'] ?></div>
                            <small class="text-muted">terjual</small>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Transaksi Terbaru</h6>
        <a href="<?= BASE_URL ?>pages/transaksi.php" class="btn btn-sm btn-outline-primary">
            Lihat Semua
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>Waktu</th>
                    <?php if (!$cabangId): ?><th>Cabang</th><?php endif; ?>
                    <th>Kasir</th>
                    <th>Tipe</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentTransactions)): ?>
                <tr>
                    <td colspan="<?= $cabangId ? 5 : 6 ?>" class="text-center py-4 text-muted">
                        Belum ada transaksi
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($recentTransactions as $trx): ?>
                <tr>
                    <td><code><?= $trx['no_transaksi'] ?></code></td>
                    <td><?= date('d/m H:i', strtotime($trx['created_at'])) ?></td>
                    <?php if (!$cabangId): ?>
                    <td><?= htmlspecialchars($trx['cabang_nama']) ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($trx['kasir']) ?></td>
                    <td>
                        <span class="badge bg-<?= $trx['tipe_order'] === 'dine_in' ? 'info' : 'secondary' ?>">
                            <?= $trx['tipe_order'] === 'dine_in' ? 'Dine In' : 'Take Away' ?>
                        </span>
                    </td>
                    <td class="text-end fw-medium"><?= Helper::rupiah($trx['total']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$extraJs = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const salesData = ' . json_encode($salesChart) . ';
const labels = salesData.map(item => {
    const date = new Date(item.tanggal);
    return date.toLocaleDateString("id-ID", { day: "numeric", month: "short" });
});
const data = salesData.map(item => item.total);

const ctx = document.getElementById("salesChart").getContext("2d");
new Chart(ctx, {
    type: "line",
    data: {
        labels: labels,
        datasets: [{
            label: "Penjualan",
            data: data,
            borderColor: "#0d6efd",
            backgroundColor: "rgba(13, 110, 253, 0.1)",
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return "Rp " + value.toLocaleString("id-ID");
                    }
                }
            }
        }
    }
});
</script>';

include 'includes/footer.php';
?>
