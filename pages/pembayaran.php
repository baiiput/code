<?php
/**
 * Pembayaran - Input & History
 * Input pembayaran dengan fee otomatis + update jatuh tempo
 */

require_once '../config/config.php';
checkLogin();
checkRole(['super_admin', 'admin', 'finance']);

$pageTitle = 'Pembayaran';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$pelanggan_id = $_GET['pelanggan_id'] ?? null;

// Get fee setting
try {
    $query = "SELECT * FROM fee_settings WHERE status = 'active' LIMIT 1";
    $stmt = $db->query($query);
    $fee_setting = $stmt->fetch();
    $default_fee = $fee_setting ? $fee_setting['nilai'] : 50000;
} catch (PDOException $e) {
    $default_fee = 50000;
}

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'add') {
    try {
        $pelanggan_id = (int)$_POST['pelanggan_id'];
        $tanggal_bayar = $_POST['tanggal_bayar'];
        $nominal = (float)$_POST['nominal'];
        $fee = (float)$_POST['fee'];
        $metode_bayar = sanitize($_POST['metode_bayar'] ?? '');
        $keterangan = sanitize($_POST['keterangan'] ?? '');
        $update_jatuh_tempo = isset($_POST['update_jatuh_tempo']) ? true : false;

        // Insert pembayaran
        $query = "INSERT INTO pembayaran
                  (pelanggan_id, tanggal_bayar, nominal, fee, metode_bayar, keterangan, status, created_by)
                  VALUES
                  (:pelanggan_id, :tanggal_bayar, :nominal, :fee, :metode_bayar, :keterangan, 'lunas', :created_by)";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':pelanggan_id', $pelanggan_id);
        $stmt->bindParam(':tanggal_bayar', $tanggal_bayar);
        $stmt->bindParam(':nominal', $nominal);
        $stmt->bindParam(':fee', $fee);
        $stmt->bindParam(':metode_bayar', $metode_bayar);
        $stmt->bindParam(':keterangan', $keterangan);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);
        $stmt->execute();

        $pembayaran_id = $db->lastInsertId();

        // Update jatuh tempo pelanggan (+1 bulan) jika dicentang
        if ($update_jatuh_tempo) {
            $query = "SELECT tanggal_jatuh_tempo FROM pelanggan WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $pelanggan_id);
            $stmt->execute();
            $pelanggan = $stmt->fetch();

            if ($pelanggan && !empty($pelanggan['tanggal_jatuh_tempo'])) {
                // Ambil tanggal saat ini
                $current_jatuh_tempo = new DateTime($pelanggan['tanggal_jatuh_tempo']);
                // Tambah 1 bulan tapi tetap di tanggal yang sama
                $new_jatuh_tempo = clone $current_jatuh_tempo;
                $new_jatuh_tempo->modify('+1 month');

                $query = "UPDATE pelanggan SET tanggal_jatuh_tempo = :new_jatuh_tempo WHERE id = :id";
                $stmt = $db->prepare($query);
                $new_jatuh_tempo_str = $new_jatuh_tempo->format('Y-m-d');
                $stmt->bindParam(':new_jatuh_tempo', $new_jatuh_tempo_str);
                $stmt->bindParam(':id', $pelanggan_id);
                $stmt->execute();
            }
        }

        // Update status pelanggan menjadi Lunas
        $query = "UPDATE pelanggan SET status = 'Lunas' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $pelanggan_id);
        $stmt->execute();

        logActivity($db, 'create', 'pembayaran', $pembayaran_id, 'Input pembayaran ID: ' . $pembayaran_id);
        redirect(APP_URL . '/pages/pembayaran.php', 'Pembayaran berhasil disimpan!', 'success');

    } catch (PDOException $e) {
        $error = 'Gagal menyimpan pembayaran: ' . $e->getMessage();
    }
}

