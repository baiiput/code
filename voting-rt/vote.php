<?php
$pageTitle = 'Vote - Sistem Voting RT';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isLoggedIn()) {
    redirect('login.php', 'Silakan login terlebih dahulu', 'warning');
}

$db = getDB();
$userId = $_SESSION['user_id'];

// Cek apakah user sudah memilih
$stmt = $db->prepare("SELECT sudah_memilih FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user['sudah_memilih']) {
    redirect('hasil.php', 'Anda sudah melakukan pemilihan', 'info');
}

// Cek status pemilihan
$statusPemilihan = getStatusPemilihan();
if ($statusPemilihan == 'belum_mulai') {
    redirect('index.php', 'Pemilihan belum dimulai', 'warning');
} elseif ($statusPemilihan == 'selesai') {
    redirect('hasil.php', 'Pemilihan sudah selesai', 'info');
}

// Ambil daftar kandidat
$stmt = $db->query("SELECT * FROM kandidat WHERE status = 'active' ORDER BY no_urut ASC");
$kandidat = $stmt->fetchAll();

$error = '';
$success = '';

// Proses voting
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kandidatId = (int)($_POST['kandidat_id'] ?? 0);
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validasi CSRF
    if (!validateCSRFToken($csrfToken)) {
        $error = 'Token keamanan tidak valid. Silakan refresh halaman.';
    } elseif ($kandidatId <= 0) {
        $error = 'Silakan pilih salah satu kandidat';
    } else {
        // Cek kandidat valid
        $stmt = $db->prepare("SELECT id FROM kandidat WHERE id = ? AND status = 'active'");
        $stmt->execute([$kandidatId]);
        if (!$stmt->fetch()) {
            $error = 'Kandidat tidak valid';
        } else {
            // Cek lagi apakah sudah memilih (double check)
            $stmt = $db->prepare("SELECT sudah_memilih FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            if ($stmt->fetch()['sudah_memilih']) {
                redirect('hasil.php', 'Anda sudah melakukan pemilihan', 'warning');
            }

            try {
                $db->beginTransaction();

                // Insert voting
                $stmt = $db->prepare("
                    INSERT INTO voting (user_id, kandidat_id, waktu_voting, ip_address, user_agent)
                    VALUES (?, ?, NOW(), ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $kandidatId,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);

                // Update status user
                $stmt = $db->prepare("UPDATE users SET sudah_memilih = 1, waktu_memilih = NOW() WHERE id = ?");
                $stmt->execute([$userId]);

                $db->commit();

                logActivity('user', $userId, 'Vote', 'User memilih kandidat ID: ' . $kandidatId);

                // Regenerate CSRF token
                unset($_SESSION[CSRF_TOKEN_NAME]);

                redirect('hasil.php', 'Terima kasih! Suara Anda telah berhasil tercatat.', 'success');

            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Terjadi kesalahan saat menyimpan suara. Silakan coba lagi.';
            }
        }
    }
}

$csrfToken = generateCSRFToken();

require_once 'includes/header.php';
?>

<section class="vote-section">
    <div class="container">
        <div class="vote-header">
            <h1>Pemilihan Ketua RT</h1>
            <p>Pilih satu kandidat di bawah ini. Suara Anda bersifat rahasia.</p>
            <div class="alert alert-warning">
                <strong>Perhatian:</strong> Anda hanya dapat memilih SATU KALI. Pastikan pilihan Anda sudah benar sebelum mengirim.
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="voteForm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="vote-grid">
                <?php foreach ($kandidat as $k): ?>
                    <label class="vote-card">
                        <input type="radio" name="kandidat_id" value="<?= $k['id'] ?>" required>
                        <div class="vote-card-content">
                            <div class="candidate-number-large"><?= $k['no_urut'] ?></div>
                            <div class="candidate-photo-large">
                                <?php if ($k['foto']): ?>
                                    <img src="uploads/<?= $k['foto'] ?>" alt="<?= sanitize($k['nama_lengkap']) ?>">
                                <?php else: ?>
                                    <div class="no-photo-large">&#128100;</div>
                                <?php endif; ?>
                            </div>
                            <h3><?= sanitize($k['nama_lengkap']) ?></h3>
                            <p class="candidate-job"><?= sanitize($k['pekerjaan']) ?></p>
                            <div class="candidate-visi">
                                <strong>Visi:</strong>
                                <p><?= sanitize($k['visi']) ?></p>
                            </div>
                        </div>
                        <div class="vote-check">&#10003;</div>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="vote-submit">
                <button type="button" class="btn btn-primary btn-lg" onclick="konfirmasiVote()">
                    Kirim Suara
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Modal Konfirmasi -->
<div id="konfirmasiModal" class="modal">
    <div class="modal-content">
        <h3>Konfirmasi Pilihan</h3>
        <p>Apakah Anda yakin dengan pilihan Anda?</p>
        <p><strong>Pilihan ini tidak dapat diubah setelah dikirim.</strong></p>
        <div class="modal-buttons">
            <button type="button" class="btn btn-secondary" onclick="tutupModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="kirimVote()">Ya, Kirim Suara</button>
        </div>
    </div>
</div>

<script>
function konfirmasiVote() {
    const selected = document.querySelector('input[name="kandidat_id"]:checked');
    if (!selected) {
        alert('Silakan pilih salah satu kandidat terlebih dahulu');
        return;
    }
    document.getElementById('konfirmasiModal').style.display = 'flex';
}

function tutupModal() {
    document.getElementById('konfirmasiModal').style.display = 'none';
}

function kirimVote() {
    document.getElementById('voteForm').submit();
}

// Tutup modal jika klik di luar
window.onclick = function(event) {
    const modal = document.getElementById('konfirmasiModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
