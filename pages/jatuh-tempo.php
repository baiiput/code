<?php
/**
 * Jatuh Tempo Page
 * Menampilkan client yang jatuh tempo besok
 */

require_once '../config/config.php';
checkLogin();

$pageTitle = 'Jatuh Tempo';

// Get data
try {
    $query = "SELECT * FROM v_jatuh_tempo ORDER BY nama";
    $stmt = $db->query($query);
    $data = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Jatuh Tempo Error: " . $e->getMessage());
    $data = [];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="flex-fill">
    <div class="container-fluid p-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="fas fa-calendar-alt text-info"></i> Jatuh Tempo</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Jatuh Tempo</li>
                </ol>
            </nav>
        </div>

        <?= getFlashMessage() ?>

        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list"></i> Daftar Pelanggan Jatuh Tempo Besok</h5>
                <span class="badge bg-info"><?= count($data) ?> Pelanggan</span>
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
                                            <span class="badge bg-info">
                                                <?= formatTanggal($row['tanggal_jatuh_tempo']) ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">Besok (<?= $row['hari_tersisa'] ?> hari)</small>
                                        </td>
                                        <td><?= getStatusBadge($row['status']) ?></td>
                                        <td class="text-nowrap">
                                            <?php if (!empty($row['nomor_cs'])): ?>
                                                <a href="<?= generateWhatsAppLink(
                                                    $row['nama'],
                                                    $row['nomor_cs'],
                                                    $row['kit_number'],
                                                    $row['paket'],
                                                    $row['tanggal_jatuh_tempo'],
                                                    'H-1'
                                                ) ?>" target="_blank" class="btn btn-success btn-sm" title="Kirim Reminder WA">
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
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="fas fa-check-circle fa-3x mb-3 d-block"></i>
                                        <p>Tidak ada pelanggan yang jatuh tempo besok</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

    </div>

<?php include '../includes/footer.php'; ?>
