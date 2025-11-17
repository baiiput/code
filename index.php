<?php
/**
 * Dashboard
 * Starlink Customer Management System
 */

require_once 'config/config.php';
checkLogin();

$pageTitle = 'Dashboard';

// Get counts for each category
try {
    // Jatuh Tempo (besok)
    $query = "SELECT COUNT(*) as count FROM v_jatuh_tempo";
    $stmt = $db->query($query);
    $jatuh_tempo_count = $stmt->fetch()['count'];

    // Proses (kemarin)
    $query = "SELECT COUNT(*) as count FROM v_proses";
    $stmt = $db->query($query);
    $proses_count = $stmt->fetch()['count'];

    // Segera (sudah lunas tapi lewat tanggal)
    $query = "SELECT COUNT(*) as count FROM v_segera";
    $stmt = $db->query($query);
    $segera_count = $stmt->fetch()['count'];

    // Observasi (status pending)
    $query = "SELECT COUNT(*) as count FROM v_observasi";
    $stmt = $db->query($query);
    $observasi_count = $stmt->fetch()['count'];

    // Total pelanggan aktif
    $query = "SELECT COUNT(*) as count FROM pelanggan WHERE status_client = 'Client Aktif'";
    $stmt = $db->query($query);
    $total_aktif = $stmt->fetch()['count'];

    // Total pembayaran bulan ini
    $query = "SELECT COUNT(*) as count, COALESCE(SUM(fee), 0) as total_fee
              FROM pembayaran
              WHERE MONTH(tanggal_bayar) = MONTH(CURDATE())
              AND YEAR(tanggal_bayar) = YEAR(CURDATE())
              AND status = 'lunas'";
    $stmt = $db->query($query);
    $pembayaran_bulan_ini = $stmt->fetch();

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $jatuh_tempo_count = $proses_count = $segera_count = $observasi_count = 0;
    $total_aktif = 0;
    $pembayaran_bulan_ini = ['count' => 0, 'total_fee' => 0];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Flash Message -->
            <?= getFlashMessage() ?>

            <!-- Welcome Card -->
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <h5><i class="fas fa-user-circle"></i> Selamat Datang, <?= $_SESSION['nama_lengkap'] ?>!</h5>
                    <p class="mb-0">
                        Role: <span class="badge badge-info"><?= getRoleName($_SESSION['user_role']) ?></span> |
                        Login terakhir: <span class="badge badge-secondary"><?= date('d/m/Y H:i') ?></span>
                    </p>
                </div>
            </div>

            <!-- 4 Menu Utama -->
            <div class="row">
                <!-- JATUH TEMPO -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $jatuh_tempo_count ?></h3>
                            <p>Jatuh Tempo</p>
                            <small>Jatuh tempo besok</small>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <a href="<?= APP_URL ?>/pages/jatuh-tempo.php" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <!-- PROSES -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $proses_count ?></h3>
                            <p>Proses</p>
                            <small>Jatuh tempo kemarin</small>
                        </div>
                        <div class="icon">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <a href="<?= APP_URL ?>/pages/proses.php" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <!-- SEGERA -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $segera_count ?></h3>
                            <p>Segera</p>
                            <small>Lunas tapi lewat tanggal</small>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <a href="<?= APP_URL ?>/pages/segera.php" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <!-- OBSERVASI -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3><?= $observasi_count ?></h3>
                            <p>Observasi</p>
                            <small>Status pending</small>
                        </div>
                        <div class="icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <a href="<?= APP_URL ?>/pages/observasi.php" class="small-box-footer">
                            Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <!-- /.row 4 menu utama -->

            <!-- Info Cards -->
            <div class="row">
                <div class="col-lg-4 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Pelanggan Aktif</span>
                            <span class="info-box-number"><?= $total_aktif ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-money-bill-wave"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Pembayaran Bulan Ini</span>
                            <span class="info-box-number"><?= $pembayaran_bulan_ini['count'] ?> transaksi</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-chart-line"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Fee Bulan Ini</span>
                            <span class="info-box-number"><?= formatRupiah($pembayaran_bulan_ini['total_fee']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity / Quick Access -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-clock"></i> Aktivitas Terbaru</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm">
                                <tbody>
                                    <?php
                                    try {
                                        $query = "SELECT * FROM activity_log
                                                  WHERE user_id = :user_id
                                                  ORDER BY created_at DESC
                                                  LIMIT 5";
                                        $stmt = $db->prepare($query);
                                        $stmt->bindParam(':user_id', $_SESSION['user_id']);
                                        $stmt->execute();
                                        $activities = $stmt->fetchAll();

                                        if (count($activities) > 0) {
                                            foreach ($activities as $activity) {
                                                echo '<tr>';
                                                echo '<td><i class="fas fa-circle text-primary" style="font-size: 8px;"></i></td>';
                                                echo '<td>' . htmlspecialchars($activity['description']) . '</td>';
                                                echo '<td class="text-muted"><small>' . date('d/m/Y H:i', strtotime($activity['created_at'])) . '</small></td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="3" class="text-center text-muted">Belum ada aktivitas</td></tr>';
                                        }
                                    } catch (PDOException $e) {
                                        echo '<tr><td colspan="3" class="text-center text-danger">Error loading activities</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-rocket"></i> Quick Access</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <a href="<?= APP_URL ?>/pages/pelanggan.php" class="btn btn-app bg-primary w-100">
                                        <i class="fas fa-users"></i> Data Pelanggan
                                    </a>
                                </div>
                                <?php if (hasPermission('add_pembayaran')): ?>
                                <div class="col-6 mb-3">
                                    <a href="<?= APP_URL ?>/pages/pembayaran.php?action=add" class="btn btn-app bg-success w-100">
                                        <i class="fas fa-plus"></i> Input Pembayaran
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php if (hasPermission('view_laporan')): ?>
                                <div class="col-6 mb-3">
                                    <a href="<?= APP_URL ?>/pages/laporan-fee.php" class="btn btn-app bg-warning w-100">
                                        <i class="fas fa-chart-line"></i> Laporan Fee
                                    </a>
                                </div>
                                <?php endif; ?>
                                <?php if ($_SESSION['user_role'] == 'super_admin'): ?>
                                <div class="col-6 mb-3">
                                    <a href="<?= APP_URL ?>/pages/settings.php" class="btn btn-app bg-secondary w-100">
                                        <i class="fas fa-cog"></i> Pengaturan
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php
include 'includes/footer.php';
?>
