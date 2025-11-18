<?php
$pageTitle = 'Dashboard Admin - Sistem Voting RT';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login admin
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();

// Statistik
$totalPemilih = getTotalPemilih();
$totalSudahMemilih = getTotalSudahMemilih();
$pengaturan = getPengaturan();
$statusPemilihan = getStatusPemilihan();

// Pemilih pending verifikasi
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE status_verifikasi = 'pending'");
$pendingVerifikasi = $stmt->fetchColumn();

// Total kandidat
$stmt = $db->query("SELECT COUNT(*) FROM kandidat WHERE status = 'active'");
$totalKandidat = $stmt->fetchColumn();

// Hasil voting
$hasil = getHasilVoting();

// Aktivitas terbaru
$stmt = $db->query("
    SELECT la.*,
        CASE
            WHEN la.user_type = 'user' THEN u.nama_lengkap
            WHEN la.user_type = 'admin' THEN a.nama_lengkap
        END as nama
    FROM log_aktivitas la
    LEFT JOIN users u ON la.user_type = 'user' AND la.user_id = u.id
    LEFT JOIN admin a ON la.user_type = 'admin' AND la.user_id = a.id
    ORDER BY la.created_at DESC
    LIMIT 10
");
$aktivitas = $stmt->fetchAll();

require_once 'header.php';
?>

<div class="admin-content">
    <h1>Dashboard</h1>
    <p>Selamat datang, <?= $_SESSION['admin_nama'] ?>!</p>

    <!-- Status Pemilihan -->
    <div class="status-box <?= $statusPemilihan ?>">
        <h3>Status Pemilihan</h3>
        <p class="status-text">
            <?php
            switch ($statusPemilihan) {
                case 'belum_mulai':
                    echo 'Pemilihan belum dimulai';
                    break;
                case 'berlangsung':
                    echo 'Pemilihan sedang berlangsung';
                    break;
                case 'selesai':
                    echo 'Pemilihan telah selesai';
                    break;
            }
            ?>
        </p>
        <?php if ($pengaturan): ?>
            <p class="status-date">
                <?= formatTanggal($pengaturan['tanggal_mulai'], 'datetime') ?> - <?= formatTanggal($pengaturan['tanggal_selesai'], 'datetime') ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Statistik -->
    <div class="stats-grid-admin">
        <div class="stat-card-admin">
            <div class="stat-icon-admin">&#128101;</div>
            <div class="stat-info">
                <div class="stat-number-admin"><?= $totalPemilih ?></div>
                <div class="stat-label-admin">Pemilih Terverifikasi</div>
            </div>
        </div>
        <div class="stat-card-admin">
            <div class="stat-icon-admin">&#9745;</div>
            <div class="stat-info">
                <div class="stat-number-admin"><?= $totalSudahMemilih ?></div>
                <div class="stat-label-admin">Sudah Memilih</div>
            </div>
        </div>
        <div class="stat-card-admin warning">
            <div class="stat-icon-admin">&#9888;</div>
            <div class="stat-info">
                <div class="stat-number-admin"><?= $pendingVerifikasi ?></div>
                <div class="stat-label-admin">Pending Verifikasi</div>
            </div>
            <?php if ($pendingVerifikasi > 0): ?>
                <a href="pemilih.php?status=pending" class="stat-link">Verifikasi</a>
            <?php endif; ?>
        </div>
        <div class="stat-card-admin">
            <div class="stat-icon-admin">&#128100;</div>
            <div class="stat-info">
                <div class="stat-number-admin"><?= $totalKandidat ?></div>
                <div class="stat-label-admin">Kandidat</div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Grafik Hasil -->
        <div class="dashboard-card">
            <h3>Perolehan Suara</h3>
            <?php if ($totalSudahMemilih > 0): ?>
                <canvas id="adminChart" height="300"></canvas>
            <?php else: ?>
                <p class="no-data">Belum ada suara masuk</p>
            <?php endif; ?>
        </div>

        <!-- Aktivitas Terbaru -->
        <div class="dashboard-card">
            <h3>Aktivitas Terbaru</h3>
            <?php if (count($aktivitas) > 0): ?>
                <ul class="activity-list">
                    <?php foreach ($aktivitas as $a): ?>
                        <li>
                            <span class="activity-type <?= $a['user_type'] ?>"><?= $a['user_type'] ?></span>
                            <div class="activity-info">
                                <strong><?= sanitize($a['nama'] ?? 'Unknown') ?></strong>
                                <span><?= sanitize($a['aktivitas']) ?></span>
                            </div>
                            <span class="activity-time"><?= formatTanggal($a['created_at'], 'datetime') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-data">Belum ada aktivitas</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Results Table -->
    <div class="dashboard-card full-width">
        <h3>Hasil Sementara</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kandidat</th>
                    <th>Suara</th>
                    <th>Persentase</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalSuara = array_sum(array_column($hasil, 'jumlah_suara'));
                foreach ($hasil as $h):
                    $persen = $totalSuara > 0 ? round(($h['jumlah_suara'] / $totalSuara) * 100, 2) : 0;
                ?>
                    <tr>
                        <td><?= $h['no_urut'] ?></td>
                        <td><?= sanitize($h['nama_lengkap']) ?></td>
                        <td><?= number_format($h['jumlah_suara']) ?></td>
                        <td>
                            <div class="mini-bar">
                                <div class="mini-bar-fill" style="width: <?= $persen ?>%"></div>
                                <span><?= $persen ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalSudahMemilih > 0): ?>
<script>
    const ctx = document.getElementById('adminChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($hasil, 'nama_lengkap')) ?>,
            datasets: [{
                label: 'Jumlah Suara',
                data: <?= json_encode(array_column($hasil, 'jumlah_suara')) ?>,
                backgroundColor: ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