// Get pelanggan list for dropdown
$pelanggan_list = [];
try {
    $query = "SELECT id, nama, kit_number, paket, status FROM pelanggan WHERE status_client = 'Client Aktif' ORDER BY nama";
    $stmt = $db->query($query);
    $pelanggan_list = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Pelanggan List Error: " . $e->getMessage());
}

// Get selected pelanggan detail (if adding from proses/jatuh tempo page)
$selected_pelanggan = null;
if ($action == 'add' && $pelanggan_id) {
    try {
        $query = "SELECT * FROM pelanggan WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $pelanggan_id);
        $stmt->execute();
        $selected_pelanggan = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get Pelanggan Error: " . $e->getMessage());
    }
}

// Get pembayaran list
$data = [];
$search = $_GET['search'] ?? '';
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

if ($action == 'list') {
    try {
        $query = "SELECT pb.*, p.nama as nama_pelanggan, p.kit_number
                  FROM pembayaran pb
                  LEFT JOIN pelanggan p ON pb.pelanggan_id = p.id
                  WHERE MONTH(pb.tanggal_bayar) = :bulan AND YEAR(pb.tanggal_bayar) = :tahun";

        if (!empty($search)) {
            $query .= " AND p.nama LIKE :search";
        }

        $query .= " ORDER BY pb.tanggal_bayar DESC, pb.id DESC";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':bulan', $bulan);
        $stmt->bindParam(':tahun', $tahun);

        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $stmt->bindParam(':search', $searchParam);
        }

        $stmt->execute();
        $data = $stmt->fetchAll();

        // Calculate total fee
        $total_fee = 0;
        foreach ($data as $row) {
            $total_fee += $row['fee'];
        }
    } catch (PDOException $e) {
        error_log("Pembayaran List Error: " . $e->getMessage());
    }
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
                    <h1 class="m-0">
                        <i class="fas fa-money-bill-wave"></i>
                        <?= $action == 'add' ? 'Input Pembayaran' : 'Data Pembayaran' ?>
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                        <?php if ($action != 'list'): ?>
                            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/pages/pembayaran.php">Pembayaran</a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active"><?= $action == 'add' ? 'Input' : 'Pembayaran' ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <?= getFlashMessage() ?>
            <?php if (isset($error)): ?>
                <?= showAlert($error, 'error') ?>
            <?php endif; ?>

            <?php if ($action == 'list'): ?>
                <!-- LIST VIEW -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Riwayat Pembayaran</h3>
                        <div class="card-tools">
                            <?php if (hasPermission('add_pembayaran')): ?>
                                <a href="?action=add" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Input Pembayaran
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter -->
                        <form method="GET" class="mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <select name="bulan" class="form-control">
                                        <?php
                                        $bulan_nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                                                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                        for ($i = 1; $i <= 12; $i++) {
                                            $selected = ($bulan == sprintf('%02d', $i)) ? 'selected' : '';
                                            echo "<option value='" . sprintf('%02d', $i) . "' $selected>{$bulan_nama[$i]}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="tahun" class="form-control">
                                        <?php
                                        $current_year = date('Y');
                                        for ($y = $current_year; $y >= $current_year - 3; $y--) {
                                            $selected = ($tahun == $y) ? 'selected' : '';
                                            echo "<option value='$y' $selected>$y</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="search" class="form-control" placeholder="Cari nama pelanggan..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if (isset($total_fee)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Total Fee Bulan Ini:</strong> <?= formatRupiah($total_fee) ?>
                                <span class="float-right"><?= count($data) ?> transaksi</span>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped datatable">
                                <thead>
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Tanggal</th>
                                        <th>Pelanggan</th>
                                        <th>KIT Number</th>
                                        <th>Nominal</th>
                                        <th>Fee</th>
                                        <th>Total</th>
                                        <th>Metode</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($data) > 0): ?>
                                        <?php foreach ($data as $i => $row): ?>
                                            <tr>
                                                <td><?= $i + 1 ?></td>
                                                <td><?= formatTanggal($row['tanggal_bayar']) ?></td>
                                                <td><strong><?= htmlspecialchars($row['nama_pelanggan']) ?></strong></td>
                                                <td><code><?= htmlspecialchars($row['kit_number']) ?></code></td>
                                                <td><?= formatRupiah($row['nominal']) ?></td>
                                                <td class="text-success"><strong><?= formatRupiah($row['fee']) ?></strong></td>
                                                <td><strong><?= formatRupiah($row['total']) ?></strong></td>
                                                <td><?= htmlspecialchars($row['metode_bayar']) ?></td>
                                                <td><?= getStatusBadge($row['status']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">Tidak ada data pembayaran</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- ADD FORM -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus"></i> Input Pembayaran Baru</h3>
                    </div>
                    <form method="POST" action="" id="formPembayaran">
                        <div class="card-body">
                            <div class="row">
                                <!-- Pelanggan -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Pelanggan <span class="text-danger">*</span></label>
                                        <select name="pelanggan_id" id="pelangganSelect" class="form-control select2" required>
                                            <option value="">-- Pilih Pelanggan --</option>
                                            <?php foreach ($pelanggan_list as $p): ?>
                                                <option value="<?= $p['id'] ?>"
                                                        data-paket="<?= htmlspecialchars($p['paket']) ?>"
                                                        data-status="<?= htmlspecialchars($p['status']) ?>"
                                                        <?= ($selected_pelanggan && $selected_pelanggan['id'] == $p['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($p['nama']) ?> - <?= htmlspecialchars($p['kit_number']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Tanggal Bayar -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Bayar <span class="text-danger">*</span></label>
                                        <input type="date" name="tanggal_bayar" class="form-control" required value="<?= date('Y-m-d') ?>">
                                    </div>
                                </div>

                                <!-- Metode Bayar -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Metode Pembayaran</label>
                                        <select name="metode_bayar" class="form-control">
                                            <option value="Transfer Bank">Transfer Bank</option>
                                            <option value="Cash">Cash</option>
                                            <option value="E-Wallet">E-Wallet</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Nominal -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nominal Pembayaran <span class="text-danger">*</span></label>
                                        <input type="number" name="nominal" id="nominalInput" class="form-control" required
                                               placeholder="750000" step="1000" min="0">
                                    </div>
                                </div>

                                <!-- Fee -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fee <span class="text-danger">*</span></label>
                                        <input type="number" name="fee" id="feeInput" class="form-control" required
                                               value="<?= $default_fee ?>" step="1000" min="0">
                                        <small class="text-muted">Fee otomatis: <?= formatRupiah($default_fee) ?></small>
                                    </div>
                                </div>

                                <!-- Total (Display Only) -->
                                <div class="col-md-12">
                                    <div class="alert alert-success">
                                        <h5><i class="fas fa-calculator"></i> Total yang Harus Dibayar:</h5>
                                        <h3 id="totalDisplay"><?= formatRupiah($default_fee) ?></h3>
                                    </div>
                                </div>

                                <!-- Update Jatuh Tempo -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="updateJatuhTempo" name="update_jatuh_tempo" checked>
                                            <label class="custom-control-label" for="updateJatuhTempo">
                                                <strong>Update Tanggal Jatuh Tempo (+1 bulan)</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted">Centang untuk otomatis menambah 1 bulan pada tanggal jatuh tempo pelanggan</small>
                                    </div>
                                </div>

                                <!-- Keterangan -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Keterangan</label>
                                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> Simpan Pembayaran
                            </button>
                            <a href="<?= APP_URL ?>/pages/pembayaran.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<?php
$additionalJS = "
<script>
$(document).ready(function() {
    // Calculate total on change
    function calculateTotal() {
        var nominal = parseFloat($('#nominalInput').val()) || 0;
        var fee = parseFloat($('#feeInput').val()) || 0;
        var total = nominal + fee;

        $('#totalDisplay').text(formatRupiah(total));
    }

    $('#nominalInput, #feeInput').on('input', calculateTotal);

    // Initialize
    calculateTotal();
});
</script>
";

include '../includes/footer.php';
?>
