<?php
$pageTitle = 'Kelola Kandidat - Admin';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();

// Handle actions
$action = $_GET['action'] ?? '';
$kandidatId = (int)($_GET['id'] ?? 0);

// Proses form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction == 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $noUrut = (int)$_POST['no_urut'];
        $nama = sanitize($_POST['nama_lengkap']);
        $tempatLahir = sanitize($_POST['tempat_lahir']);
        $tanggalLahir = $_POST['tanggal_lahir'];
        $alamat = sanitize($_POST['alamat']);
        $pekerjaan = sanitize($_POST['pekerjaan']);
        $pendidikan = sanitize($_POST['pendidikan']);
        $visi = sanitize($_POST['visi']);
        $misi = sanitize($_POST['misi']);
        $status = $_POST['status'];

        // Upload foto
        $foto = '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $result = uploadFile($_FILES['foto'], 'kandidat');
            if ($result['success']) {
                $foto = $result['filename'];
            }
        }

        if ($id > 0) {
            // Update
            $sql = "UPDATE kandidat SET no_urut=?, nama_lengkap=?, tempat_lahir=?, tanggal_lahir=?,
                    alamat=?, pekerjaan=?, pendidikan=?, visi=?, misi=?, status=?";
            $params = [$noUrut, $nama, $tempatLahir, $tanggalLahir, $alamat, $pekerjaan, $pendidikan, $visi, $misi, $status];

            if ($foto) {
                $sql .= ", foto=?";
                $params[] = $foto;
            }
            $sql .= " WHERE id=?";
            $params[] = $id;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            logActivity('admin', $_SESSION['admin_id'], 'Update Kandidat', 'ID: ' . $id);
            redirect('kandidat.php', 'Kandidat berhasil diupdate', 'success');
        } else {
            // Insert
            $stmt = $db->prepare("
                INSERT INTO kandidat (no_urut, nama_lengkap, tempat_lahir, tanggal_lahir, alamat, pekerjaan, pendidikan, visi, misi, foto, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$noUrut, $nama, $tempatLahir, $tanggalLahir, $alamat, $pekerjaan, $pendidikan, $visi, $misi, $foto, $status]);
            logActivity('admin', $_SESSION['admin_id'], 'Tambah Kandidat', 'Nama: ' . $nama);
            redirect('kandidat.php', 'Kandidat berhasil ditambahkan', 'success');
        }
    } elseif ($postAction == 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM kandidat WHERE id = ?");
        $stmt->execute([$id]);
        logActivity('admin', $_SESSION['admin_id'], 'Hapus Kandidat', 'ID: ' . $id);
        redirect('kandidat.php', 'Kandidat berhasil dihapus', 'success');
    }
}

// Get kandidat untuk edit
$editKandidat = null;
if ($action == 'edit' && $kandidatId > 0) {
    $stmt = $db->prepare("SELECT * FROM kandidat WHERE id = ?");
    $stmt->execute([$kandidatId]);
    $editKandidat = $stmt->fetch();
}

// Get semua kandidat
$stmt = $db->query("SELECT * FROM kandidat ORDER BY no_urut ASC");
$daftarKandidat = $stmt->fetchAll();

require_once 'header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>Kelola Kandidat</h1>
        <?php if ($action != 'add' && $action != 'edit'): ?>
            <a href="kandidat.php?action=add" class="btn btn-primary">+ Tambah Kandidat</a>
        <?php endif; ?>
    </div>

    <?php if ($action == 'add' || $action == 'edit'): ?>
        <!-- Form -->
        <div class="form-card">
            <h3><?= $action == 'edit' ? 'Edit' : 'Tambah' ?> Kandidat</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $editKandidat['id'] ?? 0 ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label>No. Urut *</label>
                        <input type="number" name="no_urut" class="form-control" required min="1"
                               value="<?= $editKandidat['no_urut'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" class="form-control" required
                               value="<?= $editKandidat['nama_lengkap'] ?? '' ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control"
                               value="<?= $editKandidat['tempat_lahir'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control"
                               value="<?= $editKandidat['tanggal_lahir'] ?? '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2"><?= $editKandidat['alamat'] ?? '' ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Pekerjaan</label>
                        <input type="text" name="pekerjaan" class="form-control"
                               value="<?= $editKandidat['pekerjaan'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Pendidikan</label>
                        <input type="text" name="pendidikan" class="form-control"
                               value="<?= $editKandidat['pendidikan'] ?? '' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Visi</label>
                    <textarea name="visi" class="form-control" rows="3"><?= $editKandidat['visi'] ?? '' ?></textarea>
                </div>

                <div class="form-group">
                    <label>Misi</label>
                    <textarea name="misi" class="form-control" rows="4"><?= $editKandidat['misi'] ?? '' ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Foto</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                        <?php if (isset($editKandidat['foto']) && $editKandidat['foto']): ?>
                            <small>Foto saat ini: <?= $editKandidat['foto'] ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?= ($editKandidat['status'] ?? 'active') == 'active' ? 'selected' : '' ?>>Aktif</option>
                            <option value="inactive" <?= ($editKandidat['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="kandidat.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Table -->
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>Nama</th>
                        <th>Pekerjaan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftarKandidat as $k): ?>
                        <tr>
                            <td><?= $k['no_urut'] ?></td>
                            <td>
                                <?php if ($k['foto']): ?>
                                    <img src="<?= APP_URL ?>/uploads/<?= $k['foto'] ?>" alt="" class="table-photo">
                                <?php else: ?>
                                    <span class="no-photo-sm">&#128100;</span>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($k['nama_lengkap']) ?></td>
                            <td><?= sanitize($k['pekerjaan']) ?></td>
                            <td>
                                <span class="badge badge-<?= $k['status'] == 'active' ? 'verified' : 'rejected' ?>">
                                    <?= $k['status'] == 'active' ? 'Aktif' : 'Tidak Aktif' ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="kandidat.php?action=edit&id=<?= $k['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Hapus kandidat ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
