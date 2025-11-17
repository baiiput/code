<?php
/**
 * Proses Page
 * Menampilkan client yang jatuh tempo kemarin
 */

require_once '../config/config.php';
checkLogin();

$pageTitle = 'Proses';

// Get data
try {
    $query = "SELECT * FROM v_proses ORDER BY nama";
    $stmt = $db->query($query);
    $data = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Proses Error: " . $e->getMessage());
    $data = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-sync-alt text-warning"></i> Proses</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Proses</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <?= getFlashMessage() ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Daftar Pelanggan yang Proses (Jatuh Tempo Kemarin)</h3>
                    <div class="card-tools">
                        <span class="badge badge-warning"><?= count($data) ?> Pelanggan</span>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped datatable">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Nama</th>
                                <th>Nomor WA</th>
                                <th>KIT Number</th>
                                <th>Paket</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($data) > 0): ?>
                                <?php foreach ($data as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['nomor_cs']) ?></td>
                                        <td><code><?= htmlspecialchars($row['kit_number']) ?></code></td>
                                        <td><?= htmlspecialchars($row['paket']) ?></td>
                                        <td>
                                            <span class="badge badge-warning">
                                                <?= formatTanggal($row['tanggal_jatuh_tempo']) ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">Kemarin (<?= abs($row['hari_lewat']) ?> hari lewat)</small>
                                        </td>
                                        <td><?= getStatusBadge($row['status']) ?></td>
                                        <td>
                                            <?php if (!empty($row['nomor_cs']) && hasPermission('add_pembayaran')): ?>
                                                <a href="<?= APP_URL ?>/pages/pembayaran.php?action=add&pelanggan_id=<?= $row['id'] ?>" class="btn btn-success btn-sm" title="Input Pembayaran">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= APP_URL ?>/pages/pelanggan.php?action=edit&id=<?= $row['id'] ?>" class="btn btn-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <p>Tidak ada pelanggan dalam status proses</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>
