<?php
require_once '../config/config.php';
Auth::requireRole(['super_admin', 'owner', 'admin_cabang']);

$pageTitle = 'Laporan Penjualan';

// Get filters
$periode = $_GET['periode'] ?? 'harian';
$tanggalMulai = $_GET['tanggal_mulai'] ?? date('Y-m-d');
$tanggalSelesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');
$cabangFilter = $_GET['cabang'] ?? '';

// Adjust dates based on periode
if ($periode === 'mingguan') {
    $tanggalMulai = date('Y-m-d', strtotime('monday this week'));
    $tanggalSelesai = date('Y-m-d', strtotime('sunday this week'));
} elseif ($periode === 'bulanan') {
    $tanggalMulai = date('Y-m-01');
    $tanggalSelesai = date('Y-m-t');
}

// Build query conditions
$where = "t.created_at BETWEEN ? AND ?";
$params = [$tanggalMulai . ' 00:00:00', $tanggalSelesai . ' 23:59:59'];

// Filter by cabang
$userCabangId = Auth::cabangId();
if ($userCabangId) {
    $where .= " AND t.cabang_id = ?";
    $params[] = $userCabangId;
} elseif ($cabangFilter) {
    $where .= " AND t.cabang_id = ?";
    $params[] = $cabangFilter;
}

// Get summary statistics
$summary = $db->fetch("
    SELECT
        COUNT(*) as total_transaksi,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END), 0) as total_penjualan,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN diskon_nominal ELSE 0 END), 0) as total_diskon,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN pajak_nominal ELSE 0 END), 0) as total_pajak,
        COALESCE(AVG(CASE WHEN status = 'completed' THEN total ELSE NULL END), 0) as rata_rata
    FROM transaksi t
    WHERE $where AND status = 'completed'
", $params);

