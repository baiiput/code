<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Get filter parameters
$dawisId = $_GET['dawis'] ?? null;
$bulan = $_GET['bulan'] ?? date('n');
$tahun = $_GET['tahun'] ?? date('Y');

// Get all dawis for filter
$allDawis = $db->fetchAll("SELECT * FROM dawis ORDER BY id");

// Build query based on filters
$sql = "
    SELECT w.*, d.nama_dawis,
    COALESCE(SUM(CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE 0 END), 0) as total_setoran,
    COALESCE(SUM(CASE WHEN t.jenis_transaksi = 'penarikan' THEN t.jumlah ELSE 0 END), 0) as total_penarikan,
    COALESCE(SUM(CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END), 0) as saldo
    FROM warga w
    JOIN dawis d ON w.dawis_id = d.id
    LEFT JOIN transaksi t ON w.id = t.warga_id
";

$params = [];
$conditions = ["w.status = 'aktif'"];

if ($dawisId) {
    $conditions[] = "w.dawis_id = :dawis_id";
    $params['dawis_id'] = $dawisId;
}

if ($bulan) {
    $conditions[] = "(MONTH(t.tanggal_transaksi) = :bulan OR t.tanggal_transaksi IS NULL)";
    $params['bulan'] = $bulan;
}

if ($tahun) {
    $conditions[] = "(YEAR(t.tanggal_transaksi) = :tahun OR t.tanggal_transaksi IS NULL)";
    $params['tahun'] = $tahun;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY w.id ORDER BY d.nama_dawis, w.nama_lengkap";

$laporanWarga = $db->fetchAll($sql, $params);

// Calculate totals
$grandTotal = [
    'setoran' => 0,
    'penarikan' => 0,
    'saldo' => 0
];

foreach ($laporanWarga as $warga) {
    $grandTotal['setoran'] += $warga['total_setoran'];
    $grandTotal['penarikan'] += $warga['total_penarikan'];
    $grandTotal['saldo'] += $warga['saldo'];
}

// Get dawis name if filtered
$dawisName = 'Semua Dawis';
if ($dawisId) {
    $dawisData = $db->fetchOne("SELECT nama_dawis FROM dawis WHERE id = :id", ['id' => $dawisId]);
    $dawisName = $dawisData['nama_dawis'] ?? 'Semua Dawis';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Per Dawis - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <span>💰</span>
                    <span><?php echo APP_NAME; ?></span>
                </a>
                <div class="header-actions">
                    <button id="theme-toggle" class="theme-toggle">
                        <span id="theme-icon">🌙</span>
                        <span id="theme-text">Dark</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="nav">
        <div class="container">
            <ul class="nav-list">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="laporan.php" class="active">Laporan Per Dawis</a></li>
                <li><a href="warga.php">Data Warga</a></li>
                <li><a href="transaksi.php">Transaksi</a></li>
                <li><a href="pengeluaran.php">Pengeluaran</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <div class="card mb-3">
            <div class="card-header">
                <h2 class="card-title">Filter Laporan</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="laporan.php" class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Dawis</label>
                        <select name="dawis" class="form-control">
                            <option value="">Semua Dawis</option>
                            <?php foreach ($allDawis as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo $dawisId == $d['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['nama_dawis']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Bulan</label>
                        <select name="bulan" class="form-control">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo $bulan == $m ? 'selected' : ''; ?>>
                                    <?php echo getBulanIndo($m); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tahun</label>
                        <select name="tahun" class="form-control">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            🔍 Filter Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="stats-grid mb-3">
            <div class="stat-card success">
                <div class="stat-label">Total Setoran</div>
                <div class="stat-value"><?php echo formatRupiah($grandTotal['setoran']); ?></div>
                <div class="stat-subtitle"><?php echo $dawisName; ?> - <?php echo getBulanIndo($bulan) . ' ' . $tahun; ?></div>
            </div>

            <div class="stat-card danger">
                <div class="stat-label">Total Penarikan</div>
                <div class="stat-value"><?php echo formatRupiah($grandTotal['penarikan']); ?></div>
                <div class="stat-subtitle"><?php echo $dawisName; ?> - <?php echo getBulanIndo($bulan) . ' ' . $tahun; ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Saldo Total</div>
                <div class="stat-value"><?php echo formatRupiah($grandTotal['saldo']); ?></div>
                <div class="stat-subtitle"><?php echo $dawisName; ?> - <?php echo getBulanIndo($bulan) . ' ' . $tahun; ?></div>
            </div>
        </div>

        <!-- Detailed Report -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Laporan Detail Per Warga</h2>
                <button onclick="window.print()" class="btn btn-secondary btn-sm">
                    🖨️ Cetak Laporan
                </button>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Warga</th>
                                <th>Dawis</th>
                                <th>Alamat</th>
                                <th>Total Setoran</th>
                                <th>Total Penarikan</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($laporanWarga)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada data</td>
                            </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($laporanWarga as $warga): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($warga['nama_lengkap']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($warga['nama_dawis']); ?></td>
                                    <td><?php echo htmlspecialchars($warga['alamat'] ?? '-'); ?></td>
                                    <td class="text-success"><?php echo formatRupiah($warga['total_setoran']); ?></td>
                                    <td class="text-danger"><?php echo formatRupiah($warga['total_penarikan']); ?></td>
                                    <td><strong><?php echo formatRupiah($warga['saldo']); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr style="background-color: var(--bg-tertiary); font-weight: bold;">
                                    <td colspan="4" class="text-right">TOTAL:</td>
                                    <td class="text-success"><?php echo formatRupiah($grandTotal['setoran']); ?></td>
                                    <td class="text-danger"><?php echo formatRupiah($grandTotal['penarikan']); ?></td>
                                    <td><?php echo formatRupiah($grandTotal['saldo']); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
</body>
</html>

<style>
@media print {
    .header, .nav, .btn, button {
        display: none !important;
    }

    .card {
        box-shadow: none;
        border: 1px solid #000;
    }

    body {
        background: white;
        color: black;
    }
}
</style>
