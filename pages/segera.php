<?php
/**
 * Segera Page
 * Menampilkan client yang sudah lunas tapi lewat tanggal
 */

require_once '../config/config.php';
checkLogin();

$pageTitle = 'Segera';

// Get data
try {
    $query = "SELECT * FROM v_segera ORDER BY tanggal_jatuh_tempo ASC";
    $stmt = $db->query($query);
    $data = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Segera Error: " . $e->getMessage());
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
                    <h1 class="m-0"><i class="fas fa-exclamation-triangle text-danger"></i> Segera</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active">Segera</li>
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
                    <h3 class="card-title"><i class="fas fa-list"></i> Daftar Pelanggan Segera (Lunas tapi Lewat Tanggal)</h3>
                    <div class="card-tools">
                        <span class="badge badge-danger"><?= count($data) ?> Pelanggan</span>
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
                                <th>Hari Lewat</th>
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
                                            <span class="badge badge-danger">
                                                <?= formatTanggal($row['tanggal_jatuh_tempo']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-dark"><?= abs($row['hari_lewat']) ?> hari</span>
                                        </td>
                                        <td><?= getStatusBadge($row['status']) ?></td>
                                        <td>
                                            <?php if (!empty($row['nomor_cs'])): ?>
                                                <a href="<?= generateWhatsAppLink(
                                                    $row['nama'],
                                                    $row['nomor_cs'],
                                                    $row['kit_number'],
                                                    $row['paket'],
                                                    $row['tanggal_jatuh_tempo'],
                                                    'WARNING'
                                                ) ?>" target="_blank" class="btn btn-success btn-sm" title="Kirim Warning WA">
                                                    <i class="fab fa-whatsapp"></i>
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
                                    <td colspan="9" class="text-center text-muted">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <p>Tidak ada pelanggan dalam kategori segera</p>
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
