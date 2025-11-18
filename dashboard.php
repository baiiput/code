<?php
require_once 'config.php';
requireLogin();

$user = getUserInfo($_SESSION['user_id']);

if (isAdmin()) {
    $stats = getAdminStats();
} else {
    $aktivitas = getAktivitasUser($_SESSION['user_id'], date('m'), date('Y'));
    $salary = getMonthlySalary($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="nav-menu">
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="admin/users.php">👥 Kelola Karyawan</a></li>
                    <li><a href="admin/laporan.php">📈 Laporan</a></li>
                <?php else: ?>
                    <li><a href="input.php">Input Aktivitas</a></li>
                    <li><a href="rekap.php">Rekap</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h1>👋 Selamat Datang, <?php echo htmlspecialchars($user['nama']); ?></h1>
                    <p style="color: #6b7280; margin-top: 5px;">
                        <?php echo ucfirst($user['role']); ?> • <?php echo date('d F Y'); ?>
                    </p>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>

            <?php if (isAdmin()): ?>
                <!-- Admin Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h3>Total Karyawan Aktif</h3>
                        <div class="value"><?php echo $stats['total_karyawan']; ?></div>
                        <div class="label">Karyawan</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3>Aktivitas Bulan Ini</h3>
                        <div class="value"><?php echo $stats['total_aktivitas']; ?></div>
                        <div class="label">Aktivitas</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3>Total Bonus</h3>
                        <div class="value"><?php echo formatRupiah($stats['total_bonus']); ?></div>
                        <div class="label">Bulan Ini</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3>Total Gaji</h3>
                        <div class="value"><?php echo formatRupiah($stats['total_gaji']); ?></div>
                        <div class="label">Bulan Ini</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>📊 Menu Admin</h2>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="admin/users.php" class="btn btn-primary">👥 Kelola Karyawan</a>
                        <a href="admin/laporan.php" class="btn btn-secondary">📈 Lihat Laporan</a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Karyawan Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h3>Aktivitas Bulan Ini</h3>
                        <div class="value"><?php echo count($aktivitas); ?></div>
                        <div class="label">Aktivitas</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3>Gaji Pokok</h3>
                        <div class="value"><?php echo formatRupiah($salary['gaji_pokok']); ?></div>
                        <div class="label">Per Bulan</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3>Total Bonus</h3>
                        <div class="value"><?php echo formatRupiah($salary['total_bonus']); ?></div>
                        <div class="label">Bulan Ini</div>
                    </div>
                    
                    <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3>Total Gaji</h3>
                        <div class="value"><?php echo formatRupiah($salary['total_gaji']); ?></div>
                        <div class="label">Bulan Ini</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>🚀 Menu Karyawan</h2>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="input.php" class="btn btn-success">✏️ Input Aktivitas</a>
                        <a href="rekap.php" class="btn btn-primary">📋 Rekap Aktivitas</a>
                    </div>
                </div>

                <?php if (count($aktivitas) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>📅 Aktivitas Terbaru</h2>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jam Kerja</th>
                                    <th>Aktivitas</th>
                                    <th>Durasi</th>
                                    <th>Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($aktivitas, 0, 5) as $item): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($item['tanggal'])); ?></td>
                                    <td style="font-size: 13px; color: #6b7280;">
                                        <?php 
                                        if (!empty($item['jam_mulai']) && !empty($item['jam_selesai'])) {
                                            echo substr($item['jam_mulai'], 0, 5) . ' - ' . substr($item['jam_selesai'], 0, 5);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($item['aktivitas'], 0, 50)) . (strlen($item['aktivitas']) > 50 ? '...' : ''); ?></td>
                                    <td><?php echo $item['durasi_jam']; ?> jam</td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $item['level'] == 'A' ? 'danger' : 
                                                 ($item['level'] == 'B' ? 'warning' : 'success'); 
                                        ?>">
                                            Level <?php echo $item['level']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px; text-align: center;">
                        <a href="rekap.php" class="btn btn-secondary">Lihat Semua</a>
                    </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