// Get sales by date
$salesByDate = $db->fetchAll("
    SELECT
        DATE(t.created_at) as tanggal,
        COUNT(*) as jumlah_transaksi,
        SUM(total) as total_penjualan
    FROM transaksi t
    WHERE $where AND status = 'completed'
    GROUP BY DATE(t.created_at)
    ORDER BY tanggal ASC
", $params);

// Get sales by payment method
$salesByPayment = $db->fetchAll("
    SELECT
        mp.nama as metode,
        COUNT(*) as jumlah,
        SUM(t.total) as total
    FROM transaksi t
    JOIN metode_pembayaran mp ON t.metode_pembayaran_id = mp.id
    WHERE $where AND t.status = 'completed'
    GROUP BY t.metode_pembayaran_id
    ORDER BY total DESC
", $params);

// Get best selling products
$bestSellers = $db->fetchAll("
    SELECT
        td.nama_menu,
        td.nama_variasi,
        SUM(td.qty) as total_qty,
        SUM(td.subtotal) as total_sales
    FROM transaksi_detail td
    JOIN transaksi t ON td.transaksi_id = t.id
    WHERE $where AND t.status = 'completed'
    GROUP BY td.nama_menu, td.nama_variasi
    ORDER BY total_qty DESC
    LIMIT 10
", $params);

// Get sales by cabang (for super admin/owner)
$salesByCabang = [];
if (!$userCabangId) {
    $salesByCabang = $db->fetchAll("
        SELECT
            c.nama as cabang,
            COUNT(*) as jumlah_transaksi,
            SUM(t.total) as total_penjualan
        FROM transaksi t
        JOIN cabang c ON t.cabang_id = c.id
        WHERE t.created_at BETWEEN ? AND ? AND t.status = 'completed'
        GROUP BY t.cabang_id
        ORDER BY total_penjualan DESC
    ", [$tanggalMulai . ' 00:00:00', $tanggalSelesai . ' 23:59:59']);
}

// Get cabang list for filter
$cabangs = $db->fetchAll("SELECT id, nama FROM cabang WHERE is_active = 1 ORDER BY nama ASC");

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Laporan Penjualan</h4>
    <div>
        <a href="export-laporan.php?<?= http_build_query($_GET) ?>&format=excel" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
        </a>
        <a href="export-laporan.php?<?= http_build_query($_GET) ?>&format=pdf" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
        </a>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Periode</label>
                <select class="form-select" name="periode" id="periodeSelect" onchange="updateDates()">
                    <option value="harian" <?= $periode === 'harian' ? 'selected' : '' ?>>Harian</option>
                    <option value="mingguan" <?= $periode === 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
                    <option value="bulanan" <?= $periode === 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                    <option value="custom" <?= $periode === 'custom' ? 'selected' : '' ?>>Custom</option>
                </select>
            </div>
            <div class="col-md-2" id="dateFields">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" class="form-control" name="tanggal_mulai" value="<?= $tanggalMulai ?>">
            </div>
            <div class="col-md-2" id="dateFieldEnd">
                <label class="form-label">Tanggal Selesai</label>
                <input type="date" class="form-control" name="tanggal_selesai" value="<?= $tanggalSelesai ?>">
            </div>
            <?php if (!$userCabangId): ?>
            <div class="col-md-2">
                <label class="form-label">Cabang</label>
                <select class="form-select" name="cabang">
                    <option value="">Semua Cabang</option>
                    <?php foreach ($cabangs as $cabang): ?>
                    <option value="<?= $cabang['id'] ?>" <?= $cabangFilter == $cabang['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cabang['nama']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="small opacity-75">Total Transaksi</div>
                <div class="fs-4 fw-bold"><?= number_format($summary['total_transaksi']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="small opacity-75">Total Penjualan</div>
                <div class="fs-5 fw-bold"><?= Helper::rupiah($summary['total_penjualan']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="small opacity-75">Rata-rata Transaksi</div>
                <div class="fs-5 fw-bold"><?= Helper::rupiah($summary['rata_rata']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="small opacity-75">Total Pajak</div>
                <div class="fs-5 fw-bold"><?= Helper::rupiah($summary['total_pajak']) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Sales Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Grafik Penjualan</h6>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Payment Method -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Metode Pembayaran</h6>
            </div>
            <div class="card-body">
                <canvas id="paymentChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <!-- Best Sellers -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Produk Terlaris</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Menu</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bestSellers)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada data</td></tr>
                        <?php else: ?>
                        <?php foreach ($bestSellers as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <?= htmlspecialchars($item['nama_menu']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($item['nama_variasi']) ?></small>
                            </td>
                            <td class="text-center"><?= number_format($item['total_qty']) ?></td>
                            <td class="text-end"><?= Helper::rupiah($item['total_sales']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sales by Cabang -->
    <?php if (!$userCabangId && !empty($salesByCabang)): ?>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Penjualan per Cabang</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Cabang</th>
                            <th class="text-center">Transaksi</th>
                            <th class="text-end">Total Penjualan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesByCabang as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['cabang']) ?></td>
                            <td class="text-center"><?= number_format($item['jumlah_transaksi']) ?></td>
                            <td class="text-end fw-medium"><?= Helper::rupiah($item['total_penjualan']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$extraJs = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const salesData = ' . json_encode($salesByDate) . ';
const salesCtx = document.getElementById("salesChart").getContext("2d");
new Chart(salesCtx, {
    type: "bar",
    data: {
        labels: salesData.map(d => {
            const date = new Date(d.tanggal);
            return date.toLocaleDateString("id-ID", { day: "numeric", month: "short" });
        }),
        datasets: [{
            label: "Penjualan",
            data: salesData.map(d => d.total_penjualan),
            backgroundColor: "rgba(13, 110, 253, 0.8)"
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => "Rp " + (value/1000000).toFixed(1) + "jt"
                }
            }
        }
    }
});

// Payment Method Chart
const paymentData = ' . json_encode($salesByPayment) . ';
const paymentCtx = document.getElementById("paymentChart").getContext("2d");
new Chart(paymentCtx, {
    type: "doughnut",
    data: {
        labels: paymentData.map(d => d.metode),
        datasets: [{
            data: paymentData.map(d => d.total),
            backgroundColor: ["#0d6efd", "#198754", "#ffc107", "#dc3545", "#6f42c1"]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: "bottom" }
        }
    }
});

function updateDates() {
    const periode = document.getElementById("periodeSelect").value;
    const dateFields = document.getElementById("dateFields");
    const dateFieldEnd = document.getElementById("dateFieldEnd");

    if (periode === "custom" || periode === "harian") {
        dateFields.style.display = "block";
        dateFieldEnd.style.display = periode === "custom" ? "block" : "none";
    } else {
        dateFields.style.display = "none";
        dateFieldEnd.style.display = "none";
    }
}
updateDates();
</script>';

include '../includes/footer.php';
?>
