<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Get warga yang kurang bayar (status warning dan alert)
$wargaAlert = $db->fetchAll("
    SELECT *
    FROM progress_pembayaran_warga
    WHERE status_pembayaran IN ('warning', 'alert')
    ORDER BY persentase_pencapaian ASC, nama_lengkap
");

// Get jumlah warning dan alert
$countAlert = 0;
$countWarning = 0;
foreach ($wargaAlert as $w) {
    if ($w['status_pembayaran'] == 'alert') $countAlert++;
    if ($w['status_pembayaran'] == 'warning') $countWarning++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi Pembayaran - <?php echo APP_NAME; ?></title>
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
                <li><a href="laporan.php">Laporan Per Dawis</a></li>
                <li><a href="warga.php">Data Warga</a></li>
                <li><a href="transaksi.php">Transaksi</a></li>
                <li><a href="pengeluaran.php">Pengeluaran</a></li>
                <li><a href="alert.php" class="active">Notifikasi</a></li>
                <li><a href="settings.php">Pengaturan</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <!-- Summary -->
        <div class="stats-grid mb-3">
            <div class="stat-card danger">
                <div class="stat-label">🚨 Alert</div>
                <div class="stat-value"><?php echo $countAlert; ?></div>
                <div class="stat-subtitle">Warga pembayaran &lt; 75%</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-label">⚠️ Warning</div>
                <div class="stat-value"><?php echo $countWarning; ?></div>
                <div class="stat-subtitle">Warga pembayaran 75-89%</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">📊 Total Perlu Perhatian</div>
                <div class="stat-value"><?php echo $countAlert + $countWarning; ?></div>
                <div class="stat-subtitle">Dari total warga aktif</div>
            </div>
        </div>

        <!-- Alert List -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">🔔 Notifikasi Warga Yang Perlu Ditindaklanjuti</h2>
                <a href="settings.php" class="btn btn-secondary btn-sm">
                    ⚙️ Atur Target
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($wargaAlert)): ?>
                    <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
                        <h3>Semua Warga Lancar!</h3>
                        <p>Tidak ada warga yang pembayarannya di bawah target minimum.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Nama Warga</th>
                                    <th>Dawis</th>
                                    <th>No. Telepon</th>
                                    <th>Target Sampai Bulan Ini</th>
                                    <th>Total Bayar</th>
                                    <th>Kurang</th>
                                    <th>Progress</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($wargaAlert as $w): ?>
                                <tr style="background: <?php echo $w['status_pembayaran'] == 'alert' ? 'rgba(239, 68, 68, 0.05)' : 'rgba(245, 158, 11, 0.05)'; ?>">
                                    <td>
                                        <?php if ($w['status_pembayaran'] == 'alert'): ?>
                                            <span class="badge badge-danger">🚨 Alert</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">⚠️ Warning</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($w['nama_lengkap']); ?></strong><br>
                                        <small style="color: var(--text-secondary);">
                                            <?php echo htmlspecialchars($w['alamat'] ?? '-'); ?>
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($w['nama_dawis']); ?></td>
                                    <td>
                                        <?php if ($w['no_telepon']): ?>
                                            <a href="tel:<?php echo htmlspecialchars($w['no_telepon']); ?>"
                                               style="color: var(--primary-color);">
                                                📞 <?php echo htmlspecialchars($w['no_telepon']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--text-tertiary);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo formatRupiah($w['target_sampai_bulan_ini']); ?></strong>
                                    </td>
                                    <td class="<?php echo $w['saldo_tahun_ini'] < $w['target_sampai_bulan_ini'] * 0.75 ? 'text-danger' : 'text-warning'; ?>">
                                        <strong><?php echo formatRupiah($w['saldo_tahun_ini']); ?></strong>
                                    </td>
                                    <td class="text-danger">
                                        <strong>
                                            <?php
                                            $kurang = $w['target_sampai_bulan_ini'] - $w['saldo_tahun_ini'];
                                            echo formatRupiah($kurang);
                                            ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div style="flex: 1; background: var(--bg-tertiary); height: 8px; border-radius: 4px; overflow: hidden;">
                                                <div style="background: <?php echo $w['status_pembayaran'] == 'alert' ? 'var(--danger-color)' : 'var(--warning-color)'; ?>; height: 100%; width: <?php echo min(100, $w['persentase_pencapaian']); ?>%;"></div>
                                            </div>
                                            <span style="font-weight: 600; min-width: 50px; text-align: right;">
                                                <?php echo number_format($w['persentase_pencapaian'], 1); ?>%
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($w['no_telepon']): ?>
                                            <a href="https://wa.me/62<?php echo ltrim($w['no_telepon'], '0'); ?>?text=Yth.%20<?php echo urlencode($w['nama_lengkap']); ?>,%0A%0AIni%20adalah%20reminder%20pembayaran%20jimpitan%20RT.%20Sampai%20bulan%20ini,%20target%20adalah%20<?php echo formatRupiah($w['target_sampai_bulan_ini']); ?>%20dan%20sudah%20terbayar%20<?php echo formatRupiah($w['saldo_tahun_ini']); ?>.%0A%0ATerima%20kasih."
                                               target="_blank"
                                               class="btn btn-success btn-sm">
                                                💬 WhatsApp
                                            </a>
                                        <?php endif; ?>
                                        <a href="transaksi.php?warga_id=<?php echo $w['warga_id']; ?>"
                                           class="btn btn-primary btn-sm">
                                            📝 Catat Bayar
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3" style="padding: 1rem; background: var(--bg-secondary); border-radius: var(--radius-md);">
                        <h4 style="margin-bottom: 0.75rem;">💡 Tips Menindaklanjuti</h4>
                        <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-secondary);">
                            <li>Hubungi warga dengan status Alert terlebih dahulu (kurang dari 75%)</li>
                            <li>Gunakan tombol WhatsApp untuk mengirim reminder otomatis</li>
                            <li>Catat pembayaran segera setelah warga membayar</li>
                            <li>Lakukan pengecekan rutin setiap akhir bulan</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
</body>
</html>
