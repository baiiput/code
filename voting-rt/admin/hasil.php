<?php
$pageTitle = 'Hasil Pemilihan - Admin';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();

// Get data
$hasil = getHasilVoting();
$totalSuara = array_sum(array_column($hasil, 'jumlah_suara'));
$totalPemilih = getTotalPemilih();
$totalSudahMemilih = getTotalSudahMemilih();

// Get voting timeline
$stmt = $db->query("
    SELECT DATE(waktu_voting) as tanggal, COUNT(*) as jumlah
    FROM voting
    GROUP BY DATE(waktu_voting)
    ORDER BY tanggal ASC
");
$timeline = $stmt->fetchAll();

require_once 'header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>Hasil Pemilihan</h1>
        <button onclick="window.print()" class="btn btn-secondary">&#128424; Cetak</button>
    </div>

    <!-- Statistics -->
    <div class="stats-grid-admin">
        <div class="stat-card-admin">
            <div class="stat-icon-admin">&#128101;</div>
            <div class="stat-info">
                <div class="stat-number-admin"><?= $totalPemilih ?></div>
                <div class="stat-label-admin">Total Pemilih</div>
            </div>
        </div>
        <div class="stat-card-admin">
            <div class="stat-icon-admin">&#9745;</div>
            <div class="stat-info">
                <div class="stat-number-admin"><?= $totalSudahMemilih ?></div>
                <div class="stat-label-admin">Sudah Memilih</div>
            </div>
        </div>
        <div class="stat-card-admin">
            <div class="stat-icon-admin">&#128200;</div>
            <div class="stat-info">
                <div class="stat-number-admin"><?= $totalPemilih > 0 ? round(($totalSudahMemilih / $totalPemilih) * 100, 1) : 0 ?>%</div>
                <div class="stat-label-admin">Partisipasi</div>
            </div>
        </div>
        <div class="stat-card-admin">
            <div class="stat-icon-admin">&#128176;</div>
            <div class="stat-info">
                <div class="stat-number-admin"><?= $totalSuara ?></div>
                <div class="stat-label-admin">Total Suara</div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Chart Perolehan Suara -->
        <div class="dashboard-card">
            <h3>Perolehan Suara</h3>
            <canvas id="pieChart" height="300"></canvas>
        </div>

        <!-- Chart Timeline -->
        <div class="dashboard-card">
            <h3>Timeline Voting</h3>
            <?php if (count($timeline) > 0): ?>
                <canvas id="lineChart" height="300"></canvas>
            <?php else: ?>
                <p class="no-data">Belum ada data timeline</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detailed Results -->
    <div class="dashboard-card full-width">
        <h3>Rincian Perolehan Suara</h3>
        <table class="admin-table hasil-detail">
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>No. Urut</th>
                    <th>Foto</th>
                    <th>Nama Kandidat</th>
                    <th>Jumlah Suara</th>
                    <th>Persentase</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hasil as $index => $h):
                    $persen = $totalSuara > 0 ? round(($h['jumlah_suara'] / $totalSuara) * 100, 2) : 0;
                ?>
                    <tr class="<?= $index == 0 && $h['jumlah_suara'] > 0 ? 'winner-row' : '' ?>">
                        <td class="rank">
                            <?php if ($index == 0 && $h['jumlah_suara'] > 0): ?>
                                <span class="trophy">&#127942;</span>
                            <?php else: ?>
                                <?= $index + 1 ?>
                            <?php endif; ?>
                        </td>
                        <td><?= $h['no_urut'] ?></td>
                        <td>
                            <?php if ($h['foto']): ?>
                                <img src="<?= APP_URL ?>/uploads/<?= $h['foto'] ?>" alt="" class="table-photo">
                            <?php else: ?>
                                <span class="no-photo-sm">&#128100;</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= sanitize($h['nama_lengkap']) ?></strong></td>
                        <td class="suara-cell"><?= number_format($h['jumlah_suara']) ?></td>
                        <td><?= $persen ?>%</td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $persen ?>%"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4"><strong>TOTAL</strong></td>
                    <td><strong><?= number_format($totalSuara) ?></strong></td>
                    <td><strong>100%</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
    // Pie Chart
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($hasil, 'nama_lengkap')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($hasil, 'jumlah_suara')) ?>,
                backgroundColor: ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    <?php if (count($timeline) > 0): ?>
    // Line Chart
    const lineCtx = document.getElementById('lineChart').getContext('2d');
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($timeline, 'tanggal')) ?>,
            datasets: [{
                label: 'Jumlah Vote',
                data: <?= json_encode(array_column($timeline, 'jumlah')) ?>,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                fill: true,
                tension: 0.3
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
    <?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>
