<?php
$pageTitle = 'Hasil Pemilihan - Sistem Voting RT';
require_once 'includes/header.php';

$db = getDB();

// Ambil hasil voting
$hasil = getHasilVoting();
$totalSuara = array_sum(array_column($hasil, 'jumlah_suara'));
$totalPemilih = getTotalPemilih();
$totalSudahMemilih = getTotalSudahMemilih();

// Cek pengaturan apakah hasil boleh ditampilkan
$pengaturan = getPengaturan();
$statusPemilihan = getStatusPemilihan();

// Tentukan apakah hasil ditampilkan
$tampilkanHasil = ($statusPemilihan == 'selesai') || ($pengaturan['tampilkan_hasil'] ?? false);

// Siapkan data untuk chart
$labels = [];
$data = [];
$colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c'];

foreach ($hasil as $index => $h) {
    $labels[] = $h['nama_lengkap'];
    $data[] = $h['jumlah_suara'];
}
?>

<section class="hasil-section">
    <div class="container">
        <h1>Hasil Pemilihan</h1>

        <?php if ($statusPemilihan == 'belum_mulai'): ?>
            <div class="alert alert-info">
                Pemilihan belum dimulai. Hasil akan ditampilkan setelah pemilihan selesai.
            </div>
        <?php elseif ($statusPemilihan == 'berlangsung' && !$tampilkanHasil): ?>
            <div class="alert alert-info">
                Pemilihan sedang berlangsung. Hasil sementara belum dapat ditampilkan.
            </div>
        <?php endif; ?>

        <!-- Statistik -->
        <div class="hasil-stats">
            <div class="hasil-stat-card">
                <div class="stat-value"><?= $totalPemilih ?></div>
                <div class="stat-label">Total Pemilih</div>
            </div>
            <div class="hasil-stat-card">
                <div class="stat-value"><?= $totalSudahMemilih ?></div>
                <div class="stat-label">Sudah Memilih</div>
            </div>
            <div class="hasil-stat-card">
                <div class="stat-value"><?= $totalPemilih > 0 ? round(($totalSudahMemilih / $totalPemilih) * 100, 1) : 0 ?>%</div>
                <div class="stat-label">Partisipasi</div>
            </div>
        </div>

        <?php if ($tampilkanHasil || $statusPemilihan == 'berlangsung'): ?>
            <!-- Chart -->
            <div class="chart-container">
                <div class="chart-wrapper">
                    <canvas id="hasilChart"></canvas>
                </div>
            </div>

            <!-- Tabel Hasil -->
            <div class="hasil-table-container">
                <h2><?= $statusPemilihan == 'selesai' ? 'Hasil Akhir' : 'Hasil Sementara' ?></h2>
                <table class="hasil-table">
                    <thead>
                        <tr>
                            <th>Peringkat</th>
                            <th>No. Urut</th>
                            <th>Kandidat</th>
                            <th>Jumlah Suara</th>
                            <th>Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hasil as $index => $h):
                            $persentase = $totalSuara > 0 ? round(($h['jumlah_suara'] / $totalSuara) * 100, 2) : 0;
                        ?>
                            <tr class="<?= $index == 0 && $h['jumlah_suara'] > 0 ? 'winner' : '' ?>">
                                <td class="rank">
                                    <?php if ($index == 0 && $h['jumlah_suara'] > 0 && $statusPemilihan == 'selesai'): ?>
                                        <span class="crown">&#128081;</span>
                                    <?php else: ?>
                                        <?= $index + 1 ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= $h['no_urut'] ?></td>
                                <td class="kandidat-name">
                                    <div class="kandidat-info-mini">
                                        <?php if ($h['foto']): ?>
                                            <img src="uploads/<?= $h['foto'] ?>" alt="" class="mini-photo">
                                        <?php endif; ?>
                                        <span><?= sanitize($h['nama_lengkap']) ?></span>
                                    </div>
                                </td>
                                <td class="suara"><?= number_format($h['jumlah_suara']) ?></td>
                                <td>
                                    <div class="persentase-bar">
                                        <div class="bar" style="width: <?= $persentase ?>%; background-color: <?= $colors[$index % count($colors)] ?>"></div>
                                        <span><?= $persentase ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3"><strong>Total</strong></td>
                            <td><strong><?= number_format($totalSuara) ?></strong></td>
                            <td><strong>100%</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <?php if ($statusPemilihan == 'selesai' && count($hasil) > 0 && $hasil[0]['jumlah_suara'] > 0): ?>
                <div class="winner-announcement">
                    <h2>Pemenang Pemilihan</h2>
                    <div class="winner-card">
                        <div class="winner-photo">
                            <?php if ($hasil[0]['foto']): ?>
                                <img src="uploads/<?= $hasil[0]['foto'] ?>" alt="<?= sanitize($hasil[0]['nama_lengkap']) ?>">
                            <?php else: ?>
                                <div class="no-photo-winner">&#128100;</div>
                            <?php endif; ?>
                        </div>
                        <div class="winner-info">
                            <span class="winner-label">Ketua RT Terpilih</span>
                            <h3><?= sanitize($hasil[0]['nama_lengkap']) ?></h3>
                            <p>Dengan perolehan <?= number_format($hasil[0]['jumlah_suara']) ?> suara (<?= $totalSuara > 0 ? round(($hasil[0]['jumlah_suara'] / $totalSuara) * 100, 2) : 0 ?>%)</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <script>
                // Chart.js configuration
                const ctx = document.getElementById('hasilChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode($labels) ?>,
                        datasets: [{
                            data: <?= json_encode($data) ?>,
                            backgroundColor: <?= json_encode(array_slice($colors, 0, count($data))) ?>,
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    font: {
                                        size: 14
                                    }
                                }
                            },
                            title: {
                                display: true,
                                text: 'Perolehan Suara',
                                font: {
                                    size: 18,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
