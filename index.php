<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Get dashboard data
$saldoRT = $db->fetchOne("SELECT * FROM saldo_total_rt");
$laporanDawis = $db->fetchAll("SELECT * FROM laporan_per_dawis ORDER BY dawis_id");
$totalWarga = $db->fetchOne("SELECT COUNT(*) as total FROM warga WHERE status = 'aktif'");
$transaksiTerakhir = $db->fetchAll("
    SELECT t.*, w.nama_lengkap, d.nama_dawis
    FROM transaksi t
    JOIN warga w ON t.warga_id = w.id
    JOIN dawis d ON w.dawis_id = d.id
    ORDER BY t.created_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
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
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="laporan.php">Laporan Per Dawis</a></li>
                <li><a href="warga.php">Data Warga</a></li>
                <li><a href="transaksi.php">Transaksi</a></li>
                <li><a href="pengeluaran.php">Pengeluaran</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Jimpitan</div>
                <div class="stat-value"><?php echo formatRupiah($saldoRT['total_jimpitan']); ?></div>
                <div class="stat-subtitle">Total pemasukan dari warga</div>
            </div>

            <div class="stat-card danger">
                <div class="stat-label">Total Pengeluaran</div>
                <div class="stat-value"><?php echo formatRupiah($saldoRT['total_pengeluaran']); ?></div>
                <div class="stat-subtitle">Total pengeluaran RT</div>
            </div>

            <div class="stat-card success">
                <div class="stat-label">Saldo Akhir</div>
                <div class="stat-value"><?php echo formatRupiah($saldoRT['saldo_akhir']); ?></div>
                <div class="stat-subtitle">Saldo kas RT saat ini</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-label">Total Warga Aktif</div>
                <div class="stat-value"><?php echo $totalWarga['total']; ?></div>
                <div class="stat-subtitle">Warga yang aktif jimpitan</div>
            </div>
        </div>

        <!-- Laporan Per Dawis -->
        <div class="card mb-3">
            <div class="card-header">
                <h2 class="card-title">Laporan Per Dawis</h2>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Dawis</th>
                                <th>Jumlah Warga</th>
                                <th>Total Setoran</th>
                                <th>Total Penarikan</th>
                                <th>Saldo</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($laporanDawis as $dawis): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($dawis['nama_dawis']); ?></strong></td>
                                <td><?php echo $dawis['jumlah_warga']; ?> warga</td>
                                <td class="text-success"><?php echo formatRupiah($dawis['total_setoran']); ?></td>
                                <td class="text-danger"><?php echo formatRupiah($dawis['total_penarikan']); ?></td>
                                <td><strong><?php echo formatRupiah($dawis['saldo']); ?></strong></td>
                                <td>
                                    <a href="laporan.php?dawis=<?php echo $dawis['dawis_id']; ?>" class="btn btn-primary btn-sm">
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Transaksi Terakhir -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Transaksi Terakhir</h2>
                <a href="transaksi.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Warga</th>
                                <th>Dawis</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transaksiTerakhir)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada transaksi</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($transaksiTerakhir as $t): ?>
                                <tr>
                                    <td><?php echo formatTanggal($t['tanggal_transaksi']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nama_dawis']); ?></td>
                                    <td>
                                        <?php if ($t['jenis_transaksi'] == 'setoran'): ?>
                                            <span class="badge badge-success">Setoran</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Penarikan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="<?php echo $t['jenis_transaksi'] == 'setoran' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo formatRupiah($t['jumlah']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['keterangan'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
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
