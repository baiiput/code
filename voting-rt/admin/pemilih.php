<?php
$pageTitle = 'Kelola Pemilih - Admin';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($action == 'verify' && $userId > 0) {
        $stmt = $db->prepare("UPDATE users SET status_verifikasi = 'verified' WHERE id = ?");
        $stmt->execute([$userId]);
        logActivity('admin', $_SESSION['admin_id'], 'Verifikasi User', 'User ID: ' . $userId);
        redirect('pemilih.php', 'Pemilih berhasil diverifikasi', 'success');
    } elseif ($action == 'reject' && $userId > 0) {
        $stmt = $db->prepare("UPDATE users SET status_verifikasi = 'rejected' WHERE id = ?");
        $stmt->execute([$userId]);
        logActivity('admin', $_SESSION['admin_id'], 'Tolak User', 'User ID: ' . $userId);
        redirect('pemilih.php', 'Pemilih ditolak', 'warning');
    } elseif ($action == 'delete' && $userId > 0) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        logActivity('admin', $_SESSION['admin_id'], 'Hapus User', 'User ID: ' . $userId);
        redirect('pemilih.php', 'Pemilih berhasil dihapus', 'success');
    }
}

// Filter
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$where = "1=1";
$params = [];

if ($status != 'all') {
    $where .= " AND status_verifikasi = ?";
    $params[] = $status;
}

if ($search) {
    $where .= " AND (nama_lengkap LIKE ? OR nik LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $db->prepare("SELECT * FROM users WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$pemilih = $stmt->fetchAll();

require_once 'header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>Kelola Pemilih</h1>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>Status:</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>Semua</option>
                    <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="verified" <?= $status == 'verified' ? 'selected' : '' ?>>Terverifikasi</option>
                    <option value="rejected" <?= $status == 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                </select>
            </div>
            <div class="filter-group">
                <input type="text" name="search" placeholder="Cari nama/NIK..." value="<?= sanitize($search) ?>">
                <button type="submit" class="btn btn-sm">Cari</button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Alamat</th>
                    <th>No. HP</th>
                    <th>Status</th>
                    <th>Sudah Vote</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pemilih) > 0): ?>
                    <?php foreach ($pemilih as $p): ?>
                        <tr>
                            <td><?= sanitize($p['nik']) ?></td>
                            <td><?= sanitize($p['nama_lengkap']) ?></td>
                            <td><?= sanitize(substr($p['alamat'], 0, 50)) ?>...</td>
                            <td><?= sanitize($p['no_hp']) ?></td>
                            <td>
                                <span class="badge badge-<?= $p['status_verifikasi'] ?>">
                                    <?= ucfirst($p['status_verifikasi']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($p['sudah_memilih']): ?>
                                    <span class="badge badge-verified">Ya</span>
                                <?php else: ?>
                                    <span class="badge badge-pending">Belum</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <?php if ($p['status_verifikasi'] == 'pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="verify">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Verifikasi pemilih ini?')">Verifikasi</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Tolak pemilih ini?')">Tolak</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus pemilih ini? Data tidak dapat dikembalikan.')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">Tidak ada data pemilih</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
