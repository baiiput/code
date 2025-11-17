<?php
/**
 * Data Pelanggan - CRUD
 * List, Add, Edit, Delete Pelanggan
 */

require_once '../config/config.php';
checkLogin();

$pageTitle = 'Data Pelanggan';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle Delete
if ($action == 'delete' && $id && hasPermission('delete_pelanggan')) {
    try {
        // Get pelanggan name for log
        $query = "SELECT nama FROM pelanggan WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $pelanggan = $stmt->fetch();

        // Delete
        $query = "DELETE FROM pelanggan WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        logActivity($db, 'delete', 'pelanggan', $id, 'Menghapus pelanggan: ' . $pelanggan['nama']);
        redirect(APP_URL . '/pages/pelanggan.php', 'Data pelanggan berhasil dihapus', 'success');
    } catch (PDOException $e) {
        redirect(APP_URL . '/pages/pelanggan.php', 'Gagal menghapus data: ' . $e->getMessage(), 'error');
    }
}

// Handle Form Submit (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && in_array($action, ['add', 'edit'])) {
    try {
        $data = [
            'nama' => sanitize($_POST['nama']),
            'login_gmail' => sanitize($_POST['login_gmail'] ?? ''),
            'login_starlink' => sanitize($_POST['login_starlink'] ?? ''),
            'login_alternatif' => sanitize($_POST['login_alternatif'] ?? ''),
            'acc_no' => sanitize($_POST['acc_no'] ?? ''),
            'email_client' => sanitize($_POST['email_client'] ?? ''),
            'nomor_cs' => sanitize($_POST['nomor_cs'] ?? ''),
            'alamat' => sanitize($_POST['alamat'] ?? ''),
            'kit_number' => sanitize($_POST['kit_number'] ?? ''),
            'serial_number' => sanitize($_POST['serial_number'] ?? ''),
            'tanggal_jatuh_tempo' => !empty($_POST['tanggal_jatuh_tempo']) ? $_POST['tanggal_jatuh_tempo'] : null,
            'kode' => sanitize($_POST['kode'] ?? ''),
            'payment' => sanitize($_POST['payment'] ?? ''),
            'id_transaksi' => sanitize($_POST['id_transaksi'] ?? ''),
            'status' => sanitize($_POST['status'] ?? 'Belum'),
            'paket' => sanitize($_POST['paket'] ?? ''),
            'last_4_digit' => sanitize($_POST['last_4_digit'] ?? ''),
            'no_aktivasi' => sanitize($_POST['no_aktivasi'] ?? ''),
            'status_client' => sanitize($_POST['status_client'] ?? 'Client Aktif')
        ];

        if ($action == 'add') {
            $query = "INSERT INTO pelanggan
                      (nama, login_gmail, login_starlink, login_alternatif, acc_no, email_client, nomor_cs,
                       alamat, kit_number, serial_number, tanggal_jatuh_tempo, kode, payment, id_transaksi,
                       status, paket, last_4_digit, no_aktivasi, status_client, created_by)
                      VALUES
                      (:nama, :login_gmail, :login_starlink, :login_alternatif, :acc_no, :email_client, :nomor_cs,
                       :alamat, :kit_number, :serial_number, :tanggal_jatuh_tempo, :kode, :payment, :id_transaksi,
                       :status, :paket, :last_4_digit, :no_aktivasi, :status_client, :created_by)";

            $stmt = $db->prepare($query);
            foreach ($data as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->bindParam(':created_by', $_SESSION['user_id']);
            $stmt->execute();

            $newId = $db->lastInsertId();
            logActivity($db, 'create', 'pelanggan', $newId, 'Menambah pelanggan: ' . $data['nama']);
            redirect(APP_URL . '/pages/pelanggan.php', 'Data pelanggan berhasil ditambahkan', 'success');

        } else { // Edit
            $query = "UPDATE pelanggan SET
                      nama = :nama, login_gmail = :login_gmail, login_starlink = :login_starlink,
                      login_alternatif = :login_alternatif, acc_no = :acc_no, email_client = :email_client,
                      nomor_cs = :nomor_cs, alamat = :alamat, kit_number = :kit_number,
                      serial_number = :serial_number, tanggal_jatuh_tempo = :tanggal_jatuh_tempo,
                      kode = :kode, payment = :payment, id_transaksi = :id_transaksi, status = :status,
                      paket = :paket, last_4_digit = :last_4_digit, no_aktivasi = :no_aktivasi,
                      status_client = :status_client
                      WHERE id = :id";

            $stmt = $db->prepare($query);
            foreach ($data as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            logActivity($db, 'update', 'pelanggan', $id, 'Mengupdate pelanggan: ' . $data['nama']);
            redirect(APP_URL . '/pages/pelanggan.php', 'Data pelanggan berhasil diupdate', 'success');
        }

    } catch (PDOException $e) {
        $error = 'Gagal menyimpan data: ' . $e->getMessage();
    }
}

// Get data for edit
$pelanggan = null;
if ($action == 'edit' && $id) {
    try {
        $query = "SELECT * FROM pelanggan WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $pelanggan = $stmt->fetch();

        if (!$pelanggan) {
            redirect(APP_URL . '/pages/pelanggan.php', 'Data tidak ditemukan', 'error');
        }
    } catch (PDOException $e) {
        redirect(APP_URL . '/pages/pelanggan.php', 'Error: ' . $e->getMessage(), 'error');
    }
}

// Get list data
$data = [];
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_client'] ?? '';

if ($action == 'list') {
    try {
        $query = "SELECT * FROM pelanggan WHERE 1=1";

        if (!empty($search)) {
            $query .= " AND (nama LIKE :search OR kit_number LIKE :search OR nomor_cs LIKE :search OR email_client LIKE :search)";
        }

        if (!empty($status_filter)) {
            $query .= " AND status_client = :status_client";
        }

        $query .= " ORDER BY nama ASC";

        $stmt = $db->prepare($query);

        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $stmt->bindParam(':search', $searchParam);
        }

        if (!empty($status_filter)) {
            $stmt->bindParam(':status_client', $status_filter);
        }

        $stmt->execute();
        $data = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Pelanggan List Error: " . $e->getMessage());
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
                        <i class="fas fa-users"></i>
                        <?php if ($action == 'add'): ?>
                            Tambah Pelanggan
                        <?php elseif ($action == 'edit'): ?>
                            Edit Pelanggan
                        <?php else: ?>
                            Data Pelanggan
                        <?php endif; ?>
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                        <?php if ($action != 'list'): ?>
                            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/pages/pelanggan.php">Data Pelanggan</a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active">
                            <?= $action == 'add' ? 'Tambah' : ($action == 'edit' ? 'Edit' : 'Data Pelanggan') ?>
                        </li>
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
                        <h3 class="card-title"><i class="fas fa-list"></i> Daftar Pelanggan</h3>
                        <div class="card-tools">
                            <?php if (hasPermission('add_pelanggan')): ?>
                                <a href="?action=add" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Pelanggan
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter -->
                        <form method="GET" class="mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="text" name="search" class="form-control" placeholder="Cari nama, KIT, nomor, email..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-3">
                                    <select name="status_client" class="form-control">
                                        <option value="">Semua Status</option>
                                        <option value="Client Aktif" <?= $status_filter == 'Client Aktif' ? 'selected' : '' ?>>Client Aktif</option>
                                        <option value="Client Non Aktif" <?= $status_filter == 'Client Non Aktif' ? 'selected' : '' ?>>Client Non Aktif</option>
                                        <option value="Client Lepas" <?= $status_filter == 'Client Lepas' ? 'selected' : '' ?>>Client Lepas</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                </div>
                                <?php if (!empty($search) || !empty($status_filter)): ?>
                                <div class="col-md-2">
                                    <a href="?" class="btn btn-secondary btn-block">
                                        <i class="fas fa-times"></i> Reset
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </form>

                        <div class="table-responsive">
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
                                        <th>Status Client</th>
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
                                                <td><?= formatTanggal($row['tanggal_jatuh_tempo']) ?></td>
                                                <td><?= getStatusBadge($row['status']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $row['status_client'] == 'Client Aktif' ? 'success' : 'secondary' ?>">
                                                        <?= $row['status_client'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (hasPermission('edit_pelanggan')): ?>
                                                        <a href="?action=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (hasPermission('delete_pelanggan')): ?>
                                                        <a href="#" onclick="confirmDelete('?action=delete&id=<?= $row['id'] ?>', 'Yakin ingin menghapus <?= htmlspecialchars($row['nama']) ?>?')" class="btn btn-sm btn-danger" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">Tidak ada data</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- ADD/EDIT FORM -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-<?= $action == 'add' ? 'plus' : 'edit' ?>"></i>
                            <?= $action == 'add' ? 'Tambah' : 'Edit' ?> Data Pelanggan
                        </h3>
                    </div>
                    <form method="POST" action="">
                        <div class="card-body">
                            <div class="row">
                                <!-- Nama (Required) -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nama <span class="text-danger">*</span></label>
                                        <input type="text" name="nama" class="form-control" required
                                               value="<?= htmlspecialchars($pelanggan['nama'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- Status Client -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Status Client <span class="text-danger">*</span></label>
                                        <select name="status_client" class="form-control" required>
                                            <option value="Client Aktif" <?= ($pelanggan['status_client'] ?? 'Client Aktif') == 'Client Aktif' ? 'selected' : '' ?>>Client Aktif</option>
                                            <option value="Client Non Aktif" <?= ($pelanggan['status_client'] ?? '') == 'Client Non Aktif' ? 'selected' : '' ?>>Client Non Aktif</option>
                                            <option value="Client Lepas" <?= ($pelanggan['status_client'] ?? '') == 'Client Lepas' ? 'selected' : '' ?>>Client Lepas</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Login Gmail -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Login GMAIL</label>
                                        <textarea name="login_gmail" class="form-control" rows="2"><?= htmlspecialchars($pelanggan['login_gmail'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <!-- Login Starlink -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Login Starlink</label>
                                        <textarea name="login_starlink" class="form-control" rows="2"><?= htmlspecialchars($pelanggan['login_starlink'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <!-- Login Alternatif -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Login Alternatif</label>
                                        <textarea name="login_alternatif" class="form-control" rows="2"><?= htmlspecialchars($pelanggan['login_alternatif'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <!-- ACC No -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ACC No.</label>
                                        <input type="text" name="acc_no" class="form-control"
                                               value="<?= htmlspecialchars($pelanggan['acc_no'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- Email Client -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email Client</label>
                                        <input type="email" name="email_client" class="form-control"
                                               value="<?= htmlspecialchars($pelanggan['email_client'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- Nomor CS (WA) -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nomor CS/WhatsApp</label>
                                        <input type="text" name="nomor_cs" class="form-control"
                                               value="<?= htmlspecialchars($pelanggan['nomor_cs'] ?? '') ?>"
                                               placeholder="628xxx">
                                    </div>
                                </div>

                                <!-- Alamat -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Alamat</label>
                                        <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($pelanggan['alamat'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <!-- KIT Number -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>KIT Number</label>
                                        <textarea name="kit_number" class="form-control" rows="2"><?= htmlspecialchars($pelanggan['kit_number'] ?? '') ?></textarea>
                                        <small class="text-muted">Pisahkan dengan enter jika multiple KIT</small>
                                    </div>
                                </div>

                                <!-- Serial Number -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Serial Number</label>
                                        <textarea name="serial_number" class="form-control" rows="2"><?= htmlspecialchars($pelanggan['serial_number'] ?? '') ?></textarea>
                                        <small class="text-muted">Pisahkan dengan enter jika multiple Serial</small>
                                    </div>
                                </div>

                                <!-- Paket -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Paket</label>
                                        <textarea name="paket" class="form-control" rows="2"><?= htmlspecialchars($pelanggan['paket'] ?? '') ?></textarea>
                                        <small class="text-muted">Pisahkan dengan enter jika multiple Paket</small>
                                    </div>
                                </div>

                                <!-- Tanggal Jatuh Tempo -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tanggal Jatuh Tempo</label>
                                        <input type="date" name="tanggal_jatuh_tempo" class="form-control"
                                               value="<?= formatTanggalInput($pelanggan['tanggal_jatuh_tempo'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="Belum" <?= ($pelanggan['status'] ?? 'Belum') == 'Belum' ? 'selected' : '' ?>>Belum</option>
                                            <option value="Lunas" <?= ($pelanggan['status'] ?? '') == 'Lunas' ? 'selected' : '' ?>>Lunas</option>
                                            <option value="Pending" <?= ($pelanggan['status'] ?? '') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Rencana Bayarkan" <?= ($pelanggan['status'] ?? '') == 'Rencana Bayarkan' ? 'selected' : '' ?>>Rencana Bayarkan</option>
                                            <option value="Active" <?= ($pelanggan['status'] ?? '') == 'Active' ? 'selected' : '' ?>>Active</option>
                                            <option value="Inactive" <?= ($pelanggan['status'] ?? '') == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Kode -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Kode</label>
                                        <input type="text" name="kode" class="form-control"
                                               value="<?= htmlspecialchars($pelanggan['kode'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- Payment -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Payment</label>
                                        <input type="text" name="payment" class="form-control"
                                               value="<?= htmlspecialchars($pelanggan['payment'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- ID Transaksi -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ID Transaksi</label>
                                        <textarea name="id_transaksi" class="form-control" rows="2"><?= htmlspecialchars($pelanggan['id_transaksi'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <!-- Last 4 Digit -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Last 4 Digit</label>
                                        <input type="text" name="last_4_digit" class="form-control" maxlength="10"
                                               value="<?= htmlspecialchars($pelanggan['last_4_digit'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- No Aktivasi -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>No Aktivasi</label>
                                        <input type="text" name="no_aktivasi" class="form-control"
                                               value="<?= htmlspecialchars($pelanggan['no_aktivasi'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="<?= APP_URL ?>/pages/pelanggan.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>
