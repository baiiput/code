<?php
$pageTitle = 'Kandidat - Sistem Voting RT';
require_once 'includes/header.php';

$db = getDB();

// Cek apakah ada ID kandidat spesifik
$kandidatId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($kandidatId > 0) {
    // Tampilkan detail kandidat
    $stmt = $db->prepare("SELECT * FROM kandidat WHERE id = ? AND status = 'active'");
    $stmt->execute([$kandidatId]);
    $kandidat = $stmt->fetch();

    if (!$kandidat) {
        redirect('kandidat.php', 'Kandidat tidak ditemukan', 'warning');
    }
    ?>

    <section class="kandidat-detail">
        <div class="container">
            <a href="kandidat.php" class="back-link">&larr; Kembali ke daftar kandidat</a>

            <div class="kandidat-profile">
                <div class="kandidat-photo-wrapper">
                    <div class="kandidat-number-badge"><?= $kandidat['no_urut'] ?></div>
                    <?php if ($kandidat['foto']): ?>
                        <img src="uploads/<?= $kandidat['foto'] ?>" alt="<?= sanitize($kandidat['nama_lengkap']) ?>" class="kandidat-photo-full">
                    <?php else: ?>
                        <div class="no-photo-full">&#128100;</div>
                    <?php endif; ?>
                </div>

                <div class="kandidat-info">
                    <h1><?= sanitize($kandidat['nama_lengkap']) ?></h1>
                    <p class="kandidat-subtitle">Kandidat Ketua RT No. Urut <?= $kandidat['no_urut'] ?></p>

                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Tempat, Tanggal Lahir</span>
                            <span class="info-value"><?= sanitize($kandidat['tempat_lahir']) ?>, <?= formatTanggal($kandidat['tanggal_lahir']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Pekerjaan</span>
                            <span class="info-value"><?= sanitize($kandidat['pekerjaan']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Pendidikan</span>
                            <span class="info-value"><?= sanitize($kandidat['pendidikan']) ?></span>
                        </div>
                        <div class="info-item full-width">
                            <span class="info-label">Alamat</span>
                            <span class="info-value"><?= sanitize($kandidat['alamat']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kandidat-visimisi">
                <div class="visimisi-card">
                    <h2>Visi</h2>
                    <p><?= nl2br(sanitize($kandidat['visi'])) ?></p>
                </div>
                <div class="visimisi-card">
                    <h2>Misi</h2>
                    <p><?= nl2br(sanitize($kandidat['misi'])) ?></p>
                </div>
            </div>
        </div>
    </section>

    <?php
} else {
    // Tampilkan daftar semua kandidat
    $stmt = $db->query("SELECT * FROM kandidat WHERE status = 'active' ORDER BY no_urut ASC");
    $daftarKandidat = $stmt->fetchAll();
    ?>

    <section class="kandidat-list">
        <div class="container">
            <h1>Daftar Kandidat</h1>
            <p>Kenali lebih dekat para kandidat Ketua RT periode ini</p>

            <div class="kandidat-grid">
                <?php foreach ($daftarKandidat as $k): ?>
                    <div class="kandidat-card-full">
                        <div class="kandidat-card-header">
                            <div class="kandidat-number-badge"><?= $k['no_urut'] ?></div>
                            <?php if ($k['foto']): ?>
                                <img src="uploads/<?= $k['foto'] ?>" alt="<?= sanitize($k['nama_lengkap']) ?>">
                            <?php else: ?>
                                <div class="no-photo-medium">&#128100;</div>
                            <?php endif; ?>
                        </div>
                        <div class="kandidat-card-body">
                            <h3><?= sanitize($k['nama_lengkap']) ?></h3>
                            <p class="job"><?= sanitize($k['pekerjaan']) ?></p>
                            <p class="education"><?= sanitize($k['pendidikan']) ?></p>
                            <div class="visi-preview">
                                <strong>Visi:</strong>
                                <p><?= substr(sanitize($k['visi']), 0, 150) ?>...</p>
                            </div>
                            <a href="kandidat.php?id=<?= $k['id'] ?>" class="btn btn-outline btn-block">
                                Lihat Profil Lengkap
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php
}

require_once 'includes/footer.php';
?>
