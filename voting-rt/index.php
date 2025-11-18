<?php
$pageTitle = 'Beranda - Sistem Voting RT';
require_once 'includes/header.php';

$totalPemilih = getTotalPemilih();
$totalSudahMemilih = getTotalSudahMemilih();
$kandidat = getHasilVoting();
?>

<section class="hero">
    <div class="container">
        <h1><?= $pengaturan['nama_pemilihan'] ?? 'Pemilihan Ketua RT' ?></h1>
        <p><?= $pengaturan['deskripsi'] ?? 'Sistem pemilihan online yang aman, transparan, dan terpercaya' ?></p>

        <?php if ($statusPemilihan == 'belum_mulai'): ?>
            <div class="status-badge status-upcoming">
                <span>Pemilihan akan dimulai</span>
                <strong><?= formatTanggal($pengaturan['tanggal_mulai'], 'datetime') ?></strong>
            </div>
        <?php elseif ($statusPemilihan == 'berlangsung'): ?>
            <div class="status-badge status-ongoing">
                <span>Pemilihan sedang berlangsung</span>
                <strong>Berakhir: <?= formatTanggal($pengaturan['tanggal_selesai'], 'datetime') ?></strong>
            </div>
            <?php if (isLoggedIn()): ?>
                <a href="vote.php" class="btn btn-primary btn-lg">Vote Sekarang</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-lg">Login untuk Vote</a>
            <?php endif; ?>
        <?php else: ?>
            <div class="status-badge status-ended">
                <span>Pemilihan telah selesai</span>
                <strong><?= formatTanggal($pengaturan['tanggal_selesai']) ?></strong>
            </div>
            <a href="hasil.php" class="btn btn-secondary btn-lg">Lihat Hasil</a>
        <?php endif; ?>
    </div>
</section>

<section class="stats">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">&#128100;</div>
                <div class="stat-number"><?= $totalPemilih ?></div>
                <div class="stat-label">Pemilih Terdaftar</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">&#9745;</div>
                <div class="stat-number"><?= $totalSudahMemilih ?></div>
                <div class="stat-label">Sudah Memilih</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">&#128101;</div>
                <div class="stat-number"><?= count($kandidat) ?></div>
                <div class="stat-label">Kandidat</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">&#128200;</div>
                <div class="stat-number"><?= $totalPemilih > 0 ? round(($totalSudahMemilih / $totalPemilih) * 100) : 0 ?>%</div>
                <div class="stat-label">Partisipasi</div>
            </div>
        </div>
    </div>
</section>

<section class="candidates-preview">
    <div class="container">
        <h2>Kandidat Ketua RT</h2>
        <div class="candidates-grid">
            <?php foreach ($kandidat as $k): ?>
                <div class="candidate-card">
                    <div class="candidate-number"><?= $k['no_urut'] ?></div>
                    <div class="candidate-photo">
                        <?php if ($k['foto']): ?>
                            <img src="uploads/<?= $k['foto'] ?>" alt="<?= sanitize($k['nama_lengkap']) ?>">
                        <?php else: ?>
                            <div class="no-photo">&#128100;</div>
                        <?php endif; ?>
                    </div>
                    <h3><?= sanitize($k['nama_lengkap']) ?></h3>
                    <p class="candidate-job"><?= sanitize($k['pekerjaan']) ?></p>
                    <a href="kandidat.php?id=<?= $k['id'] ?>" class="btn btn-outline">Lihat Profil</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="how-to">
    <div class="container">
        <h2>Cara Memilih</h2>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3>Daftar</h3>
                <p>Daftarkan diri Anda dengan NIK dan data yang valid</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h3>Verifikasi</h3>
                <p>Tunggu verifikasi dari panitia pemilihan</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h3>Login</h3>
                <p>Masuk ke sistem saat pemilihan berlangsung</p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <h3>Pilih</h3>
                <p>Pilih kandidat pilihan Anda</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
