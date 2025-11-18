<?php
$pageTitle = 'Pengaturan - Admin';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();

// Get current settings
$pengaturan = getPengaturan();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $namaPemilihan = sanitize($_POST['nama_pemilihan']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $tanggalMulai = $_POST['tanggal_mulai'];
    $tanggalSelesai = $_POST['tanggal_selesai'];
    $tampilkanHasil = isset($_POST['tampilkan_hasil']) ? 1 : 0;

    if ($pengaturan) {
        // Update
        $stmt = $db->prepare("
            UPDATE pengaturan SET
                nama_pemilihan = ?,
                deskripsi = ?,
                tanggal_mulai = ?,
                tanggal_selesai = ?,
                tampilkan_hasil = ?
            WHERE id = ?
        ");
        $stmt->execute([$namaPemilihan, $deskripsi, $tanggalMulai, $tanggalSelesai, $tampilkanHasil, $pengaturan['id']]);
    } else {
        // Insert
        $stmt = $db->prepare("
            INSERT INTO pengaturan (nama_pemilihan, deskripsi, tanggal_mulai, tanggal_selesai, tampilkan_hasil)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$namaPemilihan, $deskripsi, $tanggalMulai, $tanggalSelesai, $tampilkanHasil]);
    }

    logActivity('admin', $_SESSION['admin_id'], 'Update Pengaturan', 'Pengaturan pemilihan diupdate');
    redirect('pengaturan.php', 'Pengaturan berhasil disimpan', 'success');
}

require_once 'header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>Pengaturan Pemilihan</h1>
    </div>

    <div class="form-card">
        <form method="POST">
            <div class="form-group">
                <label>Nama Pemilihan *</label>
                <input type="text" name="nama_pemilihan" class="form-control" required
                       value="<?= $pengaturan['nama_pemilihan'] ?? '' ?>"
                       placeholder="Contoh: Pemilihan Ketua RT 001 Tahun 2024">
            </div>

            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="3"
                          placeholder="Deskripsi singkat tentang pemilihan"><?= $pengaturan['deskripsi'] ?? '' ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tanggal & Waktu Mulai *</label>
                    <input type="datetime-local" name="tanggal_mulai" class="form-control" required
                           value="<?= $pengaturan ? date('Y-m-d\TH:i', strtotime($pengaturan['tanggal_mulai'])) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Tanggal & Waktu Selesai *</label>
                    <input type="datetime-local" name="tanggal_selesai" class="form-control" required
                           value="<?= $pengaturan ? date('Y-m-d\TH:i', strtotime($pengaturan['tanggal_selesai'])) : '' ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="tampilkan_hasil" value="1"
                           <?= ($pengaturan['tampilkan_hasil'] ?? 0) ? 'checked' : '' ?>>
                    Tampilkan hasil sementara selama pemilihan berlangsung
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
            </div>
        </form>
    </div>

    <!-- Reset Section -->
    <div class="form-card danger-zone">
        <h3>&#9888; Zona Bahaya</h3>

        <div class="danger-item">
            <div>
                <strong>Reset Suara</strong>
                <p>Hapus semua data voting. Data pemilih dan kandidat tetap ada.</p>
            </div>
            <form method="POST" action="reset.php">
                <input type="hidden" name="action" value="reset_votes">
                <button type="submit" class="btn btn-danger"
                        onclick="return confirm('PERINGATAN: Semua data suara akan dihapus. Lanjutkan?')">
                    Reset Suara
                </button>
            </form>
        </div>

        <div class="danger-item">
            <div>
                <strong>Reset Semua Data</strong>
                <p>Hapus semua data pemilih, kandidat, dan suara. Pengaturan tetap ada.</p>
            </div>
            <form method="POST" action="reset.php">
                <input type="hidden" name="action" value="reset_all">
                <button type="submit" class="btn btn-danger"
                        onclick="return confirm('PERINGATAN KERAS: SEMUA DATA akan dihapus termasuk pemilih dan kandidat. Tindakan ini tidak dapat dibatalkan. Lanjutkan?')">
                    Reset Semua
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
