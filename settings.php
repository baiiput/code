<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Get current settings
$settings = $db->fetchAll("SELECT * FROM settings ORDER BY setting_key");
$settingsArray = [];
foreach ($settings as $setting) {
    $settingsArray[$setting['setting_key']] = $setting['setting_value'];
}

$targetTahunan = $settingsArray['target_tahunan'] ?? 120000;
$tahunBerjalan = $settingsArray['tahun_berjalan'] ?? date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <span>💰</span>
                    <span><?php echo APP_NAME; ?></span>
                </a>
                <div class="header-actions">
                    <button id="theme-toggle" class="theme-toggle">
                        <span id="theme-icon">🌙</span>
                        <span id="theme-text">Dark</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="nav">
        <div class="container">
            <ul class="nav-list">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="laporan.php">Laporan Per Dawis</a></li>
                <li><a href="warga.php">Data Warga</a></li>
                <li><a href="transaksi.php">Transaksi</a></li>
                <li><a href="pengeluaran.php">Pengeluaran</a></li>
                <li><a href="alert.php">Notifikasi</a></li>
                <li><a href="settings.php" class="active">Pengaturan</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">⚙️ Pengaturan Sistem</h2>
            </div>
            <div class="card-body">
                <form id="formSettings" onsubmit="handleSubmitSettings(event)">
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label">Target Tahunan per KK (Rupiah) *</label>
                            <input type="number"
                                   id="targetTahunan"
                                   name="target_tahunan"
                                   class="form-control"
                                   value="<?php echo $targetTahunan; ?>"
                                   min="0"
                                   step="1000"
                                   required>
                            <small style="color: var(--text-secondary);">
                                Target iuran jimpitan per Kartu Keluarga (KK) dalam 1 tahun
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tahun Berjalan *</label>
                            <input type="number"
                                   id="tahunBerjalan"
                                   name="tahun_berjalan"
                                   class="form-control"
                                   value="<?php echo $tahunBerjalan; ?>"
                                   min="2020"
                                   max="2100"
                                   required>
                            <small style="color: var(--text-secondary);">
                                Tahun yang sedang berjalan untuk perhitungan target
                            </small>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">Informasi Perhitungan</h3>
                        <div class="card" style="background: var(--bg-secondary); padding: 1rem;">
                            <p style="margin-bottom: 0.5rem;">
                                <strong>Target per Bulan:</strong>
                                <span id="targetPerBulan"><?php echo formatRupiah($targetTahunan / 12); ?></span>
                            </p>
                            <p style="margin-bottom: 0.5rem;">
                                <strong>Target s/d Bulan <?php echo getBulanIndo(date('n')); ?>:</strong>
                                <span id="targetSampaiSekarang">
                                    <?php echo formatRupiah(($targetTahunan / 12) * date('n')); ?>
                                </span>
                            </p>
                            <p style="margin-bottom: 0;">
                                <strong>Toleransi Minimum (75%):</strong>
                                <span id="toleransiMinimum">
                                    <?php echo formatRupiah((($targetTahunan / 12) * date('n')) * 0.75); ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-3">
                        <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">Keterangan Status Pembayaran</h3>
                        <div class="card" style="background: var(--bg-secondary); padding: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                                <span class="badge badge-success">OK</span>
                                <span>Pembayaran ≥ 90% dari target bulan berjalan</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                                <span class="badge badge-warning">Warning</span>
                                <span>Pembayaran 75% - 89% dari target bulan berjalan</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="badge badge-danger">Alert</span>
                                <span>Pembayaran &lt; 75% dari target bulan berjalan</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            💾 Simpan Pengaturan
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            ← Kembali ke Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
    <script>
        // Update perhitungan otomatis saat target berubah
        document.getElementById('targetTahunan').addEventListener('input', function() {
            const target = parseInt(this.value) || 0;
            const bulanSekarang = <?php echo date('n'); ?>;

            document.getElementById('targetPerBulan').textContent = formatRupiah(target / 12);
            document.getElementById('targetSampaiSekarang').textContent = formatRupiah((target / 12) * bulanSekarang);
            document.getElementById('toleransiMinimum').textContent = formatRupiah(((target / 12) * bulanSekarang) * 0.75);
        });

        async function handleSubmitSettings(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await apiCall('api/settings.php', 'POST', data);

                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast(error.message, 'error');
            }
        }
    </script>
</body>
</html>
