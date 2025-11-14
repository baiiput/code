<?php
session_start();
require_once 'config/database.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Get all warga with progress
$warga = $db->fetchAll("
    SELECT *
    FROM progress_pembayaran_warga
    ORDER BY nama_dawis, nama_lengkap
");

// Get all dawis for form
$allDawis = $db->fetchAll("SELECT * FROM dawis ORDER BY id");

// Get settings for display
$settings = $db->fetchAll("SELECT * FROM settings");
$targetTahunan = 0;
foreach ($settings as $s) {
    if ($s['setting_key'] == 'target_tahunan') {
        $targetTahunan = $s['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Warga - <?php echo APP_NAME; ?></title>
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
                <li><a href="warga.php" class="active">Data Warga</a></li>
                <li><a href="transaksi.php">Transaksi</a></li>
                <li><a href="pengeluaran.php">Pengeluaran</a></li>
                <li><a href="alert.php">Notifikasi</a></li>
                <li><a href="settings.php">Pengaturan</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <!-- Info Card -->
        <div class="card mb-3" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white;">
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0;">Target Tahunan: <?php echo formatRupiah($targetTahunan); ?></h3>
                        <p style="margin: 0; opacity: 0.9;">Target per bulan: <?php echo formatRupiah($targetTahunan / 12); ?></p>
                    </div>
                    <a href="settings.php" class="btn btn-secondary">
                        ⚙️ Ubah Target
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Data Warga & Progress Pembayaran</h2>
                <button onclick="openModal('modalWarga')" class="btn btn-primary">
                    ➕ Tambah Warga
                </button>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Dawis</th>
                                <th>Kontak</th>
                                <th>Target s/d Bulan Ini</th>
                                <th>Total Bayar Tahun Ini</th>
                                <th>Selisih</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="wargaTableBody">
                            <?php if (empty($warga)): ?>
                            <tr>
                                <td colspan="10" class="text-center">Belum ada data warga</td>
                            </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($warga as $w): ?>
                                <tr style="background: <?php
                                    if ($w['status_pembayaran'] == 'alert') echo 'rgba(239, 68, 68, 0.05)';
                                    elseif ($w['status_pembayaran'] == 'warning') echo 'rgba(245, 158, 11, 0.05)';
                                ?>">
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($w['nama_lengkap']); ?></strong><br>
                                        <small style="color: var(--text-secondary);">
                                            <?php echo htmlspecialchars($w['nomor_kk'] ?? '-'); ?>
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($w['nama_dawis']); ?></td>
                                    <td>
                                        <small style="color: var(--text-secondary);">
                                            📍 <?php echo htmlspecialchars($w['alamat'] ?? '-'); ?><br>
                                            📞 <?php echo htmlspecialchars($w['no_telepon'] ?? '-'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?php echo formatRupiah($w['target_sampai_bulan_ini']); ?></strong>
                                    </td>
                                    <td class="<?php
                                        if ($w['status_pembayaran'] == 'alert') echo 'text-danger';
                                        elseif ($w['status_pembayaran'] == 'warning') echo 'text-warning';
                                        else echo 'text-success';
                                    ?>">
                                        <strong><?php echo formatRupiah($w['saldo_tahun_ini']); ?></strong>
                                    </td>
                                    <td class="<?php echo $w['selisih_dari_target'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                        <strong><?php echo formatRupiah($w['selisih_dari_target']); ?></strong>
                                    </td>
                                    <td>
                                        <div style="min-width: 120px;">
                                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                                <div style="flex: 1; background: var(--bg-tertiary); height: 8px; border-radius: 4px; overflow: hidden;">
                                                    <div style="background: <?php
                                                        if ($w['status_pembayaran'] == 'alert') echo 'var(--danger-color)';
                                                        elseif ($w['status_pembayaran'] == 'warning') echo 'var(--warning-color)';
                                                        else echo 'var(--secondary-color)';
                                                    ?>; height: 100%; width: <?php echo min(100, $w['persentase_pencapaian']); ?>%;"></div>
                                                </div>
                                                <span style="font-weight: 600; min-width: 45px; font-size: 0.875rem;">
                                                    <?php echo number_format($w['persentase_pencapaian'], 1); ?>%
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($w['status'] == 'aktif'): ?>
                                            <?php if ($w['status_pembayaran'] == 'alert'): ?>
                                                <span class="badge badge-danger">🚨 Alert</span>
                                            <?php elseif ($w['status_pembayaran'] == 'warning'): ?>
                                                <span class="badge badge-warning">⚠️ Warning</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">✅ OK</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick="editWarga(<?php echo htmlspecialchars(json_encode([
                                            'id' => $w['warga_id'],
                                            'dawis_id' => $w['dawis_id'],
                                            'nama_lengkap' => $w['nama_lengkap'],
                                            'nomor_kk' => $w['nomor_kk'],
                                            'alamat' => $w['alamat'],
                                            'no_telepon' => $w['no_telepon'],
                                            'status' => $w['status']
                                        ])); ?>)"
                                                class="btn btn-secondary btn-sm">
                                            ✏️
                                        </button>
                                        <a href="transaksi.php?warga_id=<?php echo $w['warga_id']; ?>"
                                           class="btn btn-primary btn-sm">
                                            💰
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Tambah/Edit Warga -->
    <div id="modalWarga" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalWargaTitle">Tambah Warga</h3>
                <button class="modal-close" onclick="closeModal('modalWarga')">&times;</button>
            </div>
            <form id="formWarga" onsubmit="handleSubmitWarga(event)">
                <input type="hidden" id="wargaId" name="id">

                <div class="form-group">
                    <label class="form-label">Dawis *</label>
                    <select id="dawisId" name="dawis_id" class="form-control" required>
                        <option value="">Pilih Dawis</option>
                        <?php foreach ($allDawis as $d): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['nama_dawis']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" id="namaLengkap" name="nama_lengkap" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">No. KK</label>
                    <input type="text" id="nomorKK" name="nomor_kk" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea id="alamat" name="alamat" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">No. Telepon</label>
                    <input type="tel" id="noTelepon" name="no_telepon" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="aktif">Aktif</option>
                        <option value="tidak_aktif">Tidak Aktif</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modalWarga')" class="btn btn-secondary">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        let isEditMode = false;

        function editWarga(data) {
            isEditMode = true;
            document.getElementById('modalWargaTitle').textContent = 'Edit Warga';
            document.getElementById('wargaId').value = data.id;
            document.getElementById('dawisId').value = data.dawis_id;
            document.getElementById('namaLengkap').value = data.nama_lengkap;
            document.getElementById('nomorKK').value = data.nomor_kk || '';
            document.getElementById('alamat').value = data.alamat || '';
            document.getElementById('noTelepon').value = data.no_telepon || '';
            document.getElementById('status').value = data.status;
            openModal('modalWarga');
        }

        async function handleSubmitWarga(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const method = isEditMode ? 'PUT' : 'POST';
                const response = await apiCall('api/warga.php', method, data);

                if (response.success) {
                    showToast(response.message, 'success');
                    closeModal('modalWarga');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(response.message, 'error');
                }
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        // Reset form when opening modal for new entry
        document.getElementById('modalWarga').addEventListener('click', function(e) {
            if (e.target === this) {
                resetForm();
            }
        });

        function resetForm() {
            isEditMode = false;
            document.getElementById('modalWargaTitle').textContent = 'Tambah Warga';
            document.getElementById('formWarga').reset();
            document.getElementById('wargaId').value = '';
        }

        // Override openModal for warga to reset form
        const originalOpenModal = window.openModal;
        window.openModal = function(modalId) {
            if (modalId === 'modalWarga' && !isEditMode) {
                resetForm();
            }
            originalOpenModal(modalId);
        };
    </script>
</body>
</html>
